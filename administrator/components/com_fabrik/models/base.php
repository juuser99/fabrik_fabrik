<?php
/**
 * Fabrik Base Admin Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.5
 */
namespace Fabrik\Admin\Models;

use \JForm as JForm;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;
use \JDate as Date;
use Fabrik\Storage\MySql as Storage;
use \JPluginHelper as JPluginHelper;
use Joomla\String\String as String;
use Fabrik\Helpers\Worker as Worker;
use \JHTML as JHTML;

/**
 * Fabrik Base Admin Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.5
 */
class Base extends \JModelBase
{
	/**
	 * List of items
	 *
	 * @var array
	 */
	protected $items;

	/**
	 * Model name
	 *
	 * @var string
	 */
	protected $name = '';

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
		$this->app  = $this->state->get('app', \JFactory::getApplication());
		$this->user = $this->state->get('user', \JFactory::getUser());

		if ($this->name === '')
		{
			$path       = explode('\\', get_class($this));
			$this->name = strtolower(array_pop($path));
		}

		$this->populateState();
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
	}

	protected function filterItems($items)
	{
		$items = array_filter($items, function ($item)
		{
			$filters = $this->get('filter');

			foreach ($filters as $field => $value)
			{
				if ($field <> 'search' && $value <> '*' && $value <> '')
				{
					if ($item->$field <> $value)
					{
						return false;
					}
				}

				return true;
			}

			return true;
		});

		return $items;
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
		if (is_null($id))
		{
			$id = $this->get('id', '');
		}

		// @TODO - save & load from session?

		if ($id === '')
		{
			$json = file_get_contents(JPATH_COMPONENT_ADMINISTRATOR . '/models/schemas/template.json');
		}
		else
		{
			$json = file_get_contents(JPATH_COMPONENT_ADMINISTRATOR . '/models/views/' . $id . '.json');
		}

		$item = json_decode($json);

		return new Registry($item);

		return $item;
	}

	/**
	 * Get view items.
	 *
	 * @return array
	 */
	public function getItems()
	{
		$path  = JPATH_COMPONENT_ADMINISTRATOR . '/models/views';
		$files = \JFolder::files($path, '.json', false, true);
		$items = array();

		foreach ($files as $file)
		{
			$json    = file_get_contents($file);
			$items[] = json_decode($json);
		}

		return $items;
	}

	/**
	 * Get the list pagination object
	 *
	 * @return \JPagination
	 */
	public function getPagination()
	{
		$items = $this->getItems();

		return new \JPagination(count($items), 0, 0);
	}

	/**
	 * Save a record
	 *
	 * @param   array $data
	 *
	 * @return bool
	 */
	public function save($data)
	{
		// Clear out the form state if save is ok.
		$this->storeFormState(array());

		$this->app->enqueueMessage('Saved');

		return true;
	}

	/**
	 * Get the models active/selected plug-ins
	 *
	 * @return array
	 */
	public function getPlugins($subView = 'list')
	{
		$item = $this->getItem();
		// Load up the active plug-ins
		$plugins = $item->get($subView . '.params.plugins', array());

		return $plugins;
	}

	/**
	 * Create a drop down list of all the elements in the form
	 *
	 * @param   string $name               Drop down name
	 * @param   string $default            Current value
	 * @param   bool   $excludeUnpublished Add elements that are unpublished
	 * @param   bool   $useStep            Concat table name and el name with '___' (true) or "." (false)
	 * @param   bool   $incRaw             Include raw labels default = true
	 * @param   string $key                What value should be used for the option value 'name' (default) or 'id'
	 *                                     @since 3.0.7
	 * @param   string $attribs            Select list attributes @since 3.1b
	 *
	 * @return    string    html list
	 */

	public function getElementList($name = 'order_by', $default = '', $excludeUnpublished = false,
		$useStep = false, $incRaw = true, $key = 'name', $attribs = 'class="inputbox" size="1"')
	{
		$aEls = $this->getElementOptions($useStep, $key, false, $incRaw);
		asort($aEls);

		array_unshift($aEls, JHTML::_('select.option', '', '-'));

		return JHTML::_('select.genericlist', $aEls, $name, $attribs, 'value', 'text', $default);
	}

	/**
	 * Load up a front end form model - used in saving the list
	 *
	 * @return  object  front end form model
	 */
	public function getFormModel()
	{
		if (is_null($this->formModel))
		{
			$this->formModel = new Form;
			$this->formModel->set('id', $this->get('id'));
			/*$config          = array();
			$this->formModel = JModelLegacy::getInstance('Form', 'FabrikFEModel', $config);
			$this->formModel->setDbo($config['dbo']);*/
		}

		return $this->formModel;
	}

	/**
	 * Creates options array to be then used by getElementList to create a drop down of elements in the form
	 * separated as elements need to collate this options from multiple forms
	 *
	 * @param   bool   $useStep                concat table name and el name with '___' (true) or "." (false)
	 * @param   string $key                    name of key to use (default "name")
	 * @param   bool   $show_in_list_summary   only show those elements shown in table summary
	 * @param   bool   $incRaw                 include raw labels in list (default = false) Only works if $key = name
	 * @param   array  $filter                 list of plugin names that should be included in the list - if empty
	 *                                         include all plugin types
	 * @param   string $labelMethod            An element method that if set can alter the option's label
	 *                                         Used to only show elements that can be selected for search all
	 * @param   bool   $noJoins                do not include elements in joined tables (default false)
	 *
	 * @return    array    html options
	 */
	public function getElementOptions($useStep = false, $key = 'name', $show_in_list_summary = false, $incRaw = false,
		$filter = array(), $labelMethod = '', $noJoins = false)
	{
		$groups = $this->getFormModel()->getGroupsHiarachy();
		$aEls   = array();

		foreach ($groups as $gid => $groupModel)
		{
			if ($noJoins && $groupModel->isJoin())
			{
				continue;
			}

			$elementModels = $groupModel->getMyElements();
			$prefix        = $groupModel->isJoin() ? $groupModel->getJoinModel()->getJoin()->table_join . '.' : '';

			foreach ($elementModels as $elementModel)
			{
				$el = $elementModel->getElement();

				if (!empty($filter) && !in_array($el->plugin, $filter))
				{
					continue;
				}

				if ($show_in_list_summary == true && $el->show_in_list_summary != 1)
				{
					continue;
				}

				$val   = $el->$key;
				$label = strip_tags($prefix . $el->label);

				if ($labelMethod !== '')
				{
					$elementModel->$labelMethod($label);
				}

				if ($key != 'id')
				{
					$val = $elementModel->getFullName($useStep, false);

					if ($this->addDbQuote)
					{
						$val = FabrikString::safeColName($val);
					}

					if ($incRaw && is_a($elementModel, 'PlgFabrik_ElementDatabasejoin'))
					{
						/* @FIXME - next line had been commented out, causing undefined warning for $rawval
						 * on following line.  Not sure if getrawColumn is right thing to use here though,
						 * like, it adds filed quotes, not sure if we need them.
						 */
						if ($elementModel->getElement()->published != 0)
						{
							$rawval = $elementModel->getRawColumn($useStep);

							if (!$this->addDbQuote)
							{
								$rawval = str_replace('`', '', $rawval);
							}

							$aEls[$label . '(raw)'] = JHTML::_('select.option', $rawval, $label . '(raw)');
						}
					}
				}

				$aEls[] = JHTML::_('select.option', $val, $label);
			}
		}
		// Paul - Sort removed so that list is presented in group/id order regardless of whether $key is name or id
		// asort($aEls);

		return $aEls;
	}

	/**
	 * Set a state property
	 *
	 * @param   string $key
	 * @param   mixed  $value
	 *
	 * @return  mixed  The value that has been set
	 */
	public function set($key, $value)
	{
		return $this->state->set($key, $value);
	}

	/**
	 * Get a state property
	 *
	 * @param        $key
	 * @param string $default
	 *
	 * @return mixed
	 */
	public function get($key, $default = '')
	{
		return $this->state->get($key, $default);
	}

	/**
	 * Method to get the record form.
	 *
	 * @return  mixed    A JForm object on success, false on failure
	 */
	public function getForm()
	{
		return $this->loadForm($this->name);
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

		$form = JForm::getInstance('com_fabrik.' . $name, $name, $options, false, false);
		$item = $this->getItem();
		$klass = explode("\\", get_class($this));
		$klass =strtolower(array_pop($klass));

		if ($klass === 'lizt')
		{
			$klass = 'list';
		}

		$data = $this->getItem()->get($klass);
		$data->view = $item->get('view');
		$form->bind($data);
		$form->model = $this;

		return $form;
	}

	/**
	 * Store the submitted form's data in the session.
	 * Used in getItem() to load submitted form data
	 * after a failed submission
	 *
	 * @param   array|object $data
	 *
	 * @return  void
	 */
	public function storeFormState($data)
	{
		if (is_array($data))
		{
			$data = ArrayHelper::toObject($data);
		}

		$this->app->setUserState('com_fabrik.edit.' . $this->name . '.data', $data);
	}

	/**
	 * Validate the form data, set's errors if found.
	 *
	 * @param   array $data Posted form data
	 *
	 * @return boolean
	 */
	public function validate($data)
	{
		$form  = $this->getForm();
		$valid = $form->validate($data);
		$this->set('errors', $form->getErrors());

		if (!$valid)
		{
			// Store the submitted data to the session so we can reload it after the redirect
			$this->storeFormState($data);
		}

		return $valid;
	}

	/**
	 * Prepare the data for saving. Run after validation
	 *
	 * @param  array &$data
	 *
	 * @return array
	 */
	public function prepare(&$data)
	{
		return $data;
	}

	/**
	 * Unpublish items
	 *
	 * @param array $ids
	 */
	public function unpublish($ids = array())
	{
		$items = $this->getItems();

		foreach ($ids as $id)
		{
			$items[$id]->published = 0;
			$items[$id]->id        = $id;
			$this->save($items[$id]);
		}
	}

	/**
	 * Publish items
	 *
	 * @param array $ids
	 */
	public function publish($ids = array())
	{
		$items = $this->getItems();

		foreach ($ids as $id)
		{
			$items[$id]->published = 1;
			$items[$id]->id        = $id;
			$this->save($items[$id]);
		}
	}

	/**
	 * Check in the item
	 *
	 * @return bool
	 */
	public function checkin()
	{
		$item                   = $this->getItem();
		$item->checked_out      = '';
		$item->checked_out_time = '';

		return $this->save($item);
	}

	/**
	 * Checkout the item
	 *
	 * @return bool
	 */
	public function checkout()
	{
		$now  = new Date;
		$item = $this->getItem();

		$item->set('checked_out', $this->user->get('id'));
		$item->set('checked_out_time', $now->toSql());
		$item = $item->toObject();

		return $this->save($item);
	}

	public function cleanCache($option)
	{
		// FIXME - needs to be implemented - copy from JModelLegacy?
	}

	/**
	 * Can the user perform a core action on the model
	 *
	 * @param   string   $task Action to perform e.g 'delete'
	 * @param   stdClass $item Item
	 *
	 * @return mixed
	 */
	protected function can($task, $item = null)
	{
		return $this->user->authorise('core.' . $task, 'com_fabrik');
	}

	/**
	 * Delete main json files.
	 *
	 * @param   array $ids File names
	 *
	 * @return  int  Number of deleted files
	 */
	public function delete($ids)
	{
		// Include the content plugins for the on delete events.
		JPluginHelper::importPlugin('content');
		$dispatcher = \JEventDispatcher::getInstance();
		$count      = 0;

		if (!$this->can('delete'))
		{
			$this->app->enqueueMessage(\FText::_('JLIB_APPLICATION_ERROR_EDIT_STATE_NOT_PERMITTED'));

			return $count;
		}

		foreach ($ids as $id)
		{
			$dispatcher->trigger('onContentBeforeDelete', array('com_fabrik.list', $id));
			$file = JPATH_COMPONENT_ADMINISTRATOR . '/models/views/' . $id . '.json';

			if (\JFile::delete($file))
			{
				$count++;
			}

			$dispatcher->trigger('onContentAfterDelete', array('com_fabrik.list', $id));
		}

		return $count;
	}

	/**
	 * Drop a series of lists' tables.
	 *
	 * @param   array $ids List references
	 *
	 * @return  bool
	 */
	public function drop($ids = array())
	{
		JPluginHelper::importPlugin('content');
		$dispatcher = \JEventDispatcher::getInstance();
		$dbPrefix   = $this->app->get('dbprefix');

		foreach ($ids as $id)
		{
			$item  = $this->getItem($id);
			$table = $item->list->db_table_name;
			$dispatcher->trigger('onContentBeforeDrop', array('com_fabrik.list', $id));

			if (strncasecmp($table, $dbPrefix, String::strlen($dbPrefix)) == 0)
			{
				$this->app->enqueueMessage(JText::sprintf('COM_FABRIK_TABLE_NOT_DROPPED_PREFIX', $table, $dbPrefix), 'notice');
			}
			else
			{
				$storage = $this->getStorage(array('table' => $table));
				$storage->drop();
				$this->app->enqueueMessage(JText::sprintf('COM_FABRIK_TABLE_DROPPED', $table));
			}

			$dispatcher->trigger('onContentAfterDrop', array('com_fabrik.list', $id));
		}

		return true;
	}

	/**
	 * Get the storage model
	 *
	 * @param array $options
	 *
	 * @return Storage
	 */
	protected function getStorage($options = array())
	{
		if (!array_key_exists('db', $options))
		{
			$options['db'] = $this->getDb();
		}

		return new Storage($options);
	}

	/**
	 * Load the database object associated with the list
	 *
	 * @since   3.0b
	 *
	 * @return  object database
	 */
	public function getDb()
	{
		$listId = $this->get('list.id');
		$item   = $this->getItem($listId);
		echo "getdb";

		return Worker::getConnection($item)->getDb();
	}

}