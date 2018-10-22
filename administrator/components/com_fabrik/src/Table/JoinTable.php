<?php
/**
 * Join Fabrik Table
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2018  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Joomla\Component\Fabrik\Administrator\Table;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Database\DatabaseDriver;

/**
 * Join Fabrik Table
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       4.0
 */
class JoinTable extends FabTable
{
	public $params = '';

	public $table_join = '';

	public $table_join_alias = '';

	/**
	 * Construct
	 *
	 * @param   DatabaseDriver  $db  database object
	 *
	 * @since 4.0
	 */
	public function __construct($db)
	{
		parent::__construct('#__{package}_joins', 'id', $db);
	}

	/**
	 * Method to store a row in the database from the JTable instance properties.
	 * If a primary key value is set the row with that primary key value will be
	 * updated with the instance property values.  If no primary key value is set
	 * a new row will be inserted into the database with the properties from the
	 * JTable instance.
	 *
	 * @param   boolean  $updateNulls  True to update fields even if they are null.
	 *
	 * @return  boolean  True on success.
	 *
	 * @link    http://docs.joomla.org/JTable/store
	 *
	 * @since   4.0
	 */
	public function store($updateNulls = false)
	{
		unset($this->table_join_alias);

		parent::store($updateNulls);
	}
}
