<?php
/**
 * Entry point to Fabrik's administration pages
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Admin;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\String;
use Fabrik\Admin\Helpers\Fabrik;
use JFactory;
use JFile;

require_once JPATH_SITE . '/components/com_fabrik/autoloader.php';

// Load front end language file as well
$lang = JFactory::getLanguage();
$lang->load('com_fabrik', JPATH_SITE . '/components/com_fabrik');

Fabrik::testPublishedPlugins();

$input = JFactory::getApplication()->input;
$cName = $input->getCmd('controller');

// Check for plugin views (e.g. list email plugin's "email form"

if (String::strpos($cName, '.') != false)
{
	// @todo - recheck this for 3.5
	list($type, $name) = explode('.', $cName);

	if ($type == 'visualization')
	{
		require_once JPATH_COMPONENT . '/controllers/visualization.php';
	}

	$path = JPATH_SITE . '/plugins/fabrik_' . $type . '/' . $name . '/controllers/' . $name . '.php';

	if (JFile::exists($path))
	{
		require_once $path;
		$controller = $type . $name;

		$className  = 'FabrikController' . String::ucfirst($controller);
		//echo $className;exit;
		$controller = new $className;

		// Add in plugin view
		$controller->addViewPath(JPATH_SITE . '/plugins/fabrik_' . $type . '/' . $name . '/views');

		// Add the model path
		JModelLegacy::addIncludePath(JPATH_SITE . '/plugins/fabrik_' . $type . '/' . $name . '/models');
	}
}
else
{
	// Do we have a custom controller - if not load the main controller.
	$view = $input->get('view', 'Controller') !== '' ? $input->get('view', 'Home') : $input->get->get('view', 'Home');
	$input->set('view', $view);
	$view                = String::ucfirst($view);
	$base                = 'Fabrik\Admin\Controllers';
	$baseControllerClass = $base . '\\Controller';
	$controllerClass     = $base . '\\' . $view;
	$controller          = class_exists($controllerClass) ? new $controllerClass : new $baseControllerClass;
}

// Execute the controller.
$controller->execute();
