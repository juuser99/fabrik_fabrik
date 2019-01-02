<?php
/**
 * Plugin element to render internal id
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.internalid
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Component\Fabrik\Administrator\Table\ElementTable;
use Joomla\Component\Fabrik\Site\Plugin\AbstractElementPlugin;

/**
 * Plugin element to render internal id
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.internalid
 * @since       3.0
 */

class PlgFabrik_ElementInternalid extends AbstractElementPlugin
{
	/**
	 * If the element 'Include in search all' option is set to 'default' then this states if the
	 * element should be ignored from search all.
	 * @var bool  True, ignore in extended search all.
	 *
	 * @since 4.0
	 */
	protected $ignoreSearchAllDefault = true;

	/**
	 * Draws the html form element
	 *
	 * @param   array $data          to pre-populate element with
	 * @param   int   $repeatCounter repeat group counter
	 *
	 * @return  string    elements html
	 *
	 * @since 4.0
	 */
	public function render($data, $repeatCounter = 0)
	{
		$element = $this->getElement();
		$value   = $this->getValue($data, $repeatCounter);
		$value   = stripslashes($value);

		if (!$this->isEditable())
		{
			return ($element->hidden == '1') ? "<!-- " . $value . " -->" : $value;
		}

		$layout           = $this->getLayout('form');
		$layoutData       = new stdClass;
		$layoutData->name = $this->getHTMLName($repeatCounter);;
		$layoutData->id = $this->getHTMLId($repeatCounter);;
		$layoutData->value = htmlspecialchars($value, ENT_COMPAT, 'UTF-8');
		$layoutData->class = 'fabrikinput inputbox hidden';

		return $layout->render($layoutData);
	}

	/**
	 * Get database field description
	 *
	 * @return  string  db field type
	 *
	 * @since 4.0
	 */
	public function getFieldDescription()
	{
		return "INT(11) NOT NULL AUTO_INCREMENT";
	}

	/**
	 * Returns javascript which creates an instance of the class defined in formJavascriptClass()
	 *
	 * @param   int $repeatCounter Repeat group counter
	 *
	 * @return  array
	 *
	 * @since 4.0
	 */
	public function elementJavascript($repeatCounter)
	{
		$id   = $this->getHTMLId($repeatCounter);
		$opts = $this->getElementJSOptions($repeatCounter);

		return array('FbInternalId', $id, $opts);
	}

	/**
	 * Is the element hidden or not - if not set then return false
	 *
	 * @return  bool
	 *
	 * @since 4.0
	 */

	public function isHidden()
	{
		return true;
	}

	/**
	 * load a new set of default properties and params for the element
	 *
	 * @param   array $properties Default props
	 *
	 * @return  ElementTable    element (id = 0)
	 *
	 * @since 4.0
	 */
	public function getDefaultProperties($properties = array())
	{
		$item                 = parent::getDefaultProperties($properties);
		$item->primary_key    = true;
		$item->width          = 3;
		$item->hidden         = 1;
		$item->auto_increment = 1;

		return $item;
	}
}
