<?php
/**
 * Fabrik Admin List Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2018  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       4.0
 */

namespace Joomla\Component\Fabrik\Administrator\Model;

use Fabrik\Helpers\Worker;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Fabrik Admin List Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       4.0
 */
class FabrikListModel extends ListModel
{
	use TableTrait;

	/**
	 * Constructor.
	 * Ensure that we use the fabrik db model for the dbo
	 *
	 * @param   array $config An optional associative array of configuration settings.
	 *
	 * @since 4.0
	 *
	 * @throws \Exception
	 */
	public function __construct($config = array())
	{
		$config['dbo'] = Worker::getDbo(true);

		parent::__construct($config);
	}

	/**
	 * Get an array of objects to populate the form filter dropdown
	 *
	 * @deprecated
	 *
	 * @return  array  option objects
	 *
	 * @since 4.0
	 */
	public function getFormOptions()
	{
		// Initialise variables.
		$db    = $this->getDbo();
		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query->select('id AS value, label AS text');
		$query->from('#__{package}_forms')->where('published <> -2');
		$query->order('label ASC');
		$db->setQuery($query);
		$rows = $db->loadObjectList();

		return $rows;
	}

	/**
	 * Build the part of the list query that deals with filtering by form
	 *
	 * @param   JDatabaseQuery $query partial query
	 * @param   string         $table db table
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	protected function filterByFormQuery($query, $table)
	{
		$form = $this->getState('filter.form');

		if (!empty($form))
		{
			$query->where($table . '.form_id = ' . (int) $form);
		}
	}

	/**
	 * Method to auto-populate the model state.
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string $ordering  An optional ordering field.
	 * @param   string $direction An optional direction (asc|desc).
	 *
	 * @since    4.0
	 *
	 * @throws \Exception
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		$app = Factory::getApplication('administrator');

		// Load the package state
		$package = $app->getUserStateFromRequest('com_fabrik.package', 'package', '');
		$this->setState('com_fabrik.package', $package);

		// List state information.
		parent::populateState($ordering, $direction);
	}
}
