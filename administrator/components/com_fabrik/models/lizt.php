<?php
/**
 * Fabrik Admin List Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

namespace Fabrik\Admin\Models;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Storage\MySql as Storage;
use Joomla\String\String;
use \JText as JText;
use \FText as FText;
use \stdClass as stdClass;
use \JHTML as JHTML;
use Fabrik\Helpers\Worker;
use \JFactory as JFactory;
use Fabrik\Helpers\ArrayHelper;
use \RuntimeException as RuntimeException;
use \Joomla\Registry\Registry as JRegistry;
use \JEventDispatcher as JEventDispatcher;


interface ModelFormLiztInterface
{

}

/**
 * Fabrik Admin List Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.5
 */
class Lizt extends View implements ModelFormLiztInterface
{
	protected $name = 'list';
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var  string
	 */
	protected $text_prefix = 'COM_FABRIK_LIST';

	/**
	 * Currently loaded list row
	 *
	 * @var array
	 */
	protected $tables = array();

	/**
	 * Plugin type
	 *
	 * @var string
	 * @deprecated ?
	 */
	protected $pluginType = 'List';

	/**
	 * Database fields
	 *
	 * @var array
	 */
	protected $dbFields = null;

	/**
	 * Instantiate the model.
	 *
	 * @param   Registry $state The model state.
	 *
	 * @since   12.1
	 */
	public function __construct(Registry $state = null)
	{
		parent::__construct($state);
	}

	/**
	 * Method to get the confirm list delete form.
	 *
	 * @param   array $data     Data for the form.
	 * @param   bool  $loadData True if the form is to load its own data (default case), false if not.
	 *
	 * @return  mixed  A JForm object on success, false on failure
	 *
	 * @since    1.6
	 */
	public function getConfirmDeleteForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_fabrik.confirmdelete', 'confirmlistdelete', array('control' => 'jform', 'load_data' => $loadData));

		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	public function publish($ids = array())
	{
		return $this->_publish($ids, true);
	}

	public function unpublish($ids = array())
	{
		return $this->_publish($ids, false);
	}

	/**
	 * Method to change the published state of one or more records.
	 *
	 * @param   array &$pks  A list of the primary keys to change.
	 * @param   int   $value The value of the published state.
	 *
	 * @return  boolean    True on success.
	 *
	 * @since    1.6
	 */
	protected function _publish(&$pks, $value = 1)
	{
		// Initialise variables.
		$dispatcher = JEventDispatcher::getInstance();
		$user       = JFactory::getUser();
		$table      = $this->getTable();
		$pks        = (array) $pks;

		// Include the content plugins for the change of state event.
		JPluginHelper::importPlugin('content');

		// Access checks.
		foreach ($pks as $i => $pk)
		{
			if ($table->load($pk))
			{
				if (!$this->canEditState($table))
				{
					// Prune items that you can't change.
					unset($pks[$i]);
					$this->app->enqueueMessage(FText::_('JLIB_APPLICATION_ERROR_EDIT_STATE_NOT_PERMITTED'), 'warning');
				}
			}
		}

		// Attempt to change the state of the records.
		if (!$table->publish($pks, $value, $user->get('id')))
		{
			$this->setError($table->getError());

			return false;
		}

		$context = $this->option . '.' . $this->name;

		// Trigger the onContentChangeState event.
		$result = $dispatcher->trigger($this->event_change_state, array($context, $pks, $value));

		if (in_array(false, $result, true))
		{
			$this->setError($table->getError());

			return false;
		}

		return true;
	}

	/**
	 * Build and/or dropdown list
	 *
	 * @param   bool   $addSlashes to reutrn data
	 * @param   string $name       input name
	 *
	 * @return string dropdown
	 */

	protected function getFilterJoinDd($addSlashes = true, $name = 'join')
	{
		$aConditions   = array();
		$aConditions[] = JHTML::_('select.option', 'AND');
		$aConditions[] = JHTML::_('select.option', 'OR');
		$attribs       = 'class="inputbox input-small" size="1"';
		$dd            = str_replace("\n", "", JHTML::_('select.genericlist', $aConditions, $name, $attribs, 'value', 'text', ''));

		if ($addSlashes)
		{
			$dd = addslashes($dd);
		}

		return $dd;
	}

	/**
	 * Build prefilter dropdown
	 *
	 * @param   bool   $addSlashes add slashes to reutrn data
	 * @param   string $name       name of the drop down
	 * @param   int    $mode       states what values get put into drop down
	 *
	 * @return string dropdown
	 */

	protected function getFilterConditionDd($addSlashes = true, $name = 'conditions', $mode = 1)
	{
		$aConditions = array();

		switch ($mode)
		{
			case 1: /* used for search filter */
				$aConditions[] = JHTML::_('select.option', '<>', 'NOT EQUALS');
				$aConditions[] = JHTML::_('select.option', '=', 'EQUALS');
				$aConditions[] = JHTML::_('select.option', 'like', 'BEGINS WITH');
				$aConditions[] = JHTML::_('select.option', 'like', 'CONTAINS');
				$aConditions[] = JHTML::_('select.option', 'like', 'ENDS WITH');
				$aConditions[] = JHTML::_('select.option', '>', 'GREATER THAN');
				$aConditions[] = JHTML::_('select.option', '>=', 'GREATER THAN OR EQUALS');
				$aConditions[] = JHTML::_('select.option', '<', 'LESS THAN');
				$aConditions[] = JHTML::_('select.option', '<=', 'LESS THAN OR EQUALS');
				break;
			case 2: /* used for prefilter */
				$aConditions[] = JHTML::_('select.option', 'equals', 'EQUALS');
				$aConditions[] = JHTML::_('select.option', 'notequals', 'NOT EQUAL TO');
				$aConditions[] = JHTML::_('select.option', 'begins', 'BEGINS WITH');
				$aConditions[] = JHTML::_('select.option', 'contains', 'CONTAINS');
				$aConditions[] = JHTML::_('select.option', 'ends', 'ENDS WITH');
				$aConditions[] = JHTML::_('select.option', '>', 'GREATER THAN');
				$aConditions[] = JHTML::_('select.option', '>=', 'GREATER THAN OR EQUALS');
				$aConditions[] = JHTML::_('select.option', '<', 'LESS THAN');
				$aConditions[] = JHTML::_('select.option', 'IS NULL', 'IS NULL');
				$aConditions[] = JHTML::_('select.option', '<=', 'LESS THAN OR EQUALS');
				$aConditions[] = JHTML::_('select.option', 'in', 'IN');
				$aConditions[] = JHTML::_('select.option', 'not_in', 'NOT IN');
				$aConditions[] = JHTML::_('select.option', 'exists', 'EXISTS');
				$aConditions[] = JHTML::_('select.option', 'earlierthisyear', FText::_('COM_FABRIK_EARLIER_THIS_YEAR'));
				$aConditions[] = JHTML::_('select.option', 'laterthisyear', FText::_('COM_FABRIK_LATER_THIS_YEAR'));

				$aConditions[] = JHTML::_('select.option', 'yesterday', FText::_('COM_FABRIK_YESTERDAY'));
				$aConditions[] = JHTML::_('select.option', 'today', FText::_('COM_FABRIK_TODAY'));
				$aConditions[] = JHTML::_('select.option', 'tomorrow', FText::_('COM_FABRIK_TOMORROW'));
				$aConditions[] = JHTML::_('select.option', 'thismonth', FText::_('COM_FABRIK_THIS_MONTH'));
				$aConditions[] = JHTML::_('select.option', 'lastmonth', FText::_('COM_FABRIK_LAST_MONTH'));
				$aConditions[] = JHTML::_('select.option', 'nextmonth', FText::_('COM_FABRIK_NEXT_MONTH'));
				$aConditions[] = JHTML::_('select.option', 'birthday', FText::_('COM_FABRIK_BIRTHDAY_TODAY'));

				break;
		}

		$dd = str_replace("\n", "", JHTML::_('select.genericlist', $aConditions, $name, 'class="inputbox input-small"  size="1" ', 'value', 'text', ''));

		if ($addSlashes)
		{
			$dd = addslashes($dd);
		}

		return $dd;
	}

	/**
	 * Get connection model
	 *
	 * @return  object  connect model
	 */

	protected function getCnn()
	{
		$item      = $this->getItem();
		$connModel = new Connection;
		$connModel->set('id', $item->get('list.connection_id'));
		$connModel->getItem($item->get('list.connection_id'));

		return $connModel;
	}

	/**
	 * Create the js that manages the edit list view page
	 *
	 * @return  string  js
	 */

	public function getJs()
	{
		$item      = $this->getItem();
		$connModel = $this->getCnn();
		JText::script('COM_FABRIK_OPTIONS');
		JText::script('COM_FABRIK_JOIN');
		JText::script('COM_FABRIK_FIELD');
		JText::script('COM_FABRIK_CONDITION');
		JText::script('COM_FABRIK_VALUE');
		JText::script('COM_FABRIK_EVAL');
		JText::script('COM_FABRIK_APPLY_FILTER_TO');
		JText::script('COM_FABRIK_DELETE');
		JText::script('JYES');
		JText::script('JNO');
		JText::script('COM_FABRIK_QUERY');
		JTEXT::script('COM_FABRIK_NO_QUOTES');
		JText::script('COM_FABRIK_TEXT');
		JText::script('COM_FABRIK_TYPE');
		JText::script('COM_FABRIK_PLEASE_SELECT');
		JText::script('COM_FABRIK_GROUPED');
		JText::script('COM_FABRIK_TO');
		JText::script('COM_FABRIK_FROM');
		JText::script('COM_FABRIK_JOIN_TYPE');
		JText::script('COM_FABRIK_FROM_COLUMN');
		JText::script('COM_FABRIK_TO_COLUMN');
		JText::script('COM_FABRIK_REPEAT_GROUP_BUTTON_LABEL');
		JText::script('COM_FABRIK_PUBLISHED');

		$joinTypeOpts      = array();
		$joinTypeOpts[]    = array('inner', FText::_('INNER JOIN'));
		$joinTypeOpts[]    = array('left', FText::_('LEFT JOIN'));
		$joinTypeOpts[]    = array('right', FText::_('RIGHT JOIN'));
		$activeTableOpts[] = "";
		$activeTableOpts[] = $item->get('list.db_table_name');

		$joins = $this->getJoins();

		for ($i = 0; $i < count($joins); $i++)
		{
			$j                 = $joins[$i];
			$activeTableOpts[] = $j->table_join;
			$activeTableOpts[] = $j->join_from_table;
		}

		$activeTableOpts       = array_unique($activeTableOpts);
		$activeTableOpts       = array_values($activeTableOpts);
		$opts                  = new stdClass;
		$opts->joinOpts        = $joinTypeOpts;
		$opts->tableOpts       = $connModel->getThisTables(true);
		$opts->activetableOpts = $activeTableOpts;
		$opts                  = json_encode($opts);

		$filterOpts               = new stdClass;
		$filterOpts->filterJoinDd = $this->getFilterJoinDd(false, 'jform[params][filter-join][]');
		$filterOpts->filterCondDd = $this->getFilterConditionDd(false, 'jform[params][filter-conditions][]', 2);
		$filterOpts->filterAccess = JHtml::_('access.level', 'jform[params][filter-access][]', $item->get('list.access'), 'class="input-small"');
		$filterOpts->filterAccess = str_replace(array("\n", "\r"), '', $filterOpts->filterAccess);
		$filterOpts               = json_encode($filterOpts);

		$attribs      = 'class="inputbox input-small" size="1"';
		$filterFields = $this->getElementList('jform[params][filter-fields][]', '', false, false, true, 'name', $attribs);
		$filterFields = addslashes(str_replace(array("\n", "\r"), '', $filterFields));

		$plugins = json_encode($this->getPlugins());

		$js   = array();
		$js[] = "window.addEvent('domready', function () {";
		$js[] = "Fabrik.controller = new PluginManager($plugins, '" . $item->get('view') . "', 'list');";

		$js[] = "oAdminTable = new ListForm($opts);";
		$js[] = "oAdminTable.watchJoins();";

		for ($i = 0; $i < count($joins); $i++)
		{
			$joinGroupParams = json_decode($joins[$i]->params);
			$j               = $joins[$i];
			$joinFormFields  = json_encode($j->joinFormFields);
			$joinToFields    = json_encode($j->joinToFields);
			$repeat          = $joinGroupParams->repeat_group_button == 1 ? 1 : 0;
			$js[]            = "	oAdminTable.addJoin('{$j->group_id}','{$j->id}','{$j->join_type}','{$j->table_join}',"
				. "'{$j->table_key}','{$j->table_join_key}','{$j->join_from_table}', $joinFormFields, $joinToFields, $repeat);";
		}

		$js[]        = "oAdminFilters = new adminFilters('filterContainer', '$filterFields', $filterOpts);";
		$form        = $this->getForm();
		$filterJoins = $form->getValue('params.filter-join');

		// Force to arrays as single prefilters imported from 2.x will be stored as string values
		$filterFields     = (array) $form->getValue('params.filter-fields');
		$filterConditions = (array) $form->getValue('params.filter-conditions');
		$filterEvals      = (array) $form->getValue('params.filter-eval');
		$filterValues     = (array) $form->getValue('params.filter-value');
		$filterAccess     = (array) $form->getValue('params.filter-access');
		$aGrouped         = (array) $form->getValue('params.filter-grouped');

		for ($i = 0; $i < count($filterFields); $i++)
		{
			$selJoin = ArrayHelper::getValue($filterJoins, $i, 'and');

			// 2.0 upgraded sites had quoted filter names
			$selFilter    = str_replace('`', '', $filterFields[$i]);
			$grouped      = ArrayHelper::getValue($aGrouped, $i, 0);
			$selCondition = $filterConditions[$i];
			$filerEval    = (int) ArrayHelper::getValue($filterEvals, $i, '1');

			if ($selCondition == '&gt;')
			{
				$selCondition = '>';
			}

			if ($selCondition == '&lt;')
			{
				$selCondition = '<';
			}

			$selValue  = ArrayHelper::getValue($filterValues, $i, '');
			$selAccess = $filterAccess[$i];

			// Allow for multiline js variables ?
			$selValue = htmlspecialchars_decode($selValue, ENT_QUOTES);
			$selValue = json_encode($selValue);

			// No longer check for empty $selFilter as EXISTS prefilter condition doesn't require element to be selected
			$js[] = "\toAdminFilters.addFilterOption('$selJoin', '$selFilter', '$selCondition', $selValue, '$selAccess', $filerEval, '$grouped');\n";
		}

		$js[] = "});";

		return implode("\n", $js);
	}

	/**
	 * Get the list's join objects
	 *
	 * @return  array
	 */

	protected function getJoins()
	{
	}

	/**
	 * Set the form model
	 *
	 * @param   object $model form model
	 *
	 * @return  void
	 */

	public function setFormModel($model)
	{
		$this->formModel = $model;
	}

	/**
	 * Load up the front end list model so we can use some of its methods
	 *
	 * @return  object  front end list model
	 */

	public function getFEModel()
	{
		throw new Error ('list admin model trying to load front end model - no no - its now all in admin model');
		/*if (is_null($this->feListModel))
		{
			$this->feListModel = JModelLegacy::getInstance('List', 'FabrikFEModel');
			$this->feListModel->set('list.id', $this->get('list.id'));
		}

		return $this->feListModel;*/
	}

	/**
	 * Validate the form
	 *
	 * @param   array $data The data to validate.
	 *
	 * @return mixed  false or data
	 */
	public function validate($data)
	{
		//$params = $data['params'];
		if (!parent::validate($data))
		{
			return false;
		}

		if (empty($data['_database_name']) && ArrayHelper::getValue($data, 'db_table_name') == '')
		{
			$this->app->enqueueMessage(FText::_('COM_FABRIK_SELECT_DB_OR_ENTER_NAME'));

			return false;
		}

		// Hack - must be able to add the plugin xml fields file to $form to include in validation but cant see how at the moment
		//$data['params'] = $params;
		return $data;
	}

	/**
	 * Save the form
	 *
	 * @param   array $post The jform part of the request data pertaining to the list.
	 *
	 * @return bool
	 * @throws RuntimeException
	 */
	public function save($post)
	{
		$post = $this->prepareSave($post, 'list');
		parent::save($post);
	}

	/**
	 * Check to see if a table exists
	 *
	 * @param   string $tableName name of table (overwrites form_id val to test)
	 *
	 * @deprecated
	 *
	 * @return  bool    false if no table found true if table found
	 */

	public function databaseTableExists($tableName = null)
	{
		if (!is_null($tableName))
		{
			$this->storage->table = $tableName;
		}

		return $this->storage->tableExists($tableName);
	}

	/**
	 * New join make the group, group elements and formgroup entries for the join data
	 *
	 * @param   string $tableKey      table key
	 * @param   string $joinTableKey  join to table key
	 * @param   string $joinType      join type
	 * @param   string $joinTable     join to table
	 * @param   string $joinTableFrom join table
	 * @param   bool   $isRepeat      is the group a repeat
	 *
	 * @return  object  $join           returns new join object
	 */

	protected function makeNewJoin($tableKey, $joinTableKey, $joinType, $joinTable, $joinTableFrom, $isRepeat)
	{
		$groupData          = Worker::formDefaults('group');
		$groupData['name']  = $this->getTable()->label . '- [' . $joinTable . ']';
		$groupData['label'] = $joinTable;
		$groupId            = $this->createLinkedGroup($groupData, true, $isRepeat);

		$join                  = $this->getTable('Join');
		$join->id              = null;
		$join->list_id         = $this->get('list.id');
		$join->join_from_table = $joinTableFrom;
		$join->table_join      = $joinTable;
		$join->table_join_key  = $joinTableKey;
		$join->table_key       = str_replace('`', '', $tableKey);
		$join->join_type       = $joinType;
		$join->group_id        = $groupId;
		/**
		 * Create the 'pk' param.  Can't just call front end setJoinPk() for gory
		 * reasons, so do this by steam.
		 *
		 * Probably don't really need to create a registry object here, we could just
		 * JSON-up the pk param, but might as well make the point here that it's a
		 * params object, and it may come in useful for adding other params one day.
		 */
		$join->params = new JRegistry;
		/**
		 * This is kind of expensive, as getPrimaryKeyAndExtra() method does a table lookup,
		 * but I don't think we know what the PK of the joined table is any other
		 * way at this point.
		 */
		$pk = $this->storage->getPrimaryKeyAndExtra($join->table_join);

		if ($pk !== false)
		{
			// If it didn't return false, getPrimaryKeyAndExtra will have created and array with at least one key
			$pk_col = ArrayHelper::getValue($pk[0], 'colname', '');

			if (!empty($pk_col))
			{
				$db     = Worker::getDbo(true);
				$pk_col = $join->table_join . '.' . $pk_col;
				$join->params->set('pk', $db->quoteName($pk_col));
				$join->params = (string) $join->params;
			}
		}

		$join->store();

		$this->createLinkedElements($groupId, $joinTable);

		return $join;
	}

	/**
	 * When copying a table we need to copy its joins as well
	 * note that the group and elements already exists - just the join needs to be saved
	 *
	 * @param   int   $fromid     table id to copy from
	 * @param   int   $toid       table id to copy to
	 * @param   array $groupidmap saying which groups got copied to which new group id (key = old id, value = new id)
	 *
	 * @return null
	 */
	protected function copyJoins($fromid, $toid, $groupidmap)
	{
	}

	/**
	 * Make a database table from  XML definition
	 *
	 * @param   string $key  primary key
	 * @param   string $name table name
	 * @param   string $xml  xml table definition
	 *
	 * @return bool
	 */
	public function dbTableFromXML($key, $name, $xml)
	{
		$row  = $xml[0];
		$data = array();

		// Get which field types to use
		foreach ($row->children() as $child)
		{
			$value = sprintf("%s", $child);
			$type  = $child->attributes()->type;

			if ($type == '')
			{
				$objType = strtotime($value) == false ? "VARCHAR(255)" : "DATETIME";

				if (strstr($value, "\n"))
				{
					$objType = 'TEXT';
				}
			}
			else
			{
				switch (String::strtolower($type))
				{
					case 'integer':
						$objType = 'INT';
						break;
					case 'datetime':
						$objType = "DATETIME";
						break;
					case 'float':
						$objType = "DECIMAL(10,2)";
						break;
					default:
						$objType = "VARCHAR(255)";
						break;
				}
			}

			$data[$child->getName()] = $objType;
		}

		if (empty($data))
		{
			return false;
		}

		$db    = $this->_db;
		$query = 'CREATE TABLE IF NOT EXISTS ' . $db->quoteName($name) . ' (';

		foreach ($data as $fname => $objType)
		{
			$query .= $db->qn($fname) . " $objType, \n";
		}

		$query .= ' primary key (' . $key . '))';
		$query .= ' ENGINE = MYISAM ';
		$db->setQuery($query);
		$db->execute();

		// Get a list of existing ids
		$query = $db->getQuery(true);
		$query->select($key)->from($name);
		$db->setQuery($query);
		$existingIds = $db->loadColumn();

		// Build the row object to insert/update
		foreach ($xml as $row)
		{
			$o = new stdClass;

			foreach ($row->children() as $child)
			{
				$k     = $child->getName();
				$o->$k = sprintf("%s", $child);
			}

			// Either update or add records
			if (in_array($o->$key, $existingIds))
			{
				$db->updateObject($name, $o, $key);
			}
			else
			{
				$db->insertObject($name, $o, $key);
			}
		}

		return true;
	}

	/**
	 * Load list from form id
	 *
	 * @param   int $formId form id
	 *
	 * @throws \Exception
	 *
	 * @return  object  JTable
	 */

	public function loadFromFormId($formId)
	{
		throw new \Exception ('Admin load from form id called but ids no longer used');
		$item = $this->getTable();

		/**
		 * Not sure why but we need to populate and manually __state_set
		 * Otherwise list.id reverts to the form's id and not the list id
		 */
		$this->populateState();
		$this->__state_set = true;
		$item->load(array('form_id' => $formId));
		$this->table = $item;
		$this->set('list.id', $item->id);

		return $item;
	}

	/**
	 * Create an element
	 *
	 * @param   string $name Element name
	 * @param   array  $data Properties
	 *
	 * @return mixed false if failed, otherwise element plugin public properties
	 */
	public function makeElement($name, $data)
	{
		$pluginManager = Worker::getPluginManager();
		$element       = $pluginManager->loadPlugIn($data['plugin'], 'element');
		$item          = $element->getDefaultProperties();
		$item->id      = null;
		$item->name    = $name;
		$item->label   = str_replace('_', ' ', $name);

		return $item;
	}

	/**
	 * Return the default set of attributes when creating a new
	 * fabrik list
	 *
	 * @return string json encoded Params
	 */

	public function getDefaultParams()
	{
		$a                      = array('advanced-filter' => 0, 'show-table-nav' => 1, 'show-table-filters' => 1, 'show-table-add' => 1, 'require-filter' => 0);
		$o                      = (object) $a;
		$o->admin_template      = 'admin';
		$o->detaillink          = 0;
		$o->empty_data_msg      = 'No data found';
		$o->pdf                 = '';
		$o->rss                 = 0;
		$o->feed_title          = '';
		$o->feed_date           = '';
		$o->rsslimit            = 150;
		$o->rsslimitmax         = 2500;
		$o->csv_import_frontend = 3;
		$o->csv_export_frontend = 3;
		$o->csvfullname         = 0;
		$o->access              = 1;
		$o->allow_view_details  = 1;
		$o->allow_edit_details  = 1;
		$o->allow_add           = 1;
		$o->allow_delete        = 1;
		$o->group_by_order      = '';
		$o->group_by_order_dir  = 'ASC';
		$o->prefilter_query     = '';

		return json_encode($o);
	}

	/**
	 * Get the element ids for list ordering
	 *
	 * @since  3.0.7
	 *
	 * @return  array  element ids
	 */

	public function getOrderBys()
	{
		$item     = $this->getItem();
		$orderBys = Worker::JSONtoData($item->get('list.order_by'), true);

		foreach ($orderBys as &$orderBy)
		{
			$elementModel = $this->getElement($orderBy, true);
			$orderBy      = $elementModel ? $elementModel->getId() : '';
		}

		return $orderBys;
	}

}
