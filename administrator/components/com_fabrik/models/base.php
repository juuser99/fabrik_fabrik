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
use Joomla\Utilities\ArrayHelper;
use \JDate as Date;

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
		});

		return $items;
	}

	/**
	 * Get the item -
	 * if no id set load data template
	 *
	 * @param  string $id
	 *
	 * @return stdClass
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
	public function getPlugins()
	{
		$item = $this->getItem();
		// Load up the active plug-ins
		$plugins = array();

		if (is_array($item->params))
		{
			$plugins = ArrayHelper::getValue($item->params, 'plugins', array());
		}

		return $plugins;
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

		$form        = JForm::getInstance('com_fabrik.' . $name, $name, $options, false, false);
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
		$now                    = new Date;
		$item                   = $this->getItem();
		$item->checked_out      = $this->user->get('id');
		$item->checked_out_time = $now->toSql();

		return $this->save($item);
	}

	public function cleanCache($option)
	{
		// FIXME - needs to be implemented - copy from JModelLegacy?
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
		$count = 0;

		foreach ($ids as $id)
		{
			$file = JPATH_COMPONENT_ADMINISTRATOR . '/models/views/' . $id . '.json';
			if (\JFile::delete($file))
			{
				$count++;
			}
		}

		return $count;
	}

}