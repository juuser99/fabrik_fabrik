<?php
/**
 * Access point to render Fabrik component
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\String\String;

require_once JPATH_SITE . '/components/com_fabrik/autoloader.php';

jimport('joomla.application.component.helper');
jimport('joomla.filesystem.file');

if (!defined('COM_FABRIK_FRONTEND'))
{
	throw new RuntimeException(JText::_('COM_FABRIK_SYSTEM_PLUGIN_NOT_ACTIVE'), 400);
}

jimport('joomla.log.log');

if (JDEBUG)
{
	// Add the logger.
	JLog::addLogger(array('text_file' => 'fabrik.log.php'));
}

$app   = JFactory::getApplication();
$input = $app->input;

$controllerName = $input->getCmd('view');

// Check for a plugin controller

// Call a plugin controller via the url :
// &controller=visualization.calendar

$isPlugin = false;
$cName    = $input->getCmd('controller');

if (String::strpos($cName, '.') != false)
{
	list($type, $name) = explode('.', $cName);

	if ($type == 'visualization')
	{
		require_once JPATH_COMPONENT . '/controllers/visualization.php';
	}

	$path = JPATH_SITE . '/plugins/fabrik_' . $type . '/' . $name . '/controllers/' . $name . '.php';

	if (JFile::exists($path))
	{
		require_once $path;
		$isPlugin   = true;
		$controller = $type . $name;
	}
	else
	{
		$controller = '';
	}
}
else
{
	// Its not a plugin
	// map controller to view - load if exists

	/**
	 * $$$ rob was a simple $controller = view, which was giving an error when trying to save a popup
	 * form to the calendar viz
	 * May simply be the best idea to remove main controller and have different controllers for each view
	 */

	// Hack for package
	if ($input->getCmd('view') == 'package' || $input->getCmd('view') == 'list')
	{
		$controller = $input->getCmd('view');
	}
	else
	{
		$controller = $controllerName;
	}

	$path = JPATH_COMPONENT . '/controllers/' . $controller . '.php';

	if (JFile::exists($path))
	{
		require_once $path;
	}
	else
	{
		$controller = '';
	}
}

/**
 * Create the controller if the task is in the form view.task then get
 * the specific controller for that class - otherwise use $controller to load
 * required controller class
 */
if (strpos($input->getCmd('task'), '.') !== false)
{
	$controllerTask = explode('.', $input->getCmd('task'));
	$controller     = array_shift($controllerTask);
	$classname      = 'FabrikController' . String::ucfirst($controller);
	$path           = JPATH_COMPONENT . '/controllers/' . $controller . '.php';

	if (JFile::exists($path))
	{
		require_once $path;

		// Needed to process J content plugin (form)
		$input->set('view', $controller);
		$task       = array_pop($controllerTask);
		$controller = new $classname;
	}
	else
	{
		$controller = JControllerLegacy::getInstance('Fabrik');
	}
}
else
{
	/*$classname = 'FabrikController' . String::ucfirst($controller);
	$controller = new $classname;
	$task = $input->getCmd('task');*/

	// Do we have a custom controller - if not load the main controller.
	$view = $input->get('view', 'Controller') !== '' ? $input->get('view', 'Controller') : $input->get->get('view', 'Controller');
	$input->set('view', $view);
	$view = String::ucfirst($view);

	if ($view === 'List')
	{
		$view = 'Lizt';
	}

	$base                = 'Fabrik\Controllers';
	$baseControllerClass = $base . '\\Controller';
	$controllerClass     = $base . '\\' . $view;
	$controller = class_exists($controllerClass) ? new $controllerClass : new $baseControllerClass;
}

if ($isPlugin)
{
	// Add in plugin view
	$controller->addViewPath(JPATH_SITE . '/plugins/fabrik_' . $type . '/' . $name . '/views');

	// Add the model path
	$modelPaths = JModelLegacy::addIncludePath(JPATH_SITE . '/plugins/fabrik_' . $type . '/' . $name . '/models');
}

$package = $input->get('package', 'fabrik');
$app->setUserState('com_fabrik.package', $package);

$controller->execute();

// Redirect if set by the controller
