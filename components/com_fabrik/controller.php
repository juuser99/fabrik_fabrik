<?php
/**
 * Main Fabrik administrator controller
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Controllers;

// No direct access
use \JFactory;

defined('_JEXEC') or die('Restricted access');

/**
 * Fabrik master display controller.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.5
 */
class Controller extends \JControllerBase
{
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
		return true;
	}

	/**
	 * Set the redirect url
	 *
	 * @param   string  $url   default url
	 * @param   string  $msg   optional message to apply on redirect
	 * @param   string  $type  optional message type
	 *
	 * @return  null
	 */
	public function setRedirect($url, $msg = null, $type = 'message')
	{
		$session = JFactory::getSession();
		$package = $this->app->getUserState('com_fabrik.package', 'fabrik');
		$formData = $session->get('com_' . $package . '.form.data');
		$context = 'com_' . $package . '.form.' . $formData['fabrik'] . '.redirect.';

		// If the redirect plug-in has set a url use that in preference to the default url
		$url = $session->get($context . 'url', array($url));

		if (!is_array($url))
		{
			$url = array($url);
		}

		if (empty($url))
		{
			$url[] = $url;
		}

		$msg = $session->get($context . 'msg', array($msg));

		if (!is_array($msg))
		{
			$msg = array($msg);
		}

		if (empty($msg))
		{
			$msg[] = $msg;
		}

		$url = array_shift($url);
		$msg = array_shift($msg);

		$q = $this->app->getMessageQueue();
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

		$session->set($context . 'url', $url);
		$session->set($context . 'msg', $msg);
		$showMessage = $session->get($context . 'showsystemmsg', array(true));
		$showMessage = array_shift($showMessage);
		$msg = $showMessage ? $msg : null;

		$this->app->enqueueMessage($msg);
		$this->app->redirect($url);
		//parent::setRedirect($url, $msg, $type);
	}
}
