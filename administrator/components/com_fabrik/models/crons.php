<?php
/**
 * Crons Admin Model
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

use \JComponentHelper as JComponentHelper;

interface ModelCronsInterface
{
}

/**
 * Crons Admin Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.0
 */
class Crons extends \JModelBase implements ModelCronsInterface
{
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
			$this->state->set('filter_fields', array('c.id', 'c.label', 'p.published'));
		}
	}

	public function getItems()
	{
		return array();
	}

	public function getPagination()
	{
		return new \JPagination(0, 0, 0);
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering   An optional ordering field.
	 * @param   string  $direction  An optional direction (asc|desc).
	 *
	 * @return  void
	 */

	protected function populateState($ordering = null, $direction = null)
	{
		// Load the parameters.
		// @TODO is this used?
		$params = JComponentHelper::getParams('com_fabrik');
		$this->set('params', $params);

		// Load the filter state.
		$search = $this->app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->set('filter.search', $search);

		// Load the published state
		$published = $this->app->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '');
		$this->set('filter.published', $published);

		$state = $this->app->getUserStateFromRequest($this->context . '.filter.state', 'filter_state', '', 'string');
		$this->set('filter.state', $state);

		// List state information.
		parent::populateState('name', 'asc');
	}
}
