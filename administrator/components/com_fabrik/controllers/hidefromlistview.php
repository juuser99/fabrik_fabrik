<?php
/**
 * Fabrik Admin Element Hide from List View Controller
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.5
 */

namespace Fabrik\Admin\Controllers;

use Fabrik\Admin\Models\Elements as Elements;
use Fabrik\Helpers\Text;

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Remove element from list view
 *
 * @package  Fabrik
 * @since    3.5
 */
class hideFromListView extends Controller
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
		$ids = $this->app->input->get('cid', array(), 'array');
		$model = new Elements;

		$model->changeState($ids, 'show_in_list_summary', 0);
		$this->app->enqueueMessage(Text::plural('COM_FABRIK_ELEMENTS_N_ITEMS_REMOVED_FROM_LIST_VIEW', count($ids)));
		$this->app->redirect($this->listUrl('element'));
	}

}
