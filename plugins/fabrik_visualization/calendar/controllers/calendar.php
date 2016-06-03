<?php
/**
 * Fabrik Calendar Plug-in Controller
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.calendar
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Plugins\Visualization\Calendar;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\StringHelper;
use \Fabrik\Controllers\Visualization as VizController;
use \JComponentHelper;
use \JFactory;
use \JModelLegacy;

/**
 * Fabrik Calendar Plug-in Controller
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.calendar
 * @since       3.0
 */
class Controller extends VizController
{
	/**
	 * Delete an event
	 *
	 * @return  void
	 */
	public function deleteEvent()
	{
		$model = $this->getModel('calendar');
		$model->deleteEvent();
		$this->getEvents();
	}

	/**
	 * Get events
	 *
	 * @return  void
	 */
	public function getEvents()
	{
		$viewName = 'calendar';
		$app = JFactory::getApplication();
		$input = $app->input;
		$usersConfig = JComponentHelper::getParams('com_fabrik');
		$model = &$this->getModel($viewName);
		$id = $input->getInt('id', $usersConfig->get('visualizationid', $input->getInt('visualizationid', 0)));
		$model->setId($id);
		echo $model->getEvents();
	}

	/**
	 * Choose which list to add the event to
	 *
	 * @return  void
	 */
	public function chooseaddevent()
	{
		$document = JFactory::getDocument();
		$viewName = 'calendar';

		$viewType = $document->getType();

		// Set the default view name from the Request
		$view = $this->getView($viewName, $viewType);

		$formModel = $this->getModel('Form', 'FabrikFEModel');
		$view->setModel($formModel);

		// Push a model into the view
		$model = $this->getModel($viewName);
		$view->setModel($model, true);
		$view->chooseaddevent();
	}

	/**
	 * Show the add event form
	 *
	 * @return  void
	 */
	public function addEvForm()
	{
		$app = JFactory::getApplication();
		$package = $app->getUserState('com_fabrik.package', 'fabrik');
		$input = $app->input;
		$listId = $input->getInt('listid');
		$viewName = 'calendar';
		$usersConfig = JComponentHelper::getParams('com_fabrik');
		$model = $this->getModel($viewName);
		$id = $input->getInt('visualizationid', $usersConfig->get('visualizationid', 0));
		$model->setId($id);
		$model->setupEvents();
		$config = JFactory::getConfig();
		$prefix = $config->get('dbprefix');

		if (array_key_exists($listId, $model->events))
		{
			$dateField = $model->events[$listId][0]['startdate'];
		}
		else
		{
			$dateField = $prefix . 'fabrik_calendar_events___start_date';
		}

		$dateField = StringHelper::safeColNameToArrayKey($dateField);
		$rowId = $input->getString('rowid', '');
		$listModel = JModelLegacy::getInstance('list', 'FabrikFEModel');
		$listModel->setId($listId);
		$table = $listModel->getTable();
		$input->set('view', 'form');
		$input->set('formid', $table->form_id);
		$input->set('tmpl', 'component');
		$input->set('ajax', '1');
		$nextView = $input->get('nextview', 'form');
		$link = 'index.php?option=com_' . $package . '&view=' . $nextView . '&formid=' . $table->form_id . '&rowid=' . $rowId . '&tmpl=component&ajax=1';
		$link .= '&' . $prefix . 'fabrik_calendar_events___visualization_id=' . $input->getInt($prefix . 'fabrik_calendar_events___visualization_id');
		$link .= '&fabrik_window_id=' . $input->get('fabrik_window_id');

		$start_date = $input->getString('start_date', '');

		if (!empty($start_date))
		{
			$link .= '&' . $dateField . '=' . $start_date;
		}

		// $$$ rob have to add this to stop the calendar filtering itself after adding an new event?
		$link .= '&clearfilters=1';
		$this->setRedirect($link);
	}
}
