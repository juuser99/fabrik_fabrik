<?php
/**
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2019  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Component\Fabrik\Site\Helper;


use Joomla\CMS\Filesystem\Folder;
use Joomla\Input\Input;
use Joomla\String\StringHelper;

class PluginControllerParser
{
	/**
	 * @param Input  $input
	 * @param string $defaultController
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	public static function getControllerName(Input $input, string $defaultController): string
	{
		$format              = $input->get('format');
		$requestedController = $input->get('controller');
		$controllerName      = ($requestedController) ? $requestedController : $defaultController;

		// Special handling of formats - J4 hack since it no longer recognizes formatted controllers
		// @todo - refactor these to views
		if ($format)
		{
			$plugin             = StringHelper::ucfirst(self::getFabrikPluginName($controllerName));
			$testControllerName = StringHelper::ucfirst($plugin) . StringHelper::ucfirst($format);
			$testClassName      = sprintf(
				'%s\%s\Controller\%sController',
				self::getNamespace($controllerName),
				$plugin,
				$testControllerName
			);

			if (class_exists($testClassName))
			{
				$controllerName .= StringHelper::ucfirst($format);
			}
		}

		// Special handling of plugin controllers
		if (self::isFabrikPlugin($controllerName))
		{
			$controllerName = self::getFabrikPluginName($controllerName);
		}

		return $controllerName;
	}

	/**
	 * @param string $controllerName
	 * @param array  $config
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public static function getControllerConfig(string $controllerName, array $config): array
	{
		if (self::isFabrikPlugin($controllerName))
		{
			$path = sprintf(
				'%s/plugins/fabrik_%s/%s',
				JPATH_SITE,
				self::getFabrikPluginType($controllerName),
				self::getFabrikPluginName($controllerName)
			);

			if (Folder::exists($path))
			{
				$config['base_path'] = $path;
			}
		}

		return $config;
	}

	/**
	 * @param string $controllerName
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	public static function getNamespace(string $controllerName): string
	{
		$type = StringHelper::ucfirst(self::getFabrikPluginType($controllerName));

		return sprintf('\\Fabrik\\Plugin\\Fabrik%s', $type);
	}

	/**
	 * @param string $controllerName
	 * @param string $defaultClient
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	public static function getControllerClient(string $controllerName, string $defaultClient): string
	{
		if (!self::isFabrikPlugin($controllerName)) {
			return $defaultClient;
		}

		return StringHelper::ucfirst(self::getFabrikPluginName($controllerName));
	}

	/**
	 * @param string $controllerName
	 *
	 * @return bool
	 *
	 * @since 4.0
	 */
	public static function isFabrikPlugin(string $controllerName): bool
	{
		return StringHelper::strpos($controllerName, '.') != false;
	}

	/**
	 * @param string $controllerName
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	public static function getFabrikPluginName(string $controllerName): string
	{
		return explode('.', $controllerName)[1];
	}

	/**
	 * @param string $controllerName
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	public static function getFabrikPluginType(string $controllerName): string
	{
		return explode('.', $controllerName)[0];
	}

	/**
	 * @param string $controllerClass
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	public static function getPluginFromControllerClass(string $controllerClass): string
	{
		preg_match('/Fabrik\\\\Plugin\\\\(.*?)\\\\(.*?)\\\\Controller\\\\(.*?)Controller/', $controllerClass, $matches);

		return $matches[2];
	}

	/**
	 * @param string $controllerClass
	 *
	 * @return false|int
	 *
	 * @since 4.0
	 */
	public static function isPluginController(string $controllerClass)
	{
		return !empty(
			preg_match('/Fabrik\\\\Plugin\\\\(.*?)\\\\(.*?)\\\\Controller\\\\(.*?)Controller/', $controllerClass)
		);
	}
}