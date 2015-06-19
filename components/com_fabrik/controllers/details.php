<?php
/**
 * Fabrik Details Controller
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Utilities\ArrayHelper;
use Fabrik\Helpers\Worker;
use Fabrik\Admin\Models\Form;
use Fabrik\Admin\Models\FormSession;
use Fabrik\Admin\Models\Lizt;
use Fabrik\Helpers\Text;

/**
 * Fabrik Details Controller
 *
 * @static
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       1.5
 */

class FabrikControllerDetails extends FabrikController
{
	/**
	 * Is the view rendered from the J content plugin
	 *
	 * @var  bool
	 */
	public $isMambot = false;

	/**
	 * Id used from content plugin when caching turned on to ensure correct element rendered)
	 *
	 * @var  int
	 */
	public $cacheId = 0;

	/**
	 * Display the view
	 *
	 * @param   boolean  $cachable   If true, the view output will be cached - NOTE not actually used to control caching!!!
	 * @param   array    $urlparams  An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
	 *
	 * @return  JController  A JController object to support chaining.
	 */

	public function display($cachable = false, $urlparams = false)
	{
		$session = JFactory::getSession();
		$document = JFactory::getDocument();
		$package = $this->app->getUserState('com_fabrik.package', 'fabrik');
		$viewName = 'form';
		$modelName = 'form';

		$viewType = $document->getType();

		if ($viewType == 'pdf')
		{
			// In PDF view only shown the main component content.
			$this->input->set('tmpl', 'component');
		}

		// Set the default view name from the Request
		$view = $this->getView($viewName, $viewType);

		// Push a model into the view
		$model = !isset($this->_model) ? new Form : $this->_model;

		// If errors made when submitting from a J plugin they are stored in the session lets get them back and insert them into the form model
		if (!empty($model->errors))
		{
			$context = 'com_' . $package . '.form.' . $this->input->getString('formid');
			$model->errors = $session->get($context . '.errors', array());
			$session->clear($context . '.errors');
		}

		$view->setModel($model, true);
		$view->isMambot = $this->isMambot;

		// Get data as it will be needed for ACL when testing if current row is editable.
		// $model->getData();

		// Display the view
		$view->error = $this->getError();

		if (in_array($this->input->get('format'), array('raw', 'csv', 'pdf')))
		{
			$view->display();
		}
		else
		{
			$user = JFactory::getUser();
			$uri = JURI::getInstance();
			$uri = $uri->toString(array('path', 'query'));
			$cacheid = serialize(array($uri, $this->input->post, $user->get('id'), get_class($view), 'display', $this->cacheId));
			$cache = JFactory::getCache('com_' . $package, 'view');
			echo $cache->get($view, 'display', $cacheid);
		}
	}

	/**
	 * generic function to redirect
	 *
	 * @param   object  &$model  form model
	 * @param   string  $msg     redirection message to show
	 *
	 * @return  null
	 */

	protected function makeRedirect(&$model, $msg = null)
	{
		$package = $this->app->getUserState('com_fabrik.package', 'fabrik');
		$input = $this->input;
		$formId = $input->getString('formid');
		$listId = $input->getString('listid');
		$rowId = $input->getString('rowid');

		if (is_null($msg))
		{
			$msg = Text::_('COM_FABRIK_RECORD_ADDED_UPDATED');
		}

		if ($this->app->isAdmin())
		{
			// Admin links use com_fabrik over package option
			if (array_key_exists('apply', $model->formData))
			{
				$url = 'index.php?option=com_fabrik&c=form&task=form&formid=' . $formId . '&listid=' . $listId . '&rowid=' . $rowId;
			}
			else
			{
				$url = 'index.php?option=com_fabrik&c=table&task=viewTable&cid[]=' . $model->getTable()->id;
			}

			$this->setRedirect($url, $msg);
		}
		else
		{
			if (array_key_exists('apply', $model->formData))
			{
				$url = 'index.php?option=com_' . $package . '&c=form&view=form&formid=' . $formId . '&rowid=' . $rowId . '&listid=' . $listId;
			}
			else
			{
				if ($this->isMambot)
				{
					// Return to the same page
					$url = ArrayHelper::getvalue($_SERVER, 'HTTP_REFERER', 'index.php');
				}
				else
				{
					// Return to the page that called the form
					$url = urldecode($input->post->get('fabrik_referrer', 'index.php', 'string'));
				}

				$Itemid = Worker::itemId();

				if ($url == '')
				{
					$url = 'index.php?option=com_' . $package . '&Itemid=' . $Itemid;
				}
			}

			$config = JFactory::getConfig();

			if ($config->get('sef'))
			{
				$url = JRoute::_($url);
			}

			$this->setRedirect($url, $msg);
		}
	}

	/**
	 * validate via ajax
	 *
	 * @return  null
	 */

	public function ajax_validate()
	{
		$input = $this->input;
		$model = new Form;
		$model->setId($input->getInt('formid', 0));
		$model->getForm();
		$model->setRowId($input->get('rowid', '', 'string'));
		$model->validateForm();
		$data = array('modified' => $model->modifiedValidationData);

		// Validating entire group when navigating form pages
		$data['errors'] = $model->errors;
		echo json_encode($data);
	}

	/**
	 * save a form's page to the session table
	 *
	 * @return  null
	 */

	public function savepage()
	{
		$input = $this->input;
		$model = new FormSession;
		$formModel = new Form;
		$formModel->setId($input->getString('formid'));
		$model->savePage($formModel);
	}

	/**
	 * clear down any temp db records or cookies
	 * containing partially filled in form data
	 *
	 * @return  null
	 */

	public function removeSession()
	{
		$sessionModel = new FormSession;
		$sessionModel->setFormId($this->input->getInt('formid', 0));
		$sessionModel->setRowId($this->input->get('rowid', '', 'string'));
		$sessionModel->remove();
		$this->display();
	}

	/**
	 * called via ajax to page through form records
	 *
	 * @return  null
	 */
	public function paginate()
	{
		$model = new Form;
		$model->setId($this->input->getString('formid'));
		$model->paginateRowId($this->input->get('dir'));
		$this->display();
	}

	/**
	 * delete a record from a form
	 *
	 * @return  null
	 */
	public function delete()
	{
		// Check for request forgeries
		JSession::checkToken() or die('Invalid Token');
		$package = $this->app->getUserState('com_fabrik.package', 'fabrik');
		$model = new Lizt;
		$ids = array($this->input->get('rowid', 0, 'string'));

		$listId = $this->input->getString('listid');
		$limitstart = $this->input->getInt('limitstart' . $listId);
		$length = $this->input->getInt('limit' . $listId);

		$oldtotal = $model->getTotalRecords();
		$model->setId($listId);
		$model->deleteRows($ids);

		$total = $oldtotal - count($ids);

		$ref = $this->input->get('fabrik_referrer', "index.php?option=com_' . $package . '&view=table&listid=$listId", 'string');

		if ($total >= $limitstart)
		{
			$newlimitstart = $limitstart - $length;

			if ($newlimitstart < 0)
			{
				$newlimitstart = 0;
			}

			$ref = str_replace("limitstart$listId=$limitstart", "limitstart$listId=$newlimitstart", $ref);
			$context = 'com_' . $package . '.list.' . $model->getRenderContext() . '.';
			$this->app->setUserState($context . 'limitstart', $newlimitstart);
		}

		if ($this->input->get('format') == 'raw')
		{
			$this->input->set('view', 'list');
			$this->display();
		}
		else
		{
			// @TODO: test this
			$this->app->enqueueMessage(count($ids) . " " . Text::_('COM_FABRIK_RECORDS_DELETED'));
			$this->app->redirect($ref);
		}
	}
}
