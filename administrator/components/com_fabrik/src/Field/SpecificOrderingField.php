<?php
/**
 * Renders a list of elements found in the current group
 * for use in setting the element's order
 *
 * @package     Joomla
 * @subpackage  Form
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Component\Fabrik\Administrator\Field;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Worker;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

FormHelper::loadFieldClass('list');

/**
 * Renders a list of elements found in the current group
 * for use in setting the element's order
 *
 * @package     Joomla
 * @subpackage  Form
 * @since       4.0
 */
class SpecificOrderingField extends ListField
{
	use FormFieldNameTrait;

	/**
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $type = 'specificordering';

	/**
	 * Element name
	 * @access    protected
	 * @var        string
	 *
	 * @since     4.0
	 */
	protected $name = 'Specificordering';

	/**
	 * Method to get the field options.
	 *
	 * @return  array  The field option objects.
	 *
	 * @since 4.0
	 */
	protected function getOptions()
	{
		// ONLY WORKS INSIDE ELEMENT :(
		$db       = Worker::getDbo();
		$group_id = $this->form->getValue('group_id');
		$query    = "SELECT ordering AS value, name AS text" . "\n FROM #__{package}_elements " . "\n WHERE group_id = " . (int) $group_id
			. "\n AND published >= 0" . "\n ORDER BY ordering";
		/**
		 * $$$ rob - rather than trying to override the JHTML class lets
		 * just swap {package} for the current package.
		 */
		$query = Worker::getDbo(true)->replacePrefix($query);

		return HTMLHelper::_('list.genericordering', $query);
	}

	/**
	 * Method to get the field input markup.
	 *
	 * @return  string    The field input markup.
	 *
	 * @since 4.0
	 */
	protected function getInput()
	{
		$id = $this->form->getValue('id');

		if ($id)
		{
			// Get the field options.
			$options  = (array) $this->getOptions();
			$ordering = HTMLHelper::_('select.genericlist', $options, $this->name, 'class="inputbox custom-select" size="1"', 'value', 'text', $this->value);
		}
		else
		{
			$text     = Text::_('COM_FABRIK_NEW_ITEMS_LAST');
			$ordering = '<input type="text" size="40" readonly="readonly" class="readonly" name="' . $this->name . '" value="' . $this->value . $text
				. '" />';
		}

		return $ordering;
	}
}
