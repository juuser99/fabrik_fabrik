<?php
/**
 * Fabrik Admin Plugin Controller
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

namespace Fabrik\Admin\Controllers;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\String;
use \JPluginHelper as JPluginHelper;
use \JEventDispatcher as JEventDispatcher;

/**
 * Fabrik Admin Plugin Controller
 *
 * @package  Fabrik
 * @since    3.5
 */
class PluginAjax extends Controller
{
	/**
	 * Execute the controller.
	 *
	 * @return  boolean  True if controller finished execution, false if the controller did not
	 *                   finish execution. A controller might return false if some precondition for
	 *                   the controller to run has not been satisfied.
	 *
	 * @since   12.1
	 * @throws  LogicException
	 * @throws  RuntimeException
	 */
	public function execute()
	{
		$input  = $this->input;
		$plugin = $input->get('plugin', '');
		$method = $input->get('method', '');
		$group  = $input->get('g', 'element');

		if (!JPluginHelper::importPlugin('fabrik_' . $group, $plugin))
		{
			$o      = new stdClass;
			$o->err = 'unable to import plugin fabrik_' . $group . ' ' . $plugin;
			echo json_encode($o);

			return;
		}

		if (substr($method, 0, 2) !== 'on')
		{
			$method = 'on' . String::ucfirst($method);
		}

		$dispatcher = JEventDispatcher::getInstance();
		$dispatcher->trigger($method);

		return;
	}

}
