<?php
/**
 * Update the view's database to match the elements
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.5
 */

namespace Fabrik\Admin\Controllers;

use Fabrik\Admin\Models\Form as Form;
use Fabrik\Helpers\Text;

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Update the view's database to match the elements
 *
 * @package  Fabrik
 * @since    3.5
 */
class updateDatabase extends Controller
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
		$model = new Form;
		$model->set('id', $this->app->input->getString('id'));
		$model->updateDatabase();

		$this->app->enqueueMessage(Text::plural('COM_FABRIK_LIST_N_ITEMS_COPIED', $ids));
		$this->app->redirect($this->listUrl('list'));
	}
}
