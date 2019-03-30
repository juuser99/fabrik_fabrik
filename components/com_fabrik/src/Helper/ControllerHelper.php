<?php
/**
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Component\Fabrik\Site\Helper;


use Fabrik\Component\Fabrik\Site\Application\FabrikApplication;
use Fabrik\Component\Fabrik\Site\Controller\AbstractSiteController;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Factory\MVCFactory;

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
	private $originalApp;

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
	 *
	 * @throws \Exception
	 * @since 4.0
	 */
	public function dispatchController(string $controllerClass): void
	{
		// Parse controller class into parts for Joomla to generate
		preg_match('/Fabrik\\\\(.*?)\\\\(.*?)\\\\(.*?)\\\\Controller\\\\(.*?)Controller$/', $controllerClass, $matches);
		$namespace    = sprintf('Fabrik\\%s\%s', $matches[1], $matches[2]);
		$this->name   = $matches[3];
		$this->prefix = $matches[4];

		$this->factory     = new MVCFactory($namespace);
		$this->originalApp = Factory::getApplication();
		$this->app         = $this->getApplication();

		// set the FabrikApplication as the application for Factory to be used by the plugin's view
		Factory::$application = $this->app;

		try
		{
			$controller = $this->getController();
			array_walk($propertyVars, function ($value, $key) use ($controller) {
				$controller->$key = $value;
			});
			$controller->display();
		}
		catch (\Exception $exception)
		{
			// Don't let the visualization kill the loading of the entire page
			echo $exception->getMessage();
		}

		Factory::$application = $this->originalApp;
	}

	/**
	 * There is likely a better J4-ish way to do this but we need to basically isolate the fabrik plugin from the given
	 * component loaded or Joomla behaves funky now with it's autoloading everything.
	 *
	 * @return AbstractSiteController
	 *
	 * @since 4.0
	 * @throws \Exception
	 */
	private function getController(): AbstractSiteController
	{
		/** @var AbstractSiteController $controller */
		$controller = $this->factory->createController(
			$this->name,
			$this->prefix,
			['base_path' => sprintf('%s/%s', JPATH_SITE, 'com_fabrik')],
			$this->app,
			$this->app->input
		);

		return $controller;
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
}