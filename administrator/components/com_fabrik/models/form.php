<?php
/**
 * Fabrik Admin Form Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Utilities\ArrayHelper;

require_once 'fabmodeladmin.php';

interface FabrikAdminModelFormFormInterface
{
	/**
	 * Save the form
	 *
	 * @param   array  $data  posted jform data
	 *
	 * @return  bool
	 */
	public function save($data);

	/**
	 * After having saved the form we
	 * 1) Create a new group if none selected in edit form list
	 * 2) Delete all old form_group records
	 * 3) Recreate the form group records
	 * 4) Make a table view if needed
	 *
	 * @param   array  $data  jform data
	 *
	 * @return  bool  True if you should display the form list, False if you're
	 * redirected elsewhere
	 */
	public function saveFormGroups($data);
}

/**
 * Fabrik Admin Form Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.0
 */

abstract class FabrikAdminModelForm extends FabModelAdmin implements FabrikAdminModelFormFormInterface
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var  string
	 */
	protected $text_prefix = 'COM_FABRIK_FORM';

	/**
	 * The plugin type?
	 *
	 * @deprecated - don't think this is used
	 *
	 * @var  string
	 */
	protected $pluginType = 'Form';

	/**
	 * Returns a reference to the a Table object, always creating it.
	 *
	 * @param   string  $type    The table type to instantiate
	 * @param   string  $prefix  A prefix for the table class name. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  JTable	A database object
	 *
	 * @since	1.6
	 */
	public function getTable($type = 'Form', $prefix = 'FabrikTable', $config = array())
	{
		$config['dbo'] = FabrikWorker::getDbo(true);

		return FabTable::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to get the record form.
	 *
	 * @param   array  $data      Data for the form.
	 * @param   bool   $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  mixed  A JForm object on success, false on failure
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_fabrik.form', 'form', array('control' => 'jform', 'load_data' => $loadData));

		if (empty($form))
		{
			return false;
		}

		$form->model = $this;

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return  mixed	The data for the form.
	 */
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = $this->app->getUserState('com_fabrik.edit.form.data', array());

		if (empty($data))
		{
			$data = $this->getItem();
		}

		return $data;
	}

	/**
	 * Get JS
	 *
	 * @return string
	 */
	public function getJs()
	{
		$js[] = "\twindow.addEvent('domready', function () {";
		$plugins = json_encode($this->getPlugins());
		$js[] = "\t\tFabrik.controller = new PluginManager($plugins, " . (int) $this->getItem()->id . ", 'form');";
		$js[] = "\t})";

		return implode("\n", $js);
	}

	/**
	 * Reinsert the groups ids into formgroup rows
	 *
	 * @param   array  $data           jform post data
	 * @param   array  $currentGroups  group ids
	 *
	 * @return  void
	 */
	protected function _makeFormGroups($data, $currentGroups)
	{
		$formid = $this->getState($this->getName() . '.id');
		$db = FabrikWorker::getDbo(true);
		$query = $db->getQuery(true);
		ArrayHelper::toInteger($currentGroups);
		$query->delete('#__fabrik_formgroup')->where('form_id = ' . (int) $formid);

		if (!empty($currentGroups))
		{
			$query->where('group_id NOT IN (' . implode($currentGroups, ', ') . ')');
		}

		$db->setQuery($query);

		// Delete the old form groups
		$db->execute();

		// Get previously saved form groups
		$query->clear()->select('id, group_id')->from('#__fabrik_formgroup')->where('form_id = ' . (int) $formid);
		$db->setQuery($query);
		$fgids = $db->loadObjectList('group_id');
		$orderid = 1;
		$currentGroups = array_unique($currentGroups);

		foreach ($currentGroups as $group_id)
		{
			if ($group_id != '')
			{
				$group_id = (int) $group_id;
				$query->clear();

				if (array_key_exists($group_id, $fgids))
				{
					$query->update('#__fabrik_formgroup')
					->set('ordering = ' . $orderid)->where('id =' . $fgids[$group_id]->id);
				}
				else
				{
					$query->insert('#__fabrik_formgroup')
					->set(array('form_id =' . (int) $formid, 'group_id = ' . $group_id, 'ordering = ' . $orderid));
				}

				$db->setQuery($query);
				$db->execute();
				$orderid++;
			}
		}
	}

	/**
	 * Take an array of list ids and return the corresponding form_id's
	 * used in list publish code
	 *
	 * @param   array  $ids  list ids
	 *
	 * @return array form ids
	 */
	public function swapListToFormIds($ids = array())
	{
		if (empty($ids))
		{
			return array();
		}

		ArrayHelper::toInteger($ids);
		$db = FabrikWorker::getDbo(true);
		$query = $db->getQuery(true);
		$query->select('form_id')->from('#__fabrik_lists')->where('id IN (' . implode(',', $ids) . ')');

		return $db->setQuery($query)->loadColumn();
	}

	/**
	 * Iterate over the form's elements and update its db table to match
	 *
	 * @return  void
	 */
	public function updateDatabase()
	{
		$input = $this->app->input;
		$cid = $input->get('cid', array(), 'array');
		$formId = $cid[0];
		$model = JModelLegacy::getInstance('Form', 'FabrikFEModel');
		$model->setId($formId);
		$form = $model->getForm();

		// Use this in case there is not table view linked to the form
		if ($form->record_in_database == 1)
		{
			// There is a list view linked to the form so lets load it
			$listModel = JModelLegacy::getInstance('List', 'FabrikAdminModel');
			$listModel->loadFromFormId($formId);
			$listModel->setFormModel($model);
			$dbExisits = $listModel->databaseTableExists();

			if (!$dbExisits)
			{
				/* $$$ hugh - if we're recreating a table for an existing form, we need to pass the field
				 * list to createDBTable(), otherwise all we get is id and date_time.  Not sure if this
				 * code really belongs here, or if we should handle it in createDBTable(), but I didn't want
				 * to mess with createDBTable(), although I did have to make one small change in it (see comments
				 * therein).
				 * NOTE 1 - this code ignores joined groups, so only recreates the original table
				 * NOTE 2 - this code ignores any 'alter existing fields' settings.
				 */
				$db = FabrikWorker::getDbo(true);
				$query = $db->getQuery(true);
				$query->select('group_id')->from('#__fabrik_formgroup AS fg')->join('LEFT', '#__fabrik_groups AS g ON g.id = fg.group_id')
					->where('fg.form_id = ' . $formId . ' AND g.is_join != 1');
				$db->setQuery($query);
				$groupIds = $db->loadResultArray();

				if (!empty($groupIds))
				{
					$fields = array();
					$query = $db->getQuery(true);
					$query->select('plugin, name')->from('#__fabrik_elements')->where('group_id IN (' . implode(',', $groupIds) . ')');
					$db->setQuery($query);
					$rows = $db->loadObjectList();

					foreach ($rows as $row)
					{
						$fields[$row->name] = $row->plugin;
					}

					if (!empty($fields))
					{
						$listModel->createDBTable($listModel->getTable()->db_table_name, $fields);
					}
				}
			}
			else
			{
				$listModel->ammendTable();
			}
		}
	}

	/**
	 * Method to validate the form data.
	 *
	 * @param   object  $form   The form to validate against.
	 * @param   array   $data   The data to validate.
	 * @param   string  $group  The name of the field group to validate.
	 *
	 * @return  mixed	Array of filtered data if valid, false otherwise.
	 */
	public function validate($form, $data, $group = null)
	{
		$params = $data['params'];
		$ok = parent::validate($form, $data);

		// Standard jform validation failed so we shouldn't test further as we can't be sure of the data
		if (!$ok)
		{
			return false;
		}

		// Hack - must be able to add the plugin xml fields file to $form to include in validation but cant see how at the moment
		$data['params'] = $params;

		return $data;
	}

	/**
	 * Delete form and form groups
	 *
	 * @param   array  &$cids  to delete
	 *
	 * @return  bool
	 */
	public function delete(&$cids)
	{
		$res = parent::delete($cids);

		if ($res)
		{
			foreach ($cids as $cid)
			{
				$item = FabTable::getInstance('FormGroup', 'FabrikTable');
				$item->load(array('form_id' => $cid));
				$item->delete();
			}
		}

		return $res;
	}
}
