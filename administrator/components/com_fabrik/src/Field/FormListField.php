<?php
/**
 * Renders a repeating drop down list of forms
 *
 * @package     Joomla
 * @subpackage  Form
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Joomla\Component\Fabrik\Administrator\Field;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Worker;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

FormHelper::loadFieldClass('list');

/**
 * Renders a repeating drop down list of forms
 *
 * @package     Joomla
 * @subpackage  Form
 * @since       4.0
 */
class FormListField extends ListField
{
	/**
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $type = 'formlist';

	/**
	 * Element name
	 *
	 * @access    protected
	 * @var        string
	 *
	 * @since     4.0
	 */
	protected $name = 'Formlist';

	/**
	 * Method to get the field options.
	 *
	 * @return  array    The field option objects.
	 *
	 * @since 4.0
	 */
	protected function getOptions()
	{
		$app = Factory::getApplication();

		if ($this->element['package'])
		{
			$package = $app->setUserState('com_fabrik.package', $this->element['package']);
		}

		$db    = Worker::getDbo(true);
		$query = $db->getQuery(true);
		$query->select('id AS value, label AS ' . $db->quote('text') . ', published');
		$query->from('#__{package}_forms');

		if (!$this->element['showtrashed'])
		{
			$query->where('published <> -2');
		}

		$query->order('published DESC, label ASC');
		$db->setQuery($query);
		$rows = $db->loadObjectList();

		foreach ($rows as &$row)
		{
			switch ($row->published)
			{
				case '0':
					$row->text .= ' [' . Text::_('JUNPUBLISHED') . ']';
					break;
				case '-2':
					$row->text .= ' [' . Text::_('JTRASHED') . ']';
					break;
			}
		}

		if ($this->element['searchtools'])
		{
			$sel          = HTMLHelper::_('select.option', '', Text::_('COM_FABRIK_SELECT_FORM'));
			$sel->default = false;
		}
		else
		{
			$sel = HTMLHelper::_('select.option', '', '');
		}

		array_unshift($rows, $sel);

		return $rows;
	}

	/**
	 * Method to get the field input markup.
	 *
	 * @return    string    The field input markup.
	 *
	 * @since 4.0
	 */
	protected function getInput()
	{
		if ($this->element['searchtools'])
		{
			return parent::getInput();
		}

		$app    = Factory::getApplication();
		$input  = $app->input;
		$option = $input->get('option');

		if (!in_array($option, array('com_modules', 'com_menus', 'com_advancedmodules')))
		{
			$db    = Worker::getDbo(true);
			$query = $db->getQuery(true);
			$query->select('form_id')->from('#__{package}_formgroup')->where('group_id = ' . (int) $this->form->getValue('id'));
			$db->setQuery($query);
			$this->value = $db->loadResult();
			$this->form->setValue('form', null, $this->value);
		}

		if ((int) $this->form->getValue('id') == 0 || !$this->element['readonlyonedit'])
		{
			return parent::getInput();
		}
		else
		{
			$options = (array) $this->getOptions();
			$v       = '';

			foreach ($options as $opt)
			{
				if ($opt->value == $this->value)
				{
					$v = $opt->text;
				}
			}
		}

		return '<input type="hidden" value="' . $this->value . '" name="' . $this->name . '" />' . '<input type="text" value="' . $v
			. '" name="form_justalabel" class="readonly" readonly="true" />';
	}
}
