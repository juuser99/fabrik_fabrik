<?php
/**
 * Fabrik Admin Import Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Fabrik Admin Import Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.0
 */

class FabrikAdminModelImport extends FabModelAdmin
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var  string
	 */
	protected $text_prefix = 'COM_FABRIK_IMPORT';

	/**
	 * JTables to import
	 * @var JTables
	 */
	protected $tables = array();
}
