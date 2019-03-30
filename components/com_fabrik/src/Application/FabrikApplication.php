<?php
/**
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Component\Fabrik\Site\Application;


use Joomla\Application\Web\WebClient;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Input\Input;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\Registry\Registry;
use Joomla\Uri\Uri;

class FabrikApplication extends CMSApplication
{
	/**
	 * FabrikApplication constructor.
	 *
	 * @param string         $name
	 * @param Input|null     $input
	 * @param Registry|null  $config
	 * @param WebClient|null $client
	 * @param Container|null $container
	 *
	 * @since 4.0
	 */
	public function __construct(string $name, Input $input = null, Registry $config = null, WebClient $client = null, Container $container = null)
	{
		$this->name = $name;

		parent::__construct($input, $config, $client, $container);
	}

	/**
	 * Simulate the frontend
	 *
	 * @param null  $name
	 * @param array $options
	 *
	 * @return \Joomla\CMS\Router\Router
	 *
	 * @since version
	 */
	public static function getRouter($name = null, array $options = array())
	{
		return parent::getRouter('site', $options);
	}

	/**
	 * Simulate the frontend
	 *
	 * @param null  $name
	 * @param array $options
	 *
	 * @return \Joomla\CMS\Menu\AbstractMenu
	 *
	 * @since version
	 */
	public function getMenu($name = null, $options = array())
	{
		return parent::getMenu('site', $options);
	}

	/**
	 * Method to run the Web application routines.
	 *
	 * @return  void
	 *
	 * @since   4.0
	 */
	protected function doExecute()
	{
		// Initialise the application
		$this->initialiseApp();

		// Mark afterInitialise in the profiler.
		JDEBUG ? $this->profiler->mark('afterInitialise') : null;

		// Route the application
		$this->route();

		// Mark afterRoute in the profiler.
		JDEBUG ? $this->profiler->mark('afterRoute') : null;

		// Dispatch the application
		$this->dispatch();

		// Mark afterDispatch in the profiler.
		JDEBUG ? $this->profiler->mark('afterDispatch') : null;
	}

	/**
	 * Dispatch the application
	 *
	 * @param   string  $component  The component which is being rendered.
	 *
	 * @return  void
	 *
	 * @since   4.0
	 */
	public function dispatch($component = null)
	{
		$component = $component ?? 'com_fabrik';

		// Load the document to the API
		$this->loadDocument();

		// Set up the params
		$document = $this->getDocument();

		switch ($document->getType())
		{
			case 'html':
				// Get language
				$lang_code = $this->getLanguage()->getTag();
				$languages = LanguageHelper::getLanguages('lang_code');

				// Set metadata
				if (isset($languages[$lang_code]) && $languages[$lang_code]->metakey)
				{
					$document->setMetaData('keywords', $languages[$lang_code]->metakey);
				}
				else
				{
					$document->setMetaData('keywords', $this->get('MetaKeys'));
				}

				$document->setMetaData('rights', $this->get('MetaRights'));

				if ($this->get('sef'))
				{
					$document->setBase(htmlspecialchars(Uri::current()));
				}

				// Get the template
				$template = $this->getTemplate(true);

				// Store the template and its params to the config
				$this->set('theme', $template->template);
				$this->set('themeParams', $template->params);

				// Add Asset registry files
				$document->getWebAssetManager()
					->addRegistryFile('media/' . $component . '/joomla.asset.json')
					->addRegistryFile('templates/' . $template->template . '/joomla.asset.json');

				break;

			case 'feed':
				$document->setBase(htmlspecialchars(Uri::current()));
				break;
		}


		$contents = ComponentHelper::renderComponent($component);
		$document->setBuffer($contents, 'component');

		// Trigger the onAfterDispatch event.
		PluginHelper::importPlugin('system');
		$this->triggerEvent('onAfterDispatch');
	}

}