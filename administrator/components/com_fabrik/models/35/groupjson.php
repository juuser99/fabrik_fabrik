<?php
/**
 * Fabrik Admin Group Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Utilities\ArrayHelper;

require_once JPATH_ADMINISTRATOR . '/components/com_fabrik/models/group.php';

/**
 * Fabrik Admin Group Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.0
 */
class FabrikAdminModelGroupJSON extends FabrikAdminModelGroup
{
	/**
	 * Take an array of forms ids and return the corresponding group ids
	 * used in list publish code
	 *
	 * @param   array  $ids  form ids
	 *
	 * @return  string
	 */

	public function swapFormToGroupIds($ids = array())
	{
		if (empty($ids))
		{
			return array();
		}

		ArrayHelper::toInteger($ids);
		$db = FabrikWorker::getDbo();
		$query = $db->getQuery(true);
		$query->select('group_id')->from('#__fabrik_formgroup')->where('form_id IN (' . implode(',', $ids) . ')');
		$db->setQuery($query);
		$res = $db->loadColumn();

		return $res;
	}

	/**
	 * Clears old form group entries if found and adds new ones
	 *
	 * @param   array  $data  jform data
	 *
	 * @return void
	 */
	protected function makeFormGroup($data)
	{
		if ($data['form'] == '')
		{
			return;
		}

		$formid = (int) $data['form'];
		$id = (int) $data['id'];
		$item = FabTable::getInstance('FormGroup', 'FabrikTable');
		$item->load(array('form_id' => $formid, 'group_id' => $id));

		if ($item->id == '')
		{
			// Get max group order
			$db = FabrikWorker::getDbo(true);
			$query = $db->getQuery(true);
			$query->select('MAX(ordering)')->from('#__fabrik_formgroup')->where('form_id = ' . $formid);
			$db->setQuery($query);
			$next = (int) $db->loadResult() + 1;
			$item->ordering = $next;
			$item->form_id = $formid;
			$item->group_id = $id;
			$item->store();
		}
	}

	/**
	 * Repeat has been turned off for a group, so we need to remove the join.
	 * For now, leave the repeat table intact, just remove the join
	 * and the 'id' and 'parent_id' elements.
	 *
	 * @param   array  &$data  jform data
	 *
	 * @return boolean
	 */
	public function unMakeJoinedGroup(&$data)
	{
		if (empty($data['id']))
		{
			return false;
		}

		$db = FabrikWorker::getDbo(true);
		$query = $db->getQuery(true);
		$query->delete('#__fabrik_joins')->where('group_id = ' . $data['id']);
		$db->setQuery($query);
		$return = $db->execute();

		$query = $db->getQuery(true);
		$query->select('id')->from('#__fabrik_elements')->where('group_id  = ' . $data['id'] . ' AND name IN ("id", "parent_id")');
		$db->setQuery($query);
		$elids = $db->loadColumn();
		$elementModel = JModelLegacy::getInstance('Element', 'FabrikModel');
		$return = $elementModel->delete($elids);

		// Kinda meaningless return, but ...
		return $return;
	}

	/**
	 * Delete group elements
	 *
	 * @param   array  $pks  group ids to delete elements from
	 *
	 * @return  bool
	 */

	public function deleteElements($pks)
	{
		$db = FabrikWorker::getDbo(true);
		ArrayHelper::toInteger($pks);
		$query = $db->getQuery(true);
		$query->select('id')->from('#__fabrik_elements')->where('group_id IN (' . implode(',', $pks) . ')');
		$db->setQuery($query);
		$elids = $db->loadColumn();
		$elementModel = JModelLegacy::getInstance('Element', 'FabrikAdminModel');

		return $elementModel->delete($elids);
	}

	/**
	 * Delete formgroups
	 *
	 * @param   array  $pks  group ids
	 *
	 * @return  bool
	 */

	public function deleteFormGroups($pks)
	{
		$db = FabrikWorker::getDbo(true);
		ArrayHelper::toInteger($pks);
		$query = $db->getQuery(true);
		$query->delete('#__fabrik_formgroup')->where('group_id IN (' . implode(',', $pks) . ')');
		$db->setQuery($query);

		return $db->execute();
	}
}
