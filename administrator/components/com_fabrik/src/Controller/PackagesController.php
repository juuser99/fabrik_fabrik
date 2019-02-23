<?php
/**
 * Fabrik Admin Packages Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

namespace Fabrik\Component\Fabrik\Administrator\Controller;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Component\Fabrik\Administrator\Model\PackageModel;
use Fabrik\Helpers\Worker;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Language\Text;
use Fabrik\Component\Fabrik\Administrator\Table\FabrikTable;
use Fabrik\Component\Fabrik\Administrator\Table\PackageTable;
use Joomla\Database\DatabaseQuery;

/**
 * Fabrik Admin Packages Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       4.0
 */
class PackagesController extends AbstractAdminController
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see		JController
	 *
	 * @since	1.6
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array('p.id', 'p.label', 'p.published');
		}

		parent::__construct($config);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return  DatabaseQuery
	 *
	 * @since	1.6
	 */
	protected function getListQuery()
	{
		// Initialise variables.
		$db = $this->getDbo();
		$query = $db->getQuery(true);

		// Select the required fields from the table. Always load fabrik packages - so no {package} placeholder
		$query->select($this->getState('list.select', 'p.*'));
		$query->from('#__fabrik_packages AS p');

		// Join over the users for the checked out user.
		$query->select(' u.name AS editor');
		$query->join('LEFT', '#__users AS u ON p.checked_out = u.id');

		// Filter by published state
		$published = $this->getState('filter.published');

		if (is_numeric($published))
		{
			$query->where('p.published = ' . (int) $published);
		}
		elseif ($published === '')
		{
			$query->where('(p.published IN (0, 1))');
		}

		$query->where('(p.external_ref <> 1 OR p.external_ref IS NULL)');

		// Filter by search in title
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			$search = $db->quote('%' . $db->escape($search, true) . '%');
			$query->where('(p.label LIKE ' . $search . ' OR p.component_name LIKE ' . $search . ')');
		}
		// Add the list ordering clause.
		$orderCol = $this->state->get('list.ordering');
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
	 * @param   string  $type    The table type to instantiate
	 * @param   string  $prefix  A prefix for the table class name. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  PackageTable	A database object
	 *
	 * @since	1.6
	 */
	public function getTable($type = PackageTable::class, $prefix = '', $config = array())
	{
		$config['dbo'] = Worker::getDbo();

		return FabrikTable::getInstance($type, $prefix, $config);
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
		// Initialise variables.
		$app = Factory::getApplication('administrator');

		// Load the parameters.
		$params = ComponentHelper::getParams('com_fabrik');
		$this->setState('params', $params);

		// Load the filter state.
		$search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		// Load the published state
		$published = $app->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '');
		$this->setState('filter.published', $published);

		// List state information.
		parent::populateState('u.name', 'asc');
	}

	/**
	 * Method to get an array of data items.
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 *
	 * @since 4.0
	 */
	public function getItems()
	{
		$items = parent::getItems();

		foreach ($items as &$i)
		{
			$n = $i->component_name . '_' . $i->version;
			$file = JPATH_ROOT . '/tmp/' . $i->component_name . '/pkg_' . $n . '.zip';
			$url = COM_FABRIK_LIVESITE . 'tmp/' . $i->component_name . '/pkg_' . $n . '.zip';

			if (File::exists($file))
			{
				$i->file = '<a href="' . $url . '"><span class="icon-download"></span> pkg_' . $n . '.zip</a>';
			}
			else
			{
				$i->file = Text::_('COM_FABRIK_EXPORT_PACKAGE_TO_CREATE_ZIP');
			}
		}

		return $items;
	}

	/**
	 * @param string $name
	 * @param string $prefix
	 * @param array  $config
	 *
	 * @return PackageModel
	 *
	 * @since version
	 */
	public function getModel($name = PackageModel::class, $prefix = '',  $config = array('ignore_request' => true))
	{
		/** @var PackageModel $model */
		$model = parent::getModel($name, $prefix, $config);

		return $model;
	}
}
