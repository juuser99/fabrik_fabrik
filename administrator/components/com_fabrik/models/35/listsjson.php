<?php
/**
 * Fabrik Admin Lists Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Utilities\ArrayHelper;

require_once JPATH_ADMINISTRATOR . '/components/com_fabrik/models/lists.php';

/**
 * Fabrik Admin Lists Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.0
 */

class FabrikAdminModelListsJSON extends FabrikAdminModelLists
{
	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return  JDatabaseQuery
	 *
	 * @since	1.6
	 */

	protected function getListQuery()
	{
		// Initialise variables.
		$db = $this->getDbo();
		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query->select($this->getState('list.select', 'l.*'));
		$query->from('#__fabrik_lists AS l');

		// Filter by published state
		$published = $this->getState('filter.published');

		if (is_numeric($published))
		{
			$query->where('l.published = ' . (int) $published);
		}
		elseif ($published === '')
		{
			$query->where('(l.published IN (0, 1))');
		}

		// Checked out user name
		$query->select('u.name AS editor')->join('LEFT', '#__users AS u ON u.id = l.checked_out');

		// Filter by search in title
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			$search = $db->quote('%' . $db->escape($search, true) . '%');
			$query->where('(l.db_table_name LIKE ' . $search . ' OR l.label LIKE ' . $search . ')');
		}

		// Add the list ordering clause.
		$orderCol = $this->state->get('list.ordering');
		$orderDirn = $this->state->get('list.direction');

		if ($orderCol == 'ordering' || $orderCol == 'category_title')
		{
			$orderCol = 'category_title ' . $orderDirn . ', ordering';
		}

		if (trim($orderCol) !== '')
		{
			$query->order($db->escape($orderCol . ' ' . $orderDirn));
		}

		return $query;
	}

	/**
	 * Get list groups
	 *
	 * @return  array  groups
	 */

	public function getTableGroups()
	{
		$db = $this->getDbo();
		$query = $db->getQuery(true);
		$query->select('DISTINCT(l.id) AS id, fg.group_id AS group_id');
		$query->from('#__fabrik_lists AS l');
		$query->join('LEFT', '#__fabrik_formgroup AS fg ON l.form_id = fg.form_id');
		$db->setQuery($query);
		$rows = $db->loadObjectList('id');

		return $rows;
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

	public function getTable($type = 'View', $prefix = 'FabrikTable', $config = array())
	{
		$config['dbo'] = FabrikWorker::getDbo();

		return FabTable::getInstance($type, $prefix, $config);
	}

	/**
	 * Get an array of database table names used in fabrik lists
	 *
	 * @return  array  database table names
	 */

	public function getDbTableNames()
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$cid = $input->get('cid', array(), 'array');
		ArrayHelper::toInteger($cid);
		$db = FabrikWorker::getDbo(true);
		$query = $db->getQuery(true);
		$query->select('db_table_name')->from('#__fabrik_lists')->where('id IN(' . implode(',', $cid) . ')');
		$db->setQuery($query);

		return $db->loadColumn();
	}
}
