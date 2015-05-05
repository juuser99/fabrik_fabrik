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
use \JModelLegacy as JModelLegacy;
use \JText as JText;
use \FText as FText;
use \stdClass as stdClass;
use \JHTML as JHTML;
use Fabrik\Helpers\Worker;
use \JFactory as JFactory;
use Fabrik\Admin\Helpers\Fabrik;
use Fabrik\Helpers\ArrayHelper;
use \FabrikString as FabrikString;
use \RuntimeException as RuntimeException;
use \Joomla\Registry\Registry as JRegistry;
use \JComponentHelper as JComponentHelper;
use \JEventDispatcher as JEventDispatcher;
use \Fabrik\Plugins\Element as Element;

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
class Lizt extends Base implements ModelFormLiztInterface
{
	protected $name = 'list';
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var  string
	 */
	protected $text_prefix = 'COM_FABRIK_LIST';

	/**
	 * Front end form model
	 *
	 * @var object model
	 */
	protected $formModel = null;

	/**
	 * Front end list model
	 *
	 * @var object
	 */
	protected $feListModel = null;

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
		$this->storage = new Storage;
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
		$this->populateState();
		$input = $this->app->input;
		$user  = JFactory::getUser();
		$date  = JFactory::getDate();

		if (is_array($post))
		{
			$file  = JPATH_COMPONENT_ADMINISTRATOR . '/models/views/' . ArrayHelper::getValue($post, 'view') . '.json';

			if (file_exists($file))
			{
				$data = json_decode(file_get_contents($file));
			}
			else
			{
				$data       = new stdClass;
			}
			// We are saving from the form submission

			$post       = ArrayHelper::toObject($post);
			$data->list = $post;
			$data->view = $data->list->view;
			unset($data->list->view);
		}
		else
		{
			// We are saving from anywhere except the (new?) form - in this case the json file is being passed in
			$data = $post;
		}
//
		$data = new JRegistry($data);


		$data->set('list.order_by', $input->get('order_by', array(), 'array'));
		$data->set('list.order_dir', $input->get('order_dir', array(), 'array'));
		$data->set('checked_out', false);
		$data->set('checked_out_time', '');

		$this->collation($data);

		$file  = JPATH_COMPONENT_ADMINISTRATOR . '/models/views/' . $data->get('view') . '.json';
		$isNew = !\JFile::exists($file);

		if (!$isNew)
		{
			$dateNow = JFactory::getDate();
			$data->set('list.modified', $dateNow->toSql());
			$data->set('list.modified_by', $user->get('id'));
		}

		if ($isNew)
		{
			if ($data->get('list.created') == '')
			{
				$data->set('list.created', $date->toSql());
			}

			$newTable = trim($data->get('list._database_name', ''));
			// Mysql will force db table names to lower case even if you set the db name to upper case - so use clean()
			$newTable = FabrikString::clean($newTable);

			// Check the entered database table does not already exist
			if ($newTable != '' && $this->storage->tableExists($newTable))
			{
				throw new RuntimeException(FText::_('COM_FABRIK_DATABASE_TABLE_ALREADY_EXISTS'));

				return false;
			}

			if (!$this->storage->canCreate())
			{
				throw new RuntimeException(FText::_('COM_FABRIK_INSUFFICIENT_RIGHTS_TO_CREATE_TABLE'));

				return false;
			}

			// Create fabrik form
			$data->set('form', $this->createLinkedForm($data));

			// Create fabrik group
			$groupData       = Worker::formDefaults('group');
			$groupName       = FabrikString::clean($data->get('list.label'));
			$groupData->name = $groupName;

			$data->set('form.groups', new stdClass);
			$data->set('form.groups.' . $groupName, $this->createLinkedGroup($groupData, false));

			if ($newTable == '')
			{
				// New fabrik list but existing db $groupName
				$this->createLinkedElements($data, $groupName);
			}
			else
			{
				$data->set('list.db_table_name', $newTable);
				$data->set('list.auto_inc', 1);

				$dbOpts            = array();
				$params            = new JRegistry($data->get('list.params'));
				$dbOpts['COLLATE'] = $params->get('collation', '');

				$fields = array('id' => array('plugin' => 'internalid', 'primary_key' => true),
					'date_time' => array('plugin' => 'date'));
				$fields = (array) $this->get('defaultfields', $fields);

				foreach ($fields as $name => $fieldData)
				{
					$data->set('form.groups.' . $groupName . '.fields.' . $name, $this->makeElement($name, $fieldData));
				}

				$res = $this->createDBTable($newTable, $fields, $dbOpts);

				if (is_array($res))
				{
					$data->list->db_primary_key = $newTable . '.' . $res[0];
				}
			}
		}

		Fabrik::prepareSaveDate($data, 'list.publish_down');
		Fabrik::prepareSaveDate($data, 'list.created');
		Fabrik::prepareSaveDate($data, 'list.publish_up');

		$pk = $data->get('list.db_primary_key', '');

		if ($pk == '')
		{
			$pk    = $this->storage->getPrimaryKeyAndExtra();
			$key   = $pk[0]['colname'];
			$extra = $pk[0]['extra'];

			// Store without quoteNames as that is db specific
			if ($key)
			{
				$pk = $data->get('list.db_primary_key') == '' ? $data->get('list.db_table_name') . '.' . $key : $data->get('list.db_primary_key');
			}
			else
			{
				$pk = '';
			}

			$data->set('list.db_primary_key', $pk);

			$data->set('list.auto_inc', String::stristr($extra, 'auto_increment') ? true : false);
		}

		$this->updateJoins($data);

		$storage = $this->getStorage(array('table' => $data->get('list.db_table_name')));

		if (!$storage->isView())
		{
			$storage->updatePrimaryKey($data->get('list.db_primary_key', ''), $data->get('list.auto_inc'));
		}

		$data->set('list._database_name', null);
		$output = json_encode($data->toObject(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
		file_put_contents($file, $output);

		// Make an array of elements and a presumed index size, map is then used in creating indexes

		$this->set('id', $data->get('view'));
		$map = $this->fieldSizeMap();
		// FIXME - from here on - not tested for json views

		// Update indexes (added array_key_exists check as these may be during after CSV import)
		if (!empty($aOrderBy) && array_key_exists($data->list->order_by, $map))
		{
			foreach ($aOrderBy as $orderBy)
			{
				if (array_key_exists($orderBy, $map))
				{
					$this->storage->addIndex($orderBy, 'tableorder', 'INDEX', $map[$orderBy]);
				}
			}
		}

		$params = new JRegistry($data->get('list'));

		if (!is_null($data->get('list.group_by')))
		{
			if ($data->get('list.group_by') !== '' && array_key_exists($data->get('list.group_by'), $map))
			{
				$this->storage->addIndex($data->get('list.group_by'), 'groupby', 'INDEX', $map[$data->get('list.group_by')]);
			}

			if (trim($params->get('group_by_order')) !== '')
			{
				$this->storage->addIndex($params->get('group_by_order'), 'groupbyorder', 'INDEX', $map[$params->get('group_by_order')]);
			}
		}

		$filterFields = (array) $params->get('filter-fields', array());

		foreach ($filterFields as $field)
		{
			if (array_key_exists($field, $map))
			{
				$this->storage->addIndex($field, 'prefilter', 'INDEX', $map[$field]);
			}
		}

		parent::cleanCache('com_fabrik');

		return true;
	}

	protected function fieldSizeMap()
	{
		$map = array();
		$formModel = $this->getFormModel();
		$groups = $formModel->getGroupsHiarachy();

		foreach ($groups as $groupModel)
		{
			$elementModels = $groupModel->getMyElements();

			foreach ($elementModels as $element)
			{
				// Int and DATETIME elements cant have a index size attribute
				$colType = $element->getFieldDescription();

				if (String::stristr($colType, 'int'))
				{
					$size = '';
				}
				elseif (String::stristr($colType, 'datetime'))
				{
					$size = '';
				}
				else
				{
					$size = '10';
				}

				$map[$element->getFullName(false, false)] = $size;
				$map[$element->getElement()->id]                   = $size;
			}
		}

		return $map;
	}

	/**
	 * Alter the db table's collation
	 *
	 * @param   object $row Row being save
	 *
	 * @since   3.0.7
	 *
	 * @return boolean
	 */
	protected function collation($row)
	{
		// @FIXME - redo this after json view changes.
		return true;

		// Don't attempt to alter new table, or a view, or if we shouldn't alter the table
		if ($row->id == 0 || $this->storage->isView() || !$this->canAlterFields())
		{
			return;
		}

		$params        = new JRegistry($row->params);
		$origCollation = $params->get('collation', 'none');

		if (!empty($this->storage->table))
		{
			$origCollation = $this->storage->getCollation($origCollation);
		}

		$newCollation = $params->get('collation', 'none');

		if ($newCollation !== $origCollation)
		{
			return $this->storage->setCollation($newCollation);
		}

		return true;
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
			$this->storage->table = tableName;
		}

		return $this->storage->tableExists(tableName);
	}

	/**
	 * Deals with ensuring joins are managed correctly when table is saved
	 *
	 * @param   array $data jform data
	 *
	 * @return  void
	 */

	private function updateJoins($data)
	{
		// FIXME - test for json view;
		return;

		$db    = Worker::getDbo(true);
		$query = $db->getQuery(true);

		// If we are creating a new list then don't update any joins - can result in groups and elements being removed.
		if ((int) $this->get('list.id') === 0)
		{
			return;
		}
		// $$$ hugh - added "AND element_id = 0" to avoid fallout from "random join and group deletion" issue from May 2012
		$query->select('*')->from('#__fabrik_joins')->where('list_id = ' . (int) $this->get('list.id') . ' AND element_id = 0');
		$db->setQuery($query);
		$aOldJoins       = $db->loadObjectList();
		$params          = $data['params'];
		$aOldJoinsToKeep = array();
		$joinModel       = JModelLegacy::getInstance('Join', 'FabrikFEModel');
		$joinIds         = ArrayHelper::getValue($params, 'join_id', array());
		$joinTypes       = ArrayHelper::getValue($params, 'join_type', array());
		$joinTableFrom   = ArrayHelper::getValue($params, 'join_from_table', array());
		$joinTable       = ArrayHelper::getValue($params, 'table_join', array());
		$tableKey        = ArrayHelper::getValue($params, 'table_key', array());
		$joinTableKey    = ArrayHelper::getValue($params, 'table_join_key', array());
		$repeats         = ArrayHelper::getValue($params, 'join_repeat', array());
		$jc              = count($joinTypes);

		// Test for repeat elements to ensure their join isn't removed from here
		foreach ($aOldJoins as $oldJoin)
		{
			if ($oldJoin->params !== '')
			{
				$oldParams = json_decode($oldJoin->params);

				if (isset($oldParams->type) && $oldParams->type == 'repeatElement')
				{
					$aOldJoinsToKeep[] = $oldJoin->id;
				}
			}
		}

		for ($i = 0; $i < $jc; $i++)
		{
			$existingJoin = false;
			$thisJoin     = false;

			foreach ($aOldJoins as $oOldJoin)
			{
				if ($joinIds[$i] == $oOldJoin->id)
				{
					$existingJoin   = true;
					$joinsToIndex[] = $oOldJoin;
					break;
				}
			}

			if (!$existingJoin)
			{
				$joinsToIndex[] = $this->makeNewJoin($tableKey[$i], $joinTableKey[$i], $joinTypes[$i], $joinTable[$i], $joinTableFrom[$i], $repeats[$i][0]);
			}
			else
			{
				/* load in the exisitng join
				 * if the table_join has changed we need to create a new join
				 * (with its corresponding group and elements)
				 *  and mark the loaded one as to be deleted
				 */
				$joinModel->setId($joinIds[$i]);
				$joinModel->clearJoin();
				$join = $joinModel->getJoin();

				if ($join->table_join != $joinTable[$i])
				{
					$this->makeNewJoin($tableKey[$i], $joinTableKey[$i], $joinTypes[$i], $joinTable[$i], $joinTableFrom[$i], $repeats[$i][0]);
				}
				else
				{
					// The table_join has stayed the same so we simply update the join info
					$join->table_key      = str_replace('`', '', $tableKey[$i]);
					$join->table_join_key = $joinTableKey[$i];
					$join->join_type      = $joinTypes[$i];
					$join->store();

					// Update group
					$group = $this->getTable('Group');
					$group->load($join->group_id);
					$gparams                      = json_decode($group->params);
					$gparams->repeat_group_button = $repeats[$i][0] == 1 ? 1 : 0;
					$group->params                = json_encode($gparams);
					$group->store();
					$aOldJoinsToKeep[] = $joinIds[$i];
				}
			}
		}
		// Remove non existing joins
		if (is_array($aOldJoins))
		{
			foreach ($aOldJoins as $oOldJoin)
			{
				if (!in_array($oOldJoin->id, $aOldJoinsToKeep))
				{
					// Delete join
					$join = $this->getTable('Join');
					$joinModel->setId($oOldJoin->id);
					$joinModel->clearJoin();
					$joinModel->getJoin();
					$joinModel->deleteAll($oOldJoin->group_id);
				}
			}
		}

		// And finally, Esther ... index the join FK's
		foreach ($joinsToIndex as $thisJoin)
		{
			$fields  = $this->storage->getDBFields($thisJoin->table_join, 'Field');
			$fkField = ArrayHelper::getValue($fields, $thisJoin->table_join_key, false);
			switch ($pkField->BaseType)
			{
				case 'VARCHAR':
					$fkSize = (int) $fkField->BaseLength < 10 ? $fkField->BaseLength : 10;
					break;
				case 'INT':
				case 'DATETIME':
				default:
					$fkSize = '';
					break;
			}
			$joinField = $thisJoin->table_join . '___' . $thisJoin->table_join_key;
			$this->getStorage()->addIndex($joinField, 'join_fk', 'INDEX', $fkSize);
		}
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
	 * When saving a list that links to a database for the first time we
	 * need to create all the elements based on the database table fields and their
	 * column type
	 *
	 * @param   JRegistry $data      JSON view data
	 * @param   string    $groupName Group name
	 * @param   string    $table     Table name - if not set then use jform's db_table_name (@since 3.1)
	 *
	 * @return  void
	 */
	protected function createLinkedElements(&$data, $groupName = '', $table = '')
	{
		$table    = $table === '' ? $data->get('list.db_table_name') : $table;
		$elements = $this->makeElementsFromFields(0, $table);

		if ($groupName === '')
		{
			$groupData = new stdClass;
			$groupName = $data->list->label;
			$groupData->$groupName;
			$data->set('form.groups.' . $groupName, $this->createLinkedGroup($groupData, false));
		}

		$data->set('form.groups.' . $groupName . '.fields', $elements);
	}

	/**
	 * Take a table name and make elements for all of its fields
	 *
	 * @param   int    $groupId   group id
	 * @param   string $tableName table name
	 *
	 * @return  object  elements
	 */

	protected function makeElementsFromFields($groupId, $tableName)
	{
		$elements     = new stdClass;
		$fabrikDb     = $this->storage->db;
		$dispatcher   = JEventDispatcher::getInstance();
		$input        = $this->app->input;
		$elementModel = new Element($dispatcher);
		$user         = JFactory::getUser();
		$fbConfig     = JComponentHelper::getParams('com_fabrik');
		$elementTypes = $input->get('elementtype', array(), 'array');
		$fields       = $fabrikDb->getTableColumns($tableName, false);
		$createDate   = JFactory::getDate()->toSQL();
		$key          = $this->storage->getPrimaryKeyAndExtra($tableName);
		$ordering     = 0;
		/**
		 * no existing fabrik table so we take a guess at the most
		 * relevant element types to  create
		 */
		$elementLabels = $input->get('elementlabels', array(), 'array');

		foreach ($fields as $label => $properties)
		{
			$plugin     = 'field';
			$type       = $properties->Type;
			$maxLength  = 255;
			$maxLength2 = 0;

			if (preg_match("/\((.*)\)/i", $type, $matches))
			{
				$maxLength = ArrayHelper::getValue($matches, 1, 255);
				$maxLength = explode(',', $maxLength);

				if (count($maxLength) > 1)
				{
					$maxLength2 = $maxLength[1];
					$maxLength  = $maxLength[0];
				}
				else
				{
					$maxLength  = $maxLength[0];
					$maxLength2 = 0;
				}
			}

			// Get the basic type
			$type    = explode(" ", $type);
			$type    = ArrayHelper::getValue($type, 0, '');
			$type    = preg_replace("/\((.*)\)/i", '', $type);
			$element = file_get_contents(JPATH_COMPONENT_ADMINISTRATOR . '/models/schemas/element.json');
			$element = json_decode($element);

			if (array_key_exists($ordering, $elementTypes))
			{
				// If importing from a CSV file then we have user select field definitions
				$plugin = $elementTypes[$ordering];
			}
			else
			{
				// If the field is the primary key and it's an INT type set the plugin to be the fabrik internal id
				if ($key[0]['colname'] == $label && String::strtolower(substr($key[0]['type'], 0, 3)) === 'int')
				{
					$plugin = 'internalid';
				}
				else
				{
					// Otherwise set default type
					switch ($type)
					{
						case "int":
						case "decimal":
						case "tinyint":
						case "smallint":
						case "mediumint":
						case "bigint":
						case "varchar":
						case "time":
							$plugin = 'field';
							break;
						case "text":
						case "tinytext":
						case "mediumtext":
						case "longtext":
							$plugin = 'textarea';
							break;
						case "datetime":
						case "date":
						case "timestamp":
							$plugin = 'date';
							break;
						default:
							$plugin = 'field';
							break;
					}
				}
				// Then alter if defined in Fabrik global config
				// Jaanus: but first check if there are any pk field and if yes then create as internalid
				$defType = String::strtolower(substr($key[0]['type'], 0, 3));
				$plugin  = ($key[0]['colname'] == $label && $defType === 'int') ? 'internalid' : $fbConfig->get($type, $plugin);
			}

			$element->id                   = uniqid();
			$element->plugin               = $plugin;
			$element->hidden               = $element->label == 'id' ? '1' : '0';
			$element->group_id             = $groupId;
			$element->name                 = $label;
			$element->created              = $createDate;
			$element->created_by           = $user->get('id');
			$element->created_by_alias     = $user->get('username');
			$element->published            = '1';
			$element->show_in_list_summary = '1';

			switch ($plugin)
			{
				case 'textarea':
					$element->width = '40';
					break;
				case 'date':
					$element->width = '10';
					break;
				default:
					$element->width = '30';
					break;
			}

			if ($element->width > $maxLength)
			{
				$element->width = $maxLength;
			}

			$element->height = '6';
			$p               = $elementModel->getDefaultAttribs();

			if (in_array($type, array('int', 'tinyint', 'smallint', 'mediumint', 'bigint')) && $plugin == 'field')
			{
				$p->integer_length = $maxLength;
				$p->text_format    = 'integer';
				$p->maxlength      = '255';
				$element->width    = '30';
			}
			elseif ($type == 'decimal' && $plugin == 'field')
			{
				$p->text_format    = 'decimal';
				$p->decimal_length = $maxLength2;
				$p->integer_length = $maxLength - $maxLength2;
				$p->maxlength      = '255';
				$element->width    = '30';
			}
			else
			{
				$p->maxlength = $maxLength;
			}

			$element->params = $p;
			$element->label  = ArrayHelper::getValue($elementLabels, $ordering, str_replace('_', ' ', $label));

			//Format Label
			$labelConfig = $fbConfig->get('format_labels', '0');
			switch ($labelConfig)
			{
				case '1':
					$element->label = strtolower($element->label);
					break;
				case '2':
					$element->label = ucwords($element->label);
					break;
				case '3':
					$element->label = ucfirst($element->label);
					break;
				case '4':
					$element->label = strtoupper($element->label);
					break;
				default:
					break;
			}

			$name            = $element->name;
			$elements->$name = $element;

			// FIXME - test what happens on save for user element etc.
			/*$element->store();
			$elementModel = $pluginManager->getPlugIn($element->plugin, 'element');
			$elementModel->setId($element->id);
			$elementModel->element = $element;

			// Hack for user element
			$details = array('group_id' => $element->group_id);
			$input->set('details', $details);
			$elementModel->onSave(array());*/
			$ordering++;
		}

		return $elements;
	}

	/**
	 * When saving a list that links to a database for the first time we
	 * automatically create a form to allow the update/creation of that tables
	 * records
	 *
	 * @param   JRegistry $view   View containing all info
	 * @param   int       $formId to copy from. If = 0 then create a default form. If not 0 then copy the form id
	 *                            passed in
	 *
	 * @return  object  form model
	 */

	private function createLinkedForm($view, $formId = 0)
	{
		$user = JFactory::getUser();
		$this->getFormModel();

		if ($formId == 0)
		{
			$this->formModel->getForm();
			jimport('joomla.utilities.date');
			$createDate = JFactory::getDate();
			$createDate = $createDate->toSql();

			$form = Worker::formDefaults('form');

			$form->id                  = uniqid();
			$form->label               = $view->get('list.label');
			$form->record_in_database  = 1;
			$form->created             = $createDate;
			$form->created_by          = $user->get('id');
			$form->created_by_alias    = $user->get('username');
			$form->error               = FText::_('COM_FABRIK_FORM_ERROR_MSG_TEXT');
			$form->submit_button_label = FText::_('COM_FABRIK_SAVE');
			$form->published           = $view->get('list.published');
			$form->form_template       = 'bootstrap';
			$form->view_only_template  = 'bootstrap';
		}
		else
		{
			// @TODO json view this.
			$this->set('list.form_id', $formId);
			$this->formModel->setId($formId);
			$this->formModel->getTable();
			$this->formModel->copy();
		}

		return $form;
	}

	/**
	 * Create a group
	 * used when creating a fabrik table from an existing db table
	 *
	 * @param   stdClass $data     group data
	 * @param   bool     $isJoin   is the group a join default false
	 * @param   bool     $isRepeat is the group repeating
	 *
	 * @return  stdClass Group
	 */

	private function createLinkedGroup($data, $isJoin = false, $isRepeat = false)
	{
		$user             = JFactory::getUser();
		$createDate       = JFactory::getDate();
		$data->created    = $createDate->toSql();
		$data->created_by = $user->get('id');
		if (!isset($data->fields))
		{
			$data->fields = new stdClass;
		}

		$data->id                      = uniqid();
		$data->created_by_alias        = $user->get('username');
		$data->published               = 1;
		$opts                          = new stdClass;
		$data->repeat_group_button     = $isRepeat ? 1 : 0;
		$opts->repeat_group_show_first = 1;
		$data->params                  = $opts;
		$data->is_join                 = ($isJoin == true) ? 1 : 0;

		return $data;
	}

	/**
	 * Method to copy one or more records.
	 *
	 * @FIXME    for json views
	 *
	 * @return  boolean    True if successful, false if an error occurs.
	 *
	 * @since    1.6
	 */

	public function copy()
	{
		$db    = Worker::getDbo(true);
		$user  = JFactory::getUser();
		$input = $this->app->input;
		$pks   = $input->get('cid', array(), 'array');
		$names = $input->get('names', array(), 'array');

		foreach ($pks as $i => $pk)
		{
			$item = $this->getTable();
			$item->load($pk);
			$item->id = null;
			$input->set('newFormLabel', $names[$pk]['formLabel']);
			$input->set('newGroupNames', $names[$pk]['groupNames']);

			// FIXME - not right params
			$formModel = $this->createLinkedForm($item->form_id);

			// $$$ rob 20/12/2011 - any element id stored in the list needs to get mapped to the new element ids

			$elementMap = $formModel->newElements;
			$params     = json_decode($item->params);
			$toMaps     = array(array('list_search_elements', 'search_elements'), array('csv_elements', 'show_in_csv'));

			foreach ($toMaps as $toMap)
			{
				$key  = $toMap[0];
				$key2 = $toMap[1];
				$orig = json_decode($params->$key);
				$new  = array();

				foreach ($orig->$key2 as $elementId)
				{
					$new[] = $elementMap[$elementId];
				}

				$c            = new stdClass;
				$c->$key2     = $new;
				$params->$key = json_encode($c);
			}

			$item->form_id          = $formModel->getTable()->id;
			$createDate             = JFactory::getDate();
			$createDate             = $createDate->toSql();
			$item->label            = $names[$pk]['listLabel'];
			$item->created          = $createDate;
			$item->modified         = $db->getNullDate();
			$item->modified_by      = $user->get('id');
			$item->hits             = 0;
			$item->checked_out      = 0;
			$item->checked_out_time = $db->getNullDate();
			$item->params           = json_encode($params);

			if (!$item->store())
			{
				$this->setError($item->getError());

				return false;
			}

			$this->set('list.id', $item->id);

			// Test for seeing if joins correctly stored when coping new table
			$this->copyJoins($pk, $item->id, $formModel->groupidmap);
		}

		return true;
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
	 * Create a table to store the forms' data depending upon what groups are assigned to the form
	 *
	 * @param   string $name   Taken from the table object linked to the form
	 * @param   array  $fields List of default elements to add. (key = element name, value = plugin
	 * @param   array  $opts   Additional options, e.g. collation
	 *
	 * @return mixed false if fail otherwise array of primary keys
	 */

	public function createDBTable($name = null, $fields = array('id' => 'internalid', 'date_time' => 'date'), $opts = array())
	{
		$storage = $this->getStorage();

		return $storage->createTable($name, $fields, $opts);
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
	 * Alter the forms' data collection table when the forms' groups and/or
	 * elements are altered
	 *
	 * @return void|JError
	 */

	public function ammendTable()
	{
		$db             = Worker::getDbo(true);
		$input          = $this->app->input;
		$query          = $db->getQuery(true);
		$table          = $this->table;
		$pluginManager  = JModelLegacy::getInstance('Pluginmanager', 'FabrikFEModel');
		$amend          = false;
		$tableName      = $table->db_table_name;
		$fabrikDb       = $this->getDb();
		$columns        = $fabrikDb->getTableColumns($tableName);
		$existingFields = array_keys($columns);
		$existingFields = array_map('strtolower', $existingFields);
		$lastField      = empty($existingFields) ? '' : $existingFields[count($existingFields) - 1];
		$sql            = 'ALTER TABLE ' . $db->quoteName($tableName) . ' ';
		$sqlAdd         = array();

		// $$$ hugh - looks like this is now an array in jform
		$jForm    = $input->get('jform', array(), 'array');
		$arGroups = ArrayHelper::getValue($jForm, 'current_groups', array(), 'array');

		if (empty($arGroups))
		{
			// Get a list of groups used by the form
			$query->select('group_id')->from('#__fabrik_formgroup')->where('form_id = ' . (int) $this->getFormModel()->getId());
			$db->setQuery($query);
			$groups   = $db->loadObjectList();
			$arGroups = array();

			foreach ($groups as $g)
			{
				$arGroups[] = $g->group_id;
			}
		}

		$arAddedObj = array();

		foreach ($arGroups as $group_id)
		{
			$group = FabTable::getInstance('Group', 'FabrikTable');
			$group->load($group_id);

			if ($group->is_join == '0')
			{
				$query->clear();
				$query->select('*')->from('#__fabrik_elements')->where('group_id = ' . (int) $group_id);
				$db->setQuery($query);
				$elements = $db->loadObjectList();

				foreach ($elements as $obj)
				{
					$objName = $obj->name;

					/*
					 * Do the check in lowercase (we already strtowlower()'ed $existingFields up there ^^,
					 * because MySQL field names are case insensitive, so if the element is called 'foo' and there
					 * is a column called 'Foo', and we try and create 'foo' on the table ... it'll blow up.
					 * 
					 * However, leave the $objName unchanged, so if we do create a column for it, it uses the case
					 * they specific in the element name - it's not up to us to force their column naming to all lower,
					 * we just need to avoid clashes.
					 * 
					 * @TODO We might consider detecting and raising a warning about case inconsistencies?
					 */

					if (!in_array(strtolower($objName), $existingFields))
					{
						// Make sure that the object is not already in the table
						if (!in_array($objName, $arAddedObj))
						{
							// Any elements that are names the same (eg radio buttons) can not be entered twice into the database
							$arAddedObj[]    = $objName;
							$pluginClassName = $obj->plugin;
							$plugin          = $pluginManager->getPlugIn($pluginClassName, 'element');

							if (is_object($plugin))
							{
								$plugin->setId($obj->id);
								$objType = $plugin->getFieldDescription();
							}
							else
							{
								$objType = 'VARCHAR(255)';
							}

							if ($objName != "" && !is_null($objType))
							{
								$amend = true;
								$add   = "ADD COLUMN " . $db->quoteName($objName) . " $objType null";

								if ($lastField !== '')
								{
									$add .= " AFTER " . $db->quoteName($lastField);
								}

								$sqlAdd[] = $add;
							}
						}
					}
				}
			}
		}

		if ($amend)
		{
			$sql .= implode(', ', $sqlAdd);
			$fabrikDb->setQuery($sql);

			try
			{
				$fabrikDb->execute();
			} catch (Exception $e)
			{
				throw new \Exception('amend table: ' . $e->getMessage());
			}
		}
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
