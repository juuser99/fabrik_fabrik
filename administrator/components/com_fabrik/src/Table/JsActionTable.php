<?php
/**
 * JS Action Fabrik table
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

/**
 * JS Action Fabrik table
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       4.0
 */
class JsActionTable extends FabrikTable
{
	/**
	 * JsActionTable constructor.
	 *
	 * @param DatabaseDriver $db
	 *
	 * @since 4.0
	 */
	public function __construct(DatabaseDriver $db)
	{
		parent::__construct('#__{package}_jsactions', 'id', $db);
	}
}
