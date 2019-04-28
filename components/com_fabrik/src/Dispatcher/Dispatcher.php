<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2018  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       4.0.0
 */

namespace Fabrik\Component\Fabrik\Site\Dispatcher;

defined('_JEXEC') or die;

use Fabrik\Component\Fabrik\Site\Helper\ControllerHelper;
use Fabrik\Component\Fabrik\Site\Helper\FormattedControllerHelper;
use Fabrik\Component\Fabrik\Site\Helper\PluginControllerParser;
use Fabrik\Component\Fabrik\Site\WebService\AbstractWebService;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Dispatcher\ComponentDispatcher;

/**
 * ComponentDispatcher class for com_cpanel
 *
 * @since  4.0.0
 */
class Dispatcher extends ComponentDispatcher
{

	/**
	 * @since 4.0
	 */
	public function dispatch()
	{
		// Test if the system plugin is installed and published
		if (!defined('COM_FABRIK_FRONTEND'))
		{
			throw new \RuntimeException(Text::_('COM_FABRIK_SYSTEM_PLUGIN_NOT_ACTIVE'), 400);
		}

		if (JDEBUG)
		{
			// Add the logger.
			Log::addLogger(array('text_file' => 'fabrik.log.php'));
		}

		$this->doTemplateHacks();
		$this->setPackageInUserState();
		$this->testWebService();

		parent::dispatch();
	}

	/**
	 * Get a controller from the component with a "hack" for J4 lack of support for formatted controllers
	 *
	 * @param   string $controllerName   Controller name
	 * @param   string $client Optional client (like Administrator, Site etc.)
	 * @param   array  $config Optional controller config
	 *
	 * @return  BaseController
	 *
	 * @since   4.0.0
	 */
	public function getController(string $controllerName, string $client = '', array $config = array()): BaseController
	{
		if (PluginControllerParser::isFabrikPlugin($controllerName)) {
			return ControllerHelper::getPluginController($controllerName, $config);
		}

		$name = FormattedControllerHelper::getControllerName($this->app, $controllerName);
		$this->input->set('controller', $name);

		return parent::getController($name, $client, $config);
	}

	/**
	 * @since 4.0
	 */
	private function doTemplateHacks()
	{
		//set jquery property in app to stop yoo templates loading an additional version of jquery
		$this->app->set('jquery', true);
	}

	/**
	 * @since 4.0
	 */
	private function setPackageInUserState()
	{
		$package = $this->app->input->get('package', 'fabrik');
		$this->app->setUserState('com_fabrik.package', $package);
	}

	/**
	 * @throws \Exception
	 * @since 4.0
	 */
	private function testWebService()
	{
		if ($this->app->input->get('yql') !== 1)
		{
			return;
		}

		$opts    = array('driver' => 'yql', 'endpoint' => 'https://query.yahooapis.com/v1/public/yql');
		$service = AbstractWebService::getInstance($opts);
		$query   = "select * from upcoming.events where location='London'";
		$service->get($query, array(), 'event', null);
	}
}
