<?php
/**
 * Connection Fabrik Table
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Joomla\Component\Fabrik\Administrator\Table;

// No direct access
use Joomla\Database\DatabaseDriver;

defined('_JEXEC') or die('Restricted access');

/**
 * Connection Fabrik Table
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.0
 */
class ConnectionTable extends FabTable
{
	/**
	 * @var int
	 *
	 * @since 4.0
	 */
	public $id;

	/**
	 * @var int
	 *
	 * @since 4.0
	 */
	public $default = 0;

	/**
	 * @var string
	 *
	 * @since 4.0
	 */
	public $password = '';

	/**
	 * Constructor
	 *
	 * @param   DatabaseDriver  $db  database object
	 *
	 * @since 4.0
	 */
	public function __construct($db)
	{
		parent::__construct('#__fabrik_connections', 'id', $db);
	}
}
