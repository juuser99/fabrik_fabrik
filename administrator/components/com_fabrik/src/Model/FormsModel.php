<?php
/**
 * Fabrik Admin Form Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

namespace Fabrik\Component\Fabrik\Administrator\Model;

use Fabrik\Helpers\Worker;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Fabrik\Component\Fabrik\Administrator\Table\FabTable;
use Fabrik\Component\Fabrik\Administrator\Table\FormTable;
use Joomla\Database\DatabaseQuery;

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Fabrik Admin Form Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       4.0
 */
class FormsModel extends FabListModel
{
	/**
	 * FormsModel constructor.
	 *
	 * @param array $config
	 *
	 * @throws \Exception
	 *
	 * @since 4.0
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array('f.id', 'f.label', 'f.published');
		}

		parent::__construct($config);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return  DatabaseQuery
	 *
	 * @since 4.0
	 */
	protected function getListQuery()
	{
		// Initialise variables.
		$db    = $this->getDbo();
		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query->select($this->getState('list.select', 'DISTINCT f.id, f.*, l.id AS list_id, "" AS group_id'));
		$query->from('#__{package}_forms AS f');

		// Filter by published state
		$published = $this->getState('filter.published');

		if (is_numeric($published))
		{
			$query->where('f.published = ' . (int) $published);
		}
		elseif ($published === '')
		{
			$query->where('(f.published IN (0, 1))');
		}

		// Filter by search in title
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			$search = $db->quote('%' . $db->escape($search, true) . '%');
			$query->where('(f.label LIKE ' . $search . ')');
		}

		// Join over the users for the checked out user.
		$query->select('u.name AS editor');
		$query->join('LEFT', '#__users AS u ON checked_out = u.id');
		$query->join('LEFT', '#__{package}_lists AS l ON l.form_id = f.id');

		$query->join('INNER', '#__{package}_formgroup AS fg ON fg.form_id = f.id');

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering');
		$orderDirn = $this->state->get('list.direction');

		if ($orderCol == 'ordering' || $orderCol == 'category_title')
		{
			$orderCol = 'category_title ' . $orderDirn . ', ordering';
		}

		$query->order($db->escape($orderCol . ' ' . $orderDirn));

		return $query;
	}

	/**
	 * Returns a reference to the a Table object, always creating it.
	 *
	 * @param   string $type   The table type to instantiate
	 * @param   string $prefix A prefix for the table class name. Optional.
	 * @param   array  $config Configuration array for model. Optional.
	 *
	 * @return  Table    A database object
	 *
	 * @since    4.0
	 */
	public function getTable($type = FormTable::class, $prefix = '', $config = array())
	{
		$config['dbo'] = Worker::getDbo(true);

		return FabTable::getInstance($type, '', $config);
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string $ordering  An optional ordering field.
	 * @param   string $direction An optional direction (asc|desc).
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		// Initialise variables.
		/** @var CMSApplication $app */
		$app = Factory::getApplication('administrator');

		// Load the parameters.
		$params = ComponentHelper::getParams('com_fabrik');
		$this->setState('params', $params);

		$published = $app->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '');
		$this->setState('filter.published', $published);

		$search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		// List state information.
		parent::populateState('label', 'asc');
	}
}
