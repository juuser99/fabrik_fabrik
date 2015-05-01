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

// No direct access
defined('_JEXEC') or die('Restricted access');

use \Joomla\Utilities\ArrayHelper;
/**
 * Connections list controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.5
 */
class Connections extends Controller
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var    string
	 */
	protected $text_prefix = 'COM_FABRIK_CONNECTIONS';

	/**
	 * View item name
	 *
	 * @var string
	 */
	protected $view_item = 'connections';

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
		$app = $this->getApplication();
		list($viewName, $layoutName) = $this->viewLayout();
		$modelClass = 'Fabrik\Admin\Models\\' . ucfirst($viewName);
		$model      = new $modelClass;

		$ids        = $app->input->get('cid', array(), 'array');
		$id         = $app->input->get('id', ArrayHelper::getValue($ids, 0));
		$listUrl    = $this->listUrl($viewName);

		switch ($layoutName)
		{
			case 'unsetDefault':
				$model->setDefault(false, $ids);
				$app->redirect($listUrl);
				break;

			case 'setDefault':
				$model->setDefault(true, $ids);
				$app->redirect($listUrl);
				break;

			case 'test':
				$model->set('id', $id);
				$model->test();
				$app->redirect($listUrl);
				break;
			default:
				parent::execute();
				break;
		}
	}


	/**
	 * Method to set the home property for a list of items
	 *
	 * @since    1.6
	 *
	 * @return null
	 */

	/*public function setDefault()
	{
		// Check for request forgeries
		JSession::checkToken() or die(FText::_('JINVALID_TOKEN'));
		$input = $this->app->input;

		// Get items to publish from the request.
		$cid = $input->get('cid', array(), 'array');
		$data = array('setDefault' => 1, 'unsetDefault' => 0);
		$task = $this->getTask();
		$value = ArrayHelper::getValue($data, $task, 0, 'int');

		if ($value == 0)
		{
			$this->setMessage(FText::_('COM_FABRIK_CONNECTION_CANT_UNSET_DEFAULT'));
		}

		if (empty($cid))
		{
			$this->app->enqueueMessage(FText::_($this->text_prefix . '_NO_ITEM_SELECTED'), 'notice');
		}
		else
		{
			if ($value != 0)
			{
				$cid = $cid[0];

				// Get the model.
				$model = $this->getModel();

				// Publish the items.
				if (!$model->setDefault($cid, $value))
				{
					$this->app->enqueueMessage($model->getError(), 'error');
				}
				else
				{
					$this->setMessage(FText::_('COM_FABRIK_CONNECTION_SET_DEFAULT'));
				}
			}
		}

		$this->setRedirect(JRoute::_('index.php?option=' . $this->option . '&view=' . $this->view_list, false));
	}*/
}
