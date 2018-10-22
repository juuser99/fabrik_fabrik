<?php
/**
 * Fabrik Join Model
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2018  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Joomla\Component\Fabrik\Site\Model;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Worker;
use Joomla\CMS\Table\Table;
use Joomla\Component\Fabrik\Administrator\Table\FabTable;
use Joomla\Component\Fabrik\Administrator\Table\JoinTable;
use \Joomla\Registry\Registry;

/**
 * Fabrik Join Model
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       4.0
 */
class JoinModel extends FabModel
{
	/**
	 * Join table
	 *
	 * @var object
	 *
	 * @since 4.0
	 */
	protected $join = null;

	/**
	 * Join id to load
	 *
	 * @var int
	 *
	 * @since 4.0
	 */
	protected $id = null;

	/**
	 * Data to bind to Join table
	 *
	 * @var array
	 *
	 * @since 4.0
	 */
	protected $data = null;

	/**
	 * Whether the joined table is a MySQL view
	 *
	 * @var bool
	 *
	 * @since 4.0
	 */
	protected $isView = null;

	/**
	 * Params
	 *
	 * @var string
	 *
	 * @since 4.0
	 */
	public $params = null;
	/**
	 * Set the join id
	 *
	 * @param   int  $id  join id
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	public function setId($id)
	{
		$this->id = $id;
	}

	/**
	 * Get the join id
	 *
	 * @return  int  join id
	 *
	 * @since 4.0
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Set data
	 *
	 * @param   array  $data  to set to
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	public function setData($data)
	{
		$this->data = $data;
	}

	/**
	 * Get Join
	 *
	 * @return  FabrikTableJoin
	 *
	 * @since 4.0
	 */
	public function getJoin()
	{
		if (!isset($this->join))
		{
			$this->join = FabTable::getInstance(JoinTable::class);

			if (isset($this->data))
			{
				$this->join->bind($this->data);
			}
			else
			{
				$this->join->load($this->id);
			}

			$this->paramsType($this->join);
		}

		return $this->join;
	}

	/**
	 * When loading the join JTable ensure its params are set to be a JRegistry item
	 *
	 * @param   Table  $join  Join table
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	private function paramsType($join)
	{
		if (is_string($join->params))
		{
			$join->params = trim($join->params) == '' ? '{"type": ""}' : $join->params;
			$join->params = new Registry($join->params);
		}

		// Set a default join alias - normally overwritten in listModel::_makeJoinAliases();
		$join->table_join_alias = $join->table_join;
	}

	/**
	 * Clear the join
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	public function clearJoin()
	{
		unset($this->join);
	}

	/**
	 * Load the model from the element id
	 *
	 * @param   string  $key  Db table key
	 * @param   int     $id   Key value
	 *
	 * @return  FabTable  join
	 *
	 * @since 4.0
	 */
	public function getJoinFromKey($key, $id)
	{
		if (!isset($this->join))
		{
			$this->join = FabTable::getInstance(JoinTable::class);
			$this->join->load(array($key => $id));
			$this->paramsType($this->join);
		}

		return $this->join;
	}

	/**
	 * Get joined table's primary key
	 *
	 * @param   string  $glue  Between table and field name
	 *
	 * @return  string
	 *
	 * @since 4.0
	 */
	public function getForeignID($glue = '___')
	{
		$join = $this->getJoin();
		$pk = str_replace('`', '', $join->params->get('pk'));
		$pk = str_replace('.', $glue, $pk);

		return $pk;
	}

	/**
	 * Get the join foreign key
	 *
	 * @param   string  $glue  Between table and field name
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	public function getForeignKey($glue = '___')
	{
		$join = $this->getJoin();
		$fk = $join->table_join . $glue . $join->table_join_key;

		return $fk;
	}

	/**
	 * Get the join Primary key
	 *
	 * @param   string  $glue  Between table and field name
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	public function getPrimaryKey($glue = '___')
	{
		$join = $this->getJoin();
		$fk = $join->join_from_table . $glue . $join->table_key;

		return $fk;
	}

	/**
	 * Get joined to table primary key
	 *
	 * @param   string  $glue  Between table and field name
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	public function getJoinedToTablePk($glue = '___')
	{
		$join = $this->getJoin();

		return $join->join_from_table . $glue . $join->table_key;
	}

	/**
	 * Set the join element ID
	 *
	 * @param   int  $id  element id
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	public function setElementId($id)
	{
		$this->join->element_id = $id;
	}

	/**
	 * deletes the loaded join and then
	 * removes all elements, groups & form group record
	 *
	 * @param   int  $groupId  the group id that the join is linked to
	 *
	 * @return void/JError
	 *
	 * @since 4.0
	 */
	public function deleteAll($groupId)
	{
		$db = Worker::getDbo(true);
		$query = $db->getQuery(true);
		$query->delete(' #__{package}_elements')->where('group_id = ' . (int) $groupId);
		$db->setQuery($query);
		$db->execute();
		$query->clear();
		$query->delete(' #__{package}_groups')->where('id = ' . (int) $groupId);
		$db->setQuery($query);
		$db->execute();

		// Delete all form group records
		$query->clear();
		$query->delete(' #__{package}_formgroup')->where('group_id = ' . (int) $groupId);
		$db->setQuery($query);
		$db->execute();
		$this->getJoin()->delete();
	}

	/**
	 * saves the table join data
	 *
	 * @param   array  $source  data to save
	 *
	 * @return  bool
	 *
	 * @since 4.0
	 */
	public function save($source)
	{
		if (!$this->bind($source))
		{
			return false;
		}

		if (!$this->check())
		{
			return false;
		}

		if (!$this->store())
		{
			return false;
		}

		$this->_error = '';

		return true;
	}

	/**
	 * Tests if the table is in fact a view
	 * NOTE - not working yet, just committed so I can pull other changes
	 *
	 * @return  bool	true if table is a view
	 *
	 * @since 4.0
	 */
	public function isView()
	{
		$join = $this->getJoin();
		$params = $$join->params;
		$isView = $params->get('isview', null);

		if (!is_null($isView) && (int) $isView >= 0)
		{
			return $isView;
		}

		if (isset($this->isView))
		{
			return $this->isView;
		}

		$db = Worker::getDbo();
		$dbname = $join->table_join;
		$sql = " SELECT table_name, table_type, engine FROM INFORMATION_SCHEMA.tables " . "WHERE table_name = " . $db->quote($table->db_table_name)
		. " AND table_type = 'view' AND table_schema = " . $db->quote($dbname);
		$db->setQuery($sql);
		$row = $db->loadObjectList();
		$this->isView = empty($row) ? 0 : 1;

		// Store and save param for following tests
		$params->set('isview', $this->isView);
		$join->params = (string) $params;
		$this->save($join);

		return $this->isView;
	}
}
