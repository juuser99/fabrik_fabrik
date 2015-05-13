<?php
/**
 * Fabrik Admin Lizt Controller
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.5
 */

namespace Fabrik\Admin\Controllers;

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Fabrik Lizt Controller
 *
 * @package  Fabrik
 * @since    3.5
 */
class Lizt extends Controller
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
		$layout = $this->input->get('layout');

		if ($layout !== 'view')
		{
			return parent::execute();
		}

		$model = new \Fabrik\Models\Lizt;
		$paths = new \SplPriorityQueue;
		$paths->insert(JPATH_SITE . '/components/com_fabrik/views/lizt/tmpl/bootstrap', 'normal');
		$view = new \Fabrik\Views\Lizt\Html($model, $paths);
		$view->setLayout('default');

		// Render our view.
		echo $view->render();
	}

}
