<?php
/**
 * Connection controller class
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Admin\Controllers;

// No direct access
defined('_JEXEC') or die('Restricted access');



/**
 * Connection controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.5
 */
class Connection extends \JControllerBase
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
		echo "here";exit;
	}
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var	string
	 */
	protected $text_prefix = 'COM_FABRIK_CONNECTION';

	/**
	 * Tries to connection to the database
	 *
	 * @return string connection message
	 */

	public function test()
	{
		JSession::checkToken() or die('Invalid Token');
		$input = $this->app->input;
		$cid = $input->get('cid', array(), 'array');
		$cid = array((int) $cid[0]);
		$link = 'index.php?option=com_fabrik&view=connections';

		foreach ($cid as $id)
		{
			$model = JModelLegacy::getInstance('Connection', 'FabrikFEModel');
			$model->setId($id);

			if ($model->testConnection() == false)
			{
				$this->app->enqueueMessage(FText::_('COM_FABRIK_UNABLE_TO_CONNECT'), 'error');
				$this->setRedirect($link);

				return;
			}
		}

		$this->setRedirect($link, FText::_('COM_FABRIK_CONNECTION_SUCESSFUL'));
	}
}
