<?php
/**
 * Fabrik Admin Actually copy a list controller
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
use Fabrik\Helpers\Text;

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Remove element from list view
 *
 * @package  Fabrik
 * @since    3.5
 */
class listCopy extends Controller
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
		$names = $this->app->input->get('names', array(), 'array');
		$model = new Lists;
		$model->copy($ids, $names);

		$this->app->enqueueMessage(Text::plural('COM_FABRIK_LIST_N_ITEMS_COPIED', $ids));
		$this->app->redirect($this->listUrl('list'));
	}
}
