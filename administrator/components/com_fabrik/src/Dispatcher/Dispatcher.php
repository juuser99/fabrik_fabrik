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
	public function getController(string $name, string $client = '', array $config = array()): BaseController
	{
		$format     = $this->input->get('format');
		$controller = $this->input->get('controller');

		if (!empty($controller) && 'raw' === $format)
		{
			$classController    = ucfirst($controller);
			$format             = ucfirst($format);
			$controllerString   = "%s\\%s\\Controller\\%s%sController";
			$backendController  = sprintf(
				$controllerString,
				$this->namespace,
				self::PREFIX_SITE,
				$classController,
				$format
			);

			$frontendController = sprintf(
				$controllerString,
				$this->namespace,
				self::PREFIX_ADMIN,
				$classController,
				$format
			);

			if (!class_exists($backendController) && !class_exists($frontendController))
			{
				// Fallback to the standard controller
				return parent::getController($name, $client, $config);
			}

			$controller .= 'Raw';
			$name       .= 'Raw';
			$this->input->set('controller', $controller);
		}

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
}
