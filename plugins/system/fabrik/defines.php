<?php
/**
 * Any of these defines can be overwritten by copying this file to
 * plugins/system/fabrik/user_defines.php
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;

// Could be that the sys plugin is installed but fabrik not
if (!Folder::exists(JPATH_SITE . '/components/com_fabrik/'))
{
	return;
}

define("COM_FABRIK_BASE", JPATH_SITE . DIRECTORY_SEPARATOR);
define("COM_FABRIK_FRONTEND", COM_FABRIK_BASE . 'components/com_fabrik');
define("COM_FABRIK_BACKEND", COM_FABRIK_BASE . 'administrator/components/com_fabrik');
define("COM_FABRIK_LIBRARY", COM_FABRIK_BASE . 'libraries/fabrik');
define("COM_FABRIK_LIVESITE", JURI::root());
define("COM_FABRIK_LIVESITE_ROOT", JURI::getInstance()->toString(array('scheme', 'host', 'port')));
define("FABRIKFILTER_TEXT", 0);
define("FABRIKFILTER_EVAL", 1);
define("FABRIKFILTER_QUERY", 2);
define("FABRIKFILTER_NOQUOTES", 3);

/** delimiter used to define separator in csv export */
define("COM_FABRIK_CSV_DELIMITER", ",");
define("COM_FABRIK_EXCEL_CSV_DELIMITER", ";");

/** separator used in repeat elements/groups IS USED IN F3 */
define("GROUPSPLITTER", "//..*..//");

/** @var CMSApplication $app */
$app = Factory::getApplication();
$input = $app->input;

// Avoid errors during update, if plugin has been updated but component hasn't, use old helpers
if (File::exists(COM_FABRIK_FRONTEND . '/helpers/legacy/aliases.php'))
{
	if (!($app->input->get('option', '') === 'com_installer' && $app->input->get('task', '') === 'update.update'))
	{
		require_once COM_FABRIK_FRONTEND . '/helpers/legacy/aliases.php';
	}
}

// Register namespaces for plugins; J4 supports namespaced plugins as of alpha 6 but let's keep it here for now
// @todo convert Fabrik plugins to officially supported namespaces?
$pluginTypes = [
	'fabrik_cron'           => 'FabrikCron',
	'fabrik_element'        => 'FabrikElement',
	'fabrik_form'           => 'FabrikForm',
	'fabrik_list'           => 'FabrikList',
	'fabrik_validationrule' => 'FabrikValidationRule',
	'fabrik_visualization'  => 'FabrikVisualization',
];

$db    = Factory::getDbo();
$query = $db->getQuery(true);
$query->select($db->quoteName(array('element', 'folder')))
	->from($db->quoteName('#__extensions'))
	->where($db->quoteName('folder') . ' LIKE ' . $db->quote('fabrik_%'));
$db->setQuery($query);
$extensions = $db->loadObjectList();

foreach ($extensions as $extension)
{
	$srcPath = sprintf('%s/%s/%s/src', JPATH_PLUGINS, $extension->folder, $extension->element);
	if (Folder::exists($srcPath))
	{
		JLoader::registerNamespace(
			sprintf('Fabrik\\Plugin\\%s\\%s', $pluginTypes[$extension->folder], ucfirst($extension->element)),
			$srcPath,
			false,
			false,
			'psr4'
		);
	}
}