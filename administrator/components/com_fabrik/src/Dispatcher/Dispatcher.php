<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2018  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       4.0.0
 */

namespace Fabrik\Component\Fabrik\Administrator\Dispatcher;

defined('_JEXEC') or die;

use Fabrik\Component\Fabrik\Site\Helper\PluginControllerHelper;
use Fabrik\Component\Fabrik\Site\Helper\PluginControllerParser;
use Fabrik\Helpers\Html;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Dispatcher\ComponentDispatcher;

/**
 * ComponentDispatcher class for com_cpanel
 *
 * @since  4.0.0
 */
class Dispatcher extends ComponentDispatcher
{
	public const NAMESPACE = 'Fabrik\\Component\\Fabrik';
	public const PREFIX_SITE = 'Site';
	public const PREFIX_ADMIN = 'Administrator';

	/**
	 * The extension namespace
	 *
	 * @var    string
	 *
	 * @since  4.0.0
	 */
	protected $namespace = self::NAMESPACE;

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

		HTMLHelper::stylesheet('administrator/components/com_fabrik/tmpl/headings.css');

		$this->checkElementIsPublished();
		$this->loadFabrikFramework();

		parent::dispatch();
	}

	/**
	 * Get a controller from the component with a "hack" for J4 lack of support for formatted controllers
	 *
	 * @param   string $name   Controller name
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
			return (new PluginControllerHelper())->getController($controllerName, $config);
		}

		$client = PluginControllerParser::getControllerClient($controllerName, $client ? $client : self::PREFIX_ADMIN);
		$name   = PluginControllerParser::getControllerName($this->input, $controllerName);

		$this->input->set('controller', $name);

		return parent::getController($name, $client, $config);
	}

	/**
	 * J4 no longer loads frontend language file for the backend which Fabrik depends on
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
	protected function loadLanguage()
	{
		parent::loadLanguage();

		$this->app->getLanguage()->load($this->option, JPATH_ROOT.'/components/com_fabrik', null, false, true);
	}


	/**
	 * Test that they've published some element plugins!
	 *
	 * @since 4.0
	 */
	private function checkElementIsPublished()
	{
		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);
		$query->select('COUNT(extension_id)')->from('#__extensions')
			->where('enabled = 1 AND folder = ' . $db->q('fabrik_element'));
		$db->setQuery($query);

		if ((int)$db->loadResult() === 0)
		{
			$this->app->enqueueMessage(Text::_('COM_FABRIK_PUBLISH_AT_LEAST_ONE_ELEMENT_PLUGIN'), 'notice');
		}
	}

	/**
	 * @since 4.0
	 */
	private function loadFabrikFramework()
	{
		if ($this->app->input->get('format', 'html') === 'html')
		{
			Html::framework();
		}
	}
}
