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
defined('_JEXEC') or die('Restricted access');

use \JFactory as JFactory;
use Joomla\Utilities\ArrayHelper;
use Fabrik\Helpers\Worker;
use \JDatabaseDriver as JDatabaseDriver;

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
	 * Containing db connections
	 *
	 * @var array
	 */
	protected static $dbs = array();

	/**
	 * Default connection object
	 *
	 * @var stdClass
	 */
	protected $defaultConnection = null;

	/**
	 * Current connection object
	 *
	 * @var stdClass
	 */
	protected $connection = null;

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

		$this->app    = $this->state->get('app', JFactory::getApplication());
		$this->config = $this->state->get('config', JFactory::getConfig());
		$this->input  = $this->state->get('input', JFactory::getApplication()->input);
	}

	/**
	 * Create an object describing the default Joomla Db connection
	 *
	 * @return \stdClass
	 */
	protected function joomlaConnection()
	{
		$config               = $this->config;
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

		$uri = $_SERVER['SERVER_ADDR'];

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
	 * Get the JDatabase for the current connection.
	 *
	 * @return mixed
	 * @throws RuntimeException
	 */
	public function getDb()
	{
		if (!isset(self::$dbs))
		{
			self::$dbs = array();
		}

		$error = false;
		$cn    = $this->getItem();

		if (!array_key_exists($cn->id, self::$dbs))
		{
			if ($this->isJdb())
			{
				$db = Worker::getDbo(true);
			}
			else
			{
				$options = ArrayHelper::fromObject($cn);
				$db      = JDatabaseDriver::getInstance($options);
			}

			try
			{
				$db->connect();
			} catch (\RuntimeException $e)
			{
				$error = true;
			}

			self::$dbs[$cn->id] = $db;

			if ($error)
			{
				/**
				 * $$$Rob - not sure why this is happening on badmintonrochelais.com (mySQL 4.0.24) but it seems like
				 * you can only use one connection on the site? As JDatabase::getInstance() forces a new connection if its options
				 * signature is not found, then fabrik's default connection won't be created, hence defaulting to that one
				 */
				if ($cn->default == 1 && $this->input->get('task') !== 'test')
				{
					self::$dbs[$cn->id] = Worker::getDbo();

					// $$$rob remove the error from the error stack
					// if we don't do this the form is not rendered
					\JError::getError(true);
				}
				else
				{
					if (!$this->app->isAdmin())
					{
						throw new RuntimeException('Could not connection to database', E_ERROR);
					}
					else
					{
						// $$$ rob - unset the connection as caching it will mean that changes we make to the incorrect connection in admin, will not result
						// in the test connection link informing the user that the changed connection properties are now correct
						if ($this->input->get('task') == 'test')
						{
							$this->connection = null;
							$level            = E_NOTICE;
						}
						else
						{
							$level = E_ERROR;
						}

						throw new \RuntimeException('Could not connection to database cid = ' . $cn->id, $level);
					}
				}
			}
		}

		return self::$dbs[$cn->id];
	}

	/**
	 * Get the tables names in the loaded connection
	 *
	 * @param   bool $addBlank add an empty record to the beginning of the list
	 *
	 * @return array tables
	 */
	public function getThisTables($addBlank = false)
	{
		$fabrikDb = $this->getDb();
		$tables   = $fabrikDb->getTableList();

		if (is_array($tables))
		{
			if ($addBlank)
			{
				$tables = array_merge(array(""), $tables);
			}

			return $tables;
		}
		else
		{
			return array();
		}
	}

	/**
	 * Test if the connection is exactly the same as Joomla's db connection as
	 * defined in configuration.php
	 *
	 * @since  3.0.8
	 *
	 * @return boolean  True if the same
	 */
	public function isJdb()
	{
		$conf     = $this->config;
		$host     = $conf->get('host');
		$user     = $conf->get('user');
		$database = $conf->get('db');
		$cn       = $this->getItem();

		return $cn->host === $host && $cn->database === $database && $cn->user = $user;
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

			if ($id === '')
			{
				// Insert
				$part[] = $data;

			}
			else
			{
				// Update
				$part[$id] = $data;
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
	 * @param   string  $id  JSON view file name
	 *
	 * @return stdClass
	 */
	public function getItem($id = null)
	{
		if (is_null($id))
		{
			$id = $this->get('id', '');
		}

		if ($id === '')
		{
			return $this->itemSchema();
		}

		$data = $this->app->getUserState('com_fabrik.edit.' . $this->name . '.data', array());
		$test = (array) $data;

		if (empty($test))
		{
			$items = $this->getItems();

			if (!array_key_exists($id, $items))
			{
				return $this->itemSchema();
			}

			$items[$id]->id = $id;
			$data           = $items[$id];
		}

		return $data;
	}

	/**
	 * Set the default connection
	 *
	 * @param   bool  $default
	 * @param   array $ids
	 *
	 * @return  void
	 */
	public function setDefault($default, $ids = array())
	{
		$items = $this->getItems();
		$id    = ArrayHelper::getValue($ids, 0);

		if ($default === true)
		{
			foreach ($items as $key => $item)
			{
				$item->default = (string) $key === (string) $id ? true : false;
				$item->id      = $key;
				$this->save($item);
			}
		}
		else
		{
			foreach ($ids as $id)
			{
				$items[$id]->default = $default;
				$items[$id]->id      = $id;
				$this->save($items[$id]);
			}
		}
	}

	/**
	 * Load the default connection
	 *
	 * @return  object  default connection
	 */

	public function &loadDefaultConnection()
	{
		if (!$this->defaultConnection)
		{
			$items = $this->getItems();

			$items = array_filter($items, function ($item)
			{
				return $item->default == 1;
			});

			$item = array_shift($items);
			$this->decryptPw($item);
			$this->defaultConnection = $item;
		}

		$this->connection = $this->defaultConnection;

		return $this->defaultConnection;
	}

	/**
	 * Decrypt once a connection password - if its params->encryptedPw option is true
	 *
	 * @param   JTable &$cnn Connection
	 *
	 * @since   3.1rc1
	 *
	 * @return  void
	 */
	protected function decryptPw(&$cnn)
	{
		if (isset($cnn->decrypted) && $cnn->decrypted)
		{
			return;
		}
		$crypt  = Worker::getCrypt();
		$params = $cnn->params;

		if (is_object($params) && $params->encryptedPw == true)
		{
			$cnn->password  = $crypt->decrypt($cnn->password);
			$cnn->decrypted = true;
		}
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
		$items = $this->getItems();

		foreach ($ids as $id)
		{
			$items[$id]->published = -2;
			$items[$id]->id        = $id;
			$this->save($items[$id]);
		}
	}

}
