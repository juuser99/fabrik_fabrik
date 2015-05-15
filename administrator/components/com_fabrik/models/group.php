<?php
/**
 * Fabrik Admin Group Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

namespace Fabrik\Admin\Models;

use Fabrik\Helpers\Worker;
use Fabrik\Helpers\ArrayHelper;
use \JRegistry as JRegistry;
use \JForm as JForm;
use \Fabrik\Models\Join as Join;
use \stdClass as stdClass;
use Joomla\String\String as String;
use FText as FText;
use \FabrikString as FabrikString;

// No direct access
defined('_JEXEC') or die('Restricted access');

interface ModelGroupInterface
{

}

/**
 * Fabrik Admin Group Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.5
 */
class Group extends Base implements ModelGroupInterface
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var  string
	 */
	protected $text_prefix = 'COM_FABRIK_GROUP';

	/**
	 * Join model
	 *
	 * @var FabrikFEModelJoin
	 */
	protected $joinModel = null;

	/**
	 * Group
	 *
	 * @var JRegistry
	 */
	protected $group = null;

	/**
	 * Can the group be viewed (set to false if no elements are visible in the group
	 *
	 * @var bool
	 */
	protected $canView = null;

	/**
	 * Parameters
	 *
	 * @var JRegistry
	 */
	protected $params = null;

	/**
	 * Id of group to load
	 *
	 * @var int
	 */
	protected $id = null;

	/**
	 * Form model
	 *
	 * @var FabrikFEModelForm
	 */
	protected $form = null;

	/**
	 * List model
	 *
	 * @var FabrikFEModelList
	 */
	protected $table = null;

	/**
	 * Element plugins
	 *
	 * @var array
	 */
	public $elements = null;

	/**
	 * Published element plugins
	 *
	 * @var array
	 */
	public $publishedElements = null;

	/**
	 * Published element plugins shown in the list
	 *
	 * @var array
	 */
	protected $publishedListElements = null;

	/**
	 * How many times the group's data is repeated
	 *
	 * @var int
	 */
	public $repeatTotal = null;

	/**
	 * Form ids that the group is in (maximum of one value)
	 *
	 * @var array
	 */
	protected $formsIamIn = null;

	/**
	 * Can the group be edited (if false, will override element ACL's and make all elements read only)
	 *
	 * @var bool
	 */
	protected $canEdit = null;

	/**
	 * Take an array of forms ids and return the corresponding group ids
	 * used in list publish code
	 *
	 * @param   array $ids form ids
	 *
	 * @return  string
	 */

	public function swapFormToGroupIds($ids = array())
	{
	}

	/**
	 * Does the group have a primary key element
	 *
	 * @param   array $data jform posted data
	 *
	 * @return  bool
	 */

	protected function checkRepeatAndPK($data)
	{
		$groupModel = new Group;
		$groupModel->setId($data['id']);
		$listModel     = $groupModel->getListModel();
		$pk            = FabrikString::safeColName($listModel->getTable()->db_primary_key);
		$elementModels = $groupModel->getMyElements();

		foreach ($elementModels as $elementModel)
		{
			if (FabrikString::safeColName($elementModel->getFullName(false, false)) == $pk)
			{
				return false;
			}
		}

		return true;
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
		$view = ArrayHelper::getValue($post, 'view');
		$id = ArrayHelper::getValue($post, 'id');
		$this->set('id', $view);
		$item = $this->getItem();
		$groups = $item->get('form.groups');

		foreach ($groups as &$group)
		{
			if ($group->id === $id)
			{
				$group = (object) array_merge((array) $group, (array) $post);
				$found = true;
			}
		}

		if (!$found)
		{
			// Add in new group - TODO not tested
			$default = Worker::formDefaults('group');
			$merged = (object) array_merge((array) $default, (array) $post);
			$item->append('form.groups', $merged);
		}

		return parent::save($item);
	}


	/**
	 * Method to save the form data.
	 *
	 * @param   array $data The form data.
	 *
	 * @return  boolean  True on success, False on error.
	 */

	public function save34($data)
	{
		if ($data['id'] == 0)
		{
			$user                     = JFactory::getUser();
			$data['created_by']       = $user->get('id');
			$data['created_by_alias'] = $user->get('username');
			$data['created']          = JFactory::getDate()->toSql();
		}

		$makeJoin   = false;
		$unMakeJoin = false;

		if ($this->checkRepeatAndPK($data))
		{
			$makeJoin = ($data['params']['repeat_group_button'] == 1);

			if ($makeJoin)
			{
				$data['is_join'] = 1;
			}
		}
		else
		{
			if (($data['params']['repeat_group_button'] == 1))
			{
				$data['params']['repeat_group_button'] = 0;
				$this->app->enqueueMessage('You can not set the group containing the list primary key to be repeatable', 'notice');
			}
		}

		$data['params'] = json_encode($data['params']);
		$return         = parent::save($data);
		$data['id']     = $this->get($this->getName() . '.id');

		if ($return)
		{
			//$this->makeFormGroup($data);

			if ($makeJoin)
			{
				/**
				 * $$$ rob added this check as otherwise toggling group from repeat
				 * to norepeat back to repeat incorrectly created a 2nd join
				 */
				if (!$this->joinedGroupExists($data['id']))
				{
					$return = $this->makeJoinedGroup($data);
				}
				else
				{
					$this->checkFKIndex($data);
				}

				// Update for the is_join change
				if ($return)
				{
					$return = parent::save($data);
				}

			}
			else
			{
				// $data['is_join'] =  0; // NO! none repeat joined groups were getting unset here - not right!
				if ($unMakeJoin)
				{
					$this->unMakeJoinedGroup($data);
				}

				$return = parent::save($data);
			}
		}

		parent::cleanCache('com_fabrik');

		return $return;
	}

	/**
	 * Check if a group id has an associated join already created
	 *
	 * @param   int $id group id
	 *
	 * @return  boolean
	 */

	protected function joinedGroupExists($id)
	{
		$item = FabTable::getInstance('Group', 'FabrikTable');
		$item->load($id);

		return $item->join_id == '' ? false : true;
	}

	/**
	 * Check if an index exists on the parent_id for a repeat table.
	 * We forgot to index the parent_id until 32/2015, which could have an impact on getData()
	 * query performance.  Only called from the save() method.
	 *
	 * @param   array $data jform data
	 */

	private function checkFKIndex($data)
	{
		$groupModel = JModelLegacy::getInstance('Group', 'FabrikFEModel');
		$groupModel->setId($data['id']);
		$listModel = $groupModel->getListModel();
		$item      = FabTable::getInstance('Group', 'FabrikTable');
		$item->load($data['id']);
		$join = $this->getTable('join');
		$join->load(array('id' => $item->join_id));
		$fkFieldName    = $join->table_join . '___' . $join->table_join_key;
		$pkFieldName    = $join->join_from_table . '___' . $join->table_key;
		$formModel      = $groupModel->getFormModel();
		$pkElementModel = $formModel->getElement($pkFieldName);
		$fields         = $listModel->storage->getDBFields($join->join_from_table, 'Field');
		$pkField        = ArrayHelper::getValue($fields, $join->table_key, false);

		switch ($pkField->BaseType)
		{
			case 'VARCHAR':
				$pkSize = (int) $pkField->BaseLength < 10 ? $pkField->BaseLength : 10;
				break;
			case 'INT':
			case 'DATETIME':
			default:
				$pkSize = '';
				break;
		}
		$listModel->addIndex($fkFieldName, 'parent_fk', 'INDEX', $pkSize);
	}

	/**
	 * A group has been set to be repeatable but is not part of a join
	 * so we want to:
	 * Create a new db table for the groups elements ( + check if its not already there)
	 *
	 * @param   array &$data jform data
	 *
	 * @return  bool
	 */

	public function makeJoinedGroup(&$data)
	{
		$groupModel = JModelLegacy::getInstance('Group', 'FabrikFEModel');
		$groupModel->setId($data['id']);
		$listModel          = $groupModel->getListModel();
		$db                 = $listModel->getDb();
		$list               = $listModel->getTable();
		$elements           = (array) $groupModel->getMyElements();
		$names              = array();
		$fields             = $listModel->storage->getDBFields(null, 'Field');
		$names['id']        = "id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY";
		$names['parent_id'] = "parent_id INT(11)";

		foreach ($elements as $element)
		{
			$fname = $element->getElement()->name;
			/**
			 * if we are making a repeat group from the primary group then we don't want to
			 * overwrite the repeat group tables id definition with that of the main tables
			 */
			if (!array_key_exists($fname, $names))
			{
				$str   = FabrikString::safeColName($fname);
				$field = ArrayHelper::getValue($fields, $fname);

				if (is_object($field))
				{
					$str .= " " . $field->Type . " ";

					if ($field->Null == 'NO')
					{
						$str .= "NOT NULL ";
					}

					$names[$fname] = $str;
				}
				else
				{
					$names[$fname] = $db->quoteName($fname) . ' ' . $element->getFieldDescription();
				}
			}
		}

		$db->setQuery("show tables");
		$newTableName   = $list->db_table_name . '_' . $data['id'] . '_repeat';
		$existingTables = $db->loadColumn();

		if (!in_array($newTableName, $existingTables))
		{
			// No existing repeat group table found so lets create it
			$query = "CREATE TABLE IF NOT EXISTS " . $db->qn($newTableName) . " (" . implode(",", $names) . ")";
			$db->setQuery($query);
			$db->execute();

			// Create id and parent_id elements
			$listModel->makeIdElement($data['id']);
			$listModel->makeFkElement($data['id']);
		}
		else
		{
			if (trim($list->db_table_name) == '')
			{
				// New group not attached to a form
				$this->setError(FText::_('COM_FABRIK_GROUP_CANT_MAKE_JOIN_NO_DB_TABLE'));

				return false;
			}
			// Repeat table already created - lets check its structure matches the group elements
			$db->setQuery("DESCRIBE " . $db->qn($newTableName));
			$existingFields = $db->loadObjectList('Field');
			$newFields      = array_diff(array_keys($names), array_keys($existingFields));

			if (!empty($newFields))
			{
				$lastField = array_pop($existingFields);
				$lastField = $lastField->Field;

				foreach ($newFields as $newField)
				{
					$info = $names[$newField];
					$db->setQuery("ALTER TABLE " . $db->qn($newTableName) . " ADD COLUMN $info AFTER $lastField");
					$db->execute();
				}
			}
		}
		// Create the join as well

		$jdata = array('list_id' => $list->id, 'element_id' => 0, 'join_from_table' => $list->db_table_name, 'table_join' => $newTableName,
			'table_key' => FabrikString::shortColName($list->db_primary_key), 'table_join_key' => 'parent_id', 'join_type' => 'left',
			'group_id' => $data['id']);

		// Load the matching join if found.
		$join = $this->getTable('join');
		$join->load($jdata);
		$opts            = new stdClass;
		$opts->type      = 'group';
		$jdata['params'] = json_encode($opts);
		$join->bind($jdata);

		// Update or save a new join
		$join->store();
		$data['is_join'] = 1;

		$listModel->addIndex($newTableName . '___parent_id', 'parent_fk', 'INDEX', '');

		return true;
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

		$form  = JForm::getInstance('com_fabrik.' . $name, $name, $options, false, false);
		$item  = $this->getItem();
		$groups       = $this->getItem()->get('form.groups');

		foreach ($groups as $group)
		{
			if ($group->id === $this->get('groupid'))
			{
				$group->view = $item->get('view');
				$this->setGroup($group);
				$form->bind($group);
			}
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
		$this->set('groupid', $this->app->input->get('groupid'));
		parent::populateState($ordering, $direction);
	}

	/**
	 * Repeat has been turned off for a group, so we need to remove the join.
	 * For now, leave the repeat table intact, just remove the join
	 * and the 'id' and 'parent_id' elements.
	 *
	 * @param   array &$data jform data
	 *
	 * @return boolean
	 */
	public function unMakeJoinedGroup(&$data)
	{
	}

	/**
	 * Method to delete one or more records.
	 *
	 * @param   array &$pks           An array of record primary keys.
	 * @param   bool  $deleteElements delete elements?
	 *
	 * @return  bool  True if successful, false if an error occurs.
	 */

	/*public function delete($pks, $deleteElements = false)
	{
		if (empty($pks))
		{
			return true;
		}

		if (parent::delete($pks))
		{
			if ($this->deleteFormGroups($pks))
			{
				if ($deleteElements)
				{
					return $this->deleteElements($pks);
				}
				else
				{
					return true;
				}
			}
		}

		return false;
	}*/

	/**
	 * Set the context in which the element occurs
	 *
	 * @param   object  $formModel  Form model
	 * @param   object  $listModel  List model
	 *
	 * @return void
	 */
	public function setContext($formModel, $listModel)
	{
		$this->form = $formModel;
		$this->table = $listModel;
	}

	/**
	 * Get the item -
	 * if no id set load data template
	 *
	 * @param  string $id
	 *
	 * @return Registry
	 */
	public function getItem($id = null)
	{
		return $this->form->getItem();
	}

	public function setGroup($group)
	{
		$this->group = new JRegistry($group);
	}

	/**
	 * Get the group's associated join model
	 *
	 * @return  object  join model
	 */
	public function getJoinModel()
	{
		// FIXME
		$group = $this->getGroup();

		if (is_null($this->joinModel))
		{
			$this->joinModel = new Join;//JModelLegacy::getInstance('Join', 'FabrikFEModel');
			/*echo "join id = " . $group->get('join_id');exit;
			$this->joinModel->setId($group->get('join_id'));
			$js = $this->getListModel()->getJoins();

			// $$$ rob set join models data from preloaded table joins - reduced load time
			for ($x = 0; $x < count($js); $x++)
			{
				if ($js[$x]->id == $group->get('join_id') && $js[$x]->element_id == 0)
				{
					$this->joinModel->setData($js[$x]);
					break;
				}
			}

			$this->joinModel->getJoin();*/
		}

		return $this->joinModel;
	}

	/**
	 * Get group
	 *
	 * @return JRegistry
	 */
	public function getGroup()
	{
		return $this->group;
	}

	public function getElements()
	{
		return $this->group->get('fields', array());
	}

	public function getMyElements()
	{
		return $this->elements;
	}

	/**
	 * Is the group a join?
	 *
	 * @return  bool
	 */
	public function isJoin()
	{
		return $this->getGroup()->get('is_join', 0);
	}

	/**
	 * Is the group a repeat group
	 *
	 * @return  bool
	 */
	public function canRepeat()
	{
		$params = $this->getParams();

		return $params->get('repeat_group_button');
	}

	/**
	 * Get group params
	 *
	 * @return  object	params
	 */
	public function &getParams()
	{
		if (!$this->params)
		{
			$this->params = new JRegistry($this->getGroup()->get('params'));
		}

		return $this->params;
	}

	/**
	 * Get the list id
	 *
	 * @return  string  list id
	 */
/*	public function getId()
	{
		return $this->get('id');
	}*/

	/**
	 * Can the user view the group
	 *
	 * @param   string  $mode  View mode list|form
	 *
	 * @return   bool
	 */
	public function canView($mode = 'form')
	{
		// No ACL option for list view.
		if ($mode === 'list')
		{
			return true;
		}

		if (!is_null($this->canView))
		{
			return $this->canView;
		}

		$params = $this->getParams();
		$elementModels = $this->getPublishedElements();
		$this->canView = false;

		foreach ($elementModels as $elementModel)
		{
			// $$$ hugh - added canUse() check, corner case, see:
			// http://fabrikar.com/forums/showthread.php?p=111746#post111746
			if (!$elementModel->canView() && !$elementModel->canUse())
			{
				continue;
			}

			$this->canView = true;
			break;
		}

		// Get the group access level
		$user = $this->user;
		$groups = $user->getAuthorisedViewLevels();
		$groupAccess = $params->get('access', '');

		if ($groupAccess !== '')
		{
			$this->canView = in_array($groupAccess, $groups);

			// If the user can't access the group return that and ignore repeat_group_show_first option
			if (!$this->canView)
			{
				return $this->canView;
			}
		}

		/*
		 * Sigh - seems that the repeat group 'repeat_group_show_first' property has been bastardized to be a setting
		* that is applicable to a group even when not in a repeat group, and has basically become a standard group setting.
		* My bad for labelling it poorly to start with.
		* So, now if this is set to 'no' the group is not shown but canView was returning true - doh! Caused issues in
		* multi page forms where we were trying to set/check errors in groups which were not attached to the form.
		*/
		$formModel = $this->getFormModel();
		$showGroup = $params->get('repeat_group_show_first', '1');

		if ($showGroup == 0)
		{
			$this->canView = false;
		}

		// If editable but only show group in details view:
		if (!($formModel->isEditable() && $showGroup == 2))
		{
			$this->canView = true;
		}

		// If form not editable and show group in form view:
		if (!$formModel->isEditable() && $showGroup == 3)
		{
			$this->canView = false;
		}

		return $this->canView;
	}

	/**
	 * Get a list of all elements which are set to show in list or
	 * are set to include in list query
	 *
	 * @since   3.0.6
	 *
	 * @return  array  list of element models
	 */
	public function getListQueryElements()
	{

		if (!isset($this->listQueryElements))
		{
			$this->listQueryElements = array();
		}

		$input = $this->app->input;
		$groupparams = $this->getParams();

		// $$$ rob fabrik_show_in_list set in admin module params (will also be set in menu items and content plugins later on)
		// its an array of element ids that should be show. Overrides default element 'show_in_list' setting.
		$showInList = $input->get('fabrik_show_in_list', array(), 'array');
		$sig = empty($showInList) ? 0 : implode('.', $showInList);

		if (!array_key_exists($sig, $this->listQueryElements))
		{
			$this->listQueryElements[$sig] = array();
			$elements = $this->getMyElements();

			$joins = $this->getJoins();
			/**
			 * $$$ Paul - it is possible that the user has set Include in List Query
			 * to No for table primary key or join foreign key. If List is then set
			 * to Merge and Reduce, this causes a problem because the pk/fk
			 * placeholder is not set. We therefore include the table PK and join FK
			 * regardless of Include in List Query settings if any elements in the
			 * group have Include in List Query = Yes.
			 * In order to avoid iterating over the elements twice, we save the
			 * PK / FK elementModel and include it as soon as it is needed.
			 * If the access level does not allow for these to be used, then we should
			 * display some sort of warning - though this is not included in this fix.
			 **/
			$repeating = $this->canRepeat();
			$join = $this->getJoinModel();

			if (is_null($join->getJoin()->get('params')))
			{
				$join_id = "";
				$join_fk = "";
			}
			else
			{
				$join_id = $join->getForeignID();
				$join_fk = $join->getForeignKey();
			}

			$element_included = false;
			$table_pk_included = $join_fk_included = false;
			$table_pk_element = $join_fk_element = null;

			foreach ($elements as $elementModel)
			{
				$element = $elementModel->getElement();
				$params = $elementModel->getParams();
				/**
				 * $$$ hugh - experimenting adding non-viewable data to encrypted vars on forms,
				 * also we need them in addDefaultDataFromRO()
				 * if ($element->published == 1 && $elementModel->canView())
				 */
				if ($element->published == 1)
				{
					$full_name = $elementModel->getFullName(true, false);

					/**
					 * As this function seems to be used to build both the list view and the form view, we should NOT
					 * include elements in the list query if the user can not view them, as their data is sent to the json object
					 * and thus visible in the page source.
					 * Jaanus: also when we exclude elements globally with group settings ($groupparams->get('list_view_and_query', 1) == 0)
					 */

					if ($input->get('view') == 'list' && ((!$this->getListModel()->isUserDoElement($full_name) && !$elementModel->canView('list')) || $groupparams->get('list_view_and_query', 1) == 0))
					{
						continue;
					}

					$showThisInList = $element->primary_key || $params->get('include_in_list_query', 1) == 1
						|| (empty($showInList) && $element->show_in_list_summary) || in_array($element->id, $showInList);

					if ($showThisInList)
					{
						if ($element->primary_key || $full_name == $join_id)
						{
							$table_pk_included = true;
						}
						elseif (!$table_pk_included && !is_null($table_pk_element))
						{
							// Add primary key before other element
							$this->listQueryElements[$sig][] = $table_pk_element;
							$table_pk_included = true;
						}

						if ($full_name == $join_fk)
						{
							$join_fk_included = true;
						}
						elseif (!$join_fk_included && !is_null($join_fk_element))
						{
							// Add foreign key before other element
							$this->listQueryElements[$sig][] = $join_fk_element;
							$join_fk_included = true;
						}

						$this->listQueryElements[$sig][] = $elementModel;
						$element_included = true;
					}
					elseif ($element->primary_key || $full_name == $join_id)
					{
						if ($element_included)
						{
							// Add primary key after other element
							$this->listQueryElements[$sig][] = $elementModel;
							$table_pk_included = true;
						}
						else
						{
							// Save primary key for future use
							$table_pk_element = $elementModel;
						}
					}
					elseif ($elementModel->getFullName(true, false) == $join_fk)
					{
						if ($element_included)
						{
							// Add foreign key after other element
							$this->listQueryElements[$sig][] = $elementModel;
							$join_fk_included = true;
						}
						else
						{
							// Save foreign key for future use
							$join_fk_element = $elementModel;
						}
					}
				}
			}
		}

		return $this->listQueryElements[$sig];
	}

	/**
	 * Get an array of published elements
	 *
	 * @since 120/10/2011 - can override with elementid request data (used in inline edit to limit which elements are shown)
	 *
	 * @return  array	published element objects
	 */
	public function getPublishedElements()
	{
		if (!isset($this->publishedElements))
		{
			$this->publishedElements = array();
		}

		$ids = (array) $this->app->input->get('elementid', array(), 'array');
		$sig = implode('.', $ids);

		if (!array_key_exists($sig, $this->publishedElements))
		{
			$this->publishedElements[$sig] = array();
			$elements = $this->getMyElements();

			foreach ($elements as $elementModel)
			{
				$element = $elementModel->getELement();

				if ($element->published == 1)
				{
					if (empty($ids) || in_array($element->id, $ids))
					{
						$this->publishedElements[$sig][] = $elementModel;
					}
				}
			}
		}

		return $this->publishedElements[$sig];
	}

	/**
	 * Method to set the group id
	 *
	 * @param   int  $id  group ID number
	 *
	 * @return  void
	 */

	public function setId($id)
	{
		// Set new group ID
		$this->id = $id;
	}

	/**
	 * Get group id
	 *
	 * @return int
	 */

	public function getId()
	{
		return $this->get('id');
	}

	/**
	 * Can the user edit the group
	 *
	 * @return   bool
	 */

	public function canEdit()
	{
		/**
		 * First cut at this code, need to add actual ACL setting for edit
		 *
		 * Mostly needed so people can run plugins on this hook, to set groups to read only
		 */
		if (!is_null($this->canEdit))
		{
			return $this->canEdit;
		}

		$params = $this->getParams();
		$this->canEdit = true;

		// If group show is type 5, then always read only.
		if (in_array($params->get('repeat_group_show_first', '1'), array('2','5')))
		{
			$this->canEdit = false;

			return $this->canEdit;
		}

		$formModel = $this->getFormModel();
		$pluginCanEdit = Worker::getPluginManager()->runPlugins('onCanEditGroup', $formModel, 'form', $this);

		if (empty($pluginCanEdit))
		{
			$pluginCanEdit = true;
		}
		else
		{
			$pluginCanEdit = !in_array(false, $pluginCanEdit);
		}

		$this->canEdit = $pluginCanEdit;

		return $this->canEdit;
	}

	/**
	 * Get an array of forms that the group is in
	 * NOTE: now a group can only belong to one form
	 *
	 * @return  array  form ids
	 */

	public function getFormsIamIn()
	{
		if (!isset($this->formsIamIn))
		{
			$db = Worker::getDbo(true);
			$query = $db->getQuery(true);
			$query->select('form_id')->from('#__fabrik_formgroup')->where('group_id = ' . (int) $this->getId());
			$db->setQuery($query);
			$this->formsIamIn = $db->loadColumn();
			$db->execute();
		}

		return $this->formsIamIn;
	}

	/**
	 * Randomise the element list (note the array is the pre-rendered elements)
	 *
	 * @param   array  &$elements  form views processed/formatted list of elements that the form template uses
	 *
	 * @return  void
	 */

	public function randomiseElements(&$elements)
	{
		if ($this->getParams()->get('random', false) == true)
		{
			$keys = array_keys($elements);
			shuffle($keys);

			foreach ($keys as $key)
			{
				$new[$key] = $elements[$key];
			}

			$elements = $new;
		}
	}

	/**
	 * Set the element column css allows for group column settings to be applied
	 *
	 * @param   object  &$element  Pre-render element properties
	 * @param   int     $rowIx     Current key when looping over elements.
	 *
	 * @since 	Fabrik 3.0.5.2
	 *
	 * @return  int  the next column count
	 */

	public function setColumnCss(&$element, $rowIx)
	{
		$params = $this->getParams();
		$colcount = (int) $params->get('group_columns');

		if ($colcount === 0)
		{
			$colcount = 1;
		}

		$element->offset = $params->get('group_offset', 0);

		// Bootstrap grid formatting
		if ($colcount === 1) // Single column
		{
			$element->startRow = true;
			$element->endRow = 1;
			$element->span = ' span12';
			$element->column = ' style="clear:both;width:100%;"';
			$rowIx = -1;

			return $rowIx;
		}

		// Multi-column
		$widths = $params->get('group_column_widths', '');
		$w = floor((100 - ($colcount * 6)) / $colcount) . '%';

		if ($widths !== '')
		{
			$widths = explode(',', $widths);
			$w = ArrayHelper::getValue($widths, ($rowIx) % $colcount, $w);
		}

		$element->column = ' style="float:left;width:' . $w . ';';
		$element->startRow = 0;
		$element->endRow = 0;

		/**
		 * Hidden fields at start of row will be grouped on a separate row to avoid
		 * issues with css selector :first-child.
		 * $rowIx == -1 indicates a new row to distinguish it from
		 * $rowIx = 0 which indicates hidden fields already processed at start of row.
		 **/
		if ($rowIx === 0 && !$element->hidden)
		{
			// Previous element must have been hidden - so set end of row on that and new row on this
			$this->setColumnCssLastElement->endRow = 1;
			$rowIx = -1;
		}

		if ($rowIx < 0)
		{
			$rowIx = 0;
			$element->startRow = 1;
			$element->column .= "clear:both;";
		}

		$element->column .= '" ';
		$spans = $this->columnSpans();
		$spanKey = $rowIx % $colcount;
		$element->span = $element->hidden ? '' : ArrayHelper::getValue($spans, $spanKey, 'span' . floor(12 / $colcount));

		if (!$element->hidden)
		{
			$rowIx++;
		}

		if ($rowIx !== 0 && ($rowIx % $colcount === 0))
		{
			$element->endRow = 1;

			// Reset rowIx to indicate a new row.
			$rowIx = -1;
		}

		// Save this so we can set endRow on previous element if it was hidden and this element isn't.
		$this->setColumnCssLastElement = $element;

		return $rowIx;
	}

	/**
	 * Work out the bootstrap column spans for the group
	 * Assigned to each element in setColumnCss()
	 * Looks at the property group_column_widths which accepts either % or 1-12 as values
	 *
	 * @since 3.0b
	 *
	 * @return  array
	 */

	public function columnSpans()
	{
		$params = $this->getParams();
		$widths = $params->get('group_column_widths', '');

		if (trim($widths) === '')
		{
			return;
		}

		$widths = explode(',', $widths);

		foreach ($widths as &$w)
		{
			if ($w == '')
			{
				$w = 6;
			}

			if (strstr($w, '%'))
			{
				$w = (int) str_replace('%', '', $w);
				$w = floor(($w / 100) * 12);
			}

			$w = ' span' . $w;
		}

		return $widths;
	}

	/**
	 * Alias to getFormModel()
	 *
	 * @deprecated
	 *
	 * @return object form model
	 */

	public function getForm()
	{
		return $this->getFormModel();
	}

	/**
	 * Get the groups form model
	 *
	 * @return object form model
	 */

	public function getFormModel()
	{
		if (!isset($this->form))
		{
			$formids = $this->getFormsIamIn();
			$formid = empty($formids) ? 0 : $formids[0];
			$this->form = JModelLegacy::getInstance('Form', 'FabrikFEModel');
			$this->form->setId($formid);
			$this->form->getForm();
			$this->form->getlistModel();
		}

		return $this->form;
	}

	/**
	 * Can the user add a repeat group
	 *
	 * @since   3.0.1
	 *
	 * @return  bool
	 */

	public function canAddRepeat()
	{
		$params = $this->getParams();
		$ok = $this->canRepeat();

		if ($ok)
		{
			$user = JFactory::getUser();
			$groups = $user->getAuthorisedViewLevels();
			$ok = in_array($params->get('repeat_add_access', 1), $groups);
		}

		return $ok;
	}

	/**
	 * Can the user delete a repeat group
	 *
	 * @since   3.0.1
	 *
	 * @return  bool
	 */

	public function canDeleteRepeat()
	{
		$ok = false;

		if ($this->canRepeat())
		{
			$params = $this->getParams();
			$row = $this->getFormModel()->getData();
			$ok = Worker::canUserDo($params, $row, 'repeat_delete_access_user');

			if ($ok === -1)
			{
				$user = JFactory::getUser();
				$groups = $user->getAuthorisedViewLevels();
				$ok = in_array($params->get('repeat_delete_access', 1), $groups);
			}
		}

		return $ok;
	}

	/**
	 * Is the group a repeat group
	 *
	 * @return  bool
	 */

	public function canCopyElementValues()
	{
		$params = $this->getParams();

		return $params->get('repeat_copy_element_values', '0') === '1';
	}

	/**
	 * Get the group's join_id
	 *
	 * @return  mixed   join_id, or false if not a join
	 */

	public function getJoinId()
	{
		if (!$this->isJoin())
		{
			return false;
		}

		return $this->getGroup()->join_id;
	}

	/**
	 * Make a group object to be used in the form view. Object contains
	 * group display properties
	 *
	 * @param   object  &$formModel  form model
	 *
	 * @return  object	group display properties
	 */
	public function getGroupProperties(&$formModel)
	{
		$w = new Worker;
		$input = $this->app->input;
		$group = new stdClass;
		$groupTable = $this->getGroup();
		$params = $this->getParams();
		$view = $input->get('view');

		if (!isset($this->editable))
		{
			$this->editable = $formModel->isEditable();
		}

		if ($this->editable)
		{
			// If all of the groups elements are not editable then set the group to uneditable
			$elements = $this->getPublishedElements();
			$editable = false;

			foreach ($elements as $element)
			{
				if ($element->canUse())
				{
					$editable = true;
				}
			}

			if (!$editable)
			{
				$this->editable = false;
			}
		}

		$group->editable = $this->editable;
		$group->canRepeat = $params->get('repeat_group_button', '0');
		$showGroup = $params->def('repeat_group_show_first', '1');
		$pages = $formModel->getPages();
		$startPage = isset($formModel->sessionModel->last_page) ? $formModel->sessionModel->last_page : 0;
		/**
		 * $$$ hugh - added array_key_exists for (I think!) corner case where group properties have been
		 * changed to remove (or change) paging, but user still has session state set.  So it was throwing
		 * a PHP 'undefined index' notice.
		 */

		if (array_key_exists($startPage, $pages) && is_array($pages[$startPage])
			&& !in_array($groupTable->get('id'), $pages[$startPage]) || $showGroup == -1 || $showGroup == 0 || ($view == 'form' && $showGroup == -2) || ($view == 'details' && $showGroup == -3))
		{
			$groupTable->set('css', $groupTable->get('css') . ";display:none;");
		}

		$group->css = trim(str_replace(array("<br />", "<br>"), "", $groupTable->get('css')));
		$group->id = $groupTable->get('id');

		$label = $input->getString('group' . $group->id . '_label', $groupTable->get('label'));

		if (String::stristr($label, "{Add/Edit}"))
		{
			$replace = $formModel->isNewRecord() ? FText::_('COM_FABRIK_ADD') : FText::_('COM_FABRIK_EDIT');
			$label = str_replace("{Add/Edit}", $replace, $label);
		}

		$groupTable->set('label', $label);
		$group->title = $w->parseMessageForPlaceHolder($groupTable->get('label'), $formModel->data, false);
		$group->title = FText::_($group->title);
		$group->name = $groupTable->get('name');
		$group->displaystate = ($group->canRepeat == 1 && $formModel->isEditable()) ? 1 : 0;
		$group->maxRepeat = (int) $params->get('repeat_max');
		$group->minRepeat = $params->get('repeat_min', '') === '' ? 1 : (int) $params->get('repeat_min', '');
		$group->showMaxRepeats  = $params->get('show_repeat_max', '0') == '1';
		$group->minMaxErrMsg = $params->get('repeat_error_message', '');
		$group->minMaxErrMsg = FText::_($group->minMaxErrMsg);
		$group->canAddRepeat = $this->canAddRepeat();
		$group->canDeleteRepeat = $this->canDeleteRepeat();
		$group->intro = $text = FabrikString::translate($params->get('intro'));
		$group->outro = FText::_($params->get('outro'));
		$group->columns = $params->get('group_columns', 1);
		$group->splitPage = $params->get('split_page', 0);
		$group->showLegend = $this->showLegend($group);
		$group->labels = $params->get('labels_above', -1);
		$group->dlabels = $params->get('labels_above_details', -1);

		if ($this->canRepeat())
		{
			$group->tmpl = $params->get('repeat_template', 'repeatgroup');
		}
		else
		{
			$group->tmpl = 'group';
		}

		return $group;
	}

	/**
	 * Copies a group, form group and its elements
	 * (when copying a table (and hence a group) the groups join is copied in table->copyJoins)
	 *
	 * @param  array  &$ids  Ids to copy
	 * @param  array  $names  Old to new name map.
	 *
	 * @throws \Exception
	 *
	 * @return  boolean    True if successful, false if an error occurs. (was an array of new element id's keyed on original elements that have been copied in < 3.5)
	 */
	public function copy(&$ids, $names)
	{
		// FIXME - copy needs to be updated for 3.5
		throw new \Exception('form copy needs to be updated for 3.5');
		$input = $this->app->input;
		$elements = $this->getMyElements();
		$group = $this->getGroup();

		// NewGroupNames set in table copy
		$newNames = $input->get('newGroupNames', array(), 'array');

		if (array_key_exists($group->id, $newNames))
		{
			$group->name = $newNames[$group->id];
		}

		$group->id = null;
		$group->store();
		$newElements = array();

		foreach ($elements as $element)
		{
			$origElementId = $element->getElement()->id;
			$copy = $element->copyRow($origElementId, $element->getElement()->label, $group->id);
			$newElements[$origElementId] = $copy->id;
		}

		$this->elements = null;
		$elements = $this->getMyElements();

		// Create form group
		$formid = isset($this->_newFormid) ? $this->_newFormid : $this->getFormModel()->getId();
		$formGroup = FabTable::getInstance('FormGroup', 'FabrikTable');
		$formGroup->form_id = $formid;
		$formGroup->group_id = $group->id;
		$formGroup->ordering = 999999;
		$formGroup->store();
		$formGroup->reorder(" form_id = '$formid'");

		return $newElements;
	}

	/**
	 * Resets published element cache
	 *
	 * @return  void
	 */

	public function resetPublishedElements()
	{
		unset($this->publishedElements);
		unset($this->publishedListElements);
		unset($this->elements);
	}

	/**
	 * Get the records master Insert Id - need better description...
	 *
	 * @return  string
	 */

	protected function masterInsertId()
	{
		$formModel = $this->getFormModel();
		$joinModel = $this->getJoinModel();
		$formData =& $formModel->formDataWithTableName;
		$joinToPk = $joinModel->getJoinedToTablePk();

		return $formData[$joinToPk];
	}

	/**
	 * Part of process()
	 * Set foreign key's value to the main records insert id
	 *
	 * @return  void
	 */

	protected function setForeignKey()
	{
		$formModel = $this->getFormModel();
		$formData =& $formModel->formDataWithTableName;
		$joinModel = $this->getJoinModel();
		$masterInsertId = $this->masterInsertId();
		$fk_name = $joinModel->getForeignKey();
		$fks = array($fk_name, $fk_name . '_raw');

		foreach ($fks as $fk)
		{
			if ($this->canRepeat() && array_key_exists($fk, $formData))
			{
				if (array_key_exists($fk, $formData))
				{
					if (is_array($formData[$fk]))
					{
						foreach ($formData[$fk] as $k => $v)
						{
							$formData[$fk][$k] = $masterInsertId;
						}
					}
					else
					{
						$formData[$fk] = $masterInsertId;
					}
				}
			}
			else
			{
				$formData[$fk] = $masterInsertId;
			}
		}

		/**
		 *
		 * $$$ hugh - added the clearDefaults method and need to call it here, otherwise if any pre-processing
		 * has already called the element model's getValue(), the change we just made to formdata won't get picked up
		 * during the row store processing, as getValue() will return the cached default.
		 */

		$elementModel = $formModel->getElement($fk_name);
		$elementModel->clearDefaults();
	}

	/**
	 *
	 *
	 */

	public function fkOnParent()
	{
		/*
		 * $$$ hugh - if $pkField is same-same as FK, then this is a one-to-one join in which the FK is
		* on the "parent", so it's ...
		*
		* parent.child_id (FK) => child.id (PK)
		*
		* ... rather than ...
		*
		* parent.id (PK) <= child.parent_id (FK)
		*
		* ... which means it needs different handling, like we don't set the FK value in the child, rather
		* we have to go back and update the FK value in the parent after writing the child row.
		*/

		// @TODO - handle joins which don't involve the parent!

		$joinModel = $this->getJoinModel();
		$pkField = $joinModel->getForeignID();
		$fk = $joinModel->getForeignKey();

		return $pkField === $fk;
	}

	/**
	 * Get the number of times the group was repeated when the user fills
	 * in the form
	 *
	 * @todo whats the difference between this and @link(repeatCount)
	 *
	 * @return  int
	 */

	protected function repeatTotals()
	{
		$input = $this->app->input;
		$repeatTotals = $input->get('fabrik_repeat_group', array(0), 'post', 'array');

		return (int) ArrayHelper::getValue($repeatTotals, $this->getGroup()->id, 0);
	}

	/**
	 * Group specific form submission code - deals with saving joined data.
	 *
	 * @param   int  $parentId  insert ID of parent table
	 *
	 * @return  void
	 */

	public function process($parentId = null)
	{
		if (!$this->isJoin())
		{
			return;
		}

		$canRepeat = $this->canRepeat();
		$repeats = $this->repeatTotals();
		$joinModel = $this->getJoinModel();
		$pkField = $joinModel->getForeignID();
		$fk = $joinModel->getForeignKey();

		$fkOnParent = $this->fkOnParent();

		$listModel = $this->getListModel();
		$item = $this->getGroup();
		$formModel = $this->getFormModel();
		$formData =& $formModel->formDataWithTableName;

		if (!$fkOnParent)
		{
			$this->setForeignKey();
		}

		$elementModels = $this->getMyElements();
		$list = $listModel->getTable();
		$tblName = $list->db_table_name;
		$tblPk = $list->db_primary_key;

		// Set the list's table name to the join table, needed for storeRow()
		$join = $joinModel->getJoin();
		$list->db_table_name = $join->table_join;
		$list->db_primary_key = $joinModel->getForeignID('.');
		$usedKeys = array();

		$insertId = false;

		if (!$fkOnParent)
		{
			/*
			 * It's a "normal" join, with the FK on the child, which may or may not repeat
			 */
			for ($i = 0; $i < $repeats; $i ++)
			{
				$data = array();

				foreach ($elementModels as $elementModel)
				{
					$elementModel->onStoreRow($data, $i);
				}

				if ($formModel->copyingRow())
				{
					$pk = '';

					if ($canRepeat)
					{
						$formData[$pkField][$i] = '';
					}
					else
					{
						$formData[$pkField] = '';
					}

				}
				else
				{
					$pk = $canRepeat ? ArrayHelper::getValue($formData[$pkField], $i, '') : $formData[$pkField];

					// Say for some reason the pk was set as a dbjoin!
					if (is_array($pk))
					{
						$pk = array_shift($pk);
					}
				}

				$insertId = $listModel->storeRow($data, $pk, true, $item);

				// Update key
				if ($canRepeat)
				{
					$formData[$pkField][$i] = $insertId;
				}
				else
				{
					$formData[$pkField] = $insertId;
				}

				$usedKeys[] = $insertId;
			}

			// Delete any removed groups
			$this->deleteRepeatGroups($usedKeys);
		}
		else
		{
			/*
			 * It's an abnormal join!  FK is on the parent.  Can't repeat, and the $pk needs to be derived differently
			 */

			$data = array();

			foreach ($elementModels as $elementModel)
			{
				$elementModel->onStoreRow($data, 0);
			}

			/*
			 * Although we use getPrimaryKey(), it's not really the primary key, 'cos the relationship is flipped
			 * when we're in $fkOnParent mode!  So it's actually the FK field on the parent table.
			 */
			$fkField = $joinModel->getPrimaryKey('___');
			$pk = ArrayHelper::getValue($formData, $fkField . '_raw', ArrayHelper::getValue($formData, $fkField, ''));

			if (is_array($pk))
			{
				$pk = array_shift($pk);
			}

			// storeRow treats 0 or '0' differently to empty string!  So if empty(), specifically set to empty string
			if (empty($pk))
			{
				$pk = '';
			}

			$insertId = $listModel->storeRow($data, $pk, true, $item);
		}

		// Reset the list's table name
		$list->db_table_name = $tblName;
		$list->db_primary_key = $tblPk;

		/*
		 * If the FK is on the parent, we (may) need to update the parent row with the FK value
		 * for the joined row we just upserted.
		 */
		if ($fkOnParent && !empty($insertId))
		{
			/*
			 * Only bother doing this is the FK isn't there or has been changed.  Again, don't be
			 * confused by using getPrimaryKey, it's really the FK 'cos the relationship is flipped
			 * from a "normal" join, with the FK on the parent.
			 */
			$fkField = $joinModel->getPrimaryKey('___');
			if (!array_key_exists($fkField, $formData) || $formData[$fkField] != $insertId)
			{
				$formData[$fkField] = $insertId;
				$formData[$fkField . '_raw'] = $insertId;
				$fkField = $joinModel->getPrimaryKey('.');
				$listModel->updateRow($parentId, $fkField, $insertId);
			}
		}

	}

	/**
	 * When storing a joined group. Delete any deselected repeating joined records
	 *
	 * @param   array  $usedKeys  Keys saved in store()
	 *
	 * @return  bool
	 */
	private function deleteRepeatGroups($usedKeys = array())
	{
		if (!$this->canRepeat())
		{
			/*
			 * If the group can not be repeated then the user could not have deleted a
			 * repeat group.
			 */
			return true;
		}

		/*
		 * If we are copying a row, everything is new, leave old groups alone
		 */

		$formModel = $this->getFormModel();

		if ($formModel->copyingRow())
		{
			return true;
		}

		$listModel = $this->getListModel();
		$list = $listModel->getTable();
		$joinModel = $this->getJoinModel();
		$join = $joinModel->getJoin();
		$db = $listModel->getDb();
		$query = $db->getQuery(true);
		$masterInsertId = $this->masterInsertId();
		$query->delete($db->qn($list->db_table_name));
		$pk = $join->params->get('pk');

		/*
		 * Get the original row ids. We can ONLY delete from within this set. This
		 * allows us to respect and prefilter that was applied to the list's data.
		 */
		$groupid = $this->getId();

		/**
		 * $$$ hugh - nooooo, on AJAX submit, request array hasn't been urldecoded, but formData has.
		 * So in the request it's still hex-ified JSON.  Leaving this line and comment in here as a
		 * reminder to self we may have other places this happens.
		 * $origGroupRowsIds = $input->get('fabrik_group_rowids', array(), 'array');
		 */

		$origGroupRowsIds = ArrayHelper::getValue($formModel->formData, 'fabrik_group_rowids', array());
		$origGroupRowsIds = ArrayHelper::getValue($origGroupRowsIds, $groupid, array());
		$origGroupRowsIds = json_decode($origGroupRowsIds);

		/*
		 * Find out which keys were origionally in the form, but were not submitted
		 * i.e. those keys whose records were removed
		 */

		if (!$formModel->isNewRecord())
		{
			$keysToDelete = array_diff($origGroupRowsIds, $usedKeys);
		}

		// Nothing to delete - return
		if (empty($keysToDelete))
		{
			return true;
		}

		if (is_array($masterInsertId))
		{
			foreach ($masterInsertId as &$mid)
			{
				if (is_array($mid))
				{
					$mid = array_unshift($mid);
				}

				$mid = $db->quote($mid);
			}

			$query->where($db->qn($join->table_join_key) . ' IN (' . implode(', ', $masterInsertId) . ')');
		}
		else
		{
			$query->where($db->qn($join->table_join_key) . ' = ' . $db->quote($masterInsertId));
		}

		$query->where($pk . 'IN (' . implode(',', $db->q($keysToDelete)) . ') ');
		$db->setQuery($query);

		return $db->execute();
	}

	/**
	 * Test if the group can repeat and if the fk element is published
	 *
	 * @since   3.1rc1
	 *
	 * @return boolean
	 */

	public function fkPublished()
	{
		if ($this->canRepeat())
		{
			return true;
		}

		$joinTable = $this->getJoinModel()->getJoin();
		$fullFk = $joinTable->table_join . '___' . $joinTable->table_join_key;
		$elementModels = $this->getPublishedElements();

		foreach ($elementModels as $elementModel)
		{
			if ($elementModel->getFullName(true, false) === $fullFk)
			{
				return true;
			}
		}

		JError::raiseWarning(E_ERROR, JText::sprintf('COM_FABRIK_JOINED_DATA_BUT_FK_NOT_PUBLISHED', $fullFk));

		return false;
	}

	/**
	 * Get the number of times the group was repeated based on the form's current data
	 *
	 * @since   3.1rc1
	 *
	 * @return number
	 */

	public function repeatCount()
	{
		$data = $this->getFormModel()->getData();
		$elementModels = $this->getPublishedElements();
		reset($elementModels);
		$tmpElement = current($elementModels);

		if (!empty($elementModels))
		{
			$smallerElHTMLName = $tmpElement->getFullName(true, false);
			$d = ArrayHelper::getValue($data, $smallerElHTMLName, 1);

			if (is_object($d))
			{
				$d = ArrayHelper::fromObject($d);
			}

			$repeatGroup = count($d);
		}
		else
		{
			// No published elements - not sure if setting repeatGroup to 0 is right though
			$repeatGroup = 0;
		}

		return $repeatGroup;
	}

	/**
	 * Should the group legend be shown
	 *
	 * @param   object  $group  Group properties
	 *
	 * @return boolean
	 */
	private function showLegend($group)
	{
		$allHidden = true;

		foreach ($this->elements as $elementModel)
		{
			$allHidden &= $elementModel->isHidden();
		}
		if ((!$allHidden || !empty($group->intro)) && trim($group->title) !== '') {
			return true;
		}

		return false;
	}
}
