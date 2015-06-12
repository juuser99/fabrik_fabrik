<?php
/**
 * Fabrik From Controller
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Controllers;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Worker;
use \Fabrik\Admin\Models\FormInlineEdit;
use \JFactory;

/**
 * Fabrik From Controller
 *
 * @static
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.5
 */
class Form extends Controller
{
	/**
	 * Is the view rendered from the J content plugin
	 *
	 * @var  bool
	 */
	public $isMambot = false;

	/**
	 * Id used from content plugin when caching turned on to ensure correct element rendered
	 *
	 * @var  int
	 */
	public $cacheId = 0;

	/**
	 * Magic method to convert the object to a string gracefully.
	 *
	 * $$$ hugh - added 08/05/2012.  No idea what's going on, but I had to add this to stop
	 * the classname 'FabrikControllerForm' being output at the bottom of the form, when rendered
	 * through a Fabrik form module.  See:
	 *
	 * https://github.com/Fabrik/fabrik/issues/398
	 *
	 * @return  string  empty string.
	 */

	public function __toString()
	{
		return '';
	}

	/**
	 * Inline edit control
	 *
	 * @since   3.0b
	 *
	 * @return  null
	 */
	public function inlineedit()
	{
		$model = new FormInlineEdit;
		$model->render();
	}

	/**
	 * Display the view
	 *
	 * @return  JController  A JController object to support chaining.
	 */
	public function execute()
	{
		$input     = $this->input;
		$package   = $this->app->getUserState('com_fabrik.package', 'fabrik');
		$document  = JFactory::getDocument();
		$viewName  = $input->get('view', 'form');
		$layout    = $input->getWord('layout', 'default');
		$formId = $this->app->input->getString('formid');
		$viewFormat  = $document->getType();


		// Push a model into the view (may have been set in content plugin already)
		$model            = !isset($this->_model) ? new \Fabrik\Admin\Models\Form : $this->_model;
		$model->isMambot  = $this->isMambot;
		$model->packageId = $this->app->input->getInt('packageId');


		/*// Register the layout paths for the view
		$paths = new \SplPriorityQueue;
		$paths->insert(JPATH_COMPONENT . '/views/' . $viewName . '/tmpl', 'normal');

		// FIXME - dont hard wire bootstrap tmpl!
		$paths->insert(JPATH_SITE . '/components/com_fabrik/views/form/tmpl/bootstrap', 'normal');

		// Push a model into the view


		$view = new $viewClass($model, $paths);
		$view->setLayout($layout);
		$view->setModel($model, true);*/

		$viewClass  = 'Fabrik\Views\Form\\' . ucfirst($viewFormat);

		// Render the form itself
		$model = new \Fabrik\Admin\Models\Form;
		$model->setId($formId);

		$paths = new \SplPriorityQueue;

		// FIXME - dont hardwire bootstrap template
		$paths->insert(JPATH_SITE . '/components/com_fabrik/views/form/tmpl/bootstrap', 'normal');

		// FIXME - what about other views than HTML?
		$view = new $viewClass($model, $paths);

		$view->setLayout('default');

		// Render our view.
		echo $view->render();
		return;

		$view->isMambot = $this->isMambot;

		// Get data as it will be needed for ACL when testing if current row is editable.
		$model->getData();

		// Redirect plugin message if coming from content plugin - reloading in same page
		$model->applyMsgOnce();

		// $$$ hugh - added disable caching option, and no caching if not logged in (unless we can come up with a unique cacheid for guests)
		// NOTE - can't use IP of client, as could be two users behind same NAT'ing proxy / firewall.
		$listModel  = $model->getListModel();
		$listParams = $listModel->getParams();

		$user = JFactory::getUser();

		if ($user->get('id') == 0
			|| $listParams->get('list_disable_caching', '0') === '1'
			|| in_array($input->get('format'), array('raw', 'csv', 'pdf'))
		)
		{
			$view->display();
		}
		else
		{
			$uri     = JURI::getInstance();
			$uri     = $uri->toString(array('path', 'query'));
			$cacheId = serialize(array($uri, $input->post, $user->get('id'), get_class($view), 'display', $this->cacheId));
			$cache   = JFactory::getCache('com_' . $package, 'view');
			ob_start();
			$cache->get($view, 'display', $cacheId);
			$contents = ob_get_contents();
			ob_end_clean();

			// Workaround for token caching
			$token       = JSession::getFormToken();
			$search      = '#<input type="hidden" name="[0-9a-f]{32}" value="1" />#';
			$replacement = '<input type="hidden" name="' . $token . '" value="1" />';
			echo preg_replace($search, $replacement, $contents);
		}

		return $this;
	}

	/**
	 * Validate via ajax
	 *
	 * @return  null
	 */
	public function ajax_validate()
	{
		$input = $this->input;
		$model = $this->getModel('form', 'FabrikFEModel');
		$model->setId($input->getInt('formid', 0));
		$model->getForm();
		$model->setRowId($input->get('rowid', '', 'string'));
		$model->validate();
		$data = array('modified' => $model->modifiedValidationData);

		// Validating entire group when navigating form pages
		$data['errors'] = $model->errors;
		echo json_encode($data);
	}

	/**
	 * Save a form's page to the session table
	 *
	 * @return  null
	 */

	public function savepage()
	{
		$input     = $this->input;
		$model     = $this->getModel('Formsession', 'FabrikFEModel');
		$formModel = $this->getModel('Form', 'FabrikFEModel');
		$formModel->setId($input->getString('formid'));
		$model->savePage($formModel);
	}

	/**
	 * Clear down any temp db records or cookies
	 * containing partially filled in form data
	 *
	 * @return  null
	 */
	public function removeSession()
	{
		$input        = $this->input;
		$sessionModel = $this->getModel('formsession', 'FabrikFEModel');
		$sessionModel->setFormId($input->getInt('formid', 0));
		$sessionModel->setRowId($input->get('rowid', '', 'string'));
		$sessionModel->remove();
		$this->display();
	}

	/**
	 * Called via ajax to page through form records
	 *
	 * @return  null
	 */

	public function paginate()
	{
		$input = $this->input;
		$model = $this->getModel('Form', 'FabrikFEModel');
		$model->setId($input->getString('formid'));
		$model->paginateRowId($input->get('dir'));
		$this->display();
	}

	/**
	 * Delete a record from a form
	 *
	 * @return  null
	 */

	public function delete()
	{
		// Check for request forgeries
		JSession::checkToken() or die('Invalid Token');
		$input   = $this->input;
		$package = $this->app->getUserState('com_fabrik.package', 'fabrik');
		$model   = $this->getModel('list', 'FabrikFEModel');
		$ids     = array($input->get('rowid', 0));

		$listId     = $input->getString('listid');
		$limitStart = $input->getInt('limitstart' . $listId);
		$length     = $input->getInt('limit' . $listId);

		$oldTotal = $model->getTotalRecords();
		$model->setId($listId);
		$ok = $model->deleteRows($ids);

		$total = $oldTotal - count($ids);

		$ref = $input->get('fabrik_referrer', 'index.php?option=com_' . $package . '&view=list&listid=' . $listId, 'string');

		if ($total >= $limitStart)
		{
			$newLimitStart = $limitStart - $length;

			if ($newLimitStart < 0)
			{
				$newLimitStart = 0;
			}

			$ref     = str_replace("limitstart$listId=$limitStart", "limitstart$listId=$newLimitStart", $ref);
			$context = 'com_' . $package . '.list.' . $model->getRenderContext() . '.';
			$this->app->setUserState($context . 'limitstart', $newLimitStart);
		}

		if ($input->get('format') == 'raw')
		{
			$this->app->redirect('index.php?option=com_fabrik&view=list&listid=' . $listId . '&format=raw');
		}
		else
		{
			$msg = $ok ? count($ids) . ' ' . FText::_('COM_FABRIK_RECORDS_DELETED') : '';
			$this->app->enqueueMessage($msg);
			$this->app->redirect($ref);
		}
	}
}
