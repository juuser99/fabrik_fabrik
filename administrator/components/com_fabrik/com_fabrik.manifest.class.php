<?php
/**
 * Fabrik: Installer Manifest Class
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Installer manifest class
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.0
 */

class Com_FabrikInstallerScript
{
	/**
	 * Drivers
	 *
	 * @var array
	 */
	protected $drivers = array('mysql_fab.php', 'mysqli_fab.php');

	/**
	 * Run when the component is installed
	 *
	 * @param   object  $parent  installer object
	 *
	 * @return bool
	 */

	public function install($parent)
	{
		$parent->getParent()->setRedirectURL('index.php?option=com_fabrik');
		return true;
	}

	/**
	 * Check if there is a connection already installed if not create one
	 * by copying over the site's default connection
	 *
	 * @return  bool
	 */

	protected function setConnection()
	{
		$db = JFactory::getDbo();
		$app = JFactory::getApplication();
		$row = new stdClass;
		$row->host = $app->getCfg('host');
		$row->user = $app->getCfg('user');
		$row->password = $app->getCfg('password');
		$row->database = $app->getCfg('db');
		$row->description = 'site database';
		$row->published = 1;
		$row->default = 1;
		// FIXME - jsonify
		$res = $db->insertObject('#__fabrik_connections', $row, 'id');

		return $res;
	}

	/**
	 * Test to ensure that the main component params have a default setup
	 *
	 * @return  bool
	 */

	protected function setDefaultProperties()
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('extension_id, params')->from('#__extensions')->where('name = ' . $db->q('fabrik'))
			->where('type = ' . $db->q('component'));
		$db->setQuery($query);
		$row = $db->loadObject();
		$opts = new stdClass;
		$opts->fbConf_wysiwyg_label = 0;
		$opts->fbConf_alter_existing_db_cols = 0;
		$opts->spoofcheck_on_formsubmission = 0;

		if ($row && ($row->params == '{}' || $row->params == ''))
		{
			$json = $row->params;
			$query = $db->getQuery(true);
			$query->update('#__extensions')->set('params = ' . $db->q($json))->where('extension_id = ' . (int) $row->extension_id);
			$db->setQuery($query);

			if (!$db->execute())
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Move over files into Joomla libraries folder
	 *
	 * @param   object  &$installer  installer
	 * @param   bool    $upgrade     upgrade
	 *
	 * @return  bool
	 */

	protected function moveFiles(&$installer, $upgrade = false)
	{
		jimport('joomla.filesystem.file');
		$componentFrontend = 'components/com_fabrik';
		$docTypes = array('fabrikfeed', 'pdf');

		foreach ($docTypes as $docType)
		{
			$destination = 'libraries/joomla/document/' . $docType;

			if (!JFolder::exists(JPATH_ROOT . '/' . $destination))
			{
				JFolder::create(JPATH_ROOT . '/' . $destination);
			}
			// $$$ hugh - have to use false as last arg (use_streams) on JFolder::copy(), otherwise
			// it bypasses FTP layer, and will fail if web server does not have write access to J! folders
			$moveRes = JFolder::copy($componentFrontend . '/' . $docType, $destination, JPATH_SITE, true, false);

			if ($moveRes !== true)
			{
				echo "<p style=\"color:red\">failed to moved " . $componentFrontend . '/fabrikfeed to ' . $destination . '</p>';

				return false;
			}
		}

		$destination = 'libraries/joomla/database/database';
		$driverInstallLoc = $componentFrontend . '/dbdriver/';
		$moveRes = JFolder::copy($driverInstallLoc, $destination, JPATH_SITE, true, false);

		if ($moveRes !== true)
		{
			echo "<p style=\"color:red\">failed to moved " . $driverInstallLoc . ' to ' . $destination . '</p>';

			return false;
		}

		// Joomla 3.0 db drivers and queries
		$destination = 'libraries/joomla/database/driver';
		$driverInstallLoc = $componentFrontend . '/driver/';

		$moveRes = JFolder::copy($driverInstallLoc, $destination, JPATH_SITE, true, false);

		if ($moveRes !== true)
		{
			echo "<p style=\"color:red\">failed to moved " . $driverInstallLoc . ' to ' . $destination . '</p>';

			return false;
		}

		$destination = 'libraries/joomla/database/query';
		$driverInstallLoc = $componentFrontend . '/query/';
		$moveRes = JFolder::copy($driverInstallLoc, $destination, JPATH_SITE, true, false);

		if ($moveRes !== true)
		{
			echo "<p style=\"color:red\">failed to moved " . $driverInstallLoc . ' to ' . $destination . '</p>';

			return false;
		}

		return true;
	}

	/**
	 * Run when the component is uninstalled.
	 *
	 * @param   object  $parent  installer object
	 *
	 * @return  void
	 */

	public function uninstall($parent)
	{
		jimport('joomla.filesystem.folder');
		jimport('joomla.filesystem.file');
		$destination = JPATH_SITE . '/libraries/joomla/document/fabrikfeed';

		if (JFolder::exists($destination))
		{
			if (!JFolder::delete($destination))
			{
				return false;
			}
		}

		$destination = JPATH_SITE . '/libraries/joomla/database/database';

		foreach ($this->drivers as $driver)
		{
			if (JFile::exists($destination . '/' . $driver))
			{
				JFile::delete($destination . '/' . $driver);
			}
		}
	}

	/**
	 * God knows why but install component, uninstall component and install
	 * again and component_id is set to 0 for the menu items!
	 *
	 * @return  void
	 */

	protected function fixmMenuComponentId()
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('extension_id')->from('#__extensions')->where('element =  "com_fabrik"');
		$db->setQuery($query);
		$id = $db->loadResult();
		$query->clear();
		$query->update('#__menu')->set('component_id = ' . $id)->where('path LIKE "fabrik%"');
		$db->setQuery($query)->execute();
	}

	/**
	 * Run when the component is updated
	 *
	 * @param   object  $parent  installer object
	 *
	 * @return  bool
	 */

	public function update($parent)
	{
		/*if (!$this->moveFiles($parent, true)) {
		    return false;
		} else {
		    echo "<p style=\"color:green\">Libray files moved</p>";
		}*/

		return true;
	}

	/**
	 * Run before installation or upgrade run
	 *
	 * @param   string  $type    discover_install (Install unregistered extensions that have been discovered.)
	 *  or install (standard install)
	 *  or update (update)
	 * @param   object  $parent  installer object
	 *
	 * @return  void
	 */

	public function preflight($type, $parent)
	{
	}

	/**
	 * Run after installation or upgrade run
	 *
	 * @param   string  $type    discover_install (Install unregistered extensions that have been discovered.)
	 *  or install (standard install)
	 *  or update (update)
	 * @param   object  $parent  installer object
	 *
	 * @return  bool
	 */

	public function postflight($type, $parent)
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);

		// Remove old update site & Fabrik 3.0.x update site
		$where = "location LIKE '%update/component/com_fabrik%' OR location = 'http://fabrikar.com/update/fabrik/package_list.xml'";
		$query->delete('#__update_sites')->where($where);
		$db->setQuery($query);

		if (!$db->execute())
		{
			echo "<p>didnt remove old update site</p>";
		}
		else
		{
			echo "<p style=\"color:green\">removed old update site</p>";
		}

		$db
			->setQuery(
				"UPDATE #__extensions SET enabled = 1 WHERE type = 'plugin' AND (folder LIKE 'fabrik_%' OR (folder='system' AND element = 'fabrik')  OR (folder='content' AND element = 'fabrik'))");
		$db->execute();
		$this->fixmMenuComponentId();

		if ($type !== 'update')
		{
			if (!$this->setConnection())
			{
				echo "<p style=\"color:red\">Didn't set connection. Aborting installation</p>";
				exit;

				return false;
			}
		}

		echo "<p style=\"color:green\">Default connection created</p>";

		if (!$this->moveFiles($parent))
		{
			echo "<p style=\"color:red\">Unable to move library files. Aborting installation</p>";
			exit;

			return false;
		}
		else
		{
			echo "<p style=\"color:green\">Libray files moved</p>";
		}

		if ($type !== 'update')
		{
			if (!$this->setDefaultProperties())
			{
				echo "<p>couldnt set default properties</p>";
				exit;

				return false;
			}
		}

		echo "<p>Installation finished</p>";
		echo '<p><a target="_top" href="index.php?option=com_fabrik&amp;task=home.installSampleData">Click
here to install sample data</a></p>
	  ';

		// An example of setting a redirect to a new location after the install is completed
		// $parent->getParent()->set('redirect_url', 'http://www.google.com');

	}
}
