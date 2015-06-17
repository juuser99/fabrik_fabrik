<?php
/**
 * Process Form data controller
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Controllers;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Admin\Models\FormSession;
use Fabrik\Helpers\Worker;
use \Fabrik\Admin\Models\FormInlineEdit;
use \JFactory;
use \JProfiler;
use \JSession;
use \Fabrik\Admin\Models\Form;
use \Fabrik\Helpers\HTML;


/**
 * Process Form data controller
 *
 * @static
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.5
 */
class Process extends Controller
{
	/**
	 * Process the form
	 * Inline edit save routed here (not in raw)
	 *
	 * @return  null
	 */
	public function execute()
	{
		$profiler = JProfiler::getInstance('Application');
		$session  = JFactory::getSession();
		$document  = JFactory::getDocument();
		$viewFormat  = $document->getType();
		JDEBUG ? $profiler->mark('controller process: start') : null;
		$input = $this->app->input;

		if ($input->get('format', '') == 'raw')
		{
			error_reporting(error_reporting() ^ (E_WARNING | E_NOTICE));
		}

		$viewName = $input->get('view', 'form');
		$viewClass  = 'Fabrik\Views\Form\\' . ucfirst($viewFormat);

		$paths = new \SplPriorityQueue;

		// FIXME - dont hardwire bootstrap template
		$paths->insert(JPATH_SITE . '/components/com_fabrik/views/form/tmpl/bootstrap', 'normal');

		$model = new Form;
		$model->setId($input->getString('formid', 0));
		$model->packageId = $input->getInt('packageId');
		$this->isMambot   = $input->get('isMambot', 0);
		$model->rowId     = $input->get('rowid', '', 'string');

		$view = new $viewClass($model, $paths);

		/**
		 * $$$ hugh - need this in plugin manager to be able to treat a "Copy" form submission
		 * as 'new' for purposes of running plugins.  Rob's comment in model process() seems to
		 * indicate that origRowId was for this purposes, but it doesn't work, 'cos always has a value.
		 */
		if ($input->get('Copy', 'no') !== 'no')
		{
			$model->copyingRow(true);
		}

		// Check for request forgeries
		if ($model->spoofCheck())
		{
			JSession::checkToken() or die('Invalid Token');
		}

		JDEBUG ? $profiler->mark('controller process validate: start') : null;

		if (!$model->validateForm())
		{
			$this->handleError($view, $model);

			return;
		}

		JDEBUG ? $profiler->mark('controller process validate: end') : null;

		// Reset errors as validate() now returns ok validations as empty arrays
		$model->clearErrors();

		try
		{
			$model->process();
		} catch (Exception $e)
		{
			$model->errors['process_error'] = true;
			$this->app->enqueueMessage($e->getMessage(), 'error');
		}

		if ($input->getInt('elid', 0) !== 0)
		{
			// Inline edit show the edited element - ignores validations for now
			$inlineModel = new FormInlineEdit;
			$inlineModel->setFormModel($model);
			echo $inlineModel->showResults();

			return;
		}

		// Check if any plugin has created a new validation error
		if ($model->hasErrors())
		{
			Worker::getPluginManager()->runPlugins('onError', $model);
			$this->handleError($view, $model);

			return;
		}

		/**
		 * If debug submit is requested (&fabrikdebug=2, and J! debug on, and Fabrik debug allowed),
		 * bypass any and all redirects, so we can see the profile for the submit
		 */

		if (HTML::isDebugSubmit())
		{
			return;
		}

		$listModel = $model->getListModel();
		$listModel->set('_table', null);

		$url = $model->getRedirectURL(true, $this->isMambot);
		$msg = $model->getRedirectMessage();

		// @todo -should get handed off to the json view to do this
		if ($input->getInt('fabrik_ajax') == 1)
		{
			// $$$ hugh - adding some options for what to do with redirect when in content plugin
			// Should probably do this elsewhere, but for now ...
			$redirect_opts = array(
				'msg' => $msg,
				'url' => $url,
				'baseRedirect' => $this->baseRedirect,
				'rowid' => $input->get('rowid', '', 'string'),
				'suppressMsg' => !$model->showSuccessMsg()
			);

			if (!$this->baseRedirect && $this->isMambot)
			{
				$session                       = JFactory::getSession();
				$context                       = $model->getRedirectContext();
				$redirect_opts['redirect_how'] = $session->get($context . 'redirect_content_how', 'popup');
				$redirect_opts['width']        = (int) $session->get($context . 'redirect_content_popup_width', '300');
				$redirect_opts['height']       = (int) $session->get($context . 'redirect_content_popup_height', '300');
				$redirect_opts['x_offset']     = (int) $session->get($context . 'redirect_content_popup_x_offset', '0');
				$redirect_opts['y_offset']     = (int) $session->get($context . 'redirect_content_popup_y_offset', '0');
				$redirect_opts['title']        = $session->get($context . 'redirect_content_popup_title', '');
				$redirect_opts['reset_form']   = $session->get($context . 'redirect_content_reset_form', '1') == '1';
			}
			elseif (!$this->baseRedirect && !$this->isMambot)
			{
				/**
				 * $$$ hugh - I think this case only happens when we're a popup form from a list
				 * in which case I don't think "popup" is realy a valid option.  Anyway, need to set something,
				 * so for now just do the same as we do for isMambot, but default redirect_how to 'samepage'
				 */
				$session                       = JFactory::getSession();
				$context                       = $model->getRedirectContext();
				$redirect_opts['redirect_how'] = $session->get($context . 'redirect_content_how', 'samepage');
				$redirect_opts['width']        = (int) $session->get($context . 'redirect_content_popup_width', '300');
				$redirect_opts['height']       = (int) $session->get($context . 'redirect_content_popup_height', '300');
				$redirect_opts['x_offset']     = (int) $session->get($context . 'redirect_content_popup_x_offset', '0');
				$redirect_opts['y_offset']     = (int) $session->get($context . 'redirect_content_popup_y_offset', '0');
				$redirect_opts['title']        = $session->get($context . 'redirect_content_popup_title', '');
				$redirect_opts['reset_form']   = $session->get($context . 'redirect_content_reset_form', '1') == '1';

			}
			elseif ($this->isMambot)
			{
				// $$$ hugh - special case to allow custom code to specify that
				// the form should not be cleared after a failed AJAX submit
				$context                     = 'com_fabrik.form.' . $model->get('id') . '.redirect.';
				$redirect_opts['reset_form'] = $session->get($context . 'redirect_content_reset_form', '1') == '1';
			}
			// Let form.js handle the redirect logic
			echo json_encode($redirect_opts);

			// Stop require.js being added to output
			exit;
		}

		if ($input->get('format') == 'raw')
		{
			$input->set('view', 'list');
			$this->display();

			return;
		}
		else
		{

			// If no msg, set to null, so J! doesn't create an empty "Message" area
			if (empty($msg))
			{
				$msg = null;
			}

			$this->setRedirect($url, $msg);
		}
	}

	/**
	 * Handle the view error
	 *
	 * @param   JView  $view  View
	 * @param   \Fabrik\Admin\Models\Form $model Form Model
	 *
	 * @since   3.1b
	 *
	 * @return  void
	 */
	protected function handleError($view, $model)
	{
		$input     = $this->input;
		$validated = false;

		// If its in a module with ajax or in a package or inline edit
		if ($input->get('fabrik_ajax'))
		{
			if ($input->getInt('elid', 0) !== 0)
			{
				// Inline edit
				$messages = array();
				$errs  = $model->getErrors();

				// Only raise errors for fields that are present in the inline edit plugin
				$toValidate = array_keys($input->get('toValidate', array(), 'array'));

				foreach ($errs as $errorKey => $e)
				{
					if (in_array($errorKey, $toValidate) && count($e[0]) > 0)
					{
						array_walk_recursive($e, array('FabrikString', 'forHtml'));
						$messages[] = count($e[0]) === 1 ? '<li>' . $e[0][0] . '</li>' : '<ul><li>' . implode('</li><li>', $e[0]) . '</ul>';
					}
				}

				if (!empty($messages))
				{
					$messages = '<ul>' . implode('</li><li>', $messages) . '</ul>';
					header('HTTP/1.1 500 ' . FText::_('COM_FABRIK_FAILED_VALIDATION') . $messages);
					jexit();
				}
				else
				{
					$validated = true;
				}
			}
			else
			{
				// Package / model
				echo $model->getJsonErrors();
			}

			if (!$validated)
			{
				return;
			}
		}

		if (!$validated)
		{
			$this->savepage();

			if ($this->isMambot)
			{
				$this->setRedirect($this->getRedirectURL($model, false));
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
				// Meant that the form's data was in different format - so redirect to ensure that its showing the same data.
				$input->set('task', '');
				$view->display();
			}

			return;
		}
	}

	/**
	 * Get redirect URL
	 *
	 * @param   object $model      form model
	 * @param   bool   $incSession set url in session?
	 *
	 * @since      3.0
	 *
	 * @deprecated - use form model getRedirectUrl() instead
	 *
	 * @return   string  redirect url
	 */
	protected function getRedirectURL($model, $incSession = true)
	{
		$res                = $model->getRedirectURL($incSession, $this->isMambot);
		$this->baseRedirect = $res['baseRedirect'];

		return $res['url'];
	}

	/**
	 * Save a form's page to the session table
	 *
	 * @return  null
	 */

	public function savepage()
	{
		$input     = $this->input;
		$model     = new FormSession;
		$formModel = new Form;
		$formModel->setId($input->getString('formid'));
		$model->savePage($formModel);
	}
}
