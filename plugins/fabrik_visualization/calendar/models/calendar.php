<?php
/**
 * Fabrik Calendar Plug-in Model
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.calendar
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\ArrayHelper;
use Fabrik\Helpers\Worker;
use Fabrik\Admin\Models\Lizt;
use Fabrik\Admin\Models\Visualization;
use Fabrik\Helpers\String;
use Fabrik\Helpers\Text;

jimport('joomla.application.component.model');

require_once JPATH_SITE . '/components/com_fabrik/models/visualization.php';

/**
 * Fabrik Calendar Plug-in Model
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.calendar
 * @since       3.0
 */

class FabrikModelCalendar extends Visualization
{
	/**
	 * Array of Fabrik lists containing events
	 *
	 * @var array
	 */
	protected $eventLists = null;

	/**
	 * JS name for calendar
	 *
	 * @var string
	 */
	protected $calName = null;

	/**
	 * Event info
	 *
	 * @var array
	 */
	public $events = null;

	/**
	 * Filters from url
	 *
	 * @var array
	 */
	public $filters = array();

	/**
	 * Can add events to lists
	 *
	 * @var bool
	 */
	public $canAdd = null;

	/**
	 * Set an array of list id's whose data is used inside the visualization
	 *
	 * @return  void
	 */

	protected function setListIds()
	{
		if (!isset($this->listids))
		{
			$this->listids = (array) $this->getParams()->get('calendar_table');
			ArrayHelper::toInteger($this->listids);
		}
	}

	/**
	 * Get the lists that contain events
	 *
	 * @return array
	 */

	public function &getEventLists()
	{
		if (is_null($this->eventLists))
		{
			$this->eventLists = array();
			$db = Worker::getDbo(true);
			$params = $this->getParams();
			$lists = (array) $params->get('calendar_table');
			ArrayHelper::toInteger($lists);
			$dateFields = (array) $params->get('calendar_startdate_element');
			$dateFields2 = (array) $params->get('calendar_enddate_element');
			$labels = (array) $params->get('calendar_label_element');
			$stati = (array) $params->get('status_element');
			$colours = (array) $params->get('colour');

			$query = $db->getQuery(true);
			$query->select('id AS value, label AS text')->from('#__fabrik_lists')->where('id IN (' . implode(',', $lists) . ')');
			$db->setQuery($query);
			$rows = $db->loadObjectList();

			for ($i = 0; $i < count($rows); $i++)
			{
				if (!isset($colours[$i]))
				{
					$colours[$i] = '';
				}

				if (!isset($stati[$i]))
				{
					$stati[$i] = '';
				}

				$rows[$i]->startdate_element = $dateFields[$i];
				$rows[$i]->enddate_element = ArrayHelper::getValue($dateFields2, $i);
				$rows[$i]->label_element = $labels[$i];
				$rows[$i]->status = ArrayHelper::getValue($stati, $i, '');
				$rows[$i]->colour = $colours[$i];
			}

			$this->eventLists = $rows;
		}

		return $this->eventLists;
	}

	/**
	 * Get Standard Event Form Info
	 *
	 * @return mixed unknown|NULL
	 */
	public function getAddStandardEventFormInfo()
	{
		$config = JFactory::getConfig();
		$prefix = $config->get('dbprefix');
		$params = $this->getParams();
		$db = Worker::getDbo();
		$db->setQuery("SELECT form_id, id FROM #__fabrik_lists WHERE db_table_name = '{$prefix}fabrik_calendar_events' AND private = '1'");
		$o = $db->loadObject();

		if (is_object($o))
		{
			// There are standard events recorded
			return $o;
		}
		else
		{
			// They aren't any standards events recorded
			return null;
		}
	}

	/**
	 * Save the calendar
	 *
	 * @return  boolean False if not saved, otherwise id of saved calendar
	 */

	public function save()
	{
		$user = JFactory::getUser();
		$app = JFactory::getApplication();
		$input = $app->input;
		$filter = JFilterInput::getInstance();
		$post = $filter->clean($_POST, 'array');
		$this->bind($post);

		$params = $input->get('params', array(), 'array');
		$this->params = json_encode($params);

		if ($this->id == 0)
		{
			$this->created = date('Y-m-d H:i:s');
			$this->created_by = $user->get('id');
		}
		else
		{
			$this->modified = date('Y-m-d H:i:s');
			$this->modified_by = $user->get('id');
		}

		$this->check();
		$this->store();
		$this->checkin();

		return $this->id;
	}

	/**
	 * Set up calendar events
	 *
	 * @return  array
	 */

	public function setupEvents()
	{
		if (is_null($this->events))
		{
			$params = $this->getParams();
			$tables = (array) $params->get('calendar_table');
			$table_label = (array) $params->get('calendar_label_element');
			$table_startdate = (array) $params->get('calendar_startdate_element');
			$table_enddate = (array) $params->get('calendar_enddate_element');
			$customUrls = (array) $params->get('custom_url');
			$colour = (array) $params->get('colour');
			$legend = (array) $params->get('legendtext');
			$stati = (array) $params->get('status_element');

			$this->events = array();

			for ($i = 0; $i < count($tables); $i++)
			{
				$listModel = new Lizt;

				if ($tables[$i] != 'undefined')
				{
					$listModel->setId($tables[$i]);
					$table = $listModel->getTable();
					$endDate = ArrayHelper::getValue($table_enddate, $i, '');
					$startDate = ArrayHelper::getValue($table_startdate, $i, '');

					$startShowTime = true;
					$startDateEl = $listModel->getFormModel()->getElement($startDate);

					if ($startDateEl !== false)
					{
						$startShowTime = $startDateEl->getParams()->get('date_showtime', true);
					}

					$endShowTime = true;

					if ($endDate !== '')
					{
						$endDateEl = $listModel->getFormModel()->getElement($endDate);

						if ($endDateEl !== false)
						{
							$endShowTime = $endDateEl->getParams()->get('date_showtime', true);
						}
					}

					if (!isset($colour[$i]))
					{
						$colour[$i] = '';
					}

					if (!isset($legend[$i]))
					{
						$legend[$i] = '';
					}

					if (!isset($table_label[$i]))
					{
						$table_label[$i] = '';
					}

					$customUrl = ArrayHelper::getValue($customUrls, $i, '');
					$status = ArrayHelper::getValue($stati, $i, '');
					$this->events[$tables[$i]][] = array('startdate' => $startDate, 'enddate' => $endDate, 'startShowTime' => $startShowTime,
						'endShowTime' => $endShowTime, 'label' => $table_label[$i], 'colour' => $colour[$i], 'legendtext' => $legend[$i],
						'formid' => $table->form_id, 'listid' => $tables[$i], 'customUrl' => $customUrl, 'status' => $status);
				}
			}
		}

		return $this->events;
	}

	/**
	 * Get the linked form IDs
	 *
	 * @return array
	 */

	public function getLinkedFormIds()
	{
		$this->setUpEvents();
		$return = array();

		foreach ($this->events as $arr)
		{
			foreach ($arr as $a)
			{
				$return[] = $a['formid'];
			}
		}

		return array_unique($return);
	}

	/**
	 * Go over all the lists whose data is displayed in the calendar
	 * if any element is found in the request data, assign it to the session
	 * This will then be used by the table to filter its data.
	 * nice :)
	 *
	 * @return  void
	 */

	public function setRequestFilters()
	{
		$this->setupEvents();
		$filter = JFilterInput::getInstance();
		$request = $filter->clean($_REQUEST, 'array');
		$listModel = new Lizt;

		foreach ($this->events as $listId => $record)
		{
			$listModel->setId($listId);
			$listModel->getTable();

			foreach ($request as $key => $val)
			{
				if ($listModel->hasElement($key))
				{
					$o = new stdClass;
					$o->key = $key;
					$o->val = $val;
					$this->filters[] = $o;
				}
			}
		}
	}

	/**
	 * Can the user add a record into the calendar
	 *
	 * @return  bool
	 */

	public function getCanAdd()
	{
		if (!isset($this->canAdd))
		{
			$params = $this->getParams();
			$lists = (array) $params->get('calendar_table');

			foreach ($lists as $id)
			{
				$listModel = new Lizt;
				$listModel->setId($id);

				if (!$listModel->canAdd())
				{
					$this->canAdd = false;

					return false;
				}
			}

			$this->canAdd = true;
		}

		return $this->canAdd;
	}

	/**
	 * Get an array of list ids for which the current user has delete records rights
	 *
	 * @return  array	List ids.
	 */

	public function getDeleteAccess()
	{
		$deleteables = array();
		$params = $this->getParams();
		$lists = (array) $params->get('calendar_table');

		foreach ($lists as $id)
		{
			$listModel = new Lizt;
			$listModel->setId($id);

			if ($listModel->canDelete())
			{
				$deleteables[] = $id;
			}
		}

		return $deleteables;
	}

	/**
	 * Query all tables linked to the calendar and return them
	 *
	 * @return  string	javascript array containing json objects
	 */

	public function getEvents()
	{
		$app = JFactory::getApplication();
		$package = $app->getUserState('com_fabrik.package', 'fabrik');
		$itemId = Worker::itemId();
		$config = JFactory::getConfig();
		$tzoffset = $config->get('offset');
		$tz = new DateTimeZone($tzoffset);
		$w = new Worker;
		$this->setupEvents();
		$calendar = $this->getRow();
		$aLegend = "$this->calName.addLegend([";
		$jsevents = array();
		$input = $app->input;
		$where = $input->get('where', array(), 'array');

		foreach ($this->events as $listId => $record)
		{
			$this_where = ArrayHelper::getValue($where, $listId, '');
			$listModel = new Lizt;
			$listModel->setId($listId);

			if (!$listModel->canView())
			{
				continue;
			}

			$table = $listModel->getTable();
			$els = $listModel->getElements();
			$formModel = $listModel->getFormModel();

			foreach ($record as $data)
			{
				$db = $listModel->getDb();
				$startDate = trim($data['startdate']) !== '' ? String::safeColName($data['startdate']) : '\'\'';

				if ($data['startdate'] == '')
				{
					throw new RuntimeException('No start date selected ', 500);

					return;
				}

				$startElement = $listModel->getElement($data['startdate']);
				$endDate = trim($data['enddate']) !== '' ? String::safeColName($data['enddate']) : "''";
				$endElement = trim($data['enddate']) !== '' ? $formModel->getElement($data['enddate']) : $startElement;

				$startLocal = $store_as_local = (bool) $startElement->getParams()->get('date_store_as_local', false);
				$endLocal = $store_as_local = (bool) $endElement->getParams()->get('date_store_as_local', false);

				$label = trim($data['label']) !== '' ? String::safeColName($data['label']) : "''";
				$customUrl = $data['customUrl'];
				$qlabel = $label;

				if (array_key_exists($qlabel, $els))
				{
					// If db join selected for the label we need to get the label element and not the value
					$label = String::safeColName($els[$qlabel]->getOrderByName());

					if (method_exists($els[$qlabel], 'getJoinLabelColumn'))
					{
						$label = $els[$qlabel]->getJoinLabelColumn();
					}
					else
					{
						$label = String::safeColName($els[$qlabel]->getOrderByName());
					}
				}

				$pk = $db->qn($listModel->getTable()->get('list.db_primary_key'));
				$query = $db->getQuery(true);
				$query = $listModel->buildQuerySelect('list', $query);
				$status = trim($data['status']) !== '' ? String::safeColName($data['status']) : "''";
				$query->select($pk . ' AS id, ' . $pk . ' AS rowid, ' . $startDate . ' AS startdate, ' . $endDate . ' AS enddate')
					->select('"" AS link, ' . $label . ' AS label, ' . $db->q($data['colour']) . ' AS colour, 0 AS formid')
				->select($status . ' AS status')
				->order($startDate . ' ASC');
				$query = $listModel->buildQueryJoin($query);
				$this_where = trim(str_replace('WHERE', '', $this_where));
				$query = $this_where === '' ? $listModel->buildQueryWhere(true, $query) : $query->where($this_where);
				$db->setQuery($query);
				$formdata = $db->loadObjectList();

				if (is_array($formdata))
				{
					foreach ($formdata as $row)
					{
						if ($row->startdate != '')
						{
							$defaultURL = 'index.php?option=com_' . $package . '&Itemid=' . $itemId . '&view=form&formid='
								. $table->form_id . '&rowid=' . $row->id . '&tmpl=component';
							$thisCustomUrl = $w->parseMessageForPlaceHolder($customUrl, $row);
							$row->link = $thisCustomUrl !== '' ? $thisCustomUrl : $defaultURL;
							$row->custom = $customUrl != '';
							$row->_listid = $table->id;
							$row->_canDelete = (bool) $listModel->canDelete();
							$row->_canEdit = (bool) $listModel->canEdit($row);
							$row->_canView = (bool) $listModel->canViewDetails();

							//Format local dates toISO8601
							$mydate = new DateTime($row->startdate);
							$row->startdate_locale = $mydate->format(DateTime::RFC3339);
							$mydate = new DateTime($row->enddate);
							$row->enddate_locale = $mydate->format(DateTime::RFC3339);

							// Added timezone offset
							if ($row->startdate !== $db->getNullDate() && $data['startShowTime'] == true)
							{
								$date = JFactory::getDate($row->startdate);
								$row->startdate = $date->format('Y-m-d H:i:s', true);

								if ($startLocal)
								{
									//Format local dates toISO8601
									$mydate = new DateTime($row->startdate);
									$row->startdate_locale = $mydate->format(DateTime::RFC3339);
								}
								else
								{
									$date->setTimezone($tz);
									$row->startdate_locale = $date->toISO8601(true);
								}
							}

							if ($row->enddate !== $db->getNullDate() && (string) $row->enddate !== '')
							{
								if ($data['endShowTime'] == true)
								{
									$date = JFactory::getDate($row->enddate);
									$row->enddate = $date->format('Y-m-d H:i:d');

									if ($endLocal)
									{
										//Format local dates toISO8601
										$mydate = new DateTime($row->enddate);
										$row->enddate_locale = $mydate->format(DateTime::RFC3339);
									}
									else
									{
										$date->setTimezone($tz);
										$row->enddate_locale = $date->toISO8601(true);
									}
								}
							}
							else
							{
								$row->enddate = $row->startdate;
								$row->enddate_locale = isset($row->startdate_locale) ? $row->startdate_locale : '';
							}


							$jsevents[$table->id . '_' . $row->id . '_' . $row->startdate] = clone ($row);
						}
					}
				}
			}
		}

		$params = $this->getParams();
		$addEvent = json_encode($jsevents);

		return $addEvent;
	}

	/**
	 * Get the js code to create the legend
	 *
	 * @return  string
	 */

	public function getLegend()
	{
		$db = Worker::getDbo();
		$params = $this->getParams();
		$this->setupEvents();
		$tables = (array) $params->get('calendar_table');
		$colour = (array) $params->get('colour');
		$legend = (array) $params->get('legendtext');
		$ref = $this->getJSRenderContext();

		// @TODO: json encode the returned value and move to the view
		$calendar = $this->getRow();
		$aLegend = "$ref.addLegend([";
		$jsevents = array();

		foreach ($this->events as $listId => $record)
		{
			$listModel = new Lizt;
			$listModel->setId($listId);
			$table = $listModel->getTable();

			foreach ($record as $data)
			{
				$rubbish = $table->get('list.db_table_name') . '___';
				$colour = String::ltrimword($data['colour'], $rubbish);
				$legend = String::ltrimword($data['legendtext'], $rubbish);
				$label = (empty($legend)) ? $table->label : $legend;
				$aLegend .= "{'label':'" . $label . "','colour':'" . $colour . "'},";
			}
		}

		$aLegend = rtrim($aLegend, ",") . "]);";

		return $aLegend;
	}

	/**
	 * Get calendar js name
	 *
	 * @deprecated  Use getJSRenderContext() instead
	 *
	 * @return NULL
	 */

	public function getCalName()
	{
		if (is_null($this->calName))
		{
			$calendar = $this->getRow();
			$this->calName = 'oCalendar' . $calendar->id;
		}

		return $this->calName;
	}

	/**
	 * Update an event - Not working/used!
	 *
	 * @return  void
	 */

	public function updateevent()
	{
		$oPluginManager = Worker::getPluginManager();
	}

	/**
	 * Delete an event
	 *
	 * @return  void
	 */

	public function deleteEvent()
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$id = $input->getInt('id');
		$listId = $input->getString('listid');
		$listModel = new Lizt;
		$listModel->setId($listId);
		$list = $listModel->getTable();
		$tableDb = $listModel->getDb();
		$db = Worker::getDbo(true);
		$query = $db->getQuery(true);

		// FIXME for 3.5
		$query->select('db_table_name')->from('#__fabrik_lists')->where('id = ' . $listId);
		$db->setQuery($query);
		$tableName = $db->loadResult();
		$query = $tableDb->getQuery(true);
		$pk = $db->qn($list->get('list.db_primary_key'));
		$query->delete(String::safeColName($tableName))->where($pk . ' = ' . $id);
		$tableDb->setQuery($query);
		$tableDb->execute();
	}

	/**
	 * Create the min/max dates between which events can be added.
	 *
	 * @return stdClass  min/max properties containing sql formatted dates
	 */
	public function getDateLimits()
	{
		$params = $this->getParams();
		$limits = new stdClass;
		$min = $params->get('limit_min', '');
		$max = $params->get('limit_max', '');
		/**@@@trob: seems Firefox needs this date format in calendar.js (limits not working with toSQL*/
		$limits->min = ($min === '') ? '' : JFactory::getDate($min)->toISO8601();
		$limits->max = ($max === '') ? '' : JFactory::getDate($max)->toISO8601();

		return $limits;
	}

	/**
	 * Build the notice which explains between which dates you can add events.
	 *
	 * @return string
	 */
	public function getDateLimitsMsg()
	{
		$params = $this->getParams();
		$min = $params->get('limit_min', '');
		$max = $params->get('limit_max', '');
		$msg = '';
		$f = Text::_('DATE_FORMAT_LC2');

		if ($min !== '' && $max === '')
		{
			$msg = '<br />' . Text::sprintf('PLG_VISUALIZATION_CALENDAR_LIMIT_AFTER', JFactory::getDate($min)->format($f));
		}

		if ($min === '' && $max !== '')
		{
			$msg = '<br />' . Text::sprintf('PLG_VISUALIZATION_CALENDAR_LIMIT_BEFORE', JFactory::getDate($max)->format($f));
		}

		if ($min !== '' && $max !== '')
		{
			$min = JFactory::getDate($min)->format($f);
			$max = JFactory::getDate($max)->format($f);
			$msg = '<br />' . Text::sprintf('PLG_VISUALIZATION_CALENDAR_LIMIT_RANGE', $min, $max);
		}

		return $msg;
	}
}
