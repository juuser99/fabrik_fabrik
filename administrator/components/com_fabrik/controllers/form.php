<?php
/**
 * Fabrik Admin Form Controller
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
 * Fabrik Form Controller
 *
 * @package  Fabrik
 * @since    3.5
 */
class Form extends Controller
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
		$layout = $this->input->get('layout', '');

		if ($layout !== '')
		{
			return parent::execute();
		}

		// Render the form itself
		$model = new \Fabrik\Admin\Models\Form;
		$paths = new \SplPriorityQueue;

		// FIXME - dont hardwire bootstrap template
		$paths->insert(JPATH_SITE . '/components/com_fabrik/views/form/tmpl/bootstrap', 'normal');

		// FIXME - what about other views than HTML?
		$view = new \Fabrik\Views\Form\Html($model, $paths);

		$view->setLayout('default');

		// Render our view.
		echo $view->render();
	}

}
