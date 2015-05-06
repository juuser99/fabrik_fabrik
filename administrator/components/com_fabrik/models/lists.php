<?php
/**
 * Fabrik Admin Lists Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

namespace Fabrik\Admin\Models;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Registry\Registry;

interface ModelListsInterface
{
}

/**
 * Fabrik Admin Lists Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.5
 */
class Lists extends View implements ModelListsInterface
{
	/**
	 * State prefix
	 *
	 * @var string
	 */
	protected $context = 'fabrik.lists';

	/**
	 * Constructor.
	 *
	 * @param   Registry  $state  Optional configuration settings.
	 *
	 * @since	3.5
	 */
	public function __construct(Registry $state = null)
	{
		parent::__construct($state);

		if (!$this->state->exists('filter_fields'))
		{
			$this->state->set('filter_fields', array('l.id', 'label', 'db_table_name', 'published'));
		}
	}

	public function getPagination()
	{
		// FIXME
		return new \JPagination(0, 0, 0);
	}

	/**
	 * Method to get a store id based on model configuration state.
	 *
	 * This is necessary because the model is used by the component and
	 * different modules that might need different sets of data or different
	 * ordering requirements.
	 *
	 * @param   string  $id  A prefix for the store id.
	 *
	 * @return  string  A store id.
	 *
	 * @since	1.6
	 */
	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id .= ':' . $this->get('filter.search');
		$id .= ':' . $this->get('filter.access');
		$id .= ':' . $this->get('filter.state');
		$id .= ':' . $this->get('filter.category_id');
		$id .= ':' . $this->get('filter.language');

		return parent::getStoreId($id);
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering   An optional ordering field.
	 * @param   string  $direction  An optional direction (asc|desc).
	 *
	 * @since	1.6
	 *
	 * @return  void
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		// Load the filter state.
		$search = $this->app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->set('filter.search', $search);

		// Load the published state
		$published = $this->app->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '');
		$this->set('filter.published', $published);

		// List state information.
		parent::populateState('label', 'asc');
	}

	/**
	 * Get list view items.
	 *
	 * @return array
	 */
	public function getItems()
	{
		$items = parent::getItems();

		return $this->filterItems($items);
	}

	/**
	 * Get an array of database table names used in fabrik lists
	 *
	 * @return  array  database table names
	 */

	public function getDbTableNames()
	{

	}

	/**
	 * Trash items
	 *
	 * @param   array $ids Ids
	 *
	 * @return  void
	 */
	public function trash($ids)
	{
		foreach ($ids as $id)
		{
			$this->set('id', $id);
			$list = new Lizt;
			$item = $list->getItem($id);
			$item->list->published = -2;
			$list->save($item);
		}
	}

}
