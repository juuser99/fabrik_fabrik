<?php
/**
 * Form Group Fabrik Table
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
 * Form Group Fabrik Table
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       4.0
 */
class FormGroupTable extends FabrikTable
{
	/**
	 * Constructor
	 *
	 * @param   DatabaseDriver $db database object
	 *
	 * @since 4.0
	 */
	public function __construct(DatabaseDriver $db)
	{
		parent::__construct('#__{package}_formgroup', 'id', $db);
	}
}
