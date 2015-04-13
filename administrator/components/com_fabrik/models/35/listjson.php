<?php
/**
 * Fabrik Admin List Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\String\String;
use Joomla\Utilities\ArrayHelper;

require_once JPATH_ADMINISTRATOR . '/components/com_fabrik/models/list.php';
require_once JPATH_ADMINISTRATOR . '/components/com_fabrik/models/metaconverter.php';
/**
 * Fabrik Admin List Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.0
 */

class FabrikAdminModelListJSON extends FabrikAdminModelList
{
	use metaConverter;
	/**
	 * Get the list's join objects
	 *
	 * @return  array
	 */
	protected function getJoins()
	{
		$item = $this->getItem();

		if ((int) $item->id === 0)
		{
			return array();
		}

		$db = FabrikWorker::getDbo(true);
		$query = $db->getQuery(true);
		$query->select('*, j.id AS id, j.params as jparams')->from('#__fabrik_joins AS j')
			->join('INNER', '#__fabrik_groups AS g ON g.id = j.group_id')->where('j.list_id = ' . (int) $item->id);
		$db->setQuery($query);
		$joins = $db->loadObjectList();
		$fabrikDb = $this->getFEModel()->getDb();
		$c = count($joins);

		for ($i = 0; $i < $c; $i++)
		{
			$join =& $joins[$i];
			$jparams = $join->jparams == '' ? new stdClass : json_decode($join->jparams);

			if (isset($jparams->type) && ($jparams->type == 'element' || $jparams->type == 'repeatElement'))
			{
				unset($joins[$i]);
				continue;
			}

			if (empty($join->join_from_table) || empty($join->table_join))
			{
				unset($joins[$i]);
				continue;
			}

			$fields = $fabrikDb->getTableColumns($join->join_from_table);
			$join->joinFormFields = array_keys($fields);
			$fields = $fabrikDb->getTableColumns($join->table_join);
			$join->joinToFields = array_keys($fields);
		}
		// $$$ re-index the array in case we zapped anything
		return array_values($joins);
	}

	/**
	 * When saving a table that links to a database for the first time we
	 * need to create all the elements based on the database table fields and their
	 * column type
	 *
	 * @param   int     $groupId    group id
	 * @param   string  $tableName  table name - if not set then use jform's db_table_name (@since 3.1)
	 *
	 * @return  void
	 */
	protected function createLinkedElements($groupId, $tableName = '')
	{
		$db = FabrikWorker::getDbo(true);
		$app = JFactory::getApplication();
		$input = $app->input;
		$createdate = JFactory::getDate();
		$createdate = $createdate->toSql();

		if ($tableName === '')
		{
			$jform = $input->get('jform', array(), 'array');
			$tableName = ArrayHelper::getValue($jform, 'db_table_name');
		}

		$pluginManager = FabrikWorker::getPluginManager();
		$groupTable = FabTable::getInstance('Group', 'FabrikTable');
		$groupTable->load($groupId);

		// Here we're importing directly from the database schema
		$query = $db->getQuery(true);
		$query->select('id')->from('#__fabrik_lists')->where('db_table_name = ' . $db->quote($tableName));
		$db->setQuery($query);
		$id = $db->loadResult();
		$dispatcher = JDispatcher::getInstance();
		$elementModel = new PlgFabrik_Element($dispatcher);

		if ($id)
		{
			// A fabrik table already exists - so we can copy the formatting of its elements
			$groupListModel = JModelLegacy::getInstance('list', 'FabrikFEModel');
			$groupListModel->setId($id);
			$groupListModel->getTable();
			$groups = $groupListModel->getFormGroupElementData();
			$newElements = array();
			$ecount = 0;

			foreach ($groups as $groupModel)
			{
				/**
				 * If we are saving a new table and the previously found tables group is a join
				 * then don't add its elements to the table as they don't exist in the database table
				 * we are linking to
				 * $$$ hugh - why the test for task and new table?  When creating elements for a copy of a table,
				 * surely we NEVER want to include elements which were joined to the original,
				 * regardless of whether this is a new List?  Bearing in mind that this routine gets called from
				 * the makeNewJoin() method, when adding a join to an existing list, to build the "Foo - [bar]" join
				 * group, as well as from save() when creating a new List.
				 *
				 *  if ($groupModel->isJoin() && $input->get('task') == 'save' && $input->getInt('id') == 0)
				 */
				if ($groupModel->isJoin())
				{
					continue;
				}

				$elementModels = &$groupModel->getMyElements();

				foreach ($elementModels as $elementModel)
				{
					$ecount++;
					$element = $elementModel->getElement();
					$copy = $elementModel->copyRow($element->id, $element->label, $groupId);
					$newElements[$element->id] = $copy->id;
				}
			}

			foreach ($newElements as $origId => $newId)
			{
				$plugin = $pluginManager->getElementPlugin($newId);
				$plugin->finalCopyCheck($newElements);
			}
			// Hmm table with no elements - lets create them from the structure anyway
			if ($ecount == 0)
			{
				$this->makeElementsFromFields($groupId, $tableName);
			}
		}
		else
		{
			// No previously found fabrik list
			$this->makeElementsFromFields($groupId, $tableName);
		}
	}

	/**
	 * When copying a table we need to copy its joins as well
	 * note that the group and elements already exists - just the join needs to be saved
	 *
	 * @param   int    $fromid      table id to copy from
	 * @param   int    $toid        table id to copy to
	 * @param   array  $groupidmap  saying which groups got copied to which new group id (key = old id, value = new id)
	 *
	 * @return null
	 */
	protected function copyJoins($fromid, $toid, $groupidmap)
	{
		$db = FabrikWorker::getDbo(true);
		$query = $db->getQuery(true);
		$query->select('*')->from('#__fabrik_joins')->where('list_id = ' . (int) $fromid);
		$db->setQuery($query);
		$joins = $db->loadObjectList();
		$feModel = $this->getFEModel();

		foreach ($joins as $join)
		{
			$size = 10;
			$els = &$feModel->getElements();

			// $$$ FIXME hugh - joined els are missing tablename
			foreach ($els as $el)
			{
				// $$$ FIXME hugh - need to make sure we pick up the element from the main table,
				// not any similarly named elements from joined tables (like 'id')
				if ($el->getElement()->name == $join->table_key)
				{
					$size = String::stristr($el->getFieldDescription(), 'int') ? '' : '10';
				}
			}

			$feModel->addIndex($join->table_key, 'join', 'INDEX', $size);
			$joinTable = $this->getTable('Join');
			$joinTable->load($join->id);
			$joinTable->id = 0;
			$joinTable->group_id = $groupidmap[$joinTable->group_id];
			$joinTable->list_id = $toid;

			try
			{
				$joinTable->store();
			}
			catch (Exception $e)
			{
				return JError::raiseWarning(500, $e->getMessage());
			}
		}
	}

	/**
	 * Remove the associated form
	 *
	 * @param   object  &$item  list item
	 *
	 * @return boolean|form object
	 */
	protected function deleteAssociatedForm(&$item)
	{
		$db = FabrikWorker::getDbo(true);
		$query = $db->getQuery(true);
		$form = $this->getTable('form');
		$form->load($item->form_id);

		if ((int) $form->id === 0)
		{
			return false;
		}

		$query->delete()->from('#__fabrik_forms')->where('id = ' . (int) $form->id);
		$db->setQuery($query);
		$db->execute();

		return $form;
	}

	/**
	 * Delete associated fabrik groups
	 *
	 * @param   object  &$form           item
	 * @param   bool    $deleteElements  delete group items as well
	 *
	 * @return boolean|form id
	 */

	protected function deleteAssociatedGroups(&$form, $deleteElements = false)
	{
		$db = FabrikWorker::getDbo(true);
		$query = $db->getQuery(true);

		// Get group ids
		if ((int) $form->id === 0)
		{
			return false;
		}

		$query->select('group_id')->from('#__fabrik_formgroup')->where('form_id = ' . (int) $form->id);
		$db->setQuery($query);
		$groupids = (array) $db->loadColumn();

		// Delete groups
		$groupModel = JModelLegacy::getInstance('Group', 'FabrikAdminModel');
		$groupModel->delete($groupids, $deleteElements);

		return $form;
	}

	/**
	 * Create a table to store the forms' data depending upon what groups are assigned to the form
	 *
	 * @param   string  $dbTableName  Taken from the table oject linked to the form
	 * @param   array   $fields       List of default elements to add. (key = element name, value = plugin
	 * @param   array   $opts         Additional options, e.g. collation
	 *
	 * @return mixed false if fail otherwise array of primary keys
	 */

	public function createDBTable($dbTableName = null, $fields = array('id' => 'internalid', 'date_time' => 'date'), $opts = array())
	{
		$db = FabrikWorker::getDbo(true);
		$fabrikDb = $this->getDb();
		$formModel = $this->getFormModel();

		if (is_null($dbTableName))
		{
			$dbTableName = $this->getTable()->db_table_name;
		}

		$sql = 'CREATE TABLE IF NOT EXISTS ' . $db->quoteName($dbTableName) . ' (';
		$app = JFactory::getApplication();
		$input = $app->input;
		$jform = $input->get('jform', array(), 'array');

		if ($jform['id'] == 0 && array_key_exists('current_groups', $jform))
		{
			// Saving a new form
			$groupIds = $jform['current_groups'];
		}
		else
		{
			$query = $db->getQuery(true);
			$formid = (int) $this->get('form.id', $this->getFormModel()->id);
			$query->select('group_id')->from('#__fabrik_formgroup')->where('form_id = ' . $formid);
			$db->setQuery($query);
			$groupIds = $db->loadColumn();
		}

		$i = 0;

		foreach ($fields as $name => $plugin)
		{
			// $$$ hugh - testing corner case where we are called from form model's updateDatabase,
			// and the underlying table has been deleted.  So elements already exist.
			$element = $formModel->getElement($name);

			if ($element === false)
			{
				// Installation demo data sets 2 groud ids
				if (is_string($plugin))
				{
					$plugin = array('plugin' => $plugin, 'group_id' => $groupIds[0]);
				}

				$plugin['ordering'] = $i;
				$element = $this->makeElement($name, $plugin);

				if (!$element)
				{
					return false;
				}
			}

			$elementModels[] = clone ($element);
			$i++;
		}

		$arAddedObj = array();
		$keys = array();
		$lines = array();

		foreach ($elementModels as $elementModel)
		{
			$element = $elementModel->getElement();

			// Replace all non alphanumeric characters with _
			$objname = FabrikString::dbFieldName($element->name);

			if ($element->primary_key)
			{
				$keys[] = $objname;
			}
			// Any elements that are names the same (eg radio buttons) can not be entered twice into the database
			if (!in_array($objname, $arAddedObj))
			{
				$arAddedObj[] = $objname;
				$objtype = $elementModel->getFieldDescription();

				if ($objname != "" && !is_null($objtype))
				{
					if (String::stristr($objtype, 'not null'))
					{
						$lines[] = $fabrikDb->quoteName($objname) . " $objtype";
					}
					else
					{
						$lines[] = $fabrikDb->quoteName($objname) . " $objtype null";
					}
				}
			}
		}

		$func = create_function('$value', '$db = FabrikWorker::getDbo(true);;return $db->quoteName($value);');
		$sql .= implode(', ', $lines);

		if (!empty($keys))
		{
			$sql .= ', PRIMARY KEY (' . implode(',', array_map($func, $keys)) . '))';
		}
		else
		{
			$sql .= ')';
		}

		foreach ($opts as $k => $v)
		{
			if ($v != '')
			{
				$sql .= ' ' . $k . ' ' . $v;
			}
		}

		$sql .= ' ENGINE = MYISAM ';
		$fabrikDb->setQuery($sql);
		$fabrikDb->execute();

		return $keys;
	}

	/**
	 * Returns a reference to the a Table object, always creating it.
	 *
	 * @param   string  $type    The table type to instantiate
	 * @param   string  $prefix  A prefix for the table class name. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  JTable	A database object
	 *
	 * @since	1.6
	 */
	public function getTable($type = 'List', $prefix = 'FabrikTable', $config = array())
	{
		$sig = $type . $prefix . implode('.', $config);

		if (!array_key_exists($sig, $this->tables))
		{
			$db = JFactory::getDbo();
			$config['subview'] = strtolower($type);
			$this->tables[$sig] = new FabrikView($db, $config);
		}

		return $this->tables[$sig];
	}

	/**
	 * Method to get a single record.
	 *
	 * @param   integer  $pk  The id of the primary key.
	 *
	 * @return  mixed    Object on success, false on failure.
	 */
	public function getItem($pk = null)
	{
		$pk = !empty($pk) ? $pk : (int) $this->getState($this->getName() . '.id');
		$view = $this->viewNameFromId($pk, '#__fabrik_lists');

		$item = $this->getMetaItem($view);
		$item = $this->convertList($item);
	}

}
