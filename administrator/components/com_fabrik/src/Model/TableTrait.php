<?php
/**
 * Fabrik Admin Lists Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2018  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       4.0
 */

namespace Joomla\Component\Fabrik\Administrator\Model;


use Fabrik\Helpers\Worker;
use Joomla\Database\DatabaseDriver;

trait TableTrait
{
	/**
	 * Currently loaded list row
	 *
	 * @var array
	 *
	 * @since 4.0
	 */
	private $tables = array();

	/**
	 * MVCFactory::createTable is ugly AF due to requiring a string based $prefix for namespacing.
	 *
	 * @param string              $tableClass
	 * @param DatabaseDriver|null $db
	 *
	 * @return mixed
	 *
	 * @since 4.0
	 */
	public function getTable($tableClass, DatabaseDriver $db = null)
	{
		if (!array_key_exists($tableClass, $this->tables))
		{
			if (null === $db)
			{
				$db = Worker::getDbo(true);
			}

			$this->tables[$tableClass] = new $tableClass($db);
		}

		return $this->tables[$tableClass];
	}
}