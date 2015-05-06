<?php
/**
 * Fabrik Admin View Model. Common methods for list/forms
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.5
 */

namespace Fabrik\Admin\Models;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Worker;

/**
 * Fabrik Admin View Model. Common methods for list/forms
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.5
 */
class View extends Base
{
	/**
	 * Similar to getFormGroups() except that this returns a data structure of
	 * form
	 * --->group
	 * -------->element
	 * -------->element
	 * --->group
	 * if run before then existing data returned
	 *
	 * @return  array  element objects
	 */
	public function getGroupsHiarachy()
	{
		if (!isset($this->groups))
		{
			$this->getGroups();

			$this->groups = Worker::getPluginManager()->getFormPlugins($this);
		}

		return $this->groups;
	}

	/**
	 * Get the forms published group objects
	 *
	 * @return  stdClass  Group model objects
	 */
	public function getGroups()
	{
		if (!isset($this->groups))
		{
			$this->groups = new \stdClass;
			$listModel = $this->getListModel();
			$groupModel = new Group;
			$groupData = $this->getPublishedGroups();

			foreach ($groupData as $id => $data)
			{
				$thisGroup = clone ($groupModel);
				$thisGroup->set('id', $id);
				$thisGroup->setContext($this, $listModel);
				$thisGroup->setGroup($data);

				if ($data->published == 1)
				{
					$name = $data->name;
					$this->groups->$name = $thisGroup;
				}
			}
		}

		return $this->groups;
	}

	/**
	 * Get the View Models list model
	 *
	 * @return  \Fabrik\Admin\Models\List
	 */
	public function getListModel()
	{
		if (!isset($this->listModel))
		{
			$this->listModel = new Lizt;
			$this->listModel->set('id', $this->get('id'));
			$this->listModel->setFormModel($this);
		}

		return $this->listModel;
	}

	/**
	 * Test to try to load all group data in one query and then bind that data to group table objects
	 * in getGroups()
	 *
	 * @return  array
	 */
	public function getPublishedGroups()
	{
		if (!isset($this->_publishedformGroups) || empty($this->_publishedformGroups))
		{
			$item = $this->getItem();
			$return = array();
			$groups = $item->get('form.groups');

			foreach ($groups as $group)
			{
				if ((bool) $group->published)
				{
					$return[] = $group;
				}
			}

			if ($item->get('form.params.randomise_groups') == 1)
			{
				shuffle($return);
			}

			$this->_publishedformGroups = $this->mergeGroupsWithJoins($return);
		}

		return $this->_publishedformGroups;
	}

	/**
	 * Merge in Join Ids into an array of groups
	 *
	 * @param   array  $groups  form groups
	 *
	 * @return  array
	 */

	private function mergeGroupsWithJoins($groups)
	{
		$db = Worker::getDbo(true);
		$item = $this->getItem();

		if ($item->get('form.record_in_database'))
		{
			// FIXME - workout what to do with this in json view
			/*$listModel = $this->getListModel();
			$listid = (int) $listModel->getId();

			if (is_object($listModel) && $listid !== 0)
			{
				$query = $db->getQuery(true);
				$query->select('g.id, j.id AS joinid')->from('#__fabrik_joins AS j')
					->join('INNER', '#__fabrik_groups AS g ON g.id = j.group_id')->where('list_id = ' . $listid . ' AND g.published = 1');

				// Added as otherwise you could potentially load a element joinid as a group join id. 3.1
				$query->where('j.element_id = 0');
				$db->setQuery($query);
				$joinGroups = $db->loadObjectList('id');

				foreach ($joinGroups as $k => $o)
				{
					if (array_key_exists($k, $groups))
					{
						$groups[$k]->join_id = $o->joinid;
					}
				}
			}*/
		}

		return $groups;
	}
}
