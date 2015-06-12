<?php
/**
 * Admin Element Model
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

use Exception;
use JDate;
use JEventDispatcher;
use Joomla\String\String;
use \JPluginHelper as JPluginHelper;
use \FText as FText;
use RuntimeException;
use \stdClass as stdClass;
use Fabrik\Helpers\ArrayHelper;
use Fabrik\Helpers\Worker;
use \JText as JText;
use \JComponentHelper as JComponentHelper;
use \JForm as JForm;
use \Joomla\Registry\Registry as JRegistry;
use \FabrikString as FabrikString;

interface ModelElementFormInterface
{
}

/**
 * Admin Element Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.5
 */
class Element extends Base implements ModelElementFormInterface
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var  string
	 */
	protected $text_prefix = 'COM_FABRIK_ELEMENT';

	/**
	 * Element Object
	 *
	 * @var object
	 */
	protected $element = null;
	/**
	 * Validation plugin models for this element
	 *
	 * @since    3.0.6
	 *
	 * @var  array
	 */
	protected $aValidations = null;

	/**
	 * Core Joomla and Fabrik table names
	 *
	 * @var  array
	 */
	protected $core = array('#__assets', '#__banner_clients', '#__banner_tracks', '#__banners', '#__categories', '#__contact_details', '#__content',
		'#__content_frontpage', '#__content_rating', '#__core_log_searches', '#__extensions', '#__fabrik_connections', '#__fabrik_cron',
		'#__fabrik_elements', '#__fabrik_form_sessions', '#__fabrik_formgroup', '#__fabrik_forms', '#__fabrik_groups',
		'#__fabrik_joins', '#__fabrik_jsactions', '#__fabrik_lists', '#__fabrik_log', '#__fabrik_packages',
		'#__fabrik_validations', '#__fabrik_visualizations', '#__fb_contact_sample', '#__languages', '#__menu', '#__menu_types', '#__messages',
		'#__messages_cfg', '#__modules', '#__modules_menu', '#__newsfeeds', '#__redirect_links', '#__schemas', '#__session', '#__template_styles',
		'#__update_categories', '#__update_sites', '#__update_sites_extensions', '#__updates', '#__user_profiles', '#__user_usergroup_map',
		'#__usergroups', '#__users', '#__viewlevels', '#__weblinks');


	/**
	 * Toggle adding / removing the element from the list view
	 *
	 * @param   array &$pks  primary keys
	 * @param   int   $value add (1) or remove (0) from list view
	 *
	 * @return  bool
	 */

	public function addToListView(&$pks, $value = 1)
	{
		// Initialise variables.
		$dispatcher = JEventDispatcher::getInstance();
		$user       = JFactory::getUser();
		$item       = $this->getTable();
		$pks        = (array) $pks;

		// Include the content plugins for the change of state event.
		JPluginHelper::importPlugin('content');

		// Access checks.
		foreach ($pks as $i => $pk)
		{
			if ($item->load($pk))
			{
				if (!$this->canEditState($item))
				{
					// Prune items that you can't change.
					unset($pks[$i]);
					JError::raiseWarning(403, FText::_('JLIB_APPLICATION_ERROR_EDIT_STATE_NOT_PERMITTED'));
				}
			}
		}

		// Attempt to change the state of the records.
		if (!$item->addToListView($pks, $value, $user->get('id')))
		{
			$this->setError($item->getError());

			return false;
		}

		$context = $this->option . '.' . $this->name;

		// Trigger the onContentChangeState event.
		$result = $dispatcher->trigger($this->event_change_state, array($context, $pks, $value));

		if (in_array(false, $result, true))
		{
			$this->setError($item->getError());

			return false;
		}

		return true;
	}

	/**
	 * Get the js events that are used by the element
	 *
	 * @return  array
	 */

	public function getJsEvents()
	{
		$items = $this->element->get('jsevents', array());

		return $items;
	}

	/**
	 * Load the actual validation plugins that the element uses
	 *
	 * @return  array  plugins
	 */

	public function getPlugins($subView = 'list')
	{
		return $this->element->get('validations', array());
		/*	$item = $this->getItem();
			$plugins = (array) ArrayHelper::getNestedValue($item->params, 'validations.plugin', array());
			$published = (array) ArrayHelper::getNestedValue($item->params, 'validations.plugin_published', array());
			$icons = (array) ArrayHelper::getNestedValue($item->params, 'validations.show_icon', array());
			$in = (array) ArrayHelper::getNestedValue($item->params, 'validations.validate_in', array());
			$on = (array) ArrayHelper::getNestedValue($item->params, 'validations.validation_on', array());

			$return = array();

			for ($i = 0; $i < count($plugins); $i ++)
			{
				$o = new stdClass;
				$o->plugin = $plugins[$i];
				$o->published = ArrayHelper::getValue($published, $i, 1);
				$o->show_icon = ArrayHelper::getValue($icons, $i, 1);
				$o->validate_in = ArrayHelper::getValue($in, $i, 'both');
				$o->validation_on = ArrayHelper::getValue($on, $i, 'both');
				$return[] = $o;
			}

			return $return;*/
	}

	/**
	 * Get the js code to build the plugins etc
	 *
	 * @return  string  js code
	 */
	public function getJs()
	{
		$opts               = new stdClass;
		$opts->plugin       = $this->element->get('plugin');
		$opts->jsevents     = $this->getJsEvents();
		$opts->elementId    = $this->element->get('id');
		$opts->id           = $this->get('id');
		$opts->deleteButton = '<a class="btn btn-danger"><i class="icon-delete"></i> ';
		$opts->deleteButton .= FText::_('COM_FABRIK_DELETE') . '</a>';
		$opts = json_encode($opts);
		JText::script('COM_FABRIK_PLEASE_SELECT');
		JText::script('COM_FABRIK_JS_SELECT_EVENT');
		JText::script('COM_FABRIK_JS_INLINE_JS_CODE');
		JText::script('COM_FABRIK_JS_INLINE_COMMENT_WARNING');
		JText::script('COM_FABRIK_JS_WHEN_ELEMENT');
		JText::script('COM_FABRIK_JS_IS');
		JText::script('COM_FABRIK_JS_NO_ACTION');
		$js[] = "window.addEvent('domready', function () {";
		$js[] = "\tvar opts = $opts;";

		$plugins = json_encode($this->getPlugins());
		$js[]    = "\tFabrik.controller = new fabrikAdminElement($plugins, opts, '" . $this->element->get('id') . "');";
		$js[]    = "})";

		return implode("\n", $js);
	}

	/**
	 * Get html form fields for a plugin (filled with
	 * current element's plugin data
	 *
	 * @param   string $plugin plugin name
	 *
	 * @return  string    html form fields
	 */

	public function getPluginHTML($plugin = null)
	{
		$item = $this->getElement();

		if (is_null($plugin))
		{
			$plugin = $item->get('plugin', '');
		}
		// FIXME - not showing plugin options when you load the form.
// Should not be setting input directly in a model - poor design
		//$input->set('view', 'element');
		JPluginHelper::importPlugin('fabrik_element', $plugin);
		$pluginManager = new PluginManager;

		if ($plugin == '')
		{
			$str = '<div class="alert">' . FText::_('COM_FABRIK_SELECT_A_PLUGIN') . '</div>';
		}
		else
		{
			$plugin = $pluginManager->getPlugIn($plugin, 'Element');
			$plugin->setModel($this);
			$str = $plugin->onRenderAdminSettings(ArrayHelper::fromObject($item), null, 'nav-tabs');
		}

		return $str;
	}

	/**
	 * Prepare and sanitise the table data prior to saving.
	 *
	 * @param   JTable $table A reference to a JTable object.
	 *
	 * @return  void
	 *
	 * @since   12.2
	 */
	protected function prepareTable($table)
	{
	}

	/**
	 * Validate the form
	 *
	 * @param   array $data The data to validate.
	 *
	 * @throws RunTimeException
	 *
	 * @return array  data
	 */
	public function validate($data)
	{
		$ok    = parent::validate($data);
		$input = $this->app->input;

		// Standard jform validation failed so we shouldn't test further as we can't be sure of the data
		if (!$ok)
		{
			return false;
		}

		$db           = Worker::getDbo(true);
		$elementModel = $this->getElementPluginModel($data);
		$nameChanged  = $data['name'] !== $elementModel->getElement()->get('name');
		$elementModel->getElement()->loadArray($data);
		$listModel = $elementModel->getListModel();

		if ($data['id'] == '0')
		{
			// Have to forcefully set group id otherwise listmodel id is blank

			if ($listModel->canAddFields() === false && $listModel->noTable() === false)
			{
				throw new RuntimeException(FText::_('COM_FABRIK_ERR_CANT_ADD_FIELDS'));
			}

			if (Worker::isReserved($data['name']))
			{
				throw new RuntimeException(FText::_('COM_FABRIK_RESEVED_NAME_USED'));
			}
		}
		else
		{
			if ($listModel->canAlterFields() === false && $nameChanged && $listModel->noTable() === false)
			{
				throw new RuntimeException(FText::_('COM_FABRIK_ERR_CANT_ALTER_EXISTING_FIELDS'));
			}

			if ($nameChanged && Worker::isReserved($data['name'], false))
			{
				throw new RuntimeException(FText::_('COM_FABRIK_RESEVED_NAME_USED'));
			}
		}

		/**
		 * Test for duplicate names
		 * unlinking produces this error
		 */

		if (!$input->get('unlink', false) && (int) $data['id'] === 0)
		{
			$query = $db->getQuery(true);

			// FIXME - jsonify
			$query->select('t.id')->from('#__fabrik_joins AS j');
			$query->join('INNER', '#__fabrik_lists AS t ON j.table_join = t.db_table_name');
			$query->where('group_id = ' . (int) $data['group_id'] . ' AND element_id = 0');
			$db->setQuery($query);
			$joinTblId = (int) $db->loadResult();
			$ignore    = array($data['id']);

			if ($joinTblId === 0)
			{
				if ($listModel->fieldExists($data['name'], $ignore))
				{
					throw new Exception(FText::_('COM_FABRIK_ELEMENT_NAME_IN_USE'));
				}
			}
			else
			{
				$joinListModel = new Join;
				$joinListModel->setId($joinTblId);
				$joinEls = $joinListModel->getElements();

				foreach ($joinEls as $joinEl)
				{
					if ($joinEl->getElement()->name == $data['name'])
					{
						$ignore[] = $joinEl->getElement()->id;
					}
				}

				if ($joinListModel->fieldExists($data['name'], $ignore))
				{
					throw new Exception(FText::_('COM_FABRIK_ELEMENT_NAME_IN_USE'));
				}
			}
		}
		// Strip <p> tag from label
		$data['label'] = String::str_ireplace(array('<p>', '</p>'), '', $data['label']);

		return $data;
	}

	/**
	 * Load the element plugin / model for the posted data
	 *
	 * @param   array $data posted data
	 *
	 * @return  object  element model
	 */
	private function getElementPluginModel($data)
	{
		$pluginManager = new PluginManager;
		$id            = $data['id'];
		$elementModel  = $pluginManager->getPlugIn($data['plugin'], 'element');
		$elementModel->setModel($this);
		/**
		 * $$$ rob f3 - need to bind the data in here otherwise validate fails on dup name test (as no group_id set)
		 * $$$ rob 29/06/2011 removed as you can't then test name changes in validate() so now bind should be done after
		 * getElementPluginModel is called.
		 * $elementModel->getElement()->bind($data);
		 */
		$elementModel->setId($id);

		return $elementModel;
	}

	/**
	 * Method to save the form data.
	 *
	 * @param   array $data The form data.
	 *
	 * @return  boolean  True on success, False on error.
	 */

	public function save($data)
	{
		$config = JComponentHelper::getParams('com_fabrik');

		if ($config->get('fbConf_wysiwyg_label', 0) == 0)
		{
			// Ensure the data is in the same format as when saved by the wysiwyg element e.g. < becomes &lt;
			$data['label'] = htmlspecialchars($data['label']);
		}

		$input        = $this->app->input;
		$new          = $data['id'] == 0 ? true : false;
		$data['name'] = FabrikString::iclean($data['name']);
		$name         = $data['name'];
		$elementModel = $this->getElementPluginModel($data);
		$row          = $elementModel->getElement()->loadArray($data);
		$row->set('params.validations', ArrayHelper::getValue($data, 'validationrule', array()));

		if ($new)
		{
			$row->set('id', uniqid());
		}
		$origId = $input->getString('id');

		$listModel = $elementModel->getListModel();
		$this->listModel = $listModel;
		$item      = $listModel->getItem();

		// Only update the element name if we can alter existing columns, otherwise the name and field name become out of sync
		$name = ($listModel->canAlterFields() || $new || $listModel->noTable()) ? $name : $input->get('name_orig', '');
		$row->set('name', $name);
		$this->prepareSaveListPrimaryKey($item, $row);

		$this->prepareSaveDefaults($row);

		/**
		 * $$$ rob - test for change in element type
		 * (eg if changing from db join to field we need to remove the join
		 * entry from the #__fabrik_joins table
		 */
		$elementModel->beforeSave($row);
		$this->prepareSaveDates($row);
		$this->prepareSaveValidations($elementModel, $row);

		// FIXME - ordering of elements

		$origName = $input->get('name_orig', '');
		list($update, $q, $oldName, $newDesc, $origDesc) = $listModel->shouldUpdateElement($elementModel, $origName);

		if ($update && $input->get('task') !== 'save2copy')
		{
			$origPlugin = $input->get('plugin_orig');
			$prefix     = $this->config->get('dbprefix');
			$tableName  = $listModel->getItem()->get('list.db_table_name');
			$hasPrefix  = (strstr($tableName, $prefix) === false) ? false : true;
			$tableName  = str_replace($prefix, '#__', $tableName);

			if (in_array($tableName, $this->core))
			{
				$this->app->enqueueMessage(FText::_('COM_FABRIK_WARNING_UPDATE_CORE_TABLE'), 'notice');
			}
			else
			{
				if ($hasPrefix)
				{
					$this->app->enqueueMessage(FText::_('COM_FABRIK_WARNING_UPDATE_TABLE_WITH_PREFIX'), 'notice');
				}
			}

			$this->app->setUserState('com_fabrik.confirmUpdate', 1);

			$this->app->setUserState('com_fabrik.plugin_orig', $origPlugin);
			$this->app->setUserState('com_fabrik.q', $q);
			$this->app->setUserState('com_fabrik.newdesc', $newDesc);
			$this->app->setUserState('com_fabrik.origDesc', $origDesc);

			$this->app->setUserState('com_fabrik.origplugin', $origPlugin);
			$this->app->setUserState('com_fabrik.oldname', $oldName);
			$this->app->setUserState('com_fabrik.newname', $data['name']);
			$this->app->setUserState('com_fabrik.origtask', $input->get('task'));
			$this->app->setUserState('com_fabrik.plugin', $data['plugin']);
			$task = $input->get('task');
			$url  = 'index.php?option=com_fabrik&view=element&layout=confirmupdate&id=' . $origId . '&origplugin=' . $origPlugin . '&origtask='
				. $task . '&plugin=' . $row->get('plugin');
			$this->app->setUserState('com_fabrik.redirect', $url);
		}
		else
		{
			$this->app->setUserState('com_fabrik.confirmUpdate', 0);
		}

		if ($item->get('list.db_table_name', '') !== '')
		{
			$this->updateIndexes($elementModel, $listModel, $row);
		}


		$this->updateJavascript($row);
		$elementModel->setId($row->get('id'));
		$this->createRepeatElement($elementModel, $row);

		// If new, check if the element's db table is used by other tables and if so add the element  to each of those tables' groups
		if ($new)
		{
			$this->addElementToOtherDbTables($elementModel, $row);
		}

		$elementModel->onSave($data);

		parent::cleanCache('com_fabrik');

		$groupKey = $this->groupKey($row->get('group_id'));
		$fields = $item->get("form.groups.$groupKey.fields");
		$fields->$name = $row->toObject();
		$item->set("form.groups.$groupKey.fields", $fields);
		$file = JPATH_COMPONENT_ADMINISTRATOR . '/models/views/' . $item->get('view') . '.json';
		$output = json_encode($item->toObject(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
		file_put_contents($file, $output);
		return true;
	}

	protected function groupKey($groupId)
	{
		$item = $this->getListModel()->getItem();
		$groups = $item->get('form.groups');

		foreach ($groups as $groupKey => $group)
		{
			if ($group->id === $groupId)
			{
				return $groupKey;
			}
		}
	}

	protected function prepareSaveListPrimaryKey(&$item, $row)
	{
		// Are we updating the name of the primary key element?
		if ($row->get('name') === FabrikString::shortColName($item->get('list.db_primary_key')))
		{
			$pk = $item->get('list.db_primary_key');
			$bits = explode('.', $pk);
			$bits[1] = $row->get('name');
			$pk = join('.', $pk);
			$item->set('list.db_primary_key', $pk);
		}

	}

	protected function prepareSaveDefaults(&$row)
	{
		$ar = array('published', 'use_in_page_title', 'show_in_list_summary', 'link_to_detail', 'can_order', 'filter_exact_match');

		foreach ($ar as $a)
		{
			$row->set($a, 0);
		}
	}

	protected function prepareSaveDates(&$row)
	{
		jimport('joomla.utilities.date');
		$user    = $this->user;
		$dateNow = new JDate;

		if ($row->get('id') != 0)
		{
			$row->set('modified', $dateNow->toSql());
			$row->set('modified_by', $user->get('id'));
		}
		else
		{
			$row->set('created', $dateNow->toSql());
			$row->set('created_by', $user->get('id'));
			$row->set('created_by_alias', $user->get('username'));
		}
	}

	protected function prepareSaveValidations($elementModel, &$row)
	{
		/**
		 * $$$ hugh
		 * This insane chunk of code is needed because the validation rule params are not in sequential,
		 * completely indexed arrays.  What we have is single item arrays, with specific numeric
		 * keys, like foo-something[0], bar-otherthing[2], etc.  And if you json_encode an array with incomplete
		 * or out of sequence numeric indexes, it encodes it as an object instead of an array.  Which means the first
		 * validation plugin will encode as an array, as it's params are single [0] index, and the rest as objects.
		 * This foobars things, as we then don't know if validation params are arrays or objects!
		 *
		 * One option would be to modify every validation, and test every param we use, and if necessary convert it,
		 * but that would be a major pain in the ass.
		 *
		 * So ... we need to fill in the blanks in the arrays, and ksort them.  But, we need to know the param names
		 * for each validation.  But as they are just stuck in with the rest of the element params, there is no easy
		 * way of knowing which are validation params and which are element params.
		 *
		 * So ... we need to load the validation objects, then load the XML file for each one, and iterate through
		 * the fieldsets!  Well, that's the only way I could come up with doing it.  Hopefully Rob can come up with
		 * a quicker and simpler way of doing this!
		 */
		$validations        = ArrayHelper::getValue($params['validations'], 'plugin', array());
		$num_validations    = count($validations);
		$validation_plugins = $this->getValidations($elementModel, $validations);

		foreach ($validation_plugins as $plugin)
		{
			$plugin_form = $plugin->getJForm();
			JForm::addFormPath(JPATH_SITE . '/plugins/fabrik_validationrule/' . $plugin->get('pluginName'));
			$xmlFile = JPATH_SITE . '/plugins/fabrik_validationrule/' . $plugin->get('pluginName') . '/forms/fields.xml';
			$plugin->jform->loadFile($xmlFile, false);

			foreach ($plugin_form->getFieldsets() as $fieldset)
			{
				foreach ($plugin_form->getFieldset($fieldset->name) as $field)
				{
					if (isset($params[$field->fieldname]))
					{
						if (is_array($params[$field->fieldname]))
						{
							for ($x = 0; $x < $num_validations; $x++)
							{
								if (!(array_key_exists($x, $params[$field->fieldname])))
								{
									$params[$field->fieldname][$x] = '';
								}
							}

							ksort($params[$field->fieldname]);
						}
					}
				}
			}
		}
	}

	/**
	 * When saving an element, it may need to be added to other Fabrik lists
	 * If those lists point to the same database table.
	 *
	 * @param   object    $elementModel element
	 * @param   JRegistry $row          item
	 *
	 * @return  void
	 */

	private function addElementToOtherDbTables($elementModel, $row)
	{
		// FIXME for 3.5
		return;
		$db            = Worker::getDbo(true);
		$list          = $elementModel->getListModel()->getTable();
		$origElid      = $row->get('id');
		$tmpgroupModel = $elementModel->getGroup();
		$config        = JComponentHelper::getParams('com_fabrik');

		if ($tmpgroupModel->isJoin())
		{
			$tableName = $tmpgroupModel->getJoinModel()->getJoin()->table_join;
		}
		else
		{
			$tableName = $list->get('list.db_table_name');
		}

		$query = $db->getQuery(true);

		// FIXME - jsonify
		$query->select("DISTINCT(l.id) AS id, db_table_name, l.label, l.form_id, l.label AS form_label, g.id AS group_id");
		$query->from("#__fabrik_lists AS l");
		$query->join('INNER', '#__fabrik_forms AS f ON l.form_id = f.id');
		$query->join('LEFT', '#__fabrik_formgroup AS fg ON f.id = fg.form_id');
		$query->join('LEFT', '#__fabrik_groups AS g ON fg.group_id = g.id');
		$query->where("db_table_name = " . $db->q($tableName) . " AND l.id !=" . (int) $list->id . " AND is_join = 0");

		$db->setQuery($query);
		$otherTables = $db->loadObjectList('id');

		/**
		 * $$$ rob 20/02/2012 if you have 2 lists, counters, regions and then you join regions to countries to get a new group "countries - [regions]"
		 * Then add elements to the regions list, the above query wont find the group "countries - [regions]" to add the elements into
		 */

		$query->clear();
		// FIXME - jsonify
		$query->select('DISTINCT(l.id) AS id, l.db_table_name, l.label, l.form_id, l.label AS form_label, fg.group_id AS group_id')
			->from('#__fabrik_joins AS j')->join('LEFT', '#__fabrik_formgroup AS fg ON fg.group_id = j.group_id')
			->join('LEFT', '#__fabrik_forms AS f ON fg.form_id = f.id')->join('LEFT', '#__fabrik_lists AS l ON l.form_id = f.id')
			->where('j.table_join = ' . $db->q($tableName) . ' AND j.list_id <> 0 AND j.element_id = 0 AND list_id <> ' . (int) $list->id);
		$db->setQuery($query);
		$joinedLists = $db->loadObjectList('id');
		$otherTables = array_merge($joinedLists, $otherTables);

		if (!empty($otherTables))
		{
			/**
			 * $$$ hugh - we use $row after this, so we need to work on a copy, otherwise
			 * (for instance) we redirect to the wrong copy of the element
			 */
			$rowCopy = clone ($row);

			foreach ($otherTables as $listId => $t)
			{
				$rowCopy->set('id', 0);
				$rowCopy->set('group_id', $t->group_id);
				$rowCopy->set('name', str_replace('`', '', $rowCopy->name));

				if ($config->get('unpublish_clones', false))
				{
					$rowCopy->set('published', 0);
				}

				// FIXME cant store JRegistry
				$rowCopy->store();

				// Copy join records
				// FIXME
				$join = $this->getTable('join');

				if ($join->load(array('element_id' => $origElid)))
				{
					$join->id = 0;
					unset($join->id);
					$join->element_id = $rowCopy->id;
					$join->list_id    = $listId;
					$join->store();
				}
			}
		}
	}

	/**
	 * Update table indexes based on element settings
	 *
	 * @param   object    &$elementModel element model
	 * @param   object    &$listModel    list model
	 * @param   JRegistry &$row          element item
	 *
	 * @return  void
	 */
	private function updateIndexes(&$elementModel, &$listModel, &$row)
	{
		return;
		// FIXME for 3.5
		if ($elementModel->getGroup()->isJoin())
		{
			return;
		}
		// Update table indexes
		$type = $elementModel->getFieldDescription();

		// Int elements can't have a index size attrib
		$size = String::stristr($type, 'int') || $type == 'DATETIME' ? '' : '10';

		if ($elementModel->getParams()->get('can_order'))
		{
			$listModel->addIndex($row->get('name'), 'order', 'INDEX', $size);
		}
		else
		{
			$listModel->dropIndex($row->get('name'), 'order', 'INDEX');
		}

		if ($row->get('filter_type', '') !== '')
		{
			$listModel->addIndex($row->get('name'), 'filter', 'INDEX', $size);
		}
		else
		{
			$listModel->dropIndex($row->get('name'), 'filter', 'INDEX');
		}
	}

	/**
	 * Delete old javascript actions for the element
	 * & add new javascript actions
	 *
	 * @param   JRegistry $row to save
	 *
	 * @return void
	 */

	protected function updateJavascript($row)
	{
		// FIXME for 3.5
		/**
		 * $$$ hugh - 2012/04/02
		 * updated to apply js changes to descendants as well.  NOTE that this means
		 * all descendants (i.e. children of children, etc.), not just direct children.
		 */
		return;
		/*$input   = $this->app->input;
		$this_id = $this->get($this->getName() . '.id');
		$ids[]   = $this_id;
		$db      = Worker::getDbo(true);
		$query   = $db->getQuery(true);
		$query->delete('#__fabrik_jsactions')->where('element_id IN (' . implode(',', $ids) . ')');
		$db->setQuery($query);
		$db->execute();
		$jform      = $input->get('jform', array(), 'array');
		$eEvent     = ArrayHelper::getValue($jform, 'js_e_event', array());
		$eTrigger   = ArrayHelper::getValue($jform, 'js_e_trigger', array());
		$eCond      = ArrayHelper::getValue($jform, 'js_e_condition', array());
		$eVal       = ArrayHelper::getValue($jform, 'js_e_value', array());
		$ePublished = ArrayHelper::getValue($jform, 'js_published', array());
		$action     = (array) ArrayHelper::getValue($jform, 'action', array());

		foreach ($action as $c => $jsAction)
		{
			if ($jsAction === '')
			{
				continue;
			}

			$params                 = new stdClass;
			$params->js_e_event     = $eEvent[$c];
			$params->js_e_trigger   = $eTrigger[$c];
			$params->js_e_condition = $eCond[$c];
			$params->js_e_value     = htmlspecialchars($eVal[$c]);
			$params->js_published   = $ePublished[$c];
			$params                 = json_encode($params);
			$code                   = $jform['code'][$c];
			$code                   = htmlspecialchars($code, ENT_QUOTES);

			foreach ($ids as $id)
			{
				$query = $db->getQuery(true);
				$query->insert('#__fabrik_jsactions');
				$query->set('element_id = ' . (int) $id);
				$query->set('action = ' . $db->q($jsAction));
				$query->set('code = ' . $db->q($code));
				$query->set('params = \'' . $params . "'");
				$db->setQuery($query);
				$db->execute();
			}
		}*/
	}

	/**
	 * FIXME - reimplement in elements model
	 * Potentially drop fields then remove element record
	 *
	 * @param   array &$pks To delete
	 *
	 * @return  boolean  True if successful, false if an error occurs.
	 */

	/*public function delete(&$pks)
	{
		// Initialize variables
		$pluginManager = new PluginManager;
		$elementIds = $this->app->input->get('elementIds', array(), 'array');

		foreach ($elementIds as $id)
		{
			if ((int) $id === 0)
			{
				continue;
			}

			$pluginModel = $pluginManager->getElementPlugin($id);
			$pluginModel->onRemove($id);
			$element = $pluginModel->getElement();

			if ($pluginModel->isRepeatElement())
			{
				$listModel = $pluginModel->getListModel();
				$db = $listModel->getDb();
				$tableName = $db->qn($this->getRepeatElementTableName($pluginModel));
				$db->setQuery('DROP TABLE ' . $tableName);
				$db->execute();
			}

			$listModel = $pluginModel->getListModel();
			$item = $listModel->getTable();

			// $$$ hugh - might be a table-less form!
			if (!empty($item->id))
			{
				$db = $listModel->getDb();
				$db->setQuery('ALTER TABLE ' . $db->qn($item->db_table_name) . ' DROP ' . $db->qn($element->name));
				$db->execute();
			}
		}

		return parent::delete($pks);
	}*/

	/**
	 * FIXME - move to elements model and redo
	 * Copy an element
	 *
	 * @return  mixed  true or warning
	 */

	/*public function copy()
	{
		$input = $this->app->input;
		$cid   = $input->get('cid', array(), 'array');
		ArrayHelper::toInteger($cid);
		$names = $input->get('name', array(), 'array');
		$rule  = $this->getTable('element');

		foreach ($cid as $id => $groupId)
		{
			if ($rule->load((int) $id))
			{
				$name         = ArrayHelper::getValue($names, $id, $rule->name);
				$data         = ArrayHelper::fromObject($rule);
				$elementModel = $this->getElementPluginModel($data);
				$elementModel->getElement()->bind($data);
				$newRule      = $elementModel->copyRow($id, $rule->label, $groupId, $name);
				$data         = ArrayHelper::fromObject($newRule);
				$elementModel = $this->getElementPluginModel($data);
				$elementModel->getElement()->bind($data);
				$listModel = $elementModel->getListModel();
				$res       = $listModel->shouldUpdateElement($elementModel);
				$this->addElementToOtherDbTables($elementModel, $rule);
			}
			else
			{
				return JError::raiseWarning(500, $rule->getError());
			}
		}

		return true;
	}*/

	/**
	 * If repeated element we need to make a joined db table to store repeated data in
	 *
	 * @param   object $elementModel element model
	 * @param   object $row          element item
	 *
	 * @return  void
	 */

	public function createRepeatElement($elementModel, $row)
	{
		if (!$elementModel->isJoin())
		{
			return;
		}

		$row->name  = str_replace('`', '', $row->name);
		$listModel  = $elementModel->getListModel();
		$groupModel = $elementModel->getGroupModel();
		$tableName  = $this->getRepeatElementTableName($elementModel, $row);

		// Create db table!
		$formModel = $elementModel->getForm();
		$db        = $listModel->getDb();
		$desc      = $elementModel->getFieldDescription();
		$name      = $db->qn($row->name);
		$db
			->setQuery(
				'CREATE TABLE IF NOT EXISTS ' . $db->qn($tableName) . ' ( id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, parent_id INT(11), '
				. $name . ' ' . $desc . ', ' . $db->qn('params') . ' TEXT );');
		$db->execute();

		// Remove previous join records if found
		if ((int) $row->id !== 0)
		{
			$jdb   = Worker::getDbo(true);
			$query = $jdb->getQuery(true);

			// FIXME - jsonify
			$query->delete('#__fabrik_joins')->where('element_id = ' . (int) $row->id);
			$jdb->setQuery($query);
			$jdb->execute();
		}
		// Create or update fabrik join
		if ($groupModel->isJoin())
		{
			$joinFromTable = $groupModel->getJoinModel()->getJoin()->table_join;
		}
		else
		{
			$joinFromTable = $listModel->getTable()->get('list.db_table_name');
		}

		$data = array('list_id' => $listModel->getTable()->id, 'element_id' => $row->id, 'join_from_table' => $joinFromTable,
			'table_join' => $tableName, 'table_key' => $row->name, 'table_join_key' => 'parent_id', 'join_type' => 'left');
		$join = $this->getTable('join');
		$join->load(array('element_id' => $data['element_id']));
		$opts           = new stdClass;
		$opts->type     = 'repeatElement';
		$opts->pk       = FabrikString::safeqn($tableName . '.id');
		$data['params'] = json_encode($opts);
		$join->bind($data);
		$join->store();

		$fieldName = $tableName . '___parent_id';
		$listModel->addIndex($fieldName, 'parent_fk', 'INDEX', '');

		$fields = $listModel->storage->getDBFields($tableName, 'Field');
		$field  = ArrayHelper::getValue($fields, $row->name, false);
		switch ($field->BaseType)
		{
			case 'VARCHAR':
				$size = (int) $field->BaseLength < 10 ? $field->BaseLength : 10;
				break;
			case 'INT':
			case 'DATETIME':
			default:
				$size = '';
				break;
		}
		$fieldName = $tableName . '___' . $row->name;
		$listModel->addIndex($fieldName, 'repeat_el', 'INDEX', $size);

	}

	/**
	 * Get the name of the repeated elements table
	 *
	 * @param   object    $elementModel element model
	 * @param   JRegistry $row          element item
	 *
	 * @return  string    table name
	 */

	protected function getRepeatElementTableName($elementModel, $row = null)
	{
		$listModel  = $elementModel->getListModel();
		$groupModel = $elementModel->getGroupModel();

		if (is_null($row))
		{
			$row = $elementModel->getElement();
		}

		if ($groupModel->isJoin())
		{
			$origTableName = $groupModel->getJoinModel()->getJoin()->table_join;
		}
		else
		{
			$origTableName = $listModel->getTable()->get('list.db_table_name');
		}

		return $origTableName . '_repeat_' . str_replace('`', '', $row->get('name'));
	}

	/**
	 * Get an element
	 *
	 * @param   string $searchName Name to search for
	 * @param   bool   $checkInt   Check search name against element id
	 * @param   bool   $checkShort Check short element name
	 *
	 * @return  mixed  ok: element model not ok: false
	 */
	public function getElement($searchName = '', $checkInt = false, $checkShort = true)
	{
		return $this->element;
	}

	/**
	 * A protected method to get a set of ordering conditions.
	 *
	 * @param   object $table A JTable object.
	 *
	 * @return  array  An array of conditions to add to ordering queries.
	 *
	 * @since   Fabrik 3.0b
	 */

	protected function getReorderConditions($table)
	{
		return array('group_id = ' . $table->group_id);
	}

	/**
	 * Loads in elements validation objects
	 * $$$ hugh - trying to fix issue on saving where we have to massage the plugin
	 * params, which means knowing all the param names, but we can't call the FE model
	 * version of this method 'cos ... well, it breaks.
	 *
	 * @param   object $elementModel a front end element model
	 * @param   array  $usedPlugins  an array of validation plugin names to load
	 *
	 * @return  array    validation objects
	 */

	private function getValidations($elementModel, $usedPlugins = array())
	{
		if (isset($this->_aValidations))
		{
			return $this->_aValidations;
		}

		$pluginManager = Worker::getPluginManager();
		$pluginManager->getPlugInGroup('validationrule');
		$this->aValidations = array();

		$dispatcher = JEventDispatcher::getInstance();
		JPluginHelper::importPlugin('fabrik_validationrule');

		foreach ($usedPlugins as $usedPlugin)
		{
			if ($usedPlugin !== '')
			{
				$class                = 'plgFabrik_Validationrule' . String::ucfirst($usedPlugin);
				$conf                 = array();
				$conf['name']         = String::strtolower($usedPlugin);
				$conf['type']         = String::strtolower('fabrik_Validationrule');
				$plugIn               = new $class($dispatcher, $conf);
				$oPlugin              = JPluginHelper::getPlugin('fabrik_validationrule', $usedPlugin);
				$plugIn->elementModel = $elementModel;
				$this->aValidations[] = $plugIn;
			}
		}

		return $this->aValidations;
	}

	/**
	 * Load a form
	 *
	 * @param string $name
	 * @param array  $options
	 *
	 * @return mixed
	 */
	public function loadForm($name, $options = array())
	{
		JForm::addFormPath(JPATH_COMPONENT . '/models/forms');
		JForm::addFieldPath(JPATH_COMPONENT . '/models/fields');
		JForm::addFormPath(JPATH_COMPONENT . '/model/form');
		JForm::addFieldPath(JPATH_COMPONENT . '/model/field');

		if (empty($options))
		{
			$options = array('control' => 'jform', 'load_data' => true);
		}

		$form   = JForm::getInstance('com_fabrik.' . $name, $name, $options, false, false);
		$item   = $this->getItem();
		$groups = $this->getItem()->get('form.groups');

		foreach ($groups as $group)
		{
			foreach ($group->fields as $field)
			{
				if ($field->id === $this->get('elementid'))
				{
					$field->view     = $item->get('view');
					$field->group_id = $group->id;
					$this->element   = new JRegistry($field);
					$form->bind($field);
				}
			}
		}

		if (!$this->element)
		{
			$field         = array('id' => 0, 'checked_out' => '', 'name' => '', 'plugin' => '');
			$this->element = new JRegistry($field);
		}

		$form->model = $this;

		return $form;
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string $ordering  An optional ordering field.
	 * @param   string $direction An optional direction (asc|desc).
	 *
	 * @since    1.6
	 *
	 * @return  void
	 */
	protected function populateState($ordering = '', $direction = '')
	{
		$this->set('elementid', $this->app->input->get('elementid'));
		parent::populateState($ordering, $direction);
	}
}
