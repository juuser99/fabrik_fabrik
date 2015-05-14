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
	 * Parameters
	 *
	 * @var JRegistry
	 */
	protected $params = null;

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
}
