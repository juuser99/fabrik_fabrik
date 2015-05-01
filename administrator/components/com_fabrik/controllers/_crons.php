<?php
/**
 * Cron list controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0
 */

namespace Fabrik\Admin\Controllers;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Utilities\ArrayHelper;
use Fabrik\Helpers\Worker;

/**
 * Cron list controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.5
 */
class Crons extends \JControllerBase
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var	string
	 */
	protected $text_prefix = 'COM_FABRIK_CRONS';

	/**
	 * View item name
	 *
	 * @var string
	 */
	protected $view_item = 'crons';

	/**
	 * Execute the controller.
	 *
	 * @return  boolean  True if controller finished execution, false if the controller did not
	 *                   finish execution. A controller might return false if some precondition for
	 *                   the controller to run has not been satisfied.
	 *
	 * @since   12.1
	 * @throws  LogicException
	 * @throws  RuntimeException
	 */
	public function execute()
	{
		// Get the application
		$app = $this->getApplication();

		// Get the document object.
		$document = \JFactory::getDocument();

		$viewName   = $app->input->getWord('view', 'lists');
		$viewFormat = $document->getType();
		$layoutName = $app->input->getWord('layout', 'bootstrap');
		$app->input->set('view', $viewName);

		// Register the layout paths for the view
		$paths = new \SplPriorityQueue;
		$paths->insert(JPATH_COMPONENT . '/views/' . $viewName . '/tmpl', 'normal');

		$viewClass  = 'Fabrik\Admin\Views\\' . ucfirst($viewName) . '\\' . ucfirst($viewFormat);
		$modelClass = 'Fabrik\Admin\Models\\' . ucfirst($viewName);

		$view = new $viewClass(new $modelClass, $paths);
		$view->setLayout($layoutName);
		// Render our view.
		echo $view->render();

		return true;
	}

	/**
	 * Run the selected cron plugins
	 *
	 * @return  void
	 */

	/*public function run()
	{
		$mailer = JFactory::getMailer();
		$config = JFactory::getConfig();
		$db = Worker::getDbo(true);
		$input = $this->app->input;
		$cid = $input->get('cid', array(), 'array');
		ArrayHelper::toInteger($cid);
		$cid = implode(',', $cid);
		$query = $db->getQuery(true);
		$query->select('*')->from('#__fabrik_cron')->where('id IN (' . $cid . ')');
		$db->setQuery($query);
		$rows = $db->loadObjectList();
		$adminListModel = JModelLegacy::getInstance('List', 'FabrikAdminModel');
		$pluginManager = JModelLegacy::getInstance('Pluginmanager', 'FabrikFEModel');
		$listModel = JModelLegacy::getInstance('list', 'FabrikFEModel');
		$c = 0;
		$log = FabTable::getInstance('Log', 'FabrikTable');

		foreach ($rows as $row)
		{
			// Load in the plugin
			$rowParams = json_decode($row->params);
			$log->message = '';
			$log->id = null;
			$log->referring_url = '';
			$log->message_type = 'plg.cron.' . $row->plugin;
			$plugin = $pluginManager->getPlugIn($row->plugin, 'cron');
			$table = FabTable::getInstance('cron', 'FabrikTable');
			$table->load($row->id);
			$plugin->setRow($table);
			$thisListModel = clone ($listModel);
			$thisAdminListModel = clone ($adminListModel);
			$tid = (int) $rowParams->table;

			if ($tid !== 0)
			{
				$thisListModel->setId($tid);
				$log->message .= "\n\n$row->plugin\n listid = " . $thisListModel->getId();

				if ($plugin->requiresTableData())
				{
					$thisListModel->setLimits(0, 0);
					$thisListModel->getPagination(0, 0, 0);
					$data = $thisListModel->getData();
					$log->message .= "\n" . $thisListModel->buildQuery();
				}
			}
			else
			{
				$data = array();
			}
			// $$$ hugh - added table model param, in case plugin wants to do further table processing
			$c = $c + $plugin->process($data, $thisListModel, $thisAdminListModel);

			$log->message = $plugin->getLog() . "\n\n" . $log->message;

			if ($plugin->getParams()->get('log', 0) == 1)
			{
				$log->store();
			}

			// Email log message
			$recipient = $plugin->getParams()->get('log_email', '');

			if ($recipient != '')
			{
				$recipient = explode(',', $recipient);
				$subject = $config->get('sitename') . ': ' . $row->plugin . ' scheduled task';
				$mailer->sendMail($config->get('mailfrom'), $config->get('fromname'), $recipient, $subject, $log->message, true);
			}
		}

		$this->setRedirect('index.php?option=com_fabrik&view=crons', $c . ' records updated');
	}*/
}
