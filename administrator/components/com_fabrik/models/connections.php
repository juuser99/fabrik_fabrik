<?php
/**
 * Fabrik Admin Connections Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

namespace Fabrik\Admin\Models;

// No direct access
use Joomla\Utilities\ArrayHelper;

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.modellist');

interface ConnectionsInterface
{
}

/**
 * Fabrik Admin Connections Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.5
 */
class Connections extends Base implements ConnectionsInterface
{
	/**
	 * Session state context prefix
	 *
	 * @var string
	 */
	protected $context = 'fabrik.connections';

	/**
	 * Constructor.
	 *
	 * @param   Registry $state Optional configuration settings.
	 *
	 * @since    3.5
	 */
	public function __construct(Registry $state = null)
	{
		parent::__construct($state);

		if (!$this->state->exists('filter_fields'))
		{
			$this->state->set('filter_fields', array('c.id'));
		}
	}

	/**
	 * Create an object describing the default Joomla Db connection
	 *
	 * @return \stdClass
	 */
	protected function joomlaConnection()
	{
		$config               = \JFactory::getConfig();
		$default              = new \stdClass;
		$default->default     = true;
		$default->description = 'Joomla Db';
		$default->driver      = $config->get('dbtype');
		$default->host        = $config->get('host');
		$default->user        = $config->get('user');
		$default->password    = $config->get('password');
		$default->port        = '';
		$default->socket      = '';
		$default->database    = $config->get('db');
		$default->prefix      = $config->get('dbprefix');
		$default->published   = true;
		$default->checked_out = false;

		return $default;
	}

	/**
	 * Get the list of connections for this server address.
	 * Connections stored in models/connections.json.
	 *
	 * @return array
	 */
	public function getItems()
	{
		if (isset($this->items))
		{
			return $this->items;
		}

		$json = file_get_contents(JPATH_COMPONENT_ADMINISTRATOR . '/models/connections.json');
		$json = json_decode($json);
		$uri  = $_SERVER['SERVER_ADDR'];

		if (!isset($json->$uri))
		{
			$json->$uri = array();
			$json->$uri = array($this->joomlaConnection());
		}

		$this->items = $this->filterItems($json->$uri);

		foreach ($this->items as &$item)
		{
			if ($item->checked_out !== '')
			{
				$user         = \JFactory::getUser($item->checked_out);
				$item->editor = $user->get('name');
			}
		}

		return $this->items;
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string $ordering  An optional ordering field.
	 * @param   string $direction An optional direction (asc|desc).
	 *
	 * @since    1.6
	 *
	 * @return  void
	 */
	protected function populateState($ordering = '', $direction = '')
	{
		// Load the parameters.
		/*$params = JComponentHelper::getParams('com_fabrik');
		$this->setState('params', $params);*/
		$published = $this->app->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '');
		$this->set('filter.published', $published);
		$search = $this->app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->set('filter.search', $search);

		// List state information.
		parent::populateState('name', 'asc');
	}

	/**
	 * Get list of active connections
	 *
	 * @return  array connection items
	 */

	public function activeConnections()
	{
		// @todo
		echo "todo - return active connections";
		exit;

		/*$db = $this->getDbo();
		$query = $db->getQuery(true);
		$query->select('*')->from('#__fabrik_connections')->where('published = 1');
		$items = $this->_getList($query);*/

		return $items;
	}

	/**
	 * Save the connection
	 *
	 * @param   array $data Connection data
	 *
	 * @return bool
	 */
	public function save($data)
	{
		if (is_object($data))
		{
			$data = ArrayHelper::fromObject($data);
		}

		unset($data['passwordConf']);
		unset($data['editor']);
		$path = JPATH_COMPONENT_ADMINISTRATOR . '/models/connections.json';
		$json = file_get_contents($path);
		$json = json_decode($json);
		$uri  = $_SERVER['SERVER_ADDR'];
		$id   = $data['id'];
		unset($data['id']);

		if (!isset($json->$uri))
		{

			$json->$uri = array($id => $data);
		}
		else
		{
			$part =& $json->$uri;

			if ($id <> '')
			{
				// Update
				$part[$id] = $data;
			}
			else
			{
				// Insert
				$part[] = $data;
			}
		}

		$output = json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

		\JFile::write($path, $output);

		return parent::save($data);
	}

	/**
	 * Load a schema file which defines a new item
	 *
	 * @return object
	 */
	protected function itemSchema()
	{
		$path = JPATH_COMPONENT_ADMINISTRATOR . '/models/schemas/connection.json';
		$json = file_get_contents($path);

		return json_decode($json);
	}

	/**
	 * Get an item
	 *
	 * @return stdClass
	 */
	public function getItem()
	{
		if ($this->get('id', '') === '')
		{
			return $this->itemSchema();
		}

		$data = $this->app->getUserState('com_fabrik.edit.' . $this->name . '.data', array());
		$test = (array) $data;

		if (empty($test))
		{
			$id             = $this->get('id');
			$items          = $this->getItems();
			$items[$id]->id = $id;
			$data           = $items[$id];
		}

		return $data;
	}

	/**
	 * Set the default connection
	 *
	 * @param $default
	 * @param $ids
	 */
	public function setDefault($default, $ids)
	{
		$items = $this->getItems();

		foreach ($ids as $id)
		{
			$items[$id]->default = $default;
			$items[$id]->id      = $id;
			$this->save($items[$id]);
		}
	}

}
