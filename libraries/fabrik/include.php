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
		spl_autoload_register(array($this, 'controller'));
	}

	/**
	 * Load controller file
	 *
	 * @param   string $class Class name
	 */
	private function controller($class)
	{
		if (!strstr(strtolower($class), 'controller'))
		{
			return;
		}

		$class = str_replace('\\', '/', $class);
		$file  = explode('/', $class);
		$file  = strtolower(array_pop($file));
		$path  = JPATH_SITE . '/libraries/fabrik/fabrik/Controllers/' . \Joomla\String\StringHelper::ucfirst($file) . '.php';

		if (file_exists($path))
		{
			require_once $path;
		}

	}
}

// PSR-4 Auto-loader.
$loader     = require JPATH_LIBRARIES . '/fabrik/vendor/autoload.php';
$autoLoader = new FabrikAutoloader();
