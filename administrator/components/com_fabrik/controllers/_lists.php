<?php
/**
 * Fabrik Lists List Controller class
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 *
 * @deprecated - use main controller
 * @todo - refactor publish/delete into their own controllers.
 */

namespace Fabrik\Admin\Controllers;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\ArrayHelper;
/**
 * Lists controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.0
 */
class Lists extends \JControllerBase
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var    string
	 */
	protected $text_prefix = 'COM_FABRIK_LISTS';

	/**
	 * View item name
	 *
	 * @var string
	 */
	protected $view_item = 'lists';

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
		// Get the application
		$app = $this->getApplication();

		// Get the document object.
		$document = \JFactory::getDocument();

		$viewName   = $app->input->getWord('view', 'lists');
		$viewFormat = $document->getType();
		$layoutName = $app->input->getWord('layout', 'bootstrap');
		$app->input->set('view', $viewName);

		// Register the layout paths for the view
		$paths = new \SplPriorityQueue;
		$paths->insert(JPATH_COMPONENT . '/views/' . $viewName . '/tmpl', 'normal');

		$viewClass  = 'Fabrik\Admin\Views\\' . ucfirst($viewName) . '\\' . ucfirst($viewFormat);
		$modelClass = 'Fabrik\Admin\Models\\' . ucfirst($viewName);

		$view = new $viewClass(new $modelClass, $paths);
		$view->setLayout($layoutName);
		// Render our view.
		echo $view->render();

		return true;
	}



	/**
	 * Method to publish a list of items
	 *
	 * @return  null
	 */

	public function publish()
	{
		$input = $this->app->input;
		$cid   = $input->get('cid', array(), 'array');
		$data  = array('publish' => 1, 'unpublish' => 0, 'archive' => 2, 'trash' => -2, 'report' => -3);
		$task  = $this->getTask();
		$value = ArrayHelper::getValue($data, $task, 0, 'int');

		if (empty($cid))
		{
			$this->app->enqueueMessage(FText::_($this->text_prefix . '_NO_ITEM_SELECTED'), 'notice');
		}
		else
		{
			// Make sure the item ids are integers
			ArrayHelper::toInteger($cid);
			$model   = $this->getModel('Form', 'FabrikAdminModel');
			$formids = $model->swapListToFormIds($cid);

			// Publish the items.
			if (!$model->publish($formids, $value))
			{
				$this->app->enqueueMessage($model->getError(), 'error');
			}
			else
			{
				// Publish the groups
				$groupModel = $this->getModel('Group');

				if (is_object($groupModel))
				{
					$groupids = $groupModel->swapFormToGroupIds($formids);

					if (!empty($groupids))
					{
						if ($groupModel->publish($groupids, $value) === false)
						{
							$this->app->enqueueMessage($groupModel->getError(), 'error');
						}
						else
						{
							// Publish the elements
							$elementModel = $this->getModel('Element');
							$elementIds   = $elementModel->swapGroupToElementIds($groupids);

							if (!$elementModel->publish($elementIds, $value))
							{
								$this->app->enqueueMessage($elementModel->getError(), 'error');
							}
						}
					}
				}
				// Finally publish the list
				parent::publish();
			}
		}

		$this->setRedirect(JRoute::_('index.php?option=' . $this->option . '&view=' . $this->view_list, false));
	}

	/**
	 * Set up page asking about what to delete
	 *
	 * @return  null
	 */

	public function delete()
	{
		$listsModel = $this->getModel('lists');
		$viewType   = JFactory::getDocument()->getType();
		$view       = $this->getView($this->view_item, $viewType);
		$view->setLayout('confirmdelete');

		if ($model = $this->getModel())
		{
			$view->setModel($model, true);
			$view->setModel($listsModel);
		}
		// Used to load in the confirm form fields
		$view->setModel($this->getModel('list'));
		$view->display();
	}
}
