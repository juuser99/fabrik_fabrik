<?php
/**
 * Fabrik Notification Model
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.cron.notification
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\String;
use Joomla\Utilities\ArrayHelper;
use Fabrik\Helpers\Worker;

jimport('joomla.application.component.model');

/**
 * The cron notification plugin model.
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.cron.notification
 * @since       3.0
 */

class FabrikModelNotification extends JModelLegacy
{
	/**
	 * Get the current logged in users notifications
	 *
	 * @return array
	 */

	public function getUserNotifications()
	{
		$rows = $this->getRows();

		if (!$rows)
		{
			$this->makeDbTable();
			$rows = $this->getRows();
		}

		$listModel = new \Fabrik\Admin\Models\Lizt;;

		foreach ($rows as &$row)
		{
			/*
			 * {observer_name, creator_name, event, record url
			 * dear %s, %s has %s on %s
			 */
			list($listId, $formid, $rowId) = explode('.', $row->reference);

			$listModel->setId($listId);
			$data = $listModel->getRow($rowId);
			$row->url = JRoute::_('index.php?option=com_fabrik&view=details&listid=' . $listId . '&formid=' . $formid . '&rowid=' . $rowId);
			$row->title = $row->url;

			foreach ($data as $key => $value)
			{
				$key = explode('___', $key);
				$key = array_pop($key);
				$k = String::strtolower($key);

				if ($k == 'title')
				{
					$row->title = $value;
				}
			}
		}

		return $rows;
	}

	/**
	 * Get Rows
	 *
	 * @return  array
	 */

	protected function getRows()
	{
		$db = Worker::getDbo();
		$query = $db->getQuery(true);
		$query->select('*')->from('#__fabrik_notification')->where('user_id = ' . (int) $this->user->get('id'));
		$db->setQuery($query);

		return $db->loadObjectList();
	}

	/**
	 * Delete a notification
	 *
	 * @return  void
	 */

	public function delete()
	{
		// Check for request forgeries
		JSessoin::checkToken() or die('Invalid Token');
		$ids = $this->app->input->get('cid', array());
		ArrayHelper::toInteger($ids);

		if (empty($ids))
		{
			return;
		}

		$db = Worker::getDbo();
		$query = $db->getQuery(true);
		$query->delete('#__fabrik_notification')->where('id IN (' . implode(',', $ids) . ')');
		$db->setQuery($query);
		$db->execute();
	}

	/**
	 * Load the plugin language files
	 *
	 * @return  bool
	 */

	public function loadLang()
	{
		$lang = $this->language;
		$client = JApplicationHelper::getClientInfo(0);
		$langFile = 'plg_fabrik_cron_notification';
		$langPath = $client->path . '/plugins/fabrik_cron/notification';

		return $lang->load($langFile, $langPath, null, false, false) || $lang->load($langFile, $langPath, $lang->getDefault(), false, false);
	}

	/**
	 * Get the plugin id
	 *
	 * @return number
	 */

	public function getId()
	{
		return $this->app->input->getInt('id');
	}
}
