<?php
/**
 * Joomla! Fabrik cron job plugin
 *
 * @package     Joomla.Plugin
 * @subpackage  System
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Fabrik\Component\Fabrik\Administrator\Table\FabrikTable;
use Fabrik\Component\Fabrik\Administrator\Table\LogTable;
use Fabrik\Component\Fabrik\Site\Model\ListModel;
use Fabrik\Component\Fabrik\Site\Model\PluginManagerModel;
use Fabrik\Component\Fabrik\Site\Plugin\AbstractCronPlugin;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseQuery;
use Fabrik\Helpers\ArrayHelper;
use Fabrik\Helpers\Worker;

/**
 * Joomla! Fabrik cron job plugin
 *
 * @package     Joomla.Plugin
 * @subpackage  System
 * @since       3.0
 */

class PlgSystemFabrikcron extends CMSPlugin
{

	/**
	 * Row for the currently running plugin, used by the shutdown handler
	 *
	 * @var \stdClass
	 *
	 * @since 4.0
	 */
	protected $row = null;

	/**
	 * Log object
	 *
	 * @var LogTable
	 *
	 * @since 4.0
	 */
	protected $log = null;

	/**
	 * @var DatabaseDriver
	 *
	 * @since 4.0
	 */
	protected $db;

	/**
	 * @var CMSApplication
	 *
	 * @since 4.0
	 */
	protected $app;

	/**
	 * @var DatabaseQuery
	 *
	 * @since 4.0
	 */
	protected $query;

	/**
	 * Plugin model
	 *
	 * @var AbstractCronPlugin
	 *
	 * @since 4.0
	 */
	protected $pluginModel = null;

	/**
	 * Reschedule the plugin for next run, and republish
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	protected function reschedule()
	{
		$now = Factory::getDate();
		$now = $now->toUnix();
		$new = Factory::getDate($this->row->nextrun);
		$tmp = $new->toUnix();

		switch ($this->row->unit)
		{
			case 'second':
				$inc = 1;
				break;
			case 'minute':
				$inc = 60;
				break;
			case 'hour':
				$inc = 60 * 60;
				break;
			default:
			case 'day':
				$inc = 60 * 60 * 24;
				break;
		}

		while ($tmp + ($inc * $this->row->frequency) < $now)
		{
			$tmp = $tmp + ($inc * $this->row->frequency);
		}

		// Mark them as being run
		$nextRun = Factory::getDate($tmp);
		$this->query->clear();
		$this->query->update('#__{package}_cron');
		$this->query->set('lastrun = ' . $this->db->quote($nextRun->toSql()));

		if ($this->pluginModel->shouldReschedule() && $this->pluginModel->doRunGating())
		{
			$this->query->set("published = '1'");
		}

		if (!$this->pluginModel->shouldReschedule())
		{
			$this->query->set("published = '0'");
			$this->log->message .= "\nPlugin has unpublished itself";
		}

		$this->query->where('id = ' . $this->row->id);
		$this->db->setQuery($this->query);
		$this->db->execute();
	}

	/**
	 * Catch any fatal errors and log them
	 *
	 * @since 4.0
	 */
	public function shutdownHandler()
	{
		if (@is_array($e = @error_get_last()))
		{
			$code = isset($e['type']) ? $e['type'] : 0;
			$msg  = isset($e['message']) ? $e['message'] : '';
			$file = isset($e['file']) ? $e['file'] : '';
			$line = isset($e['line']) ? $e['line'] : '';

			if ($code > 0)
			{
				$this->log->message = "$code,$msg,$file,$line";
				$this->log->store();
			}

			$this->reschedule();

		}
	}

	/**
	 * Run all active cron jobs
	 *
	 * @return void
	 *
	 * @since 4.0
	 *
	 * @throws \Exception
	 */
	protected function doCron()
	{
		$mailer = Factory::getMailer();
		$config = $this->app->getConfig();
		$input  = $this->app->input;

		if ($this->app->isAdmin() || $input->get('option') == 'com_acymailing')
		{
			return;
		}
		// $$$ hugh - don't want to run on things like AJAX calls
		if ($input->get('format', '') == 'raw')
		{
			return;
		}

		// Get all active tasks
		$this->db    = Worker::getDbo(true);
		$this->query = $this->db->getQuery(true);

		$now = $input->get('fabrikcron_run', false);

		$this->log = FabrikTable::getInstance(LogTable::class);

		if (!$now)
		{
			/* $$$ hugh - changed from using NOW() to Factory::getDate(), to avoid time zone issues, see:
			 * http://fabrikar.com/forums/showthread.php?p=102245#post102245
			 * .. which seems reasonable, as we use getDate() to set 'lastrun' to at the end of this func
			 */

			$nextRun = "CASE "
				. "WHEN unit = 'second' THEN DATE_ADD( lastrun, INTERVAL frequency SECOND )\n"
				. "WHEN unit = 'minute' THEN DATE_ADD( lastrun, INTERVAL frequency MINUTE )\n"
				. "WHEN unit = 'hour' THEN DATE_ADD( lastrun, INTERVAL frequency HOUR )\n"
				. "WHEN unit = 'day' THEN DATE_ADD( lastrun, INTERVAL frequency DAY )\n"
				. "WHEN unit = 'week' THEN DATE_ADD( lastrun, INTERVAL frequency WEEK )\n"
				. "WHEN unit = 'month' THEN DATE_ADD( lastrun, INTERVAL frequency MONTH )\n"
				. "WHEN unit = 'year' THEN DATE_ADD( lastrun, INTERVAL frequency YEAR ) END";

			$this->query
				->select("id, plugin, lastrun, unit, frequency, " . $nextRun . " AS nextrun")
				->from('#__{package}_cron')
				->where("published = '1'")
				->where("$nextRun < '" . Factory::getDate()->toSql() . "'");
		}
		else
		{
			$this->query
				->select('id, plugin')
				->from("#__{package}_cron WHERE published = '1'");
		}

		$this->db->setQuery($this->query);
		$rows = $this->db->loadObjectList();
		if (empty($rows))
		{
			return;
		}

		// register our shutdownHandler(), so we can re-publish and reschedule the event if the script errors out
		register_shutdown_function(array($this, 'shutdownHandler'));

		$this->log->message = '';

		/** @var PluginManagerModel $pluginManager */
		$pluginManager = FabModel::getInstance(PluginManagerModel::class);
		/** @var ListModel $listModel */
		$listModel = FabModel::getInstance(ListModel::class);

		foreach ($rows as $row)
		{
			// assign $row to $this->row, as we may need it in shutdown handling
			$this->row = $row;

			// Load in the plugin
			$this->pluginModel = $pluginManager->getPluginFromId($this->row->id, 'Cron');

			$params                   = $this->pluginModel->getParams();
			$this->log->message       = '';
			$this->log->id            = null;
			$this->log->referring_url = '';

			$this->log->message_type = 'plg.cron.' . $this->row->plugin;
			if (!$this->pluginModel->queryStringActivated())
			{
				continue;
			}

			if ($this->pluginModel->doRunGating())
			{
				$this->query->clear()->update('#__{package}_cron')->set('published = 0')->where('id = ' . $this->db->quote($this->row->id));
				$this->db->setQuery($this->query);
				$this->db->execute();
			}

			$tid           = (int) $params->get('table');
			$thisListModel = clone ($listModel);

			if ($tid !== 0)
			{
				$thisListModel->setId($tid);
				$this->log->message .= "\n\n" . $this->row->plugin . "\n listid = " . $thisListModel->getId();

				if ($this->pluginModel->requiresTableData())
				{
					//$table = $thisListModel->getTable();
					//$total = $thisListModel->getTotalRecords();
					//$nav = $thisListModel->getPagination($total, 0, $total);
					$cron_row_limit = (int) $params->get('cron_row_limit', 100);
					$thisListModel->setLimits(0, $cron_row_limit);
					$thisListModel->getPagination(0, 0, $cron_row_limit);
					$data = $thisListModel->getData();
					// for some reason this hoses up next query
					//$this->log->message .= "\n" . $thisListModel->buildQuery();
				}
			}
			else
			{
				$data = array();
			}

			$this->pluginModel->process($data, $thisListModel);
			$this->log->message = $this->pluginModel->getLog() . "\n\n" . $this->log->message;

			$this->reschedule();

			// Log if asked for
			if ($params->get('log', 0) == 1)
			{
				$this->log->store();
			}

			// Email log message
			$recipient = explode(',', $params->get('log_email', ''));
			if (!ArrayHelper::emptyish($recipient))
			{
				$subject = $config->get('sitename') . ': ' . $this->row->plugin . ' scheduled task';
				$mailer->sendMail($config->get('mailfrom'), $config->get('fromname'), $recipient, $subject, $this->log->message, true);
			}
		}
	}

	/**
	 * Perform the actual cron after the page has rendered
	 *
	 * @return  void
	 *
	 * @since 4.0
	 *
	 * @throws \Exception
	 */
	public function onAfterRender()
	{
		$this->doCron();
	}
}
