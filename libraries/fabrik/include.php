<?php
/**
 * Fabrik Autoloader Class
 *
 * @package     Fabrik
 * @copyright   Copyright (C) 2014 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabble\Helpers\Factory;
use Joomla\String\Normalise;
use Joomla\String\Inflector;

/**'
 * Autoloader Class
 *
 * @package  Fabble
 * @since    1.0
 */
class FabrikAutoloader
{
	public function __construct()
	{
		spl_autoload_register(array($this, 'formPlugin'));
		spl_autoload_register(array($this, 'listPlugin'));
		spl_autoload_register(array($this, 'validationPlugin'));
		spl_autoload_register(array($this, 'element'));
		spl_autoload_register(array($this, 'helper'));
	}

	/**
	 * Load list plugin class
	 *
	 * @param   string $class Class name
	 */
	private function validationPlugin($class)
	{
		if (!strstr(($class), 'Fabrik\Plugins\Validationrule'))
		{
			return;
		}

		$class = str_replace('\\', '/', $class);
		$file  = explode('/', $class);
		$file  = strtolower(array_pop($file));
		$path  = JPATH_SITE . '/plugins/fabrik_validationrule/' . $file . '/' . $file . '.php';

		if (file_exists($path))
		{
			require_once $path;
		}
	}

	/**
	 * Load list plugin class
	 *
	 * @param   string $class Class name
	 */
	private function listPlugin($class)
	{
		if (!strstr(($class), 'Fabrik\Plugins\Lizt'))
		{
			return;
		}

		$class = str_replace('\\', '/', $class);
		$file  = explode('/', $class);
		$file  = strtolower(array_pop($file));
		$path  = JPATH_SITE . '/plugins/fabrik_list/' . $file . '/' . $file . '.php';

		if (file_exists($path))
		{
			require_once $path;
		}
	}

	/**
	 * Load plugin class
	 *
	 * @param   string $class Class name
	 */
	private function formPlugin($class)
	{
		if (!strstr(($class), 'Fabrik\Plugins\Form'))
		{
			return;
		}

		$class = str_replace('\\', '/', $class);
		$file  = explode('/', $class);
		$file  = strtolower(array_pop($file));
		$path  = JPATH_SITE . '/plugins/fabrik_form/' . $file . '/' . $file . '.php';

		if (file_exists($path))
		{
			require_once $path;
		}
	}

	/**
	 * Load element plugin class
	 *
	 * @param   string $class Class name
	 */
	private function element($class)
	{
		if (!strstr(($class), 'Fabrik\Plugins\Element'))
		{
			return;
		}

		$class = str_replace('\\', '/', $class);
		$file  = explode('/', $class);
		$file  = strtolower(array_pop($file));
		$path  = JPATH_SITE . '/plugins/fabrik_element/' . $file . '/' . $file . '.php';

		if (file_exists($path))
		{
			require_once $path;
		}
	}

	private function helper($class)
	{
		if (!strstr(($class), 'Fabrik\Helpers'))
		{
			return;
		}

		$class = str_replace('\\', '/', $class);
		$class = str_replace('Fabrik/Helpers', '', $class);
		$path  = JPATH_SITE . '/components/com_fabrik/helpers/' . strtolower($class) . '.php';

		require_once $path;
	}

}

// PSR-4 Auto-loader.
$loader     = require JPATH_LIBRARIES . '/fabrik/vendor/autoload.php';
$autoLoader = new FabrikAutoloader();
