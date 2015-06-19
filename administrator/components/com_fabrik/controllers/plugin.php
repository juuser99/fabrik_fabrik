<?php
/**
 * Fabrik Admin Plugin Controller
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

namespace Fabrik\Admin\Controllers;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Admin\Models\Lizt;
use Fabrik\Helpers\String;
use Joomla\Utilities\ArrayHelper;
use Fabrik\Helpers\Worker;

/**
 * Fabrik Admin Plugin Controller
 *
 * @package  Fabrik
 * @since    3.5
 */
class Plugin extends Controller
{
	/**
	 * Id used from content plugin when caching turned on to ensure correct element rendered)
	 *
	 * @var int
	 */
	public $cacheId = 0;

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
		// @todo move userAjax and doCron into their own controllers. FIXME
		list($viewName, $layoutName) = $this->viewLayout();

		switch ($layoutName)
		{
			case 'pluginAjax':
				break;
			default:
				parent::execute();
				break;
		}

	}


	/**
	 * Custom user ajax class handling as per F1.0.x
	 *
	 * @return   void
	 */

	public function userAjax()
	{
		$db = Worker::getDbo();
		require_once COM_FABRIK_FRONTEND . '/user_ajax.php';
		$method = $this->input->get('method', '');
		$userAjax = new userAjax($db);

		if (method_exists($userAjax, $method))
		{
			$userAjax->$method();
		}
	}

	/**
	 * Run cron plugin
	 *
	 * @param   object  &$pluginManager  Plugin manager
	 *
	 * @return  void
	 */

	public function doCron(&$pluginManager)
	{
		$db = Worker::getDbo();
		$cid = $this->input->get('element_id', array(), 'array');
		ArrayHelper::toInteger($cid);

		if (empty($cid))
		{
			return;
		}

		$query = $db->getQuery();
		$query->select('id, plugin')->from('#__fabrik_cron');

		if (!empty($cid))
		{
			$query->where(' id IN (' . implode(',', $cid) . ')');
		}

		$db->setQuery($query);
		$rows = $db->loadObjectList();
		$listModel = new Lizt;
		$c = 0;

		foreach ($rows as $row)
		{
			// Load in the plugin
			$plugin = $pluginManager->getPlugIn($row->plugin, 'cron');
			$plugin->setId($row->id);
			$params = $plugin->getParams();

			$thisListModel = clone ($listModel);
			$thisListModel->setId($params->get('table'));
			$table = $listModel->getTable();

			/* $$$ hugh @TODO - really think we need to add two more options to the cron plugins
			 * 1) "Load rows?" because it really may not be practical to load ALL rows into $data
			 * on large tables, and the plugin itself may not need all data.
			 * 2) "Bypass prefilters" - I think we need a way of bypassing pre-filters for cron
			 * jobs, as they are run with access of whoever happened to hit the page at the time
			 * the cron was due to run, so it's pot luck as to what pre-filters get applied.
			 */
			$total = $thisListModel->getTotalRecords();
			$nav = $thisListModel->getPagination($total, 0, $total);
			$data = $thisListModel->getData();

			// $$$ hugh - added table model param, in case plugin wants to do further table processing
			$c = $c + $plugin->process($data, $thisListModel);
		}

		$query = $db->getQuery();
		$query->update('#__fabrik_cron')->set('lastrun=NOW()')->where('id IN (' . implode(',', $cid) . ')');
		$db->setQuery($query);
		$db->execute();
	}
}
