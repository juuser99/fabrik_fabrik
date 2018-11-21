<?php
/**
 * Package Fabrik Table
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
 * Package Fabrik Table
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       4.0
 */
class PackageTable extends FabTable
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
		parent::__construct('#__fabrik_packages', 'id', $db);
	}
}
