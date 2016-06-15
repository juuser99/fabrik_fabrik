<?php
/**
 * Fabrik Model
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Models;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\ArrayHelper;
use Fabrik\Helpers\Text;
use \FabTable;
use Joomla\Registry\Registry;
use \JFactory;
use \JForm;
use \JPluginHelper;
use \JEventDispatcher;

use \RuntimeException;

jimport('joomla.application.component.model');

/**
 * Model form
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.0
 */
class Model extends \JModelLegacy
{
	/**
	 * @var \JApplicationCms
	 */
	protected $app;

	/**
	 * @var \JUser
	 */
	protected $user;

	/**
	 * @var \JDate
	 */
	protected $date;

	/**
	 * App name
	 *
	 * @var string
	 */
	protected $package = 'fabrik';

	/**
	 * @var Registry
	 */
	protected $config;

	/**
	 * @var \JLanguage
	 */
	protected $lang;

	/**
	 * @var \JSession
	 */
	protected $session;

	/**
	 * Table row id
	 * @var
	 */
	protected $id;

	/**
	 * Constructor
	 *
	 * @param   array  $config  An array of configuration options (name, state, dbo, table_path, ignore_request).
	 *
	 * @since   3.3.4
	 * @throws  \Exception
	 */
	public function __construct($config = array())
	{
		$this->app = ArrayHelper::getValue($config, 'app', JFactory::getApplication());
		$this->user = ArrayHelper::getValue($config, 'user', JFactory::getUser());
		$this->config = ArrayHelper::getValue($config, 'config', JFactory::getConfig());
		$this->session = ArrayHelper::getValue($config, 'session', JFactory::getSession());
		$this->date = ArrayHelper::getValue($config, 'date', JFactory::getDate());
		$this->lang = ArrayHelper::getValue($config, 'lang', JFactory::getLanguage());
		$this->package = $this->app->getUserState('com_fabrik.package', 'fabrik');

		parent::__construct($config);
	}

	/**
	 * Method to load and return a model object.
	 *
	 * @param   string  $name    The name of the view
	 * @param   string  $prefix  The class prefix. Optional.
	 * @param   array   $config  configuration
	 *
	 * @return	FabTable|false	Model object or boolean false if failed
	 */
	protected function _createTable($name, $prefix = 'Table', $config = array())
	{
		// Clean the model name
		$name = preg_replace('/[^A-Z0-9_]/i', '', $name);
		$prefix = preg_replace('/[^A-Z0-9_]/i', '', $prefix);

		// Make sure we are returning a DBO object
		if (!array_key_exists('dbo', $config))
		{
			$config['dbo'] = $this->getDbo();
		}

		return FabTable::getInstance($name, $prefix, $config);
	}

	/**
	 * Method to get a table object, load it if necessary.
	 *
	 * @param   string  $name     The table name. Optional.
	 * @param   string  $prefix   The class prefix. Optional.
	 * @param   array   $options  Configuration array for model. Optional.
	 *
	 * @return	\JTable	The table
	 */
	public function getTable($name = '', $prefix = 'Table', $options = array())
	{
		if (empty($name))
		{
			$name = $this->getName();
		}

		if ($table = $this->_createTable($name, $prefix, $options))
		{
			return $table;
		}

		throw new RuntimeException(Text::sprintf('JLIB_APPLICATION_ERROR_TABLE_NAME_NOT_SUPPORTED', $name));
	}

	/**
	 * Method to set the table row id
	 *
	 * @param   int  $id  Table row id
	 *
	 * @return  null
	 */
	public function setId($id)
	{
		$this->id = $id;
	}

	/**
	 * Method to get the record form.
	 *
	 * @param   array    $data      An optional array of data for the form to interogate.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  JForm    A JForm object on success, false on failure
	 *
	 * @since   1.6
	 */
	public function getForm($data = array(), $loadData = true)
	{
		return new \JForm;
	}

	/**
	 * Method to get a form object.
	 *
	 * @param   string   $name     The name of the form.
	 * @param   string   $source   The form source. Can be XML string if file flag is set to false.
	 * @param   array    $options  Optional array of options for the form creation.
	 * @param   boolean  $clear    Optional argument to force load a new form.
	 * @param   string|bool   $xpath    An optional xpath to search for the fields.
	 *
	 * @return  mixed  JForm object on success, False on error.
	 *
	 * @see     JForm
	 */
	protected function loadForm($name, $source = null, $options = array(), $clear = false, $xpath = false)
	{
		// Handle the optional arguments.
		$options['control'] = ArrayHelper::getValue($options, 'control', false);

		// Create a signature hash.
		$hash = md5($source . serialize($options));

		// Check if we can use a previously loaded form.
		if (isset($this->_forms[$hash]) && !$clear)
		{
			return $this->_forms[$hash];
		}

		// Get the form.
		JForm::addFormPath(JPATH_COMPONENT . '/models/forms');
		JForm::addFieldPath(JPATH_COMPONENT . '/models/fields');
		JForm::addFormPath(JPATH_COMPONENT . '/model/form');
		JForm::addFieldPath(JPATH_COMPONENT . '/model/field');

		$form = JForm::getInstance($name, $source, $options, false, $xpath);

		if (isset($options['load_data']) && $options['load_data'])
		{
			// Get the data for the form.
			$data = $this->loadFormData();
		}
		else
		{
			$data = array();
		}

		// Allow for additional modification of the form, and events to be triggered.
		// We pass the data because plugins may require it.
		$this->preprocessForm($form, $data);

		// Load the data into the form after the plugins have operated.
		$form->bind($data);

		// Store the form for later.
		$this->_forms[$hash] = $form;

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return  array    The default data is an empty array.
	 */
	protected function loadFormData()
	{
		return array();
	}

	/**
	 * Method to allow derived classes to preprocess the data.
	 *
	 * @param   string  $context  The context identifier.
	 * @param   mixed   &$data    The data to be processed. It gets altered directly.
	 *
	 * @return  void
	 */
	protected function preprocessData($context, &$data)
	{
		// Get the dispatcher and load the users plugins.
		$dispatcher = JEventDispatcher::getInstance();
		JPluginHelper::importPlugin('content');

		// Trigger the data preparation event.
		$results = $dispatcher->trigger('onContentPrepareData', array($context, &$data));

		// Check for errors encountered while preparing the data.
		if (count($results) > 0 && in_array(false, $results, true))
		{
			$this->setError($dispatcher->getError());
		}
	}

	/**
	 * Method to allow derived classes to preprocess the form.
	 *
	 * @param   JForm   $form   A JForm object.
	 * @param   mixed   $data   The data expected for the form.
	 * @param   string  $group  The name of the plugin group to import (defaults to "content").
	 *
	 * @return  void
	 *
	 * @see     JFormField
	 * @since   12.2
	 * @throws  Exception if there is an error in the form event.
	 */
	protected function preprocessForm(JForm $form, $data, $group = 'content')
	{
		// Import the appropriate plugin group.
		JPluginHelper::importPlugin($group);

		// Get the dispatcher.
		$dispatcher = JEventDispatcher::getInstance();

		// Trigger the form preparation event.
		$results = $dispatcher->trigger('onContentPrepareForm', array($form, $data));

		// Check for errors encountered while preparing the form.
		if (count($results) && in_array(false, $results, true))
		{
			// Get the last error.
			$error = $dispatcher->getError();

			if (!($error instanceof Exception))
			{
				throw new Exception($error);
			}
		}
	}
}
