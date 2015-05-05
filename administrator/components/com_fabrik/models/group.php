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
	 * Group
	 *
	 * @var stdClass
	 */
	protected $group = null;
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

	public function setGroup($group)
	{
		$this->group = $group;
	}

	public function getGroup()
	{
		return $this->group;
	}

	public function getElements()
	{
		return $this->group->fields;
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
		return $this->getGroup()->is_join;
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
			$this->params = new JRegistry($this->getGroup()->params);
		}

		return $this->params;
	}

}
