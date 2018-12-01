<?php
/**
 * Base Fabrik Table
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2014  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Joomla\Component\Fabrik\Administrator\Table;

use Fabrik\Helpers\ArrayHelper;
use Fabrik\Helpers\Worker;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Base Fabrik Table
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       4.0
 */
class FabTable extends Table
{
	/**
	 * JSON encoded JFormField param options
	 *
	 * @var string
	 *
	 * @since 4.0
	 */
	public $params = '';

	/**
	 * @param string $tableClass
	 * @param string $prefix
	 * @param array  $config
	 *
	 * @return FabTable
	 *
	 * @since version
	 */
	public static function getInstance($tableClass, $prefix = '', $config = [])
	{
		if (!class_exists($tableClass)) {
			throw new \InvalidArgumentException($tableClass." was not found");
		}

		$db = array_key_exists('db', $config) ? $config['db'] : Worker::getDbo(true);

		$instance = new $tableClass($db);

		/**
		 * $$$ hugh - we added $params in this commit:
		 * https://github.com/Fabrik/fabrik/commit/d98ad7dfa48fefc8b2db55dd5c7a8de16f9fbab4
		 * ... but the FormGroup table doesn't have a params column.  For now, zap the params for FormGroup,
		 * until we do another release and can add an SQL update to add it.
		 *
		 * $$$ hugh - neither does the comments table ...
		 *
		 * @todo - add JCommentsTableObjects table back from fabrik_form plugin once it's namespaced
		 */
		if (in_array($tableClass, array(FormGroupTable::class)))
		{
			unset($instance->params);
		}

		return $instance;
	}

	/**
	 * Batch set a properties and params
	 *
	 * @param   array $batch properties and params
	 *
	 * @since   4.0
	 *
	 * @return  bool
	 */
	public function batch($batch)
	{
		$batchParams = ArrayHelper::getValue($batch, 'params');
		unset($batch['params']);
		$query = $this->_db->getQuery(true);
		$this->bind($batch);
		$params = json_decode($this->params);

		foreach ($batchParams as $key => $val)
		{
			$params->$key = $val;
		}

		$this->params = json_encode($params);

		return $this->store();
	}

	/**
	 * Get the columns from database table.
	 *
	 * @param   bool $reload flag to reload cache
	 *
	 * @return  mixed  An array of the field names, or false if an error occurs.
	 *
	 * @since   4.0
	 * @throws  \UnexpectedValueException
	 */
	public function getFields($reload = false)
	{
		static $cache = array();

		if (ArrayHelper::getValue($cache, $this->_tbl) === null || $reload)
		{
			// Lookup the fields for this table only once. PER TABLE NAME!
			$name   = $this->_tbl;
			$fields = $this->_db->getTableColumns($name, false);

			if (empty($fields))
			{
				throw new \UnexpectedValueException(sprintf('No columns found for %s table', $name));
			}

			$cache[$this->_tbl] = $fields;
		}

		return $cache[$this->_tbl];
	}

	/**
	 * Set values to prevent errors due to J4 setting strict table in the session
	 *
	 * @return bool
	 *
	 * @since 4.0
	 */
	public function check()
	{
		// set null dates for applicable fields
		if ($this->hasField('publish_up') && !$this->publish_up)
		{
			$this->publish_up = $this->_db->getNullDate();
		}

		if ($this->hasField('publish_down') && !$this->publish_down)
		{
			$this->publish_down = $this->_db->getNullDate();
		}

		// Check the publish down date is not earlier than publish up.
		if ($this->publish_down < $this->publish_up && $this->publish_down > $this->_db->getNullDate())
		{
			// Swap the dates.
			$temp = $this->publish_up;
			$this->publish_up = $this->publish_down;
			$this->publish_down = $temp;
		}

		$date = Factory::getDate();
		$user = Factory::getUser();

		if ($this->hasField('checked_out_time') && !$this->checked_out_time)
		{
			$this->checked_out_time = $this->_db->getNullDate();
		}

		if ($this->id)
		{
			// Existing item
			if ($this->hasField('modified'))
			{
				$this->modified = $this->_db->getNullDate();
			}

			if ($this->hasField('modified_by'))
			{
				$this->modified_by = $user->get('id');
			}


			return parent::check();
		}

		if ($this->hasField('checked_out'))
		{
			$this->checked_out = 0;
		}

		if ($this->hasField('modified_by'))
		{
			$this->modified_by = 0;
		}

		if ($this->hasField('modified') && !$this->modified)
		{
			$this->modified = $this->_db->getNullDate();
		}

		if ($this->hasField('created') && !(int) $this->created)
		{
			$this->created = $date->toSql();
		}

		if ($this->hasField('created_by') && !(int) $this->created_by)
		{
			$this->created_by = $user->get('id');
		}

		if ($this->hasField('created_by_alias') && !$this->created_by_alias)
		{
			$this->created_by_alias = $user->get('username');
		}

		return parent::check();
	}
}
