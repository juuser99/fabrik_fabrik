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
	 * Last current element found in hasElement()
	 *
	 * @var \Fabrik\Plugins\Element
	 */
	protected $currentElement = null;

	/**
	 * Similar to getFormGroups() except that this returns a data structure of
	 * form
	 * --->group
	 * -------->element
	 * -------->element
	 * --->group
	 * if run before then existing data returned
	 *
	 * @return  \Fabrik\Admin\Models\Group[]  element objects
	 */
	public function getGroupsHierarchy()
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
			$groupModel = new Group;
			$groupData = $this->getPublishedGroups();

			foreach ($groupData as $data)
			{
				$thisGroup = clone ($groupModel);
				$thisGroup->set('id', $data->id);
				$thisGroup->setContext($this);
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
	 * @return  \Fabrik\Admin\Models\Lizt
	 */
	public function getListModel()
	{
		if (!isset($this->listModel))
		{
			$this->listModel = new Lizt;
			$this->listModel->set('id', $this->get('id'));
			$this->listModel->setFormModel($this);
			$this->listModel->getParams();
		}

		return $this->listModel;
	}

	/**
	 * Test to try to load all group data in one query and then bind that data to group table objects
	 * in getGroups()
	 *
	 * @return  \Fabrik\Admin\Models\Group[]
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
			$listId = (int) $listModel->getId();

			if (is_object($listModel) && $listId !== 0)
			{
				$query = $db->getQuery(true);
				$query->select('g.id, j.id AS joinid')->from('#__fabrik_joins AS j')
					->join('INNER', '#__fabrik_groups AS g ON g.id = j.group_id')->where('list_id = ' . $listId . ' AND g.published = 1');

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

	/**
	 * Iterate over the form's elements and update its db table to match
	 *
	 * @return  void
	 */
	public function updateDatabase()
	{
		$item = $this->getItem();
		$form = $item->get('form');

		// Use this in case there is not table view linked to the form
		if ($form->record_in_database == 1)
		{
			$tableName = $item->get('list.db_table_name');
			$exists = $this->storage->tableExists($tableName);
			$fields = array();
			$groups = $this->getGroupsHierarchy();

			foreach ($groups as $group)
			{
				foreach ($group->elements as $elementModel)
				{
					$element = $elementModel->getElement();
					$fields[$element->get('name')] = array(
						'plugin' => $element->get('plugin'),
						'field' => $elementModel->getFieldDescription(),
						'primary_key' => $element->get('primary_key')
					);
				}
			}

			if (!$exists)
			{
				/* $$$ hugh - if we're recreating a table for an existing form, we need to pass the field
				 * list to createDBTable(), otherwise all we get is id and date_time.  Not sure if this
				 * code really belongs here, or if we should handle it in createDBTable(), but I didn't want
				 * to mess with createDBTable(), although I did have to make one small change in it (see comments
				 * therein).
				 * NOTE 1 - this code ignores joined groups, so only recreates the original table
				 * NOTE 2 - this code ignores any 'alter existing fields' settings.
				 */

				if (!empty($fields))
				{
					$this->storage->createTable($tableName, $fields);
				}
			}
			else
			{
				$this->storage->amendTable($tableName, $fields);
			}
		}
	}

	/**
	 * Method to set the view model id
	 *
	 * @param   string $id ID
	 *
	 * @return  void
	 */
	public function setId($id)
	{
		$this->set('id', $id);
	}

	/**
	 * Attempts to determine if the form contains the element
	 *
	 * @param   string  $searchName  Element name to search for
	 * @param   bool    $checkInt    Check search name against element id
	 * @param   bool    $checkShort  Check short element name
	 *
	 * @return  bool  true if found, false if not found
	 */
	public function hasElement($searchName, $checkInt = false, $checkShort = true)
	{
		$groups = $this->getGroupsHierarchy();

		foreach ($groups as $groupModel)
		{
			$groupModel->getMyElements();

			if (!is_array($groupModel->elements))
			{
				continue;
			}

			foreach ($groupModel->elements as $elementModel)
			{
				$element = $elementModel->getElement();

				if ($checkInt)
				{
					echo "elemetn id = " . $element->get('id');
					if ($searchName == $element->get('id'))
					{
						$this->currentElement = $elementModel;

						return true;
					}
				}

				if ($searchName == $element->get('name') && $checkShort)
				{
					$this->currentElement = $elementModel;

					return true;
				}

				if ($searchName == $elementModel->getFullName(true, false))
				{
					$this->currentElement = $elementModel;

					return true;
				}

				if ($searchName == $elementModel->getFullName(false, false))
				{
					$this->currentElement = $elementModel;

					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Get an element
	 *
	 * @param   string  $searchName  Name to search for
	 * @param   bool    $checkInt    Check search name against element id
	 * @param   bool    $checkShort  Check short element name
	 *
	 * @return  \Fabrik\Plugins\Element\Element ok: element model not ok: false
	 */
	public function getElement($searchName = '', $checkInt = false, $checkShort = true)
	{
		return $this->hasElement($searchName, $checkInt, $checkShort) ? $this->currentElement : false;
	}
}
