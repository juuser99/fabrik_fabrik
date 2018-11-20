<?php
/**
 * Visualization Fabrik Table
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
use \Joomla\Registry\Registry;

/**
 * Visualization Fabrik Table
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       4.0
 */
class VisualizationTable extends FabTable
{
	/**
	 * Constructor
	 *
	 * @param   DatabaseDriver  &$db  database object
	 *
	 * @since 4.0
	 */
	public function __construct(DatabaseDriver $db)
	{
		parent::__construct('#__{package}_visualizations', 'id', $db);
	}

	/**
	 * Method to bind an associative array or object to the JTable instance.This
	 * method only binds properties that are publicly accessible and optionally
	 * takes an array of properties to ignore when binding.
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

		parent::bind($src, $ignore);

		return true;
	}
}
