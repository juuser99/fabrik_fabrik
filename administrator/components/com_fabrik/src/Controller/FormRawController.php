<?php
/**
 * Raw Form controller class.
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

use Fabrik\Helpers\Worker;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Fabrik\Component\Fabrik\Administrator\Model\FabModel;
use Fabrik\Component\Fabrik\Site\Model\FormInlineEditModel;
use Fabrik\Component\Fabrik\Site\Model\FormModel;
use Fabrik\Component\Fabrik\Site\Model\FormSessionModel;
use Fabrik\Helpers\ArrayHelper as FArrayHelper;

/**
 * Raw Form controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       4.0
 */
class FormRawController extends AbstractFormController
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var    string
	 *
	 * @since 4.0
	 */
	protected $text_prefix = 'COM_FABRIK_FORM';

	/**
	 * @var string
	 *
	 * @since since 4.0
	 */
	protected $context = 'form';

	/**
	 * Is in J content plugin
	 *
	 * @var bool
	 *
	 * @since 4.0
	 */
	public $isMambot = false;

	/**
	 * Set up inline edit view
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	public function inlineedit()
	{
		$model = FabModel::getInstance(FormInlineEditModel::class);
		$model->render();
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
		$input = $this->input;
		/** @var FormSessionModel $model */
		$model = $this->getModel(FormSessionModel::class);
		/** @var FormModel $formModel */
		$formModel = $this->getModel(FormModel::class);
		$formModel->setId($input->getInt('formid'));
		$model->savePage($formModel);
	}

	/**
	 * Handle saving posted form data from the admin pages
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	public function process()
	{
		$app      = Factory::getApplication();
		$input    = $app->input;
		$viewName = $input->get('view', 'form');

		// For now lets route this to the html view.
		$view = $this->getView($viewName, 'html');

		/** @var FormModel $model */
		if ($model = FabModel::getInstance(FormModel::class))
		{
			$view->setModel($model, true);
		}

		$model->setId($input->get('formid', 0));
		$this->isMambot = $input->get('_isMambot', 0);
		$model->getForm();
		$model->rowId = $input->get('rowid', '', 'string');

		// Check for request forgeries
		if ($model->spoofCheck())
		{
			Session::checkToken() or die('Invalid Token');
		}

		$validated = $model->validate();

		if (!$validated)
		{
			// If its in a module with ajax or in a package or inline edit
			if ($input->get('fabrik_ajax'))
			{
				if ($input->getInt('elid', 0) !== 0)
				{
					// Inline edit
					$eMsgs = array();
					$errs  = $model->getErrors();

					// Only raise errors for fields that are present in the inline edit plugin
					$toValidate = array_keys($input->get('toValidate', array(), 'array'));

					foreach ($errs as $errorKey => $e)
					{
						if (in_array($errorKey, $toValidate) && count($e[0]) > 0)
						{
							array_walk_recursive($e, array('FabrikString', 'forHtml'));
							$eMsgs[] = count($e[0]) === 1 ? '<li>' . $e[0][0] . '</li>' : '<ul><li>' . implode('</li><li>', $e[0]) . '</ul>';
						}
					}

					if (!empty($eMsgs))
					{
						$eMsgs = '<ul>' . implode('</li><li>', $eMsgs) . '</ul>';
						header('HTTP/1.1 500 ' . Text::_('COM_FABRIK_FAILED_VALIDATION') . $eMsgs);
						jexit();
					}
					else
					{
						$validated = true;
					}
				}
				else
				{
					echo $model->getJsonErrors();
				}

				if (!$validated)
				{
					return;
				}
			}

			if (!$validated)
			{
				if ($this->isMambot)
				{
					$referrer = filter_var(FArrayHelper::getValue($_SERVER, 'HTTP_REFERER', ''), FILTER_SANITIZE_URL);
					$input->post->set('fabrik_referrer', $referrer);

					/**
					 * $$$ hugh - testing way of preserving form values after validation fails with form plugin
					 * might as well use the 'savepage' mechanism, as it's already there!
					 */
					$this->savepage();
					$this->makeRedirect($model, '');
				}
				else
				{
					$view->display();
				}

				return;
			}
		}

		// Reset errors as validate() now returns ok validations as empty arrays
		$model->errors = array();
		$model->process();

		// Check if any plugin has created a new validation error
		if ($model->hasErrors())
		{
			Worker::getPluginManager()->runPlugins('onError', $model);
			$view->display();

			return;
		}

		$listModel = $model->getListModel();
		$tid       = $listModel->getTable()->id;

		$res                = $model->getRedirectURL(true, $this->isMambot);
		$this->baseRedirect = $res['baseRedirect'];
		$url                = $res['url'];

		$msg = $model->getRedirectMessage($model);

		if ($input->getInt('elid', 0) !== 0)
		{
			// Inline edit show the edited element
			/** @var FormInlineEditModel $inlineModel */
			$inlineModel = $this->getModel(FormInlineEditModel::class);
			$inlineModel->setFormModel($model);
			echo $inlineModel->showResults();

			return;
		}

		if ($input->getInt('packageId', 0) !== 0)
		{
			$rowId = $input->getString('rowid', '', 'string');
			echo json_encode(array('msg' => $msg, 'rowid' => $rowId));

			return;
		}

		// @todo -should get handed off to the json view to do this
		if ($input->getInt('fabrik_ajax') == 1)
		{
			// $$$ hugh - adding some options for what to do with redirect when in content plugin
			// Should probably do this elsewhere, but for now ...
			$redirect_opts = array(
				'msg'          => $msg,
				'url'          => $url,
				'baseRedirect' => $this->baseRedirect,
				'rowid'        => $input->get('rowid', '', 'string'),
				'suppressMsg'  => !$model->showSuccessMsg()
			);

			if (!$this->baseRedirect && $this->isMambot)
			{
				$session                       = $this->app->getSession();
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
				$session                       = $this->app->getSession();
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
				$session                     = $this->app->getSession();
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
			$url = 'index.php?option=com_fabrik&task=list.view&format=raw&listid=' . $tid;
			$this->setRedirect($url, $msg);
		}
		else
		{
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
		$app   = Factory::getApplication();
		$input = $app->input;
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
}
