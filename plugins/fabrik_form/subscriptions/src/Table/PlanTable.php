<?php
/**
 *  JTable For Subscriptions Plans
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.subscriptions
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Plugin\FabrikForm\Subscriptions\Table;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Component\Fabrik\Administrator\Table\FabTable;
use Joomla\Database\DatabaseDriver;

/**
 *  JTable For Subscriptions Plans
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.subscriptions
 * @since       4.0
 */
class PlanTable extends FabTable
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
		parent::__construct('#__fabrik_subs_plans', 'id', $db);
	}
}
