<?php
/**
 * Fabrik Notification Model
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.cron.notification
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Plugin\FabrikCron\Notification\Model;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Component\Fabrik\Administrator\Model\FabrikModel;
use Fabrik\Component\Fabrik\Site\Model\FabrikSiteModel;
use Fabrik\Component\Fabrik\Site\Model\ListModel;
use Fabrik\Helpers\Worker;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\Utilities\ArrayHelper;
use Joomla\String\StringHelper;

/**
 * The cron notification plugin model.
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.cron.notification
 * @since       4.0
 */
class NotificationModel extends FabrikSiteModel
{
	/**
	 * Get the current logged in users notifications
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function getUserNotifications()
	{
		$rows = $this->getRows();

		/** @var ListModel $listModel */
		$listModel = FabrikModel::getInstance(ListModel::class);

		foreach ($rows as &$row)
		{
			/*
			 * {observer_name, creator_name, event, record url
			 * dear %s, %s has %s on %s
			 */
			list($listId, $formId, $rowId) = explode('.', $row->reference);

			$listModel->setId($listId);
			$data       = $listModel->getRow($rowId);
			$row->url   = Route::_('index.php?option=com_fabrik&view=details&listid=' . $listId . '&formid=' . $formId . '&rowid=' . $rowId);
			$row->title = $row->url;

			foreach ($data as $key => $value)
			{
				$key = explode('___', $key);
				$key = array_pop($key);
				$k   = StringHelper::strtolower($key);

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
	 *
	 * @since 4.0
	 */
	protected function getRows()
	{
		$db    = Worker::getDbo();
		$query = $db->getQuery(true);
		$query->select('*')->from('#__{package}_notification')->where('user_id = ' . (int) $this->user->get('id'));
		$db->setQuery($query);

		return $db->loadObjectList();
	}

	/**
	 * Delete a notification
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	public function delete()
	{
		// Check for request forgeries
		Session::checkToken() or die('Invalid Token');
		$ids = $this->app->input->get('cid', array());
		$ids = ArrayHelper::toInteger($ids);

		if (empty($ids))
		{
			return;
		}

		$db    = Worker::getDbo();
		$query = $db->getQuery(true);
		$query->delete('#__{package}_notification')->where('id IN (' . implode(',', $ids) . ')');
		$db->setQuery($query);
		$db->execute();
	}

	/**
	 * Load the plugin language files
	 *
	 * @return  bool
	 *
	 * @since 4.0
	 */
	public function loadLang()
	{
		$client   = ApplicationHelper::getClientInfo(0);
		$langFile = 'plg_fabrik_cron_notification';
		$langPath = $client->path . '/plugins/fabrik_cron/notification';

		return $this->lang->load($langFile, $langPath, null, false, false) || $this->lang->load($langFile, $langPath, $this->lang->getDefault(), false, false);
	}

	/**
	 * Get the plugin id
	 *
	 * @return number
	 *
	 * @since 4.0-
	 */
	public function getId()
	{
		return $this->app->input->getInt('id');
	}
}
