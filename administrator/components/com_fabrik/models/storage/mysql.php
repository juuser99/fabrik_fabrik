<?php
/**
 * Fabrik Admin Group Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.5
 */
namespace Fabrik\Storage;

use Fabrik\Helpers\ArrayHelper;
use Joomla\Registry\Registry;
use Joomla\String\String;
use Fabrik\Helpers\Worker;

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Fabrik Admin View Model.Handles storing a 'view' to a json file
 * A view is a combination of list/form/details.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.5
 */
class MySql
{
	/**
	 * JDb class
	 *
	 * @var \JDatabaseDriver
	 */
	public $db = null;

	/**
	 * Table name
	 *
	 * @var  string
	 */
	public $table = null;

	/**
	 * Cached indexes
	 *
	 * @var  array
	 */
	protected $indexes = null;

	/**
	 * @var Registry|null
	 */
	public $params;

	/**
	 * Constructor.
	 *
	 * @param   array    $config An optional associative array of configuration settings.
	 * @param   Registry $params Registry - optional
	 */
	public function __construct($config = array(), $params = null)
	{
		if (is_null($params))
		{
			$params = new Registry;
		}

		$this->params = $params;

		$this->db    = ArrayHelper::getValue($config, 'db', \JFactory::getDbo());
		$this->app = ArrayHelper::getValue($config, 'app', \JFactory::getApplication());
		$this->table = ArrayHelper::getValue($config, 'table');
	}

	/**
	 * Get the db table's collation
	 *
	 * @param   string $default Default collation to return
	 *
	 * @return  string  collation
	 */
	public function getCollation($default = 'none')
	{
		$this->db->setQuery('SHOW TABLE STATUS LIKE ' . $this->db->q($this->table));
		$info = $this->db->loadObject();

		return is_object($info) ? $info->Collation : $default;
	}

	/**
	 * Alter the db table's collation
	 *
	 * @param   string $collation Collection name
	 *
	 * @return boolean
	 */
	public function setCollation($collation)
	{
		$this->db->setQuery('ALTER TABLE ' . $this->table . ' COLLATE  ' . $collation);

		return $this->db->execute();
	}

	public function canCreate()
	{
		return true;
	}

	/**
	 * Does a table exist in the db.
	 *
	 * @param string $table if not supplied uses this->table;
	 *
	 * @return bool
	 */
	public function tableExists($table = '')
	{
		if ($table !== '')
		{
			$this->setTable($table);
		}

		$tables = $this->db->getTableList();

		return in_array($table, $tables);
	}

	public function getIndexes()
	{
		if (!isset($this->indexes))
		{
			$this->db->setQuery('SHOW INDEXES FROM ' . $this->db->qn($this->table));
			$this->indexes = $this->db->loadObjectList();
		}

		return $this->indexes;
	}

	/**
	 * Drop an index
	 *
	 * @param   string $field  field name
	 * @param   string $prefix index name prefix (allows you to differentiate between indexes created in
	 *                         different parts of fabrik)
	 * @param   string $type   table name @since 29/03/2011
	 *
	 * @return  string  index type
	 */

	public function dropIndex($field, $prefix = '', $type = 'INDEX')
	{
		$table = $this->table;
		$field = \FabrikString::shortColName($field);

		if ($field == '')
		{
			return;
		}

		$dbIndexes = $this->getIndexes();

		if (is_array($dbIndexes))
		{
			foreach ($dbIndexes as $index)
			{
				if ($index->Key_name == "fb_{$prefix}_{$field}_{$type}")
				{
					$this->db->setQuery('ALTER TABLE ' . $this->db->qn($table) . ' DROP INDEX ' . $this->db->qn($index->Key_name));

					return $this->db->execute();

					break;
				}
			}
		}
	}

	/**
	 * Add an index to the table
	 *
	 * @param   string $field  field name
	 * @param   string $prefix index name prefix (allows you to differentiate between indexes created in
	 *                         different parts of fabrik)
	 * @param   string $type   index type
	 * @param   int    $size   index length
	 *
	 * @return void
	 */

	public function addIndex($field, $prefix = '', $type = 'INDEX', $size = '')
	{
		$indexes = $this->getIndexes();

		if (is_numeric($field))
		{
			$el    = $this->getFormModel()->getElement($field, true);
			$field = $el->getFullName(true, false);
		}

		/* $$$ hugh - @TODO $field is in 'table.element' format but $indexes
		 * has Column_name as just 'element' ... so we're always rebuilding indexes!
			* I'm in the middle of fixing something else, must come back and fix this!!
			* OK, moved these two lines from below to here
			*/
		$field = str_replace('_raw', '', $field);

		// $$$ rob 29/03/2011 ensure its in tablename___elementname format
		$field = str_replace('.', '___', $field);

		// $$$ rob 28/02/2011 if index in joined table we need to use that the make the key on
		$table = !strstr($field, '___') ? $this->table : array_shift(explode('___', $field));
		$field = \FabrikString::shortColName($field);
		ArrayHelper::filter($indexes, 'Column_name', $field);

		if (!empty($indexes))
		{
			// An index already exists on that column name no need to add
			return;
		}

		if ($field == '')
		{
			return;
		}

		if ($size != '')
		{
			$size = '( ' . $size . ' )';
		}

		$this->dropIndex($field, $prefix, $type);
		$query = ' ALTER TABLE ' . $this->db->qn($table) . ' ADD INDEX ' . $this->db->qn("fb_{$prefix}_{$field}_{$type}") . ' ('
			. $this->db->qn($field) . ' ' . $size . ')';
		$this->db->setQuery($query);

		return $this->db->execute();
	}

	/**
	 * Get the tables primary key and if the primary key is auto increment
	 *
	 * @param  string $table Table name (defaults to this->table)
	 *
	 * @return  mixed    If ok returns array(key, extra, type, name) otherwise
	 */
	public function getPrimaryKeyAndExtra($table = null)
	{
		if (is_null($table))
		{
			$table = $this->table;
		}

		$origColNames = $this->getDBFields($table);
		$keys         = array();

		if (is_array($origColNames))
		{
			foreach ($origColNames as $origColName)
			{
				$colName = $origColName->Field;
				$key     = $origColName->Key;
				$extra   = $origColName->Extra;
				$type    = $origColName->Type;

				if ($key === 'PRI')
				{
					$keys[] = array('key' => $key, 'extra' => $extra, 'type' => $type, 'colname' => $colName);
				}
			}
		}

		if (empty($keys))
		{
			// $$$ hugh - might be a view, so Hail Mary attempt to find it in our lists
			// $$$ So ... see if we know about it, and if so, fake out the PK details
			$query = $this->db->getQuery(true);
			$query->select('db_primary_key')->from('#__fabrik_lists')->where('db_table_name = ' . $this->db->q($table));
			$this->db->setQuery($query);
			$join_pk = $this->db->loadResult();

			if (!empty($join_pk))
			{
				$shortColName = \FabrikString::shortColName($join_pk);
				$key          = $origColName->Key;
				$extra        = $origColName->Extra;
				$type         = $origColName->Type;
				$keys[]       = array('colname' => $shortColName, 'type' => $type, 'extra' => $extra, 'key' => $key);
			}
		}

		return empty($keys) ? false : $keys;
	}

	/**
	 * Create a db table
	 * @param   string  $name    Table name
	 * @param   array   $fields  Fields to create
	 * @param   array   $opts    Options
	 *
	 * @return array
	 */
	public function createTable($name, $fields, $opts)
	{
		$db = Worker::getDbo(true);
		$fabrikDb = $this->db;

		if (is_null($name))
		{
			$name = $this->getTable()->db_table_name;
		}

		$sql = 'CREATE TABLE IF NOT EXISTS ' . $db->qn($name) . ' (';

		$arAddedObj = array();
		$keys = array();
		$lines = array();
		$pluginManager = Worker::getPluginManager();

		foreach ($fields as $fieldName => $fieldData)
		{
			$elementModel = $pluginManager->loadPlugIn($fieldData['plugin'], 'element');
			$element = $elementModel->getElement();

			// Replace all non alphanumeric characters with _
			$fieldName = \FabrikString::dbFieldName($fieldName);

			if ($element->primary_key || ArrayHelper::getValue($fieldData, 'primary_key'))
			{
				$keys[] = $fieldName;
			}

			// Any elements that are names the same (eg radio buttons) can not be entered twice into the database
			if (!in_array($fieldName, $arAddedObj))
			{
				$arAddedObj[] = $fieldName;
				$fieldType = $elementModel->getFieldDescription();

				if ($fieldName != '' && !is_null($fieldType))
				{
					if (String::stristr($fieldType, 'not null'))
					{
						$lines[] = $fabrikDb->qn($fieldName) . ' ' . $fieldType;
					}
					else
					{
						$lines[] = $fabrikDb->qn($fieldName) . ' ' . $fieldType . ' null';
					}
				}
			}
		}

		$sql .= implode(', ', $lines);
		$sql .= !empty($keys) ? ', PRIMARY KEY (' . implode(',', $db->qn($keys)) . '))' : ')';

		foreach ($opts as $k => $v)
		{
			if ($v != '')
			{
				$sql .= ' ' . $k . ' ' . $v;
			}
		}
		$sql .= ' ENGINE = MYISAM ';
		$fabrikDb->setQuery($sql);
		$fabrikDb->execute();

		return $keys;
	}

	/**
	 * Set the table name
	 *
	 * @param string $table
	 *
	 * @return $this
	 */
	public function setTable($table = '')
	{
		if (!is_null($table))
		{
			$this->table = $table;
		}

		return $this;
	}

	/**
	 * Tests if the table is in fact a view
	 *
	 * @TODO fix this for connection model which will need to look at db tables or json meta according to version.s
	 *
	 * @return  bool    true if table is a view
	 */

	public function isView()
	{
		$isView = $this->params->get('isview', null);

		if (!is_null($isView) && (int) $isView >= 0)
		{
			return $isView;
		}

		/* $$$ hugh - because querying INFORMATION_SCHEMA can be very slow (like minutes!) on
		 * a shared host, I made a small change.  The edit table view now adds a hidden 'isview'
			* param, defaulting to -1 on new tables.  So the following code should only ever execute
			* one time, when a new table is saved.  Before this change, because 'isview' wasn't
			* included on the edit view (because it's not a "real" user settable param), so didn't
			* exist when we picked up the params from the submitted data, this code was running (twice!)
			* every time a table was saved.
			* http://fabrikar.com/forums/showthread.php?t=16622&page=6
			*/

		return false;

	}

	/**
	 * @TODO implement this
	 *
	 * @return boolean
	 */
	public function canAlterFields()
	{
		return true;
	}

	/**
	 * Adds a primary key to the database table
	 *
	 * @param   string $fieldName     the column name to make into the primary key
	 * @param   bool   $autoIncrement is the column an auto incrementing number
	 * @param   string $type          column type definition (eg varchar(255))
	 *
	 * @return  void
	 */
	public function updatePrimaryKey($fieldName, $autoIncrement, $type = 'int(11)')
	{
		if (!$this->canAlterFields())
		{
			return;
		}

		$aPriKey = $this->getPrimaryKeyAndExtra();

		if (!$aPriKey)
		{
			// No primary key set so we should set it
			$this->addKey($fieldName, $autoIncrement, $type);
		}
		else
		{
			if (count($aPriKey) > 1)
			{
				// $$$ rob multi field pk - ignore for now

				return;
			}

			$aPriKey  = $aPriKey[0];
			$shortKey = \FabrikString::shortColName($fieldName);

			// $shortKey = $feModel->_shortKey($fieldName, true); // added true for second arg so it strips quotes, as was never matching colname with quotes
			if ($fieldName != $aPriKey['colname'] && $shortKey != $aPriKey['colname'])
			{
				// Primary key already exists so we should drop it
				$this->dropKey($aPriKey);
				$this->addKey($fieldName, $autoIncrement, $type);
			}
			else
			{
				// Update the key, it if we need to
				$priInc = $aPriKey['extra'] == 'auto_increment' ? '1' : '0';

				if ($priInc != $autoIncrement || $type != $aPriKey['type'])
				{
					$this->updateKey($fieldName, $autoIncrement, $type);
				}
			}
		}
	}

	/**
	 * Internal function: add a key to the table
	 *
	 * @param   string $fieldName     primary key column name
	 * @param   bool   $autoIncrement is the column auto incrementing
	 * @param   string $type          the primary keys column type (if autoincrement true then int(6) is always used as
	 *                                the type)
	 *
	 * @return  mixed  false / JError
	 */

	public function addKey($fieldName, $autoIncrement, $type = "INT(6)")
	{
		$result = true;
		$type   = $autoIncrement != true ? $type : 'INT(6)';

		if ($fieldName === '')
		{
			return false;
		}

		$fieldName = $this->db->qn($fieldName);
		$sql       = 'ALTER TABLE ' . $this->db->qn($this->table) . ' ADD PRIMARY KEY (' . $fieldName . ')';

		// Add a primary key
		$this->db->setQuery($sql);

		if ($this->db->execute())
		{

			if ($autoIncrement)
			{
				// Add the autoinc
				$sql = 'ALTER TABLE ' . $this->db->qn($this->table) . ' CHANGE ' . $fieldName . ' ' . $fieldName . ' ' . $type . ' NOT NULL AUTO_INCREMENT';
				$this->db->setQuery($sql);
				$result = $result && $this->db->execute();
			}
		}
		else
		{
			$result = false;
		}

		return $result;
	}

	/**
	 * Internal function: drop the table's key
	 *
	 * @param   array $aPriKey existing key data
	 *
	 * @return  bool true if key droped
	 */

	public function dropKey($aPriKey)
	{
		$sql = 'ALTER TABLE ' . $this->db->qn($this->table) . ' CHANGE ' . $this->db->qn($aPriKey['colname']) . ' '
			. $this->db->qn($aPriKey['colname']) . ' ' . $aPriKey['type'] . ' NOT NULL';

		// Remove the autoinc
		$this->db->setQuery($sql);

		if ($this->db->execute())
		{
			// Drop the primary key
			$sql = 'ALTER TABLE ' . $this->db->qn($this->table) . ' DROP PRIMARY KEY';
			$this->db->setQuery($sql);

			return $this->db->execute();
		}

		return true;
	}

	/**
	 * Internal function: update an exisitng key in the table
	 *
	 * @param   string $fieldName     primary key column name
	 * @param   bool   $autoIncrement is the column auto incrementing
	 * @param   string $type          the primary keys column type
	 *
	 * @return  bool
	 */

	public function updateKey($fieldName, $autoIncrement, $type = "INT(11)")
	{
		if (strstr($fieldName, '.'))
		{
			$fieldName = array_pop(explode('.', $fieldName));
		}

		$sql = 'ALTER TABLE ' . $this->db->qn($this->table) . ' CHANGE ' . $this->db->qn($fieldName) . ' ' . $this->db->qn($fieldName) . ' '
			. $type . ' NOT NULL';

		// Update primary key
		if ($autoIncrement)
		{
			$sql .= ' AUTO_INCREMENT';
		}

		$this->db->setQuery($sql);

		return $this->db->execute();
	}

	/**
	 * Gets the field names for the given table
	 * $$$ hugh - added this to backend, as I need it in some places where we have
	 * a backend list model, and until now only existed in the FE model.
	 *
	 * @param   string $tbl      Table name
	 * @param   string $key      Field to key return array on
	 * @param   bool   $basetype Deprecated - not used
	 *
	 * @return  array  table fields
	 */

	public function getDBFields($tbl = null, $key = null, $basetype = false)
	{
		if (is_null($tbl))
		{
			$tbl = $this->table;
		}

		if ($tbl == '')
		{
			return array();
		}

		$sig = $tbl . $key;
		$tbl = $this->db->qn($tbl);

		if (!isset($this->dbFields[$sig]))
		{
			$this->db->setQuery('DESCRIBE ' . $tbl);
			$this->dbFields[$sig] = $this->db->loadObjectList($key);

			/**
			 * $$$ hugh - added BaseType, which strips (X) from things like INT(6) OR varchar(32)
			 * Also converts it to UPPER, just to make things a little easier.
			 */
			foreach ($this->dbFields[$sig] as &$row)
			{
				/**
				 * Boil the type down to just the base type, so "INT(11) UNSIGNED" becomes just "INT"
				 * I'm sure there's other cases than just UNSIGNED I need to deal with, but for now that's
				 * what I most care about, as this stuff is being written handle being more specific about
				 * the elements the list PK can be selected from.
				 */
				$row->BaseType = strtoupper(preg_replace('#(\(\d+\))$#', '', $row->Type));
				$row->BaseType = preg_replace('#(\s+SIGNED|\s+UNSIGNED)#', '', $row->BaseType);
			}
		}

		return $this->dbFields[$sig];
	}
}
