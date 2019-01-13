<?php
/**
 * Log Fabrik Table
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Component\Fabrik\Administrator\Table;

// No direct access
use Joomla\Database\DatabaseDriver;

defined('_JEXEC') or die('Restricted access');

/**
 * Log Fabrik Table
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       4.0
 */
class LogTable extends FabrikTable
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
		parent::__construct('#__{package}_log', 'id', $db);
	}
}
