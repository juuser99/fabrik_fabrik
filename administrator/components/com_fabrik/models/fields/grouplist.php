<?php
/**
 * Renders a list of groups
 *
 * @package     Joomla
 * @subpackage  Form
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Worker;
use Joomla\Registry\Registry as JRegistry;
use Fabrik\Helpers\Text;

jimport('joomla.html.html');
jimport('joomla.form.formfield');
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('groupedlist');

/**
 * Renders a list of groups
 *
 * @package     Joomla
 * @subpackage  Form
 * @since       1.6
 */

class JFormFieldGroupList extends JFormFieldGroupedList
{
	/**
	 * Element name
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $name = 'Grouplist';

	/**
	 * Method to get the field input markup.
	 *
	 * @return  string	The field input markup.
	 */
	protected function getGroups()
	{
		$this->app = JFactory::getApplication();
		if ($this->value == '')
		{
			$this->value = $this->app->getUserStateFromRequest('com_fabrik.elements.filter.group', 'filter_groupId', $this->value);
		}

		$items = $this->form->model->getItems();
		$options = array();

		// Add please select
		$sel = new stdClass;
		$sel->value = '';
		$sel->text = Text::_('COM_FABRIK_PLEASE_SELECT');
		$options[''][] = $sel;

		foreach ($items as $item)
		{
			$item = new JRegistry($item);
			$label = $item->get('form.label');
			$groups = $item->get('form.groups', array());
			$options[$label] = array();

			foreach ($groups as $group)
			{
				if ($group->published <> -2)
				{
					$option = new stdClass;
					$option->value = $group->id;
					$option->text = $group->name;
					$options[$label][] = $option;
				}
			}
		}

		return $options;
	}
}
