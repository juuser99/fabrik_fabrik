<?php
/**
 * Fabrik Admin Component Helper
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

namespace Fabrik\Admin\Helpers;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\String\String;
use \JFactory as JFactory;
use \JHtmlSidebar as JHtmlSidebar;
use \FText as FText;
use Fabrik\Helpers\Worker;

/**
 * Fabrik Admin Component Helper
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.5
 */
class Fabrik
{
	/**
	 * Test that they've published some element plugins!
	 *
	 * @throws \Exception
	 * @throws \RuntimeException
	 */
	public static function testPublishedPlugins()
	{
		// Access check.
		if (!JFactory::getUser()->authorise('core.manage', 'com_fabrik'))
		{
			throw new \Exception(JText::_('JERROR_ALERTNOAUTHOR'));
		}

		// Test if the system plugin is installed and published
		if (!defined('COM_FABRIK_FRONTEND'))
		{
			throw new \RuntimeException(JText::_('COM_FABRIK_SYSTEM_PLUGIN_NOT_ACTIVE'), 400);
		}

		$db    = JFactory::getDbo();
		$app   = JFactory::getApplication();
		$query = $db->getQuery(true);
		$query->select('COUNT(extension_id)')->from('#__extensions')->where('enabled = 1 AND folder = "fabrik_element"');
		$db->setQuery($query);

		if (count($db->loadResult()) === 0)
		{
			$app->enqueueMessage(JText::_('COM_FABRIK_PUBLISH_AT_LEAST_ONE_ELEMENT_PLUGIN'), 'notice');
		}
	}

	/**
	 * Prepare the date for saving
	 * DATES SHOULD BE SAVED AS UTC
	 *
	 * @param   JRegistry &$data Data to prepare
	 * @param   string    $key   Key to prepare
	 *
	 * @return  JRegistry
	 */
	public static function prepareSaveDate(&$data, $key)
	{
		$strDate = $data->get($key);
		$config  = JFactory::getConfig();
		$offset  = $config->get('offset');
		$db      = Worker::getDbo(true);

		// Handle never unpublished dates
		if (trim($strDate) == FText::_('Never') || trim($strDate) == '' || trim($strDate) == $db->getNullDate())
		{
			$strDate = $db->getNullDate();
		}
		else
		{
			if (String::strlen(trim($strDate)) <= 10)
			{
				$strDate .= ' 00:00:00';
			}

			$date    = JFactory::getDate($strDate, $offset);
			$strDate = $date->toSql();
		}

		$data->set($key, $strDate);

		return $data;
	}

	/**
	 * Gets a list of the actions that can be performed.
	 *
	 * @param   int $categoryId The category ID.
	 *
	 * @since    1.6
	 *
	 * @return    JObject
	 */

	public static function getActions($categoryId = 0)
	{
		$user   = JFactory::getUser();
		$result = new \JObject;

		if (empty($categoryId))
		{
			$assetName = 'com_fabrik';
		}
		else
		{
			$assetName = 'com_fabrik.category.' . (int) $categoryId;
		}

		$actions = array('core.admin', 'core.manage', 'core.create', 'core.edit', 'core.edit.state', 'core.delete');

		foreach ($actions as $action)
		{
			$result->set($action, $user->authorise($action, $assetName));
		}

		return $result;
	}

	/**
	 * Configure the Linkbar.
	 *
	 * @param   string $vName The name of the active view.
	 *
	 * @since    1.6
	 *
	 * @return    void
	 */

	public static function addSubmenu($vName)
	{
		$vizUrl = 'index.php?option=com_fabrik&view=visualizations';

		JHtmlSidebar::addEntry(FText::_('COM_FABRIK_SUBMENU_HOME'), 'index.php?option=com_fabrik', $vName == 'home');
		JHtmlSidebar::addEntry(FText::_('COM_FABRIK_SUBMENU_LISTS'), 'index.php?option=com_fabrik&view=lists', $vName == 'lists');
		JHtmlSidebar::addEntry(FText::_('COM_FABRIK_SUBMENU_FORMS'), 'index.php?option=com_fabrik&view=forms', $vName == 'forms');
		JHtmlSidebar::addEntry(FText::_('COM_FABRIK_SUBMENU_GROUPS'), 'index.php?option=com_fabrik&view=groups', $vName == 'groups');
		JHtmlSidebar::addEntry(FText::_('COM_FABRIK_SUBMENU_ELEMENTS'), 'index.php?option=com_fabrik&view=elements', $vName == 'elements');
		JHtmlSidebar::addEntry(FText::_('COM_FABRIK_SUBMENU_VISUALIZATIONS'), $vizUrl, $vName == 'visualizations');
		JHtmlSidebar::addEntry(FText::_('COM_FABRIK_SUBMENU_CONNECTIONS'), 'index.php?option=com_fabrik&view=connections', $vName == 'connections');
		JHtmlSidebar::addEntry(FText::_('COM_FABRIK_SUBMENU_CRONS'), 'index.php?option=com_fabrik&view=crons', $vName == 'crons');
	}

	/**
	 * Applies the content tag filters to arbitrary text as per settings for current user group
	 *
	 * @param   string $text The string to filter
	 *
	 * @return  string  The filtered string
	 */

	public static function filterText($text)
	{
		// Filter settings
		jimport('joomla.application.component.helper');
		$config     = JComponentHelper::getParams('com_config');
		$user       = JFactory::getUser();
		$userGroups = JAccess::getGroupsByUser($user->get('id'));

		$filters = $config->get('filters');

		$blackListTags       = array();
		$blackListAttributes = array();

		$whiteListTags       = array();
		$whiteListAttributes = array();

		$noHtml     = false;
		$whiteList  = false;
		$blackList  = false;
		$unfiltered = false;

		// Cycle through each of the user groups the user is in.
		// Remember they are include in the Public group as well.
		foreach ($userGroups as $groupId)
		{
			// May have added a group by not saved the filters.
			if (!isset($filters->$groupId))
			{
				continue;
			}

			// Each group the user is in could have different filtering properties.
			$filterData = $filters->$groupId;
			$filterType = String::strtoupper($filterData->filter_type);

			if ($filterType == 'NH')
			{
				// Maximum HTML filtering.
				$noHtml = true;
			}
			elseif ($filterType == 'NONE')
			{
				// No HTML filtering.
				$unfiltered = true;
			}
			else
			{
				// Black or white list.
				// Pre-process the tags and attributes.
				$tags           = explode(',', $filterData->filter_tags);
				$attributes     = explode(',', $filterData->filter_attributes);
				$tempTags       = array();
				$tempAttributes = array();

				foreach ($tags as $tag)
				{
					$tag = trim($tag);

					if ($tag)
					{
						$tempTags[] = $tag;
					}
				}

				foreach ($attributes as $attribute)
				{
					$attribute = trim($attribute);

					if ($attribute)
					{
						$tempAttributes[] = $attribute;
					}
				}

				// Collect the black or white list tags and attributes.
				// Each list is cumulative.
				if ($filterType == 'BL')
				{
					$blackList           = true;
					$blackListTags       = array_merge($blackListTags, $tempTags);
					$blackListAttributes = array_merge($blackListAttributes, $tempAttributes);
				}
				elseif ($filterType == 'WL')
				{
					$whiteList           = true;
					$whiteListTags       = array_merge($whiteListTags, $tempTags);
					$whiteListAttributes = array_merge($whiteListAttributes, $tempAttributes);
				}
			}
		}

		// Remove duplicates before processing (because the black list uses both sets of arrays).
		$blackListTags       = array_unique($blackListTags);
		$blackListAttributes = array_unique($blackListAttributes);
		$whiteListTags       = array_unique($whiteListTags);
		$whiteListAttributes = array_unique($whiteListAttributes);

		// Unfiltered assumes first priority.
		if ($unfiltered)
		{
			// Don't apply filtering.
		}
		else
		{
			// Black lists take second precedence.
			if ($blackList)
			{
				// Remove the white-listed attributes from the black-list.
				$tags   = array_diff($blackListTags, $whiteListTags);
				$attrs  = array_diff($blackListAttributes, $whiteListAttributes);
				$filter = JFilterInput::getInstance($tags, $attrs, 1, 1);
			}
			// White lists take third precedence.
			elseif ($whiteList)
			{
				$filter = JFilterInput::getInstance($whiteListTags, $whiteListAttributes, 0, 0, 0);
			}
			// No HTML takes last place.
			else
			{
				$filter = JFilterInput::getInstance();
			}

			$text = $filter->clean($text, 'html');
		}

		return $text;
	}
}
