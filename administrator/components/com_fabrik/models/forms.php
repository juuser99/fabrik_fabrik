<?php
/**
 * Fabrik Admin Form Model
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

interface ModelFormsInterface
{
}

/**
 * Fabrik Admin Form Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.5
 */
class Forms extends View implements ModelFormsInterface
{
	/**
	 * State prefix
	 *
	 * @var string
	 */
	protected $context = 'fabrik.forms';

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
			$this->state->set('filter_fields', array('f.id', 'f.label', 'f.published'));
		}
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
		$published = $this->app->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '');
		$this->set('filter.published', $published);

		$search = $this->app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->set('filter.search', $search);

		// List state information.
		parent::populateState('label', 'asc');
	}
}
