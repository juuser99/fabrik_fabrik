<?php
/**
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2019  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Component\Fabrik\Site\Helper;


use Fabrik\Component\Fabrik\Site\Application\FabrikApplication;
use Fabrik\Component\Fabrik\Site\Controller\AbstractSiteController;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Factory\MVCFactory;

class PluginControllerHelper
{
	/**
	 * @var string
	 * @since 4.0
	 */
	private $name;

	/**
	 * @var string
	 * @since 4.0
	 */
	private $prefix;

	/**
	 * @var MVCFactory
	 * @since 4.0
	 */
	private $factory;

	/**
	 * @var FabrikApplication
	 * @since 4.0
	 */
	private $app;

	/**
	 * @var
	 * @since 4.0
	 */
	private $inputVars = [];

	/**
	 * @var array
	 * @since 4.0
	 */
	private $propertyVars = [];

	/**
	 * @var CMSApplication
	 * @since 4.0
	 */
	private $originalApp;

	/**
	 * @param string $controllerName
	 * @param array  $config
	 *
	 * @return AbstractSiteController
	 *
	 * @throws \Exception
	 * @since 4.0
	 */
	public static function getController(string $controllerName, array $config = []): AbstractSiteController
	{
		$app     = Factory::getApplication();
		$plugin  = ucfirst(PluginControllerParser::getFabrikPluginName($controllerName));
		$name    = PluginControllerParser::getControllerName($app->input, $controllerName);
		$factory = new MVCFactory(PluginControllerParser::getNamespace($controllerName));

		/** @var AbstractSiteController $controller */
		$controller = $factory->createController(
			$name,
			$plugin,
			PluginControllerParser::getControllerConfig($controllerName, $config),
			$app,
			$app->input
		);

		return $controller;
	}

	/**
	 * @param array $inputVars Set these in the application's Input
	 *
	 * @return $this
	 *
	 * @since 4.0
	 */
	public function setInputVars(array $inputVars)
	{
		$this->inputVars = $inputVars;

		return $this;
	}

	/**
	 * @param array $propertyVars Public property values for the controller
	 *
	 * @return $this
	 *
	 * @since 4.0
	 */
	public function setPropertyVars(array $propertyVars)
	{
		$this->propertyVars = $propertyVars;

		return $this;
	}

	/**
	 * J4 seems to do a lot of stuff based on the Input vars (views, dispatchers, etc) so we have to isolate
	 *
	 * @param string $controllerClass
	 * @param string $task
	 *
	 *
	 * @throws \Exception
	 * @since 4.0
	 */
	public function dispatchController(string $controllerClass, string $task = 'display'): void
	{
		// Parse controller class into parts for Joomla to generate
		preg_match('/Fabrik\\\\(.*?)\\\\(.*?)\\\\(.*?)\\\\Controller\\\\(.*?)Controller$/', $controllerClass, $matches);
		$namespace     = sprintf('Fabrik\\%s\%s', $matches[1], $matches[2]);
		$this->name    = $matches[3];
		$this->prefix  = $matches[4];
		$this->factory = new MVCFactory($namespace);

		$this->originalApp = Factory::getApplication();
		$this->app         = $this->getApplication();

		// set the FabrikApplication as the application for Factory to be used by the plugin or module's view
		Factory::$application = $this->app;

		try
		{
			$controller = $this->createController();
			array_walk($this->propertyVars, function ($value, $key) use ($controller) {
				$controller->$key = $value;
			});
			$controller->execute($task);
		}
		catch (\Exception $exception)
		{
			// Don't let the modules or plugins kill the loading of the entire page
			echo $exception->getMessage();
		}

		Factory::$application = $this->originalApp;
	}

	/**
	 * @return FabrikApplication
	 *
	 * @since 4.0
	 */
	private function getApplication(): FabrikApplication
	{
		$container = Factory::getContainer();
		$input     = clone $this->originalApp->input;
		$input->set('option', 'com_fabrik');
		$input->set('tmpl', null);
		$input->set('layout', null);
		array_walk($this->inputVars, function ($value, $key) use ($input) {
			$input->set($key, $value);
		});

		$app = new FabrikApplication($this->name, $input, $container->get('config'), null, $container);
		$app->setDispatcher($this->originalApp->getDispatcher());
		$app->loadIdentity($this->originalApp->getIdentity());
		$app->loadDocument(clone $this->originalApp->getDocument());
		$app->loadLanguage($this->originalApp->getLanguage());
		$app->setSession($this->originalApp->getSession());

		return $app;
	}

	/**
	 * @return AbstractSiteController
	 *
	 * @throws \Exception
	 * @since version
	 */
	private function createController(): AbstractSiteController
	{
		/** @var AbstractSiteController $controller */
		$controller = $this->factory->createController(
			$this->name,
			$this->prefix,
			PluginControllerParser::getControllerConfig($this->name, []),
			$this->app,
			$this->app->input
		);

		return $controller;
	}
}