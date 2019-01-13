<?php
/**
 * Access point to render Fabrik component
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Filesystem\File;
use Joomla\CMS\MVC\Controller\BaseController;
use Fabrik\Component\Fabrik\Site\WebService\AbstractWebService;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\String\StringHelper;

if (!defined('COM_FABRIK_FRONTEND'))
{
	throw new \RuntimeException(Text::_('COM_FABRIK_SYSTEM_PLUGIN_NOT_ACTIVE'), 400);
}

if (JDEBUG)
{
	// Add the logger.
	Log::addLogger(array('text_file' => 'fabrik.log.php'));
}

/** @var CMSApplication $app */
$app = Factory::getApplication();
$app->set('jquery', true);
$input = $app->input;

$controllerName = $input->getCmd('view');

// Check for a plugin controller

// Call a plugin controller via the url :
// &controller=visualization.calendar

$isPlugin = false;
$cName = $input->getCmd('controller');

if (StringHelper::strpos($cName, '.') != false)
{
	list($type, $name) = explode('.', $cName);

	if ($type == 'visualization')
	{
		require_once JPATH_COMPONENT . '/controllers/visualization.php';
	}

	$path = JPATH_SITE . '/plugins/fabrik_' . $type . '/' . $name . '/controllers/' . $name . '.php';

	if (File::exists($path))
	{
		require_once $path;
		$isPlugin = true;
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
		$controller = $cName === 'oai' ? $cName : $controllerName;
	}

	$path = JPATH_COMPONENT . '/controllers/' . $controller . '.php';

	if (File::exists($path))
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
	$controller = array_shift($controllerTask);
	$className = 'FabrikController' . StringHelper::ucfirst($controller);
	$path = JPATH_COMPONENT . '/controllers/' . $controller . '.php';

	if (File::exists($path))
	{
		require_once $path;

		// Needed to process J content plugin (form)
		$input->set('view', $controller);
		$task = array_pop($controllerTask);
		$controller = new $className;
	}
	else
	{
		$controller = BaseController::getInstance('Fabrik');
	}
}
else
{
	$className = 'FabrikController' . StringHelper::ucfirst($controller);
	$controller = new $className;
	$task = $input->getCmd('task');
}

if ($isPlugin)
{
	// Add in plugin view
	$controller->addViewPath(JPATH_SITE . '/plugins/fabrik_' . $type . '/' . $name . '/views');

	// Add the model path
	$modelpaths = JModelLegacy::addIncludePath(JPATH_SITE . '/plugins/fabrik_' . $type . '/' . $name . '/models');
}

$package = $input->get('package', 'fabrik');
$app->setUserState('com_fabrik.package', $package);

if ($input->get('yql') == 1)
{
	$opts = array('driver' => 'yql', 'endpoint' => 'https://query.yahooapis.com/v1/public/yql');

	$service = AbstractWebService::getInstance($opts);
	$query = "select * from upcoming.events where location='London'";
	$program = $service->get($query, array(), 'event', null);
}
// End web service testing

$controller->execute($task);

// Redirect if set by the controller
$controller->redirect();
