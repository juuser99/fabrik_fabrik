<?php
/**
 * Fabrik Admin Elements Model
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

require_once JPATH_ADMINISTRATOR . '/components/com_fabrik/models/elements.php';

/**
 * Fabrik Admin Elements Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.0
 */

class FabrikAdminModelElementsJSON extends FabrikAdminModelElements
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
		$query->select($this->getState('list.select', 'e.*, e.ordering AS ordering'));
		$query->from('#__fabrik_elements AS e');

		$query->select('(SELECT COUNT(*) FROM #__fabrik_jsactions AS js WHERE js.element_id = e.id) AS numJs');

		// Filter by published state
		$published = $this->getState('filter.published');

		if (is_numeric($published))
		{
			$query->where('e.published = ' . (int) $published);
		}
		elseif ($published === '')
		{
			$query->where('(e.published IN (0, 1))');
		}

		// Filter by search in title
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			$search = $db->quote('%' . $db->escape($search, true) . '%');
			$query->where('(e.name LIKE ' . $search . ' OR e.label LIKE ' . $search . ')');
		}

		$group = $this->getState('filter.group');

		if (trim($group) !== '')
		{
			$query->where('g.id = ' . (int) $group);
		}

		$showInList = $this->getState('filter.showinlist');

		if (trim($showInList) !== '')
		{
			$query->where('e.show_in_list_summary = ' . (int) $showInList);
		}

		$plugin = $this->getState('filter.plugin');

		if (trim($plugin) !== '')
		{
			$query->where('e.plugin = ' . $db->quote($plugin));
		}

		// For drop fields view
		$cids = (array) $this->getState('filter.cid');

		if (!empty($cids))
		{
			$query->where('e.id IN (' . implode(',', $cids) . ')');
		}

		$this->filterByFormQuery($query, 'fg');

		// Join over the users for the checked out user.

		$query->select('e.id');

		$query->join('LEFT', '#__users AS u ON checked_out = u.id');
		$query->join('LEFT', '#__fabrik_groups AS g ON e.group_id = g.id ');

		// Was inner join but if el assigned to group which was not part of a form then the element was not shown in the list
		$query->join('LEFT', '#__fabrik_formgroup AS fg ON fg.group_id = e.group_id');
		$query->join('LEFT', '#__fabrik_lists AS l ON l.form_id = fg.form_id');

		// Add the list ordering clause.
		$orderCol = $this->state->get('list.ordering', 'ordering');
		$orderDirn = $this->state->get('list.direction');

		if ($orderCol == 'ordering' || $orderCol == 'category_title')
		{
			$orderCol = 'ordering';
		}

		if (trim($orderCol) !== '')
		{
			$query->order($db->escape($orderCol . ' ' . $orderDirn));
		}

		// Work out the element ids so we can limit the fullname subquery
		$db->setQuery($query, $start = $this->getState('list.start'), $this->getState('list.limit'));
		$elementIds = $db->loadColumn();

		/**
		 * $$$ hugh - altered this query as ...
		 * WHERE (jj.list_id != 0 AND jj.element_id = 0)
		 * ...instead of ...
		 * WHERE jj.list_id != 0
		 * ... otherwise we pick up repeat elements, as they have both table and element set
		 * and he query fails with "returns multiple values" for the fullname select
		 */

		if (count($elementIds) > 0)
		{
			$fullname = "(SELECT DISTINCT(
			IF( ISNULL(jj.table_join), CONCAT(ll.db_table_name, '___', ee.name), CONCAT(jj.table_join, '___', ee.name))
			)
			FROM #__fabrik_elements AS ee
			LEFT JOIN #__fabrik_joins AS jj ON jj.group_id = ee.group_id
			LEFT JOIN #__fabrik_formgroup as fg ON fg.group_id = ee.group_id
			LEFT JOIN #__fabrik_lists AS ll ON ll.form_id = fg.form_id
			WHERE (jj.list_id != 0 AND jj.element_id = 0)
			AND ee.id = e.id AND ee.group_id <> 0 AND ee.id IN (" . implode(',', $elementIds) . ") LIMIT 1)  AS full_element_name";

			$query->select('u.name AS editor, ' . $fullname . ', g.name AS group_name, l.db_table_name');
		}

		return $query;
	}

	/**
	 * Method to get an array of data items.
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 */

	public function getItems()
	{
		$items = parent::getItems();
		$db = $this->getDbo();
		$query = $db->getQuery(true);
		$query->select('id, title')->from('#__viewlevels');
		$db->setQuery($query);
		$viewLevels = $db->loadObjectList('id');

		// Get the join element name of those elements not in a joined group
		foreach ($items as &$item)
		{
			if ($item->full_element_name == '')
			{
				$item->full_element_name = $item->db_table_name . '___' . $item->name;
			}

			// Add a tip containing the access level information
			$params = new JRegistry($item->params);

			$addAccessTitle = ArrayHelper::getValue($viewLevels, $item->access);
			$addAccessTitle = is_object($addAccessTitle) ? $addAccessTitle->title : 'n/a';

			$editAccessTitle = ArrayHelper::getValue($viewLevels, $params->get('edit_access', 1));
			$editAccessTitle = is_object($editAccessTitle) ? $editAccessTitle->title : 'n/a';

			$viewAccessTitle = ArrayHelper::getValue($viewLevels, $params->get('view_access', 1));
			$viewAccessTitle = is_object($viewAccessTitle) ? $viewAccessTitle->title : 'n/a';

			$item->tip = FText::_('COM_FABRIK_ACCESS_EDITABLE_ELEMENT') . ': ' . $addAccessTitle
				. '<br />' . FText::_('COM_FABRIK_ELEMENT_EDIT_ACCESS_LABEL') . ': ' . $editAccessTitle
				. '<br />' . FText::_('COM_FABRIK_ACCESS_VIEWABLE_ELEMENT') . ': ' . $viewAccessTitle;

			$validations = $params->get('validations');
			$v = array();

			// $$$ hugh - make sure the element has validations, if not it could return null or 0 length array
			if (is_object($validations))
			{
				for ($i = 0; $i < count($validations->plugin); $i ++)
				{
					$pname = $validations->plugin[$i];
					/*
					 * $$$ hugh - it's possible to save an element with a validation that hasn't
					 * actually had a plugin type selected yet.
					 */
					if (empty($pname))
					{
						$v[] = '&nbsp;&nbsp;<strong>' . FText::_('COM_FABRIK_ELEMENTS_NO_VALIDATION_SELECTED'). '</strong>';
						continue;
					}

					$msgs = $params->get($pname . '-message');
					/*
					 * $$$ hugh - elements which haven't been saved since Published param was added won't have
					 * plugin_published, and just default to Published
					 */
					if (!isset($validations->plugin_published))
					{
						$published = FText::_('JPUBLISHED');
					}
					else
					{
						$published = $validations->plugin_published[$i] ? FText::_('JPUBLISHED') : FText::_('JUNPUBLISHED');
					}

					$v[] = '&nbsp;&nbsp;<strong>' . $pname . ': <em>' . $published . '</em></strong>'
						. '<br />&nbsp;&nbsp;&nbsp;&nbsp;' . FText::_('COM_FABRIK_FIELD_ERROR_MSG_LABEL') . ': <em>' . htmlspecialchars(ArrayHelper::getValue($msgs, $i, 'n/a')) . '</em>';
				}
			}

			$item->numValidations = count($v);
			$item->validationTip = $v;
		}

		return $items;
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
	 * @since   1.6
	 */

	public function getTable($type = 'Element', $prefix = 'FabrikTable', $config = array())
	{
		$config['dbo'] = FabrikWorker::getDbo();

		return FabTable::getInstance($type, $prefix, $config);
	}
}
