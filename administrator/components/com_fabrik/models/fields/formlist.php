<?php
/**
 * Renders a repeating drop down list of forms
 *
 * @package     Joomla
 * @subpackage  Form
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

// Needed for when you make a menu item link to a form.
require_once JPATH_SITE . '/components/com_fabrik/helpers/parent.php';
require_once JPATH_SITE . '/components/com_fabrik/helpers/string.php';

jimport('joomla.html.html');
jimport('joomla.form.formfield');
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');

/**
 * Renders a repeating drop down list of forms
 *
 * @package     Joomla
 * @subpackage  Form
 * @since       1.6
 */
class JFormFieldFormList extends JFormFieldList
{
	/**
	 * Element name
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $name = 'Formlist';

	/**
	 * Method to get the field options.
	 *
	 * @return  array	The field option objects.
	 */
	protected function getOptions()
	{
		$items = $this->form->model->getItems();

		$options = array();
		foreach ($items as $item)
		{
			$item = new JRegistry($item);

			if (!$this->element['showtrashed'] && (int) $item->get('published') === -2)
			{
				continue;
			}

			$option = new stdClass;
			$option->value = $item->get('view');
			$option->text = $item->get('form.label');
			$key = $item->get('published') . '.' . $option->text;

			switch ($item->get('published'))
			{
				case '0':
					$option->text .= ' [' . FText::_('JUNPUBLISHED') . ']';
					break;
				case '-2':
					$option->text .= ' [' . FText::_('JTRASHED') . ']';
					break;
			}

			$options[$key] = $option;
		}

		ksort($options);
		$o = new stdClass;
		$o->value = '';
		$o->text = '';
		array_unshift($options, $o);

		return $options;
	}

	/**
	 * Method to get the field input markup.
	 *
	 * @return	string	The field input markup.
	 */
	protected function getInput()
	{
		$this->app = JFactory::getApplication();
		$input = $this->app->input;
		$option = $input->get('option');

		if (!in_array($option, array('com_modules', 'com_menus', 'com_advancedmodules')))
		{
			$item = $this->form->model->getItem();
			$this->value = $item->get('view');
			$this->form->setValue('form', null, $this->value);
		}

		return parent::getInput();
	}
}
