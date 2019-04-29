<?php
/**
 * Form Session Fabrik Table
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Component\Fabrik\Administrator\Table;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Component\Fabrik\Administrator\Table\FabrikTable;
use Joomla\Database\DatabaseDriver;

/**
 * Form Session Fabrik Table
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       4.0
 */
class FormSessionTable extends FabrikTable
{
	/**
	 * FormSessionTable constructor.
	 *
	 * @param DatabaseDriver $db
	 *
	 * @since 4.0
	 */
	public function __construct(DatabaseDriver $db)
	{
		parent::__construct('#__{package}_form_sessions', 'id', $db);
	}
}
