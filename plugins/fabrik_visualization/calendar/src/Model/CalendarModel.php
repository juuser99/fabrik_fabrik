<?php
/**
 * Fabrik Calendar Plug-in Model
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.calendar
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Plugin\FabrikVisualization\Calendar\Model;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Component\Fabrik\Administrator\Model\FabrikModel;
use Fabrik\Component\Fabrik\Site\Model\AbstractVisualizationModel;
use Fabrik\Component\Fabrik\Site\Model\ListModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Language\Text;
use Joomla\Utilities\ArrayHelper;
use Fabrik\Helpers\Worker;
use Fabrik\Helpers\ArrayHelper as FArrayHelper;
use Fabrik\Helpers\StringHelper as FStringHelper;

/**
 * Fabrik Calendar Plug-in Model
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.calendar
 * @since       4.0
 */
class CalendarModel extends AbstractVisualizationModel
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
			$this->listids = (array) $this->getParams()->get('calendar_table');
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
			$lists            = (array) $params->get('calendar_table');
			$lists            = ArrayHelper::toInteger($lists);
			$dateFields       = (array) $params->get('calendar_startdate_element');
			$dateFields2      = (array) $params->get('calendar_enddate_element');
			$labels           = (array) $params->get('calendar_label_element');
			$stati            = (array) $params->get('status_element');
			$colours          = (array) $params->get('colour');

			$query = $db->getQuery(true);
			$query->select('id AS value, label AS text')->from('#__{package}_lists')->where('id IN (' . implode(',', $lists) . ')');
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
				$rows[$i]->enddate_element   = FArrayHelper::getValue($dateFields2, $i);
				$rows[$i]->label_element     = $labels[$i];
				$rows[$i]->status            = FArrayHelper::getValue($stati, $i, '');
				$rows[$i]->colour            = $colours[$i];
			}

			$this->eventLists = $rows;
		}

		return $this->eventLists;
	}

	/**
	 * Get Standard Event Form Info
	 *
	 * @return object|null
	 *
	 * @since 4.0
	 */
	public function getAddStandardEventFormInfo()
	{
		$prefix = $this->config->get('dbprefix');
		$db     = Worker::getDbo();
		$query  = $db->getQuery(true);
		$query->select('form_id, id')->from('#__{package}_lists')
			->where('db_table_name = ' . $db->q($prefix . 'fabrik_calendar_events') . ' AND private = 1');
		$db->setQuery($query);
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
	 *
	 * @since 4.0
	 */
	public function save()
	{
		$input  = $this->app->input;
		$filter = InputFilter::getInstance();
		$post   = $filter->clean($_POST, 'array');
		$this->bind($post);

		$params       = $input->get('params', array(), 'array');
		$this->params = json_encode($params);

		if ($this->id == 0)
		{
			$this->created    = date('Y-m-d H:i:s');
			$this->created_by = $this->user->get('id');
		}
		else
		{
			$this->modified    = date('Y-m-d H:i:s');
			$this->modified_by = $this->user->get('id');
		}

		if ($this->check())
		{
			$this->store();
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
			$params         = $this->getParams();
			$tables         = (array) $params->get('calendar_table');
			$table_label    = (array) $params->get('calendar_label_element');
			$tableStartDate = (array) $params->get('calendar_startdate_element');
			$tableEndDate   = (array) $params->get('calendar_enddate_element');
			$customUrls     = (array) $params->get('custom_url');
			$colour         = (array) $params->get('colour');
			$legend         = (array) $params->get('legendtext');
			$stati          = (array) $params->get('status_element');

			$this->events = array();

			for ($i = 0; $i < count($tables); $i++)
			{
				/** @var ListModel $listModel */
				$listModel = FabrikModel::getInstance(ListModel::class);

				if ($tables[$i] != 'undefined')
				{
					$listModel->setId($tables[$i]);
					$table     = $listModel->getTable();
					$endDate   = FArrayHelper::getValue($tableEndDate, $i, '');
					$startDate = FArrayHelper::getValue($tableStartDate, $i, '');

					$startShowTime = true;
					$startDateEl   = $listModel->getFormModel()->getElement($startDate);

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

					$customUrl                   = FArrayHelper::getValue($customUrls, $i, '');
					$status                      = FArrayHelper::getValue($stati, $i, '');
					$this->events[$tables[$i]][] = array('startdate'   => $startDate, 'enddate' => $endDate, 'startShowTime' => $startShowTime,
					                                     'endShowTime' => $endShowTime, 'label' => $table_label[$i], 'colour' => $colour[$i], 'legendtext' => $legend[$i],
					                                     'formid'      => $table->form_id, 'listid' => $tables[$i], 'customUrl' => $customUrl, 'status' => $status);
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

		foreach ($this->events as $listId => $record)
		{
			$listModel->setId($listId);
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
			$params = $this->getParams();
			$lists  = (array) $params->get('calendar_table');

			foreach ($lists as $id)
			{
				/** @var ListModel $listModel */
				$listModel = FabrikModel::getInstance(ListModel::class);
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
	 * @return  array    List ids.
	 *
	 * @since 4.0
	 */
	public function getDeleteAccess()
	{
		$ids    = array();
		$params = $this->getParams();
		$lists  = (array) $params->get('calendar_table');

		foreach ($lists as $id)
		{
			/** @var ListModel $listModel */
			$listModel = FabrikModel::getInstance(ListModel::class);
			$listModel->setId($id);

			if ($listModel->canDelete())
			{
				$ids[] = $id;
			}
		}

		return $ids;
	}

	/**
	 * Query all tables linked to the calendar and return them
	 *
	 * @return  string    javascript array containing json objects
	 *
	 * @since 4.0
	 */
	public function getEvents()
	{
		$itemId   = Worker::itemId();
		$tzOffset = $this->config->get('offset');
		$tz       = new \DateTimeZone($tzOffset);
		$w        = new Worker;
		$this->setupEvents();
		$jsEvents = array();
		$input    = $this->app->input;
		$where    = $input->get('where', array(), 'array');

		foreach ($this->events as $listId => $record)
		{
			$this_where = FArrayHelper::getValue($where, $listId, '');
			$this_where = html_entity_decode($this_where, ENT_QUOTES);

			/** @var ListModel $listModel */
			$listModel = FabrikModel::getInstance(ListModel::class);
			$listModel->setId($listId);

			if (!$listModel->canView())
			{
				continue;
			}

			$table     = $listModel->getTable();
			$els       = $listModel->getElements();
			$formModel = $listModel->getFormModel();

			foreach ($record as $data)
			{
				$db        = $listModel->getDb();
				$startDate = trim($data['startdate']) !== '' ? FStringHelper::safeColName($data['startdate']) : '\'\'';

				if ($data['startdate'] == '')
				{
					throw new \RuntimeException('No start date selected ', 500);
				}

				$startElement = $formModel->getElement($data['startdate']);
				$endDate      = trim($data['enddate']) !== '' ? FStringHelper::safeColName($data['enddate']) : "''";
				$endElement   = trim($data['enddate']) !== '' ? $formModel->getElement($data['enddate']) : $startElement;

				$startLocal = $store_as_local = (bool) $startElement->getParams()->get('date_store_as_local', false);
				$endLocal   = $store_as_local = (bool) $endElement->getParams()->get('date_store_as_local', false);

				$label     = trim($data['label']) !== '' ? FStringHelper::safeColName($data['label']) : "''";
				$customUrl = $data['customUrl'];
				$qLabel    = $label;

				if (array_key_exists($qLabel, $els))
				{
					// If db join selected for the label we need to get the label element and not the value
					if (method_exists($els[$qLabel], 'getJoinLabelColumn'))
					{
						$label = $els[$qLabel]->getJoinLabelColumn();
					}
					else
					{
						$label = FStringHelper::safeColName($els[$qLabel]->getOrderByName());
					}
				}

				$pk     = $listModel->getPrimaryKey();
				$query  = $db->getQuery(true);
				$query  = $listModel->buildQuerySelect('list', $query);
				$status = trim($data['status']) !== '' ? FStringHelper::safeColName($data['status']) : "''";
				$query->select($pk . ' AS id, ' . $pk . ' AS rowid, ' . $startDate . ' AS startdate, ' . $endDate . ' AS enddate')
					->select('"" AS link, ' . $label . ' AS label, ' . $db->q($data['colour']) . ' AS colour, 0 AS formid')
					->select($status . ' AS status')
					->order($startDate . ' ASC');
				$query = $listModel->buildQueryJoin($query);
				//$this_where = trim(str_replace('WHERE', '', $this_where));
				$this_where = FStringHelper::ltrimiword($this_where, 'WHERE');
				$query      = $this_where === '' ? $listModel->buildQueryWhere(true, $query) : $query->where($this_where);
				$db->setQuery($query);
				$formData = $db->loadObjectList();

				if (is_array($formData))
				{
					foreach ($formData as $row)
					{
						if ($row->startdate != '')
						{
							$defaultURL      = 'index.php?option=com_' . $this->package . '&Itemid=' . $itemId . '&view=form&formid='
								. $table->form_id . '&rowid=' . $row->id . '&tmpl=component';
							$thisCustomUrl   = $w->parseMessageForPlaceHolder($customUrl, $row);
							$row->link       = $thisCustomUrl !== '' ? $thisCustomUrl : $defaultURL;
							$row->custom     = $customUrl != '';
							$row->_listid    = $table->id;
							$row->_canDelete = (bool) $listModel->canDelete();
							$row->_canEdit   = (bool) $listModel->canEdit($row);
							$row->_canView   = (bool) $listModel->canViewDetails();

							//Format local dates toISO8601
							$myDate                = new \DateTime($row->startdate);
							$row->startdate_locale = $myDate->format(DateTime::RFC3339);
							$myDate                = new \DateTime($row->enddate);
							$row->enddate_locale   = $myDate->format(DateTime::RFC3339);

							// Added timezone offset
							if ($row->startdate !== $db->getNullDate() && $data['startShowTime'] == true)
							{
								$date           = Factory::getDate($row->startdate);
								$row->startdate = $date->format('Y-m-d H:i:s', true);

								if ($startLocal)
								{
									//Format local dates toISO8601
									$myDate                = new \DateTime($row->startdate);
									$row->startdate_locale = $myDate->format(DateTime::RFC3339);
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
									$date         = Factory::getDate($row->enddate);
									$row->enddate = $date->format('Y-m-d H:i:d');

									if ($endLocal)
									{
										//Format local dates toISO8601
										$myDate              = new \DateTime($row->enddate);
										$row->enddate_locale = $myDate->format(DateTime::RFC3339);
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
								$row->enddate        = $row->startdate;
								$row->enddate_locale = isset($row->startdate_locale) ? $row->startdate_locale : '';
							}


							$jsEvents[$table->id . '_' . $row->id . '_' . $row->startdate] = clone ($row);
						}
					}
				}
			}
		}

		$addEvent = json_encode($jsEvents);

		return $addEvent;
	}

	/**
	 * Get the js code to create the legend
	 *
	 * @return  string
	 *
	 * @since 4.0
	 */
	public function getLegend()
	{
		$this->setupEvents();
		$ref = $this->getJSRenderContext();

		// @TODO: json encode the returned value and move to the view
		$aLegend = "$ref.addLegend([";

		foreach ($this->events as $listId => $record)
		{
			/** @var ListModel $listModel */
			$listModel = FabrikModel::getInstance(ListModel::class);
			$listModel->setId($listId);
			$table = $listModel->getTable();

			foreach ($record as $data)
			{
				$rubbish = $table->db_table_name . '___';
				$colour  = FStringHelper::ltrimword($data['colour'], $rubbish);
				$legend  = FStringHelper::ltrimword($data['legendtext'], $rubbish);
				$label   = (empty($legend)) ? $table->label : $legend;
				$aLegend .= "{'label':'" . $label . "','colour':'" . $colour . "'},";
			}
		}

		$aLegend = rtrim($aLegend, ",") . "]);";

		return $aLegend;
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
		$input  = $this->app->input;
		$id     = $input->getInt('id');
		$listId = $input->getInt('listid');
		/** @var ListModel $listModel */
		$listModel = FabrikModel::getInstance(ListModel::class);
		$listModel->setId($listId);
		$list    = $listModel->getTable();
		$tableDb = $listModel->getDb();
		$db      = Worker::getDbo(true);
		$query   = $db->getQuery(true);
		$query->select('db_table_name')->from('#__{package}_lists')->where('id = ' . $listId);
		$db->setQuery($query);
		$tableName = $db->loadResult();
		$query     = $tableDb->getQuery(true);
		$query->delete(FStringHelper::safeColName($tableName))->where($list->db_primary_key . ' = ' . $id);
		$tableDb->setQuery($query);
		$tableDb->execute();
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
		/** Seems Firefox needs this date format in calendar.js (limits not working with toSQL*/
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
			$msg = '<br />' . Text::sprintf('PLG_VISUALIZATION_CALENDAR_LIMIT_AFTER', Factory::getDate($min)->format($f));
		}

		if ($min === '' && $max !== '')
		{
			$msg = '<br />' . Text::sprintf('PLG_VISUALIZATION_CALENDAR_LIMIT_BEFORE', Factory::getDate($max)->format($f));
		}

		if ($min !== '' && $max !== '')
		{
			$min = Factory::getDate($min)->format($f);
			$max = Factory::getDate($max)->format($f);
			$msg = '<br />' . Text::sprintf('PLG_VISUALIZATION_CALENDAR_LIMIT_RANGE', $min, $max);
		}

		return $msg;
	}
}
