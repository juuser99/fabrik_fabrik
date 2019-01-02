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

use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;

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

// Register the element class with the loader
JLoader::register('JElement', JPATH_SITE . '/administrator/components/com_fabrik/element.php');

// Avoid errors during update, if plugin has been updated but component hasn't, use old helpers
if (File::exists(COM_FABRIK_FRONTEND . '/helpers/legacy/aliases.php'))
{
	if (!($app->input->get('option', '') === 'com_installer' && $app->input->get('task', '') === 'update.update'))
	{
		require_once COM_FABRIK_FRONTEND . '/helpers/legacy/aliases.php';
	}
}