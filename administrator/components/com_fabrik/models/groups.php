<?php
/**
 * Fabrik Admin Groups Model
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

interface ModelGroupsInterface
{
}

/**
 * Fabrik Admin Groups Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.5
 */
class Groups extends \JModelBase implements ModelGroupsInterface
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
			$this->state->set('filter_fields', array('g.id', 'g.name', 'g.label', 'f.label', 'g.published'));
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
		$params = JComponentHelper::getParams('com_fabrik');
		$this->setState('params', $params);

		$published = $this->app->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '');
		$this->setState('filter.published', $published);

		$search = $this->app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		// Load the form state
		$package = $this->app->getUserStateFromRequest($this->context . '.filter.form', 'filter_form', '');
		$this->setState('filter.form', $package);

		// List state information.
		parent::populateState('name', 'asc');
	}
}
