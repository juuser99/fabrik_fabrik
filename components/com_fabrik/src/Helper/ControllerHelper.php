<?php
/**
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2019  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Component\Fabrik\Site\Helper;

use Fabrik\Component\Fabrik\Administrator\Dispatcher\Dispatcher;
use Fabrik\Component\Fabrik\Site\Application\FabrikApplication;
use Fabrik\Component\Fabrik\Site\Controller\AbstractSiteController;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Factory\MVCFactory;
use Joomla\String\StringHelper;

class ControllerHelper
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
	private $nativeApp;

	/**
	 * @param string $controllerName
	 * @param array  $config
	 *
	 * @return AbstractSiteController
	 *
	 * @throws \Exception
	 * @since 4.0
	 */
	public static function getPluginController(string $controllerName, array $config = []): AbstractSiteController
	{
		$app     = Factory::getApplication();
		$plugin  = StringHelper::ucfirst(PluginControllerParser::getFabrikPluginName($controllerName));
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
	 * @param string $controllerClass
	 *
	 * @return AbstractSiteController
	 *
	 * @throws \Exception
	 * @since 4.0
	 */
	public function getIsolatedController(string $controllerClass): AbstractSiteController
	{
		// Parse controller class into parts for Joomla to generate
		preg_match('/Fabrik\\\\(.*?)\\\\(.*?)\\\\(.*?)\\\\Controller\\\\(.*?)Controller$/', $controllerClass, $matches);

		if ('Component' === $matches[1])
		{
			$this->createFactory(Dispatcher::NAMESPACE, $matches[4], $matches[3]);

		}
		elseif ('Plugin' === $matches[1])
		{
			$namespace = sprintf('Fabrik\\%s\%s', $matches[1], $matches[2]);
			$this->createFactory($namespace, $matches[3], $matches[4]);
		}

		$this->nativeApp = Factory::getApplication();
		$this->app       = $this->getApplication('Plugin' === $matches[1]);

		// set the FabrikApplication as the application for Factory to be used by the plugin or module's view
		Factory::$application = $this->app;

		$controller = $this->createController();

		array_walk($this->propertyVars, function ($value, $key) use ($controller) {
			$controller->$key = $value;
		});

		return $controller;
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
		try
		{
			$controller = $this->getIsolatedController($controllerClass);

			$controller->execute($task);
		}
		catch (\Exception $exception)
		{
			// Don't let the modules or plugins kill the loading of the entire page
			echo (JDEBUG) ? $exception->getMessage() : 'Fabrik encountered an error.';
		} catch (\Error $exception) {
			echo (JDEBUG) ? $exception->getMessage() : 'Fabrik encountered an error.';
		}

		$this->restoreFactoryApplication();
	}

	/**
	 * @since 4.0
	 */
	public function restoreFactoryApplication()
	{
		if (!$this->nativeApp) {
			return;
		}

		// Reset the application
		Factory::$application = $this->nativeApp;

		$this->nativeApp = null;
	}

	/**
	 * @param bool $isPlugin
	 *
	 * @return FabrikApplication|SiteApplication
	 *
	 * @since 4.0
	 */
	private function getApplication(bool $isPlugin): CMSApplication
	{
		$container = Factory::getContainer();
		$input     = clone $this->nativeApp->input;
		$input->set('option', 'com_fabrik');
		$input->set('tmpl', null);
		$input->set('layout', null);
		array_walk($this->inputVars, function ($value, $key) use ($input) {
			$input->set($key, $value);
		});

		$app = ($isPlugin) ?
			new FabrikApplication($this->name, $input, $container->get('config'), null, $container) :
			new SiteApplication($input, $container->get('config'), null, $container);

		$app->setDispatcher($this->nativeApp->getDispatcher());
		$app->loadIdentity($this->nativeApp->getIdentity());
		$app->loadDocument(clone $this->nativeApp->getDocument());
		$app->loadLanguage($this->nativeApp->getLanguage());
		$app->setSession($this->nativeApp->getSession());

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
			PluginControllerParser::isFabrikPlugin($this->name) ? PluginControllerParser::getControllerConfig($this->name, []) : [],
			$this->app,
			$this->app->input
		);

		return $controller;
	}

	/**
	 * @param string $namespace
	 * @param string $name
	 * @param string $prefix
	 *
	 *
	 * @since 4.0
	 */
	private function createFactory(string $namespace, string $name, string $prefix): void
	{
		$this->name    = $name;
		$this->prefix  = $prefix;
		$this->factory = new MVCFactory($namespace);
	}
}