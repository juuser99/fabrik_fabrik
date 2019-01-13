<?php
/**
 * Send an SMS
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.sms
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\StringHelper;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Language\Text;
use Joomla\Component\Fabrik\Site\Plugin\AbstractFormPlugin;
use Fabrik\Helpers\Worker;

/**
 * Send an SMS
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.sms
 * @since       3.0
 */
class PlgFabrik_FormSMS extends AbstractFormPlugin
{
	/**
	 * Run right at the end of the form processing
	 * form needs to be set to record in database for this to hook to be called
	 *
	 * @return    bool
	 *
	 * @since 4.0
	 */
	public function onAfterProcess()
	{
		return $this->process();
	}

	/**
	 * Send SMS
	 *
	 * @return    bool
	 *
	 * @since 4.0
	 */
	protected function process()
	{
		$formModel = $this->getModel();
		$params    = $this->getParams();
		$data      = $formModel->formData;

		if (!$this->shouldProcess('sms_conditon', $data, $params))
		{
			return true;
		}

		$w        = new Worker;
		$opts     = array();
		$userName = $params->get('sms-username');
		$password = $params->get('sms-password');
		$from     = $params->get('sms-from');
		$to       = $params->get('sms-to');
		$toEval   = $params->get('sms-to-eval');

		if (!empty($toEval))
		{
			$toEval = $w->parseMessageForPlaceholder($toEval, $data, false);
			$toEval = @eval($toEval);
			Worker::logEval($toEval, 'Caught exception on eval in email emailto : %s');

			if (!is_array($toEval))
			{
				$toEval = empty($toEval) ? array() : explode(',', $toEval);
			}

			$to = empty($to) ? array() : explode(',', $to);
			$to = array_merge($to, $toEval);
			$to = implode(',', $to);
		}

		if (empty($to))
		{
			return true;
		}

		$opts['sms-username'] = $w->parseMessageForPlaceHolder($userName, $data);
		$opts['sms-password'] = $w->parseMessageForPlaceHolder($password, $data);
		$opts['sms-from']     = $w->parseMessageForPlaceHolder($from, $data);
		$opts['sms-to']       = $w->parseMessageForPlaceHolder($to, $data);


		$message = $this->getMessage();
		$gateway = $this->getInstance();

		return $gateway->process($message, $opts);
	}

	/**
	 * Get specific SMS gateway instance
	 *
	 * @return  object  gateway
	 *
	 * @since 4.0
	 */
	private function getInstance()
	{
		if (!isset($this->gateway))
		{
			$params  = $this->getParams();
			$gateway = $params->get('sms-gateway', 'kapow.php');
			$input   = new InputFilter();
			$gateway = $input->clean($gateway, 'CMD');
			require_once JPATH_ROOT . '/libraries/fabrik/fabrik/Helpers/sms_gateways/' . StringHelper::strtolower($gateway);
			$gateway               = ucfirst(File::stripExt($gateway));
			$this->gateway         = new $gateway;
			$this->gateway->params = $params;
		}

		return $this->gateway;
	}

	/**
	 * Default email handling routine, called if no email template specified
	 *
	 * @return    string    email message
	 *
	 * @since 4.0
	 */
	protected function getMessage()
	{
		$params    = $this->getParams();
		$msg       = $params->get('sms_message', '');
		$formModel = $this->getModel();
		$data      = $formModel->formData;

		if ($msg !== '')
		{
			$w = new Worker;

			return $w->parseMessageForPlaceHolder($msg, $data);
		}
		else
		{
			return $this->defaultMessage();
		}
	}

	/**
	 * @return string
	 *
	 * @since 4.0
	 */
	protected function defaultMessage()
	{
		$formModel           = $this->getModel();
		$data                = $formModel->formData;
		$arDontEmailThesKeys = array();

		// Remove raw file upload data from the email
		foreach ($_FILES as $key => $file)
		{
			$arDontEmailThesKeys[] = $key;
		}

		$message = '';
		$groups  = $formModel->getGroupsHiarachy();

		foreach ($groups as $groupModel)
		{
			$elementModels = $groupModel->getPublishedElements();

			foreach ($elementModels as $elementModel)
			{
				$element        = $elementModel->getElement();
				$element->label = strip_tags($element->label);

				if (!array_key_exists($element->name, $data))
				{
					$elName = $elementModel->getFullName();
				}
				else
				{
					$elName = $element->name;
				}

				$key = $elName;

				if (!in_array($key, $arDontEmailThesKeys))
				{
					if (array_key_exists($elName, $data))
					{
						$val    = stripslashes($data[$elName]);
						$params = $elementModel->getParams();

						if (method_exists($elementModel, 'getEmailValue'))
						{
							$val = $elementModel->getEmailValue($val);
						}
						else
						{
							if (is_array($val))
							{
								$val = implode("\n", $val);
							}
						}

						$val     = StringHelper::rtrimword($val, '<br />');
						$message .= $element->label . ': ' . $val . "\r\n";
					}
				}
			}
		}

		$message = Text::_('PLG_FORM_SMS_FROM') . $this->config->get('sitename') . "\r \n \r \nMessage:\r \n" . stripslashes($message);

		return $message;
	}
}
