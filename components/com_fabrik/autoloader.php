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
		spl_autoload_register(array($this, 'plugins'));
	}

	/**
	 * Load plugin file
	 *
	 * @param   string $class Class name
	 */
	private function plugins($class)
	{
		if (!strstr(strtolower($class), 'fabrik\plugins\\'))
		{
			return;
		}
		// Front end model.
		$class = str_replace('Fabrik\\Plugins\\Validation\\', '', $class);
		$file  = str_replace('\\', '/', strtolower($class));

		if ($file === 'validation')
		{
			require_once JPATH_SITE . '/components/com_fabrik/models/plugin-validation.php';
		}
		else
		{
			$class = strtolower($class);
			$class = str_replace('fabrik\\plugins\\validation\\', '', $class);
			$file  = str_replace('\\', '/', $class);
			$file  = JPATH_SITE . '/plugins/fabrik_validationrule/' . $file . '/' . $file . '.php';

			require_once $file;
		}
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

		require_once $file;
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

		require_once $file;
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
		$class = str_replace('Fabrik\\', '', $class);
		$file  = str_replace('\\', '/', strtolower($class));
		$file  = strtolower($file . '.php');

		if ($admin)
		{
			$file = JPATH_ADMINISTRATOR . '/components/com_fabrik/' . $file;
		}
		else
		{
			$file = JPATH_SITE . '/components/com_fabrik/' . $file;
		}

		require_once $file;
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
		$class = str_replace('Fabrik\\', '', $class);
		$file  = str_replace('\\', '/', strtolower($class));
		$file  = strtolower($file . '.php');

		if ($admin)
		{
			require_once JPATH_COMPONENT_ADMINISTRATOR . '/controller.php';
			$file = JPATH_ADMINISTRATOR . '/components/com_fabrik/' . $file;
		}
		else
		{
			require_once JPATH_COMPONENT . '/controller.php';
			$file = JPATH_SITE . '/components/com_fabrik/' . $file;
		}

		if (\JFile::exists($file))
		{
			require_once $file;
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


