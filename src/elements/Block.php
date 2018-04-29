<?php
namespace benf\neo\elements;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\ElementHelper;
use craft\validators\SiteIdValidator;

use benf\neo\Plugin as Neo;
use benf\neo\elements\db\BlockQuery;
use benf\neo\records\Block as BlockRecord;

class Block extends Element
{
	public static function displayName(): string
	{
		return Craft::t('neo', "Neo Block");
	}

	public static function refHandle(): string
	{
		return 'neoblock';
	}

	public static function hasContent(): bool
	{
		return true;
	}

	public static function isLocalized(): bool
	{
		return true;
	}

	public static function hasStatuses(): bool
	{
		return true;
	}

	public static function find(): ElementQueryInterface
	{
		return new BlockQuery(static::class);
	}

	public $fieldId;
	public $ownerId;
	public $ownerSiteId;
	public $typeId;

	private $_owner;

	public function extraFields(): array
	{
		$names = parent::extraFields();
		$names[] = 'owner';
		$names[] = 'type';

		return $names;
	}

	public function rules(): array
	{
		$rules = parent::rules();
		$rules[] = [ ['fieldId', 'ownerId', 'typeId'], 'number', 'integerOnly' => true ];
		$rules[] = [ ['ownerSiteId'], SiteIdValidator::class ];

		return $rules;
	}

	public function getSupportedSites(): array
	{
		$siteIds = [];

		if ($this->ownerSiteId !== null)
		{
			$siteIds[] = $this->ownerSiteId;
		}
		else
		{
			$owner = $this->getOwner();

			if ($owner)
			{
				foreach (ElementHelper::supportedSitesForElement($owner) as $siteInfo)
				{
					$siteIds[] = $siteInfo['siteId'];
				}
			}
			else
			{
				$siteIds[] = Craft::$app->getSites()->getPrimarySite()->id;
			}
		}

		return $siteIds;
	}

	public function getFieldLayout()
	{
		return parent::getFieldLayout() ?? $this->getType()->getFieldLayout();
	}

	public function getType()
	{
		if ($this->typeId === null)
		{
			throw new InvalidConfigException("Neo block is missing its type ID");
		}

		$blockType = Neo::$plugin->blockTypes->getById($this->typeId);

		if (!$blockType)
		{
			throw new InvalidConfigException("Invalid Neo block ID: $this->typeId");
		}

		return $blockType;
	}

	public function getOwner()
	{
		$owner = $this->_owner;

		if ($owner !== null)
		{
			if ($owner === false)
			{
				$owner = null;
			}
			else
			{
				$owner = Craft::$app->getElements()->getElementById($this->ownerId, null, $this->siteId);
				$this->_owner = $owner ?? false;
			}
		}

		return $owner;
	}

	public function setOwner(ElementInterface $owner = null)
	{
		$this->_owner = $owner;
	}

	public function getHasFreshContent(): bool
	{
		$owner = $this->getOwner();

		return $owner ? $owner->getHasFreshContent() : false;
	}

	public function afterSave(bool $isNew)
	{
		$record;

		if ($isNew)
		{
			$record = new BlockRecord();
			$record->id = $this->id;
		}
		else
		{
			$record = BlockRecord::findOne($this->id);

			if (!$record)
			{
				throw new Exception("Invalid Neo block ID: $this->id");
			}
		}

		$record->fieldId = $this->fieldId;
		$record->ownerId = $this->ownerId;
		$record->ownerSiteId = $this->ownerSiteId;
		$record->typeId = $this->typeId;
		$record->save(false);

		parent::afterSave($isNew);
	}
}
