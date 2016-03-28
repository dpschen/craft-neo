<?php
namespace Craft;

/**
 * Neo block model class.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.models
 * @since     1.3
 */
class Neo_BlockModel extends BaseElementModel
{
	// Properties
	// =========================================================================

	/**
	 * @var string
	 */
	protected $elementType = Neo_ElementType::NeoBlock;

	/**
	 * @var
	 */
	private $_owner;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseElementModel::getFieldLayout()
	 *
	 * @return FieldLayoutModel|null
	 */
	public function getFieldLayout()
	{
		$blockType = $this->getType();

		if ($blockType)
		{
			return $blockType->getFieldLayout();
		}
	}

	/**
	 * @inheritDoc BaseElementModel::getLocales()
	 *
	 * @return array
	 */
	public function getLocales()
	{
		// If the Neo field is translatable, than each individual block is tied to a single locale, and thus aren't
		// translatable. Otherwise all blocks belong to all locales, and their content is translatable.

		if ($this->ownerLocale)
		{
			return array($this->ownerLocale);
		}
		else
		{
			$owner = $this->getOwner();

			if ($owner)
			{
				// Just send back an array of locale IDs -- don't pass along enabledByDefault configs
				$localeIds = array();

				foreach ($owner->getLocales() as $localeId => $localeInfo)
				{
					if (is_numeric($localeId) && is_string($localeInfo))
					{
						$localeIds[] = $localeInfo;
					}
					else
					{
						$localeIds[] = $localeId;
					}
				}

				return $localeIds;
			}
			else
			{
				return array(craft()->i18n->getPrimarySiteLocaleId());
			}
		}
	}

	/**
	 * Returns the block type.
	 *
	 * @return Neo_BlockTypeModel|null
	 */
	public function getType()
	{
		if ($this->typeId)
		{
			return craft()->neo->getBlockTypeById($this->typeId);
		}
	}

	/**
	 * Returns the owner.
	 *
	 * @return BaseElementModel|null
	 */
	public function getOwner()
	{
		if (!isset($this->_owner) && $this->ownerId)
		{
			$this->_owner = craft()->elements->getElementById($this->ownerId, null, $this->locale);

			if (!$this->_owner)
			{
				$this->_owner = false;
			}
		}

		if ($this->_owner)
		{
			return $this->_owner;
		}
	}

	/**
	 * Sets the owner
	 *
	 * @param BaseElementModel
	 */
	public function setOwner(BaseElementModel $owner)
	{
		$this->_owner = $owner;
	}


	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseModel::defineAttributes()
	 *
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array_merge(parent::defineAttributes(), array(
			'fieldId'     => AttributeType::Number,
			'ownerId'     => AttributeType::Number,
			'ownerLocale' => AttributeType::Locale,
			'typeId'      => AttributeType::Number,
			'sortOrder'   => AttributeType::Number,
			'collapsed'   => AttributeType::Bool,
		));
	}

	// Private Methods
	// =========================================================================

	/**
	 * Returns the Neo field.
	 *
	 * @return FieldModel
	 */
	private function _getField()
	{
		return craft()->fields->getFieldById($this->fieldId);
	}
}
