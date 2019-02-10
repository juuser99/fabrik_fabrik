<?php
/**
 * Fabrik Details Controller
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Component\Fabrik\Site\Controller;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Component\Fabrik\Site\Model\FormModel;
use Fabrik\Component\Fabrik\Site\Model\FormSessionModel;
use Fabrik\Component\Fabrik\Site\Model\ListModel;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\Utilities\ArrayHelper;
use Fabrik\Helpers\Html;
use Fabrik\Helpers\Worker;

/**
 * Fabrik Details Controller
 *
 * @static
 * @package     Joomla
 * @subpackage  Fabrik
 * @since      4.0
 */
class DetailsController extends AbstractSiteController
{
	/**
	 * Is the view rendered from the J content plugin
	 *
	 * @var  bool
	 *
	 * @since 4.0
	 */
	public $isMambot = false;

	/**
	 * Id used from content plugin when caching turned on to ensure correct element rendered)
	 *
	 * @var  int
	 *
	 * @since 4.0
	 */
	public $cacheId = 0;

	/**
	 * Display the view
	 *
	 * @param   boolean       $cachable  If true, the view output will be cached - NOTE not actually used to control caching!!!
	 * @param   array|boolean $urlparams An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
	 *
	 * @return  $this  A JController object to support chaining.
	 *
	 * @since 4.0
	 */
	public function display($cachable = false, $urlparams = false)
	{
		/** @var CMSApplication $app */
		$app      = Factory::getApplication();
		$session  = $app->getSession();
		$document = $app->getDocument();

		$package  = $app->getUserState('com_fabrik.package', 'fabrik');
		$input    = $app->input;
		$viewName = 'form';

		$viewType = $document->getType();

		if ($viewType == 'pdf')
		{
			// In PDF view only shown the main component content.
			$input->set('tmpl', 'component');
		}

		// Set the default view name from the Request
		$view = $this->getView($viewName, $viewType);

		// Push a model into the view
		/** @var FormModel $model */
		$model = !isset($this->_model) ? $this->getModel(FormModel::class) : $this->_model;

		// If errors made when submitting from a J plugin they are stored in the session lets get them back and insert them into the form model
		if (!empty($model->errors))
		{
			$context       = 'com_' . $package . '.form.' . $input->getInt('formid');
			$model->errors = $session->get($context . '.errors', array());
			$session->clear($context . '.errors');
		}

		$view->setModel($model, true);
		$view->isMambot = $this->isMambot;

		// Get data as it will be needed for ACL when testing if current row is editable.
		// $model->getData();

		// Display the view
		$view->error = $this->getError();

		// $$$ hugh - added disable caching option, and no caching if not logged in (unless we can come up with a unique cacheid for guests)
		// NOTE - can't use IP of client, as could be two users behind same NAT'ing proxy / firewall.
		$listModel  = $model->getListModel();
		$listParams = $listModel->getParams();

		$user = Factory::getUser();

		// don't cache for certain formats, or if used in social profile (CB, JomSocial)
		if (!Worker::useCache($listModel)
			|| $input->get('fabrik_social_profile_hash', '') !== ''
		)
		{
			$view->display();
		}
		else
		{
			$user    = Factory::getUser();
			$uri     = Uri::getInstance();
			$uri     = $uri->toString(array('path', 'query'));
			$cacheId = serialize(array($uri, $input->post, $user->get('id'), get_class($view), 'display', $this->cacheId));
			$cache   = Factory::getCache('com_' . $package, 'view');
			Html::addToSessionCacheIds($cacheId);
			echo $cache->get($view, 'display', $cacheId);
		}
	}

	/**
	 * Process the form
	 *
	 * @return  null
	 *
	 * @since 4.0
	 */
	public function process()
	{
		@set_time_limit(300);
		/** @var CMSApplication $app */
		$app      = Factory::getApplication();
		$session  = $app->getSession();
		$package  = $app->getUserState('com_fabrik.package', 'fabrik');
		$input    = $app->input;
		$document = $app->getDocument();
		$viewName = $input->get('view', 'form');
		$viewType = $document->getType();
		$view     = $this->getView($viewName, $viewType);

		/** @var FormModel $model */
		if ($model = $this->getModel(FormModel::class))
		{
			$view->setModel($model, true);
		}

		$model->setId($input->getInt('formid', 0));

		$this->isMambot = $input->get('isMambot', 0);
		$model->getForm();
		$model->setRowId($input->get('rowid', '', 'string'));

		// Check for request forgeries
		$fbConfig = ComponentHelper::getParams('com_fabrik');

		if ($model->getParams()->get('spoof_check', $fbConfig->get('spoofcheck_on_formsubmission', true)) == true)
		{
			Session::checkToken() or die('Invalid Token');
		}

		if ($input->getBool('fabrik_ignorevalidation', false) != true)
		{
			// Put in when saving page of form
			if (!$model->validate())
			{
				// If its in a module with ajax or in a package
				if ($input->getInt('packageId') !== 0)
				{
					$data = array('modified' => $model->modifiedValidationData);

					// Validating entire group when navigating form pages
					$data['errors'] = $model->errors;
					echo json_encode($data);

					return;
				}

				if ($this->isMambot)
				{
					// Store errors in session
					$context = 'com_' . $package . '.form.' . $model->get('id') . '.';
					$session->set($context . 'errors', $model->errors);
					/**
					 * $$$ hugh - testing way of preserving form values after validation fails with form plugin
					 * might as well use the 'savepage' mechanism, as it's already there!
					 */
					$session->set($context . 'session.on', true);
					$this->savepage();
					$this->makeRedirect($model, '');
				}
				else
				{
					/**
					 * $$$ rob - http://fabrikar.com/forums/showthread.php?t=17962
					 * couldn't determine the exact set up that triggered this, but we need to reset the rowid to -1
					 * if reshowing the form, otherwise it may not be editable, but rather show as a detailed view
					 */
					if ($input->get('usekey', '') !== '')
					{
						$input->set('rowid', -1);
					}

					$view->display();
				}

				return;
			}
		}

		// Reset errors as validate() now returns ok validations as empty arrays
		$model->errors = array();
		$model->process();

		// Check if any plugin has created a new validation error
		if (!empty($model->errors))
		{
			Worker::getPluginManager()->runPlugins('onError', $model);
			$view->display();

			return;
		}

		/**
		 * $$$ rob 31/01/2011
		 * Now redirect always occurs even with redirect thx message, $this->setRedirect
		 * will look up any redirect url specified in the session by a plugin and use that or
		 * fall back to the url defined in $this->makeRedirect()
		 */

		$listModel = $model->getListModel();
		$listModel->set('_table', null);

		$msg = $model->getSuccessMsg();

		if ($input->getInt('packageId') !== 0)
		{
			echo json_encode(array('msg' => $msg));

			return;
		}

		if ($input->get('format') == 'raw')
		{
			$input->set('view', 'list');
			$this->display();

			return;
		}
		else
		{
			$this->makeRedirect($model, $msg);
		}
	}

	/**
	 * Set the redirect url
	 *
	 * @param   string $url  Default url
	 * @param   string $msg  Optional message to apply on redirect
	 * @param   string $type Optional message type
	 *
	 * @return  null
	 *
	 * @since 4.0
	 */
	public function setRedirect($url, $msg = null, $type = 'message')
	{
		$app      = Factory::getApplication();
		$session  = $app->getSession();
		$package  = $app->getUserState('com_fabrik.package', 'fabrik');
		$formData = $session->get('com_' . $package . '.form.data');
		$context  = 'com_' . $package . '.form.' . $formData['fabrik'] . '.redirect.';

		// If the redirect plug-in has set a url use that in preference to the default url
		$sUrl = $session->get($context . 'url', array($url));

		if (!is_array($sUrl))
		{
			$sUrl = array($sUrl);
		}

		if (empty($sUrl))
		{
			$sUrl[] = $url;
		}

		$sMsg = $session->get($context . 'msg', array($msg));

		if (!is_array($sMsg))
		{
			$sMsg = array($sMsg);
		}

		if (empty($sMsg))
		{
			$sMsg[] = $msg;
		}

		$url = array_shift($sUrl);
		$msg = array_shift($sMsg);

		$q     = $app->getMessageQueue();
		$found = false;

		foreach ($q as $m)
		{
			// Custom message already queued - unset default msg
			if ($m['type'] == 'message' && trim($m['message']) !== '')
			{
				$found = true;
				break;
			}
		}

		if ($found)
		{
			$msg = null;
		}

		$session->set($context . 'url', $sUrl);
		$session->set($context . 'msg', $sMsg);
		$showMsg = array_shift($session->get($context . 'showsystemmsg', array(true)));
		$msg     = $showMsg ? $msg : null;
		parent::setRedirect($url, $msg, $type);
	}

	/**
	 * Generic function to redirect
	 *
	 * @param   FormModel $model Form model
	 * @param   string    $msg   Redirection message to show
	 *
	 * @return  null
	 *
	 * @since 4.0
	 */
	protected function makeRedirect(FormModel $model, $msg = null)
	{
		/** @var CMSApplication $app */
		$app     = Factory::getApplication();
		$package = $app->getUserState('com_fabrik.package', 'fabrik');
		$input   = $app->input;
		$formId  = $input->getInt('formid');
		$listId  = $input->getInt('listid');
		$rowId   = $input->getString('rowid');

		if (is_null($msg))
		{
			$msg = FText::_('COM_FABRIK_RECORD_ADDED_UPDATED');
		}

		if ($app->isAdmin())
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
					$url = filter_var(ArrayHelper::getValue($_SERVER, 'HTTP_REFERER', 'index.php'), FILTER_SANITIZE_URL);
				}
				else
				{
					// Return to the page that called the form
					$url = urldecode($input->post->get('fabrik_referrer', 'index.php', 'string'));
				}

				$itemId = Worker::itemId();

				if ($url == '')
				{
					$url = 'index.php?option=com_' . $package . '&Itemid=' . $itemId;
				}
			}

			$config = $app->getConfig();

			if ($config->get('sef'))
			{
				$url = JRoute::_($url);
			}

			$this->setRedirect($url, $msg);
		}
	}

	/**
	 * Validate via ajax
	 *
	 * @return  null
	 *
	 * @since 4.0
	 */
	public function ajax_validate()
	{
		/** @var CMSApplication $app */
		$app   = Factory::getApplication();
		$input = $app->input;
		/** @var FormModel $model */
		$model = $this->getModel(FormModel::class);
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
	 *
	 * @since 4.0
	 */
	public function savepage()
	{
		$app   = Factory::getApplication();
		$input = $app->input;
		/** @var FormSessionModel $model */
		$model = $this->getModel(FormSessionModel::class);
		/** @var FormModel $formModel */
		$formModel = $this->getModel(FormModel::class);
		$formModel->setId($input->getInt('formid'));
		$model->savePage($formModel);
	}

	/**
	 * Clear down any temp db records or cookies
	 * containing partially filled in form data
	 *
	 * @return  null
	 *
	 * @since 4.0
	 */
	public function removeSession()
	{
		$app   = Factory::getApplication();
		$input = $app->input;
		/** @var FormSessionModel $sessionModel */
		$sessionModel = $this->getModel(FormSessionModel::class);
		$sessionModel->setFormId($input->getInt('formid', 0));
		$sessionModel->setRowId($input->get('rowid', '', 'string'));
		$sessionModel->remove();
		$this->display();
	}

	/**
	 * Called via ajax to page through form records
	 *
	 * @return  null
	 *
	 * @since 4.0
	 */
	public function paginate()
	{
		$app   = Factory::getApplication();
		$input = $app->input;
		/** @var FormModel $model */
		$model = $this->getModel(FormModel::class);
		$model->setId($input->getInt('formid'));
		$model->paginateRowId($input->get('dir'));
		$this->display();
	}

	/**
	 * Delete a record from a form
	 *
	 * @return  null
	 *
	 * @since 4.0
	 */
	public function delete()
	{
		// Check for request forgeries
		Session::checkToken() or die('Invalid Token');
		$app     = Factory::getApplication();
		$package = $app->getUserState('com_fabrik.package', 'fabrik');
		$input   = $app->input;
		/** @var ListModel $model */
		$model = $this->getModel(ListModel::class);
		$ids   = array($input->get('rowid', 0, 'string'));

		$listId     = $input->getInt('listid');
		$limitStart = $input->getInt('limitstart' . $listId);
		$length     = $input->getInt('limit' . $listId);

		$oldTotal = $model->getTotalRecords();
		$model->setId($listId);
		$model->deleteRows($ids);

		$total = $oldTotal - count($ids);

		$ref = $input->get('fabrik_referrer', "index.php?option=com_' . $package . '&view=table&listid=$listId", 'string');

		if ($total >= $limitStart)
		{
			$newLimitStart = $limitStart - $length;

			if ($newLimitStart < 0)
			{
				$newLimitStart = 0;
			}

			$ref     = str_replace("limitstart$listId=$limitStart", "limitstart$listId=$newLimitStart", $ref);
			$app     = Factory::getApplication();
			$context = 'com_' . $package . '.list.' . $model->getRenderContext() . '.';
			$app->setUserState($context . 'limitstart', $newLimitStart);
		}

		if ($input->get('format') == 'raw')
		{
			$input->set('view', 'list');
			$this->display();
		}
		else
		{
			// @TODO: test this
			$app->enqueueMessage(count($ids) . " " . FText::_('COM_FABRIK_RECORDS_DELETED'));
			$app->redirect($ref);
		}
	}
}
