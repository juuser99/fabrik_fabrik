<?php
/**
 * Renders a grouped list of fabrik groups and elements
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
use Joomla\CMS\Form\Field\GroupedlistField;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\Component\Fabrik\Administrator\Model\FabrikModel;
use Joomla\Component\Fabrik\Site\Model\FormModel;

FormHelper::loadFieldClass('groupedlist');

/**
 * Renders a list of fabrik lists or db tables
 *
 * @package     Fabrik
 * @subpackage  Form
 * @since       4.0
 */
class GroupElementsField extends GroupedlistField
{
	use FormFieldNameTrait;

	/**
	 * The form field type.
	 *
	 * @var    string
	 *
	 * @since 4.0
	 */
	public $type = 'GroupElements';

	/**
	 * Method to get the list of groups and elements
	 * grouped by group and element.
	 *
	 * @return  array  The field option objects as a nested array in groups.
	 *
	 * @since 4.0
	 */
	protected function getGroups()
	{
		$app   = Factory::getApplication();
		$input = $app->input;
		$db    = Worker::getDbo(true);

		$query = $db->getQuery(true);
		$query->select('form_id')
			->from($db->quoteName('#__{package}_formgroup') . ' AS fg')
			->join('LEFT', '#__{package}_elements AS e ON e.group_id = fg.group_id')
			->where('e.id = ' . $input->getInt('elementid'));
		$db->setQuery($query);
		$formId = $db->loadResult();
		/** @var FormModel $formModel */
		$formModel = FabrikModel::getInstance(FormModel::class);
		$formModel->setId($formId);

		$rows                                 = array();
		$rows[Text::_('COM_FABRIK_GROUPS')]   = array();
		$rows[Text::_('COM_FABRIK_ELEMENTS')] = array();

		// Get available element types
		$groups = $formModel->getGroupsHiarachy();

		foreach ($groups as $groupModel)
		{
			$group                                = $groupModel->getGroup();
			$label                                = $group->name;
			$value                                = 'fabrik_trigger_group_group' . $group->id;
			$rows[Text::_('COM_FABRIK_GROUPS')][] = HTMLHelper::_('select.option', $value, $label);
			$elementModels                        = $groupModel->getMyElements();

			foreach ($elementModels as $elementModel)
			{
				$label                                  = $elementModel->getFullName(false, false);
				$value                                  = 'fabrik_trigger_element_' . $elementModel->getFullName(true, false);
				$rows[Text::_('COM_FABRIK_ELEMENTS')][] = HTMLHelper::_('select.option', $value, $label);
			}
		}

		reset($rows);
		asort($rows[Text::_('COM_FABRIK_ELEMENTS')]);

		return $rows;
	}
}
