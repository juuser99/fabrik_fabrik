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

namespace Fabrik\Admin\Models;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Utilities\ArrayHelper;
use \JForm as JForm;
use Fabrik\Helpers\Worker;
use \Joomla\Registry\Registry as JRegistry;

interface ModelFormFormInterface
{
	/**
	 * Save the form
	 *
	 * @param   array  $data  posted jform data
	 *
	 * @return  bool
	 */
	public function save($data);

}

/**
 * Fabrik Admin Form Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.5
 */
class Form extends View implements ModelFormFormInterface
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
		$this->set('id', $view);
		$item = $this->getItem();
		$groups = $item->get('form.groups');

		$post = $this->prepareSave($post, 'form');
		$selectedGroups = ArrayHelper::fromObject($post->get('form.current_groups'));

		$newGroups = new \stdClass;

		foreach ($groups as $group)
		{
			if (in_array($group->id, $selectedGroups))
			{
				$name = $group->name;
				$newGroups->$name = $group;
			}
		}

		$post->set('form.groups', $newGroups);

		return parent::save($post);
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
		$js[] = "\t\tFabrik.controller = new PluginManager($plugins, '" . $this->getItem()->get('id') . "', 'form');";
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
		// FIXME for json view
		echo "_makeFormGroups not workee ";exit;
		$formid = $this->get($this->getName() . '.id');
		$db = Worker::getDbo(true);
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
		// FIXME
		echo "swapListToFormIds not json vied";exit;
		if (empty($ids))
		{
			return array();
		}

		ArrayHelper::toInteger($ids);
		$db = Worker::getDbo(true);
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
		// FIXME
		echo "update database not json viewd";exit;
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
				$db = Worker::getDbo(true);
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
	 * Validate the form
	 *
	 * @param   array   $data   The data to validate.
	 *
	 * @return mixed  false or data
	 */

	public function validate($data)
	{
		$params = $data['params'];
		$ok = parent::validate($data);

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
/*	public function delete(&$cids)
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
	}*/


}
