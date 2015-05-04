<?php
/**
 * Connections controller class
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

namespace Fabrik\Admin\Controllers;

use Fabrik\Admin\Models\Lists as Lists;

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Delete meta data and drop db tables controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.5
 */
class Deletedrop extends Controller
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
		$app   = $this->getApplication();
		$model = new Lists;
		$ids   = $app->input->get('cid', array(), 'array');
		$model->delete($ids);
		$model->drop($ids);
	}
}
