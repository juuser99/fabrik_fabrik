<?php
/**
 * Fabrik Admin Autoloader
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Admin;

/**
 * Class AutoLoader
 *
 * @package Fabrik\Admin
 */
class AutoLoader
{
	public function __construct()
	{
		spl_autoload_register(array($this, 'controller'));
		spl_autoload_register(array($this, 'view'));
		spl_autoload_register(array($this, 'model'));
		spl_autoload_register(array($this, 'storage'));
		spl_autoload_register(array($this, 'helpers'));
	}

	/**
	 * Load helper file
	 *
	 * @param   string $class Class name
	 */
	private function helpers($class)
	{
		if (!strstr(strtolower($class), 'helpers\\'))
		{
			return;
		}


		if (strstr($class, '\Admin'))
		{
			// Loading an admin model
			$class = str_replace('Fabrik\Admin\\', '', $class);
			$file  = str_replace('\\', '/', strtolower($class));
			$file  = JPATH_ADMINISTRATOR . '/components/com_fabrik/' . strtolower($file . '.php');
		}
		else
		{
			// Front end model.
			$class = str_replace('Fabrik\\', '', $class);
			$file  = str_replace('\\', '/', strtolower($class));
			$file  = JPATH_SITE . '/components/com_fabrik/' . strtolower($file . '.php');
		}

		require $file;
	}

	/**
	 * Load model file
	 *
	 * @param   string $class Class name
	 */
	private function model($class)
	{
		if (!strstr(strtolower($class), 'models'))
		{
			return;
		}

		if (strstr($class, '\Admin'))
		{
			// Loading an admin model
			$class = str_replace('Fabrik\Admin\\', '', $class);
			$file  = str_replace('\\', '/', strtolower($class));
			$file  = JPATH_ADMINISTRATOR . '/components/com_fabrik/' . strtolower($file . '.php');
		}
		else
		{
			// Front end model.
			$class = str_replace('Fabrik\\', '', $class);
			$file  = str_replace('\\', '/', strtolower($class));
			$file  = JPATH_SITE . '/components/com_fabrik/' . strtolower($file . '.php');
		}

		require $file;
	}

	/**
	 * Load view file
	 *
	 * @param   string $class Class name
	 */
	private function view($class)
	{
		if (!strstr(strtolower($class), 'views'))
		{
			return;
		}

		$admin = strstr($class, '\Admin');
		$class = str_replace('Fabrik\Admin\\', '', $class);
		$file  = str_replace('\\', '/', strtolower($class));
		$file  = strtolower($file . '.php');

		if ($admin)
		{
			$file  = JPATH_ADMINISTRATOR . '/components/com_fabrik/' . $file;
		}
		else
		{
			$file  = JPATH_SITE . '/components/com_fabrik/' . $file;
		}

		require $file;
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

		$admin = strstr($class, '\Admin');
		$class = str_replace('Fabrik\Admin\\', '', $class);
		$file  = str_replace('\\', '/', strtolower($class));
		$file  = strtolower($file . '.php');
		require_once JPATH_COMPONENT_ADMINISTRATOR . '/controller.php';

		if ($admin)
		{
			$file  = JPATH_ADMINISTRATOR . '/components/com_fabrik/' . $file;
		}
		else
		{
			$file  = JPATH_SITE . '/components/com_fabrik/' . $file;
		}

		if (\JFile::exists($file))
		{
			require $file;
		}
	}

	/**
	 * Load storage file
	 *
	 * @param   string $class Class name
	 */
	private function storage($class)
	{
		if (!strstr(strtolower($class), 'storage'))
		{
			return;
		}

		$class = str_replace('Fabrik\\', '', $class);
		$file  = str_replace('\\', '/', strtolower($class));
		$file  = strtolower($file . '.php');

		if (file_exists(JPATH_COMPONENT_ADMINISTRATOR . '/models/' . $file))
		{
			require_once JPATH_COMPONENT_ADMINISTRATOR . '/models/' . $file;
		}
	}
}

new AutoLoader();


