<?php
/**
 * Fabrik Lists Confirm Copy View Controller
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.5
 */

namespace Fabrik\Admin\Controllers;

use Fabrik\Admin\Models\Lists as Lists;
use Fabrik\Admin\Views\Lists\Html as View;

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Set up confirm copy list view
 *
 * @package  Fabrik
 * @since    3.5
 */
class listConfirmCopy extends Controller
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
		$ids   = $this->app->input->get('cid', array(), 'array');
		$model = new Lists;
		$model->set('ids', $ids);

		// Register the layout paths for the view
		$paths = new \SplPriorityQueue;
		$paths->insert(JPATH_COMPONENT . '/views/lists/tmpl', 'normal');
		$view  = new View($model, $paths);
		$view->setLayout('confirm_copy');

		// Render our view.
		echo $view->render();
	}
}
