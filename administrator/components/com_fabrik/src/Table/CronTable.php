<?php
/**
 * Cron Fabrik Table
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Joomla\Component\Fabrik\Administrator\Table;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Database\DatabaseDriver;
use Joomla\Registry\Registry;

/**
 * Cron Fabrik Table
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       4.0
 */
class CronTable extends FabTable
{
	/**
	 * Constructor
	 *
	 * @param   DatabaseDriver  $db  database object
	 *
	 * @since 4.0
	 */
	public function __construct(DatabaseDriver $db)
	{
		parent::__construct('#__{package}_cron', 'id', $db);
	}

	/**
	 * Overloaded bind function
	 *
	 * @param   mixed  $src     An associative array or object to bind to the JTable instance.
	 * @param   mixed  $ignore  An optional array or space separated list of properties to ignore while binding.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since 4.0
	 */
	public function bind($src, $ignore = array())
	{
		if (isset($src['params']) && is_array($src['params']))
		{
			$registry = new Registry;
			$registry->loadArray($src['params']);
			$src['params'] = (string) $registry;
		}

		return parent::bind($src, $ignore);
	}

	/**
	 *
	 * @return bool
	 *
	 * @since version
	 */
	public function check()
	{
		if (!$this->lastrun)
		{
			$this->lastrun = $this->_db->getNullDate();
		}

		return parent::check();
	}
}
