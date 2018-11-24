<?php
/**
 * Renders a radio group but only if the fabrik group is assigned to a form
 *
 * @package     Joomla
 * @subpackage  Form
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Joomla\Component\Fabrik\Administrator\Field;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Form\Field\RadioField;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Language\Text;

FormHelper::loadFieldClass('radio');

/**
 * Renders a radio group but only if the fabrik group is assigned to a form
 * see: https://github.com/Fabrik/fabrik/issues/95
 *
 * @package     Joomla
 * @subpackage  Form
 * @since       4.0
 */
class GroupRepeatField extends RadioField
{
	/**
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $type = 'grouprepeat';

	/**
	 * Element name
	 *
	 * @var        string
	 *
	 * @since 4.0
	 */
	protected $name = 'Grouprepeat';

	/**
	 * Method to get the field input markup.
	 *
	 * @return    string    The field input markup.
	 *
	 * @since 4.0
	 */
	protected function getInput()
	{
		if ((int) $this->form->getValue('form') === 0)
		{
			return '<input class="readonly" size="60" value="' . Text::_('COM_FABRIK_FIELD_ASSIGN_GROUP_TO_FORM_FIRST') . '" type="readonly" />';
		}
		else
		{
			return parent::getInput();
		}
	}
}
