<?php
/**
 * Fabrik Lists List Controller class
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2018  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

namespace Joomla\Component\Fabrik\Administrator\Controller;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\Component\Fabrik\Administrator\Model\ElementModel;
use Joomla\Component\Fabrik\Administrator\Model\FormModel;
use Joomla\Component\Fabrik\Administrator\Model\ListModel;
use Joomla\Component\Users\Administrator\Model\GroupModel;
use Joomla\Utilities\ArrayHelper;

/**
 * Lists list controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       4.0
 */
class ListsController extends AbstractAdminController
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var    string
	 *
	 * @since 4.0
	 */
	protected $text_prefix = 'COM_FABRIK_LISTS';

	/**
	 * View item name
	 *
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $view_item = 'lists';

	/**
	 * Method to publish a list of items
	 *
	 * @since 4.0
	 */
	public function publish()
	{
		$input = $this->input;
		$cid   = $input->get('cid', array(), 'array');
		$data  = array('publish' => 1, 'unpublish' => 0, 'archive' => 2, 'trash' => -2, 'report' => -3);
		$task  = $this->getTask();
		$value = ArrayHelper::getValue($data, $task, 0, 'int');

		if (empty($cid))
		{
			$this->setMessage(Text::_($this->text_prefix . '_NO_ITEM_SELECTED'), 'error');
		}
		else
		{
			// Make sure the item ids are integers
			$cid     = ArrayHelper::toInteger($cid);
			$model   = $this->getModel(FormModel::class);
			$formIds = $model->swapListToFormIds($cid);

			// Publish the items.

			if (!$model->publish($formIds, $value))
			{
				$this->setMessage($model->getError(), 'error');
			}
			else
			{
				// Publish the groups
				$groupModel = $this->getModel(GroupModel::class);

				if (is_object($groupModel))
				{
					$groupIds = $groupModel->swapFormToGroupIds($formIds);

					if (!empty($groupIds))
					{
						if ($groupModel->publish($groupIds, $value) === false)
						{
							$this->setMessage($groupModel->getError(), 'error');
						}
						else
						{
							// Publish the elements
							$elementModel = $this->getModel(ElementModel::class);
							$elementIds   = $elementModel->swapGroupToElementIds($groupIds);

							if (!$elementModel->publish($elementIds, $value))
							{
								$this->setMessage($elementModel->getError(), 'error');
							}
						}
					}
				}
				// Finally publish the list
				parent::publish();
			}
		}

		$this->setRedirect(Route::_('index.php?option=' . $this->option . '&view=' . $this->view_list, false));
	}

	/**
	 * Set up page asking about what to delete
	 *
	 * @since 4.0
	 */
	public function delete()
	{
		$listsModel = $this->getModel();
		$viewType   = Factory::getDocument()->getType();
		$view       = $this->getView($this->view_item, $viewType);
		$view->setLayout('confirmdelete');

		if ($model = $this->getModel())
		{
			$view->setModel($model, true);
			$view->setModel($listsModel);
		}
		// Used to load in the confirm form fields
		$view->setModel($this->getModel(ListModel::class));
		$view->display();
	}
}
