<?php
/**
 * Admin Connection Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0
 */

namespace Fabrik\Admin\Models;

// No direct access
defined('_JEXEC') or die('Restricted access');

use GCore\Libs\Arr;
use Joomla\Utilities\ArrayHelper;
use \FText as FText;
use \stdClass as stdClass;
use Fabrik\Helpers\Worker;
use \JFactory as JFactory;

jimport('joomla.application.component.modeladmin');

interface ConnectionInterface
{
}

/**
 * Admin Connection Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.5
 */
class Connection extends Connections implements ConnectionInterface
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var  string
	 */
	protected $text_prefix = 'COM_FABRIK_CONNECTION';

	/**
	 * Constructor.
	 *
	 * @param   Registry $state Optional configuration settings.
	 *
	 * @since    3.5
	 */
	public function __construct(Registry $state = null)
	{
		parent::__construct($state);
		$this->config = $this->state->get('config', \JFactory::getConfig());
	}
	/**
	 * Check if connection is the default and if so reset its values to those of the J db connection
	 *
	 * @param   object &$item connection item
	 *
	 * @return  null
	 */
	public function checkDefault(&$item)
	{
		if ($item->id == 0)
		{
			$this->app->enqueueMessage(FText::_('COM_FABRIK_ORIGINAL_CONNECTION'));

			if (!$this->matchesDefault($item))
			{
				$item->host     = $this->config->get('host');
				$item->user     = $this->config->get('user');
				$item->password = $this->config->get('password');
				$item->database = $this->config->get('db');
				$this->app->enqueueMessage(FText::_('COM_FABRIK_YOU_MUST_SAVE_THIS_CNN'), 'notice');
			}
		}
	}

	/**
	 * Do the item details match the J db connection details
	 *
	 * @param   object $item connection item
	 *
	 * @return  bool  matches or not
	 */
	protected function matchesDefault($item)
	{
		$password = $this->config->get('password');
		$crypt    = Worker::getCrypt();
		$pwMatch  = $password == $item->password || $crypt->encrypt($password) == $item->password;

		return $this->config->get('host') == $item->host && $this->config->get('user') == $item->user && $pwMatch
		&& $this->config->get('db') == $item->database;
	}

	/**
	 * Prepare the data for saving. Run after validation
	 *
	 * @param  array $data
	 *
	 * @return array
	 */
	public function prepare(&$data)
	{
		$crypt               = Worker::getCrypt();
		$params              = new stdClass;
		$params->encryptedPw = true;
		$data['params']      = $params;
		$data['password']    = $crypt->encrypt($data['password']);

		return $data;
	}

	/**
	 * Method to validate the form data.
	 *
	 * @param   array $data The data to validate.
	 *
	 * @see     JFormRule
	 * @see     JFilterInput
	 *
	 * @return  mixed  Array of filtered data if valid, false otherwise.
	 */
	public function validate($data)
	{
		if ($data['password'] !== $data['passwordConf'])
		{
			$this->app->enqueueMessage(FText::_('COM_FABRIK_PASSWORD_MISMATCH'), 'error');

			return false;
		}

		return parent::validate($data);
	}

	/**
	 * Test a connection
	 *
	 * @return bool
	 */
	public function test()
	{
		$item = $this->getItem();
		$options = ArrayHelper::fromObject($item);
		$db = \JDatabaseDriver::getInstance($options);

		try
		{
			$db->connect();
			$ok = true;
			$this->app->enqueueMessage('Connection successful');
		}
		catch (RuntimeException $e)
		{
			$this->app->enqueueMessage($e->getMessage(), 'error');
			$ok = false;
		}

		return $ok;
	}
}
