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

use Joomla\String\String;

require_once 'autoloader.php';

// Access check.
if (!\JFactory::getUser()->authorise('core.manage', 'com_fabrik'))
{
	throw new Exception(JText::_('JERROR_ALERTNOAUTHOR'));
}

// Load front end language file as well
$lang = \JFactory::getLanguage();
$lang->load('com_fabrik', JPATH_SITE . '/components/com_fabrik');

// Test if the system plugin is installed and published
if (!defined('COM_FABRIK_FRONTEND'))
{
	throw new RuntimeException(JText::_('COM_FABRIK_SYSTEM_PLUGIN_NOT_ACTIVE'), 400);
}

$app   = \JFactory::getApplication();
$input = $app->input;

// Check for plugin views (e.g. list email plugin's "email form"
$cName = $input->getCmd('controller');

if (String::strpos($cName, '.') != false)
{
	// @todo - recheck this for 3.5
	list($type, $name) = explode('.', $cName);

	if ($type == 'visualization')
	{
		require_once JPATH_COMPONENT . '/controllers/visualization.php';
	}

	$path = JPATH_SITE . '/plugins/fabrik_' . $type . '/' . $name . '/controllers/' . $name . '.php';

	if (\JFile::exists($path))
	{
		require_once $path;
		$controller = $type . $name;

		$className  = 'FabrikController' . String::ucfirst($controller);
		$controller = new $className;

		// Add in plugin view
		$controller->addViewPath(JPATH_SITE . '/plugins/fabrik_' . $type . '/' . $name . '/views');

		// Add the model path
		JModelLegacy::addIncludePath(JPATH_SITE . '/plugins/fabrik_' . $type . '/' . $name . '/models');
	}
}
else
{
	// Test if we have a custom controller - if not load the main controller.
	$view                = String::ucfirst($input->get('view', 'Controller'));
	$base                = 'Fabrik\Admin\Controllers';
	$baseControllerClass = $base . '\\Controller';
	$controllerClass     = $base . '\\' . $view;
	$controller          = class_exists($controllerClass) ? new $controllerClass : new $baseControllerClass;
}

// Test that they've published some element plugins!
// @todo move this into a helper
$db    = \JFactory::getDbo();
$query = $db->getQuery(true);
$query->select('COUNT(extension_id)')->from('#__extensions')->where('enabled = 1 AND folder = "fabrik_element"');
$db->setQuery($query);

if (count($db->loadResult()) === 0)
{
	$app->enqueueMessage(JText::_('COM_FABRIK_PUBLISH_AT_LEAST_ONE_ELEMENT_PLUGIN'), 'notice');
}

// Execute the controller.
$controller->execute();
