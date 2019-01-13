<?php
/**
 * Elements list controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

namespace Fabrik\Component\Fabrik\Administrator\Controller;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Fabrik\Component\Fabrik\Administrator\Model\ElementModel;
use Fabrik\Component\Fabrik\Administrator\Model\ElementsModel;
use Fabrik\Component\Fabrik\Administrator\Model\FabModel;
use Fabrik\Component\Fabrik\Administrator\Model\ListModel;
use Joomla\Utilities\ArrayHelper;
use Fabrik\Helpers\ArrayHelper as FArrayHelper;

/**
 * Elements list controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       4.0
 */
class ElementsController extends AbstractAdminController
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var	string
	 *
	 * @since 4.0
	 */
	protected $text_prefix = 'COM_FABRIK_ELEMENTS';

	/**
	 * View item name
	 *
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $view_item = 'elements';

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see		JController
	 * @since	1.6
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);

		$this->registerTask('showInListView', 'toggleInList');
		$this->registerTask('hideFromListView', 'toggleInList');
	}

	/**
	 * Proxy for getModel.
	 *
	 * @param   string  $name    Model name
	 * @param   string  $prefix  Model prefix
	 * @param   array   $config  Model config
	 *
	 * @return  ElementModel|FabModel
	 *
	 * @since 4.0
	 */
	public function getModel($name = ElementModel::class, $prefix = '', $config = array())
	{
		$config = array();
		$config['ignore_request'] = true;

		$model = parent::getModel($name, $prefix, $config);

		return $model;
	}

	/**
	 * Set selected elements to be shown/not shown in list
	 *
	 * @return null
	 */
	public function toggleInList()
	{
		// Check for request forgeries
		Session::checkToken() or die(Text::_('JINVALID_TOKEN'));

		// Get items to publish from the request.
		$app = Factory::getApplication();
		$input = $app->input;
		$cid = $input->get('cid', array(), 'array');
		$data = array('showInListView' => 1, 'hideFromListView' => 0);
		$task = $this->getTask();
		$value = FArrayHelper::getValue($data, $task, 0, 'int');

		if (empty($cid))
		{
			Log::add(Text::_($this->text_prefix . '_NO_ITEM_SELECTED'), Log::WARNING, 'jerror');
		}
		else
		{
			// Get the model.
			$model = $this->getModel();

			// Make sure the item ids are integers
			$cid = ArrayHelper::toInteger($cid);

			// Publish the items.
			if (!$model->addToListView($cid, $value))
			{
				Log::add($model->getError(), Log::WARNING, 'jerror');
			}
			else
			{
				if ($value == 1)
				{
					$nText = $this->text_prefix . '_N_ITEMS_ADDED_TO_LIST_VIEW';
				}
				else
				{
					$nText = $this->text_prefix . '_N_ITEMS_REMOVED_FROM_LIST_VIEW';
				}

				$this->setMessage(Text::plural($nText, count($cid)));
			}
		}

		$this->setRedirect(Route::_('index.php?option=' . $this->option . '&view=' . $this->view_list, false));
	}

	/**
	 * Set up page asking about what to delete
	 *
	 * @since	1.6
	 *
	 * @return null
	 */
	public function delete()
	{
		$viewType = Factory::getDocument()->getType();
		$view = $this->getView($this->view_item, $viewType);
		$view->setLayout('confirmdelete');

		if ($model = $this->getModel(ElementsModel::class))
		{
			// Push the model into the view (as default)
			$view->setModel($model, true);
		}

		// Used to load in the confirm form fields
		$view->setModel($this->getModel(ListModel::class));
		$view->display();
	}

	/**
	 * Cancel delete element
	 *
	 * @return  null
	 *
	 * @since 4.0
	 */
	public function cancel()
	{
		$this->setRedirect('index.php?option=com_fabrik&view=elements');
	}

	/**
	 * Set up the page to ask for which group to copy the element to
	 *
	 * @return  null
	 *
	 * @since 4.0
	 */
	public function copySelectGroup()
	{
		Session::checkToken() or die('Invalid Token');
		$viewType = Factory::getDocument()->getType();
		$view = $this->getView($this->view_item, $viewType);
		$view->setLayout('copyselectgroup');

		if ($model = $this->getModel(ElementsModel::class))
		{
			$view->setModel($model, true);
		}

		// Used to load in the confirm form fields
		$view->setModel($this->getModel(ListModel::class));
		$view->display();
	}

	/**
	 * Batch process elements, setting acl levels
	 *
	 * @since   3.0.7
	 *
	 * @return  void
	 */
	public function batch()
	{
		Session::checkToken() or die('Invalid Token');
		$app = Factory::getApplication();
		$input = $app->input;
		$model = $this->getModel(ElementsModel::class);
		$cid = $input->get('cid', array(), 'array');
		$opts = $input->get('batch', array(), 'array');
		$model->batch($cid, $opts);
		$this->setRedirect('index.php?option=com_fabrik&view=elements', Text::_('COM_FABRIK_MSG_BATCH_DONE'));
	}

	/**
	 * Method to save the submitted ordering values for records via AJAX.
	 *
	 * @return	void
	 *
	 * @since   3.1rc1
	 */
	public function saveOrderAjax()
	{
		$pks = $this->input->post->get('cid', array(), 'array');
		$order = $this->input->post->get('order', array(), 'array');

		// Sanitize the input
		$pks = ArrayHelper::toInteger($pks);
		$order = ArrayHelper::toInteger($order);

		// Get the model
		$model = $this->getModel();

		// Save the ordering
		$return = $model->saveorder($pks, $order);

		if ($return)
		{
			echo "1";
		}

		// Close the application
		Factory::getApplication()->close();
	}

	/**
	 * Method to publish a list of items
	 *
	 * @return  null
	 *
	 * @since 4.0
	 */
	public function publish()
	{
		$app = Factory::getApplication();
		$input = $app->input;
		$cid = $input->get('cid', array(), 'array');
		$model = $this->getModel(ElementsModel::class);
		$task = $this->getTask();

		if ($task === 'unpublish')
		{
			$cid = $model->canUnpublish($cid);
			$input->set('cid', $cid);
		}

		if (empty($cid))
		{
			$this->setRedirect('index.php?option=com_fabrik&view=elements');
		}
		else
		{
			parent::publish();
		}
	}
}
