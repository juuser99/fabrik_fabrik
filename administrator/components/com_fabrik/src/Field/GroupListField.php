<?php
/**
 * Renders a list of groups
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
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\GroupedlistField;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

FormHelper::loadFieldClass('groupedlist');

/**
 * Renders a list of groups
 *
 * @package     Joomla
 * @subpackage  Form
 * @since       4.0
 */
class GroupListField extends GroupedlistField
{
	use FormFieldNameTrait;

	/**
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $type = 'grouplist';

	/**
	 * Element name
	 *
	 * @access    protected
	 * @var        string
	 *
	 * @since     4.0
	 */
	protected $name = 'Grouplist';

	/**
	 * Method to get the field input markup.
	 *
	 * @return  string    The field input markup.
	 *
	 * @since 4.0
	 */
	protected function getGroups()
	{
		$app = Factory::getApplication();

		if ($this->value == '')
		{
			if ($this->element['searchtools'])
			{
				$this->value = $app->getUserState('com_fabrikadmin.elements.filter.group', '');
			}
			else
			{
				$this->value = $app->getUserStateFromRequest('com_fabrik.elements.filter.group', 'filter_groupId', $this->value);
			}
		}

		// Initialize variables.
		$options = array();
		$db      = Worker::getDbo(true);
		$query   = $db->getQuery(true);

		$query->select('g.id AS value, g.name AS text, f.label AS form');
		$query->from('#__{package}_groups AS g');
		$query->where('g.published <> -2')
			->join('INNER', '#__{package}_formgroup AS fg ON fg.group_id = g.id')
			->join('INNER', '#__{package}_forms AS f on fg.form_id = f.id');
		$query->order('f.label, g.name');

		if ($this->element['searchtools'])
		{
			$formId = (int) $app->getUserState('com_fabrikadmin.elements.filter.form', '');

			if (!empty($formId))
			{
				$query->where('f.id = ' . $formId);
			}
		}

		// Get the options.
		$db->setQuery($query);
		$options = $db->loadObjectList();

		if ($this->element['searchtools'])
		{
			$sel          = HTMLHelper::_('select.option', '', Text::_('COM_FABRIK_SELECT_GROUP'));
			$sel->default = false;
		}
		else
		{
			$sel       = HTMLHelper::_('select.option', '', Text::_('COM_FABRIK_PLEASE_SELECT'));
			$sel->form = '';
		}

		array_unshift($options, $sel);

		$groups = array();

		foreach ($options as $option)
		{
			if (!array_key_exists($option->form, $groups))
			{
				$groups[$option->form] = array();
			}

			$groups[$option->form][] = $option;
		}

		return $groups;
	}
}
