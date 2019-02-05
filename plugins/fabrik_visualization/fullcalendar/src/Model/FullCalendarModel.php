<?php
/**
 * Fabrik Calendar Plug-in Model
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.calendar
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Plugin\FabrikVisualization\FullCalendar\Model;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Component\Fabrik\Administrator\Model\FabrikModel;
use Fabrik\Component\Fabrik\Site\Model\AbstractVisualizationModel;
use Fabrik\Component\Fabrik\Site\Model\ListModel;
use Fabrik\Helpers\Worker;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Language\Text;
use Joomla\Utilities\ArrayHelper;
use Fabrik\Helpers\ArrayHelper as FArrayHelper;
use Fabrik\Helpers\StringHelper as FStringHelper;

/**
 * Fabrik Calendar Plug-in Model
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.calendar
 * @since       4.0
 */
class FullCalendarModel extends AbstractVisualizationModel
{
	/**
	 * Array of Fabrik lists containing events
	 *
	 * @var array
	 *
	 * @since 4.0
	 */
	protected $eventLists = null;

	/**
	 * JS name for calendar
	 *
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $calName = null;

	/**
	 * Event info
	 *
	 * @var array
	 *
	 * @since 4.0
	 */
	public $events = null;

	/**
	 * Filters from url
	 *
	 * @var array
	 *
	 * @since 4.0
	 */
	public $filters = array();

	/**
	 * Can add events to lists
	 *
	 * @var bool
	 *
	 * @since 4.0
	 */
	public $canAdd = null;

	/**
	 * Set an array of list id's whose data is used inside the visualization
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	protected function setListIds()
	{
		if (!isset($this->listids))
		{
			$this->listids = (array) $this->getParams()->get('fullcalendar_table');
			$this->listids = ArrayHelper::toInteger($this->listids);
		}
	}

	/**
	 * Get the lists that contain events
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function &getEventLists()
	{
		if (is_null($this->eventLists))
		{
			$this->eventLists = array();
			$db               = Worker::getDbo(true);
			$params           = $this->getParams();
			$lists            = (array) $params->get('fullcalendar_table');
			$lists            = ArrayHelper::toInteger($lists);
			$dateFields       = (array) $params->get('fullcalendar_startdate_element');
			$dateFields2      = (array) $params->get('fullcalendar_enddate_element');
			$labels           = (array) $params->get('fullcalendar_label_element');
			$stati            = (array) $params->get('status_element');
			$colours          = (array) $params->get('colour');

			$query = $db->getQuery(true);
			$query->select('id AS value, label AS text')->from('#__{package}_lists')->where('id IN (' . implode(',', $lists) . ')');
			$db->setQuery($query);
			$rows = $db->loadObjectList('value');

			/**
			 * If the same list ID has been selected multiple times, the query will only have returned it once,
			 * so we need to manually add any duplicates.
			 */
			$dupes = array();

			foreach ($lists as $listId)
			{
				$dupes[] = clone($rows[$listId]);
			}

			for ($i = 0; $i < count($dupes); $i++)
			{
				if (!isset($colours[$i]))
				{
					$colours[$i] = '';
				}

				if (!isset($stati[$i]))
				{
					$stati[$i] = '';
				}

				$dupes[$i]->startdate_element = $dateFields[$i];
				$dupes[$i]->enddate_element   = FArrayHelper::getValue($dateFields2, $i);
				$dupes[$i]->label_element     = $labels[$i];
				$dupes[$i]->status            = FArrayHelper::getValue($stati, $i, '');
				$dupes[$i]->colour            = $colours[$i];
			}

			$this->eventLists = $dupes;
		}

		return $this->eventLists;
	}

	/**
	 * Save the calendar
	 *
	 * @return  boolean False if not saved, otherwise id of saved calendar
	 *
	 * @since 4.0
	 */
	public function save()
	{
		$user   = Factory::getUser();
		$app    = Factory::getApplication();
		$input  = $app->input;
		$filter = InputFilter::getInstance();
		$post   = $filter->clean($_POST, 'array');

		if (!$this->bind($post))
		{
			throw new \RuntimeException($this->getError());
		}

		$params       = $input->get('params', array(), 'array');
		$this->params = json_encode($params);

		if ($this->id == 0)
		{
			$this->created    = date('Y-m-d H:i:s');
			$this->created_by = $user->get('id');
		}
		else
		{
			$this->modified    = date('Y-m-d H:i:s');
			$this->modified_by = $user->get('id');
		}

		if (!$this->check())
		{
			throw new \RuntimeException($this->getError());
		}

		if (!$this->store())
		{
			throw new \RuntimeException($this->getError());
		}

		$this->checkin();

		return $this->id;
	}

	/**
	 * Set up calendar events
	 *
	 * @return  array
	 *
	 * @since 4.0
	 */
	public function setupEvents()
	{
		if (is_null($this->events))
		{
			$params          = $this->getParams();
			$tables          = (array) $params->get('fullcalendar_table');
			$table_label     = (array) $params->get('fullcalendar_label_element');
			$table_startdate = (array) $params->get('fullcalendar_startdate_element');
			$table_enddate   = (array) $params->get('fullcalendar_enddate_element');
			$customUrls      = (array) $params->get('custom_url');
			$colour          = (array) $params->get('colour');
			$legend          = (array) $params->get('legendtext');
			$stati           = (array) $params->get('status_element');
			$allDayEl        = (array) $params->get('allday_element');
			$popupTemplates  = (array) $params->get('popup_template');
			$this->events    = array();

			for ($i = 0; $i < count($tables); $i++)
			{
				/** @var ListModel $listModel */
				$listModel = FabrikModel::getInstance(ListModel::class);

				if ($tables[$i] != 'undefined')
				{
					$listModel->setId($tables[$i]);
					$table     = $listModel->getTable();
					$endDate   = FArrayHelper::getValue($table_enddate, $i, '');
					$startDate = FArrayHelper::getValue($table_startdate, $i, '');

					$startShowTime = true;
					$startDateEl   = $listModel->getFormModel()->getElement($startDate);

					if ($startDateEl !== false)
					{
						$startShowTime = $startDateEl->getParams()->get('date_showtime', true);
					}

					$endShowTime = false;

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

					$popupTemplate = FArrayHelper::getValue($popupTemplates, $i, '');

					$customUrl                     = FArrayHelper::getValue($customUrls, $i, '');
					$status                        = FArrayHelper::getValue($stati, $i, '');
					$allday                        = FArrayHelper::getValue($allDayEl, $i, '');
					$this->events[$tables[$i]][$i] = array(
						'startdate'     => $startDate,
						'enddate'       => $endDate,
						'startShowTime' => $startShowTime,
						'endShowTime'   => $endShowTime,
						'label'         => $table_label[$i],
						'colour'        => $colour[$i],
						'legendtext'    => $legend[$i],
						'formid'        => $table->form_id,
						'listid'        => $tables[$i],
						'customUrl'     => $customUrl,
						'status'        => $status,
						'allday'        => $allday,
						'popupTemplate' => $popupTemplate
					);
				}
			}
		}

		return $this->events;
	}

	/**
	 * Get the linked form IDs
	 *
	 * @return array
	 *
	 * @since 4.0
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
	 *
	 * @since 4.0
	 */
	public function setRequestFilters()
	{
		$this->setupEvents();
		$filter  = InputFilter::getInstance();
		$request = $filter->clean($_REQUEST, 'array');

		/** @var ListModel $listModel */
		$listModel = FabrikModel::getInstance(ListModel::class);

		foreach ($this->events as $listid => $record)
		{
			$listModel->setId($listid);
			$table     = $listModel->getTable();
			$formModel = $listModel->getFormModel();

			foreach ($request as $key => $val)
			{
				if ($formModel->hasElement($key))
				{
					$o               = new \stdClass;
					$o->key          = $key;
					$o->val          = $val;
					$this->filters[] = $o;
				}
			}
		}
	}

	/**
	 * Can the user add a record into the calendar
	 *
	 * @return  bool
	 *
	 * @since 4.0
	 */
	public function getCanAdd()
	{
		if (!isset($this->canAdd))
		{
			$params       = $this->getParams();
			$lists        = (array) $params->get('fullcalendar_table');
			$this->canAdd = false;

			foreach ($lists as $id)
			{
				/** @var ListModel $listModel */
				$listModel = FabrikModel::getInstance(ListModel::class);
				$listModel->setId($id);

				if ($listModel->canAdd())
				{
					$this->canAdd = true;
				}
			}
		}

		return $this->canAdd;
	}

	/**
	 * Get an array of list ids for which the current user has delete records rights
	 *
	 * @return  array    List ids.
	 *
	 * @since 4.0
	 */
	public function getDeleteAccess()
	{
		$deleteables = array();
		$params      = $this->getParams();
		$lists       = (array) $params->get('fullcalendar_table');

		foreach ($lists as $id)
		{
			/** @var ListModel $listModel */
			$listModel = FabrikModel::getInstance(ListModel::class);
			$listModel->setId($id);

			if ($listModel->canDelete())
			{
				$deleteables[] = $id;
			}
		}

		return $deleteables;
	}

	/**
	 * Query one or all tables linked to the calendar and return them
	 *
	 * @param  string $listid list id
	 *
	 * @return  string    javascript array containing json objects
	 *
	 * @since 4.0
	 */
	public function getEvents($listid = '', $eventListKey = '')
	{
		/** @var CMSApplication $app */
		$app      = Factory::getApplication();
		$package  = $app->getUserState('com_fabrik.package', 'fabrik');
		$Itemid   = Worker::itemId();
		$config   = $app->getConfig();
		$tzoffset = $config->get('offset');
		$tz       = new \DateTimeZone($tzoffset);
		$w        = new Worker;
		$this->setupEvents();
		$calendar = $this->getRow();
		$aLegend  = "$this->calName.addLegend([";
		$jsevents = array();
		$input    = $app->input;
		$where    = $input->get('where', array(), 'array');
		$calStart = $input->get('startDate', '');
		$calEnd   = $input->get('endDate', '');

		foreach ($this->events as $this_listid => $record)
		{
			if (!empty($listid) && $this_listid != $listid)
			{
				continue;
			}

			$this_where = FArrayHelper::getValue($where, $this_listid, '');
			$this_where = html_entity_decode($this_where, ENT_QUOTES);

			/** @var ListModel $listModel */
			$listModel = FabrikModel::getInstance(ListModel::class);
			$listModel->setId($this_listid);

			if (!$listModel->canView())
			{
				continue;
			}

			$table     = $listModel->getTable();
			$els       = $listModel->getElements();
			$formModel = $listModel->getFormModel();

			foreach ($record as $key => $data)
			{
				if ($eventListKey !== '' && (int) $key !== (int) $eventListKey)
				{
					continue;
				}

				$db        = $listModel->getDb();
				$startdate = trim($data['startdate']) !== '' ? FStringHelper::safeColName($data['startdate']) : '\'\'';

				if ($data['startdate'] == '')
				{
					throw new \RuntimeException('No start date selected ', 500);
				}

				$startElement = $formModel->getElement($data['startdate']);
				$startField   = $startElement->getFullName(false, false);
				$enddate      = trim($data['enddate']) !== '' ? FStringHelper::safeColName($data['enddate']) : "''";
				$endElement   = trim($data['enddate']) !== '' ? $formModel->getElement($data['enddate']) : $startElement;
				$endField     = $endElement->getFullName(false, false);

				$startLocal = $store_as_local = (bool) $startElement->getParams()->get('date_store_as_local', false);
				$endLocal   = $store_as_local = (bool) $endElement->getParams()->get('date_store_as_local', false);

				$label     = trim($data['label']) !== '' ? FStringHelper::safeColName($data['label']) : "''";
				$customUrl = $data['customUrl'];
				$qlabel    = $label;

				if (array_key_exists($qlabel, $els))
				{
					// If db join selected for the label we need to get the label element and not the value
					$label = FStringHelper::safeColName($els[$qlabel]->getOrderByName());

					if (method_exists($els[$qlabel], 'getJoinLabelColumn'))
					{
						$label = $els[$qlabel]->getJoinLabelColumn();
					}
					else
					{
						$label = FStringHelper::safeColName($els[$qlabel]->getOrderByName());
					}
				}

				$pk     = $listModel->getTable()->db_primary_key;
				$status = empty($data['status']) ? '""' : $data['status'];
				$query  = $db->getQuery(true);
				$query  = $listModel->buildQuerySelect('list', $query);
				$status = trim($data['status']) !== '' ? FStringHelper::safeColName($data['status']) : "''";
				$allday = trim($data['allday']) !== '' ? FStringHelper::safeColName($data['allday']) : "''";
				$query->select($pk . ' AS id, ' . $pk . ' AS rowid, ' . $startdate . ' AS startdate, ' . $enddate . ' AS enddate')
					->select('"" AS link, ' . $label . ' AS label, ' . $db->quote($data['colour']) . ' AS colour, 0 AS formid')
					->select($status . ' AS status')
					->select($allday . ' AS allday')
					->order($startdate . ' ASC');
				$query = $listModel->buildQueryJoin($query);
				//$this_where = trim(str_replace('WHERE', '', $this_where));
				$this_where = FStringHelper::ltrimiword($this_where, 'WHERE');
				$query      = $this_where === '' ? $listModel->buildQueryWhere(true, $query) : $query->where($this_where);

				$query->where(FStringHelper::safeColName($endField) . ' >= ' . $db->quote($calStart));
				$query->where(FStringHelper::safeColName($startField) . ' <= ' . $db->quote($calEnd));

				$db->setQuery($query);
				$sql      = (string) $query;
				$formdata = $db->loadObjectList();

				if (is_array($formdata))
				{
					foreach ($formdata as $row)
					{
						if ($row->startdate != '')
						{
							if (empty($row->enddate))
							{
								$row->enddate = $row->startdate;
							}

							$defaultURL    = 'index.php?option=com_' . $package . '&Itemid=' . $Itemid . '&view=form&formid='
								. $table->form_id . '&rowid=' . $row->id . '&tmpl=component';
							$thisCustomUrl = $w->parseMessageForPlaceHolder($customUrl, $row);
							//$row->link = $thisCustomUrl !== '' ? $thisCustomUrl : $defaultURL;
							$row->link       = $defaultURL;
							$row->customLink = $thisCustomUrl;
							$row->details    = 'index.php?option=com_' . $package . '&Itemid=' . $Itemid . '&view=details&formid='
								. $table->form_id . '&rowid=' . $row->id . '&tmpl=component';
							$row->custom     = $customUrl != '';
							$row->_listid    = $table->id;
							$row->_formid    = $table->form_id;
							$row->_canDelete = (bool) $listModel->canDelete($row);
							$row->_canEdit   = (bool) $listModel->canEdit($row);
							$row->_canView   = (bool) $listModel->canViewDetails($row);
							$row->allday     = is_string($row->allday) ? filter_var($row->allday, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : (bool) $row->allday;

							//Format local dates
							$date           = Factory::getDate($row->startdate);
							$row->startdate = $date->format('Y-m-d H:i:s', true);

							if (!$startLocal)
							{
								$date->setTimezone($tz);
								$row->startdate = $date->format('Y-m-d H:i:s', true);
							}

							$date = Factory::getDate($row->enddate);
							// Full Calendar allDay end date is now exclusive, need to add a day
							if ($row->allday)
							{
								$date->modify('+1 day');
							}

							$row->enddate = $date->format('Y-m-d H:i:s', true);

							if (!$endLocal)
							{
								$date->setTimezone($tz);
								$row->enddate = $date->format('Y-m-d H:i:s', true);
							}

							$row->startShowTime = (bool) $data['startShowTime'];
							$row->endShowTime   = (bool) $data['endShowTime'];

							$row->popupTemplate = $w->parseMessageForPlaceHolder($data['popupTemplate'], $row);

							if (!empty($row->status))
							{
								$row->status = FStringHelper::getRowClass($row->status, FStringHelper::shortColName($status));
							}
							else
							{
								$row->status = '';
							}

							$jsevents[$table->id . '_' . $row->id . '_' . $row->startdate] = clone ($row);
						}
					}
				}
			}
		}

		$params   = $this->getParams();
		$addEvent = json_encode($jsevents);

		return $addEvent;
	}

	/**
	 * Get the js code to create the legend
	 *
	 * @return  array
	 *
	 * @since 4.0
	 */
	public function getLegend()
	{
		$db     = Worker::getDbo();
		$params = $this->getParams();
		$this->setupEvents();
		$tables = (array) $params->get('fullcalendar_table');
		$colour = (array) $params->get('colour');
		$legend = (array) $params->get('legendtext');

		// @TODO: json encode the returned value and move to the view
		$calendar = $this->getRow();
		$aLegend  = array();
		$jsevents = array();

		foreach ($this->events as $listid => $record)
		{
			/** @var ListModel $listModel */
			$listModel = FabrikModel::getInstance(ListModel::class);
			$listModel->setId($listid);
			$table = $listModel->getTable();

			foreach ($record as $data)
			{
				$rubbish   = $table->db_table_name . '___';
				$colour    = FStringHelper::ltrimword($data['colour'], $rubbish);
				$legend    = FStringHelper::ltrimword($data['legendtext'], $rubbish);
				$label     = (empty($legend)) ? $table->label : $legend;
				$aLegend[] = array('label' => $label, 'colour' => $colour);
			}
		}

		return $aLegend;
	}

	/**
	 * Get calendar js name
	 *
	 * @deprecated  Use getJSRenderContext() instead
	 *
	 * @return NULL
	 *
	 * @since       4.0
	 */
	public function getCalName()
	{
		if (is_null($this->calName))
		{
			$calendar      = $this->getRow();
			$this->calName = 'oCalendar' . $calendar->id;
		}

		return $this->calName;
	}

	/**
	 * Delete an event
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	public function deleteEvent()
	{
		$app    = Factory::getApplication();
		$input  = $app->input;
		$id     = $input->getInt('id');
		$listid = $input->getInt('listid');
		if (!empty($id) && !empty($listid))
		{
			$ids       = array($id);
			$listModel = FabrikModel::getInstance(ListModel::class);
			$listModel->setId($listid);
			$ok = $listModel->deleteRows($ids);
		}
	}

	/**
	 * Create the min/max dates between which events can be added.
	 *
	 * @return \stdClass  min/max properties containing sql formatted dates
	 *
	 * @since 4.0
	 */
	public function getDateLimits()
	{
		$params = $this->getParams();
		$limits = new \stdClass;
		$min    = $params->get('limit_min', '');
		$max    = $params->get('limit_max', '');
		/**@@@trob: seems Firefox needs this date format in calendar.js (limits not working with toSQL */
		$limits->min = ($min === '') ? '' : Factory::getDate($min)->toISO8601();
		$limits->max = ($max === '') ? '' : Factory::getDate($max)->toISO8601();

		return $limits;
	}

	/**
	 * Build the notice which explains between which dates you can add events.
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	public function getDateLimitsMsg()
	{
		$params = $this->getParams();
		$min    = $params->get('limit_min', '');
		$max    = $params->get('limit_max', '');
		$msg    = '';
		$f      = Text::_('DATE_FORMAT_LC2');

		if ($min !== '' && $max === '')
		{
			$msg = '<br />' . Text::sprintf('PLG_VISUALIZATION_FULLCALENDAR_LIMIT_AFTER', Factory::getDate($min)->format($f));
		}

		if ($min === '' && $max !== '')
		{
			$msg = '<br />' . Text::sprintf('PLG_VISUALIZATION_FULLCALENDAR_LIMIT_BEFORE', Factory::getDate($max)->format($f));
		}

		if ($min !== '' && $max !== '')
		{
			$min = Factory::getDate($min)->format($f);
			$max = Factory::getDate($max)->format($f);
			$msg = '<br />' . Text::sprintf('PLG_VISUALIZATION_FULLCALENDAR_LIMIT_RANGE', $min, $max);
		}

		return $msg;
	}
}