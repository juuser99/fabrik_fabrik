<?php
/**
 * Cron Admin Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

namespace Fabrik\Component\Fabrik\Administrator\Model;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Worker;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Fabrik\Component\Fabrik\Administrator\Table\CronTable;
use Fabrik\Component\Fabrik\Administrator\Table\FabrikTable;
use Joomla\Utilities\ArrayHelper;
use Fabrik\Helpers\StringHelper as FStringHelper;
use Fabrik\Helpers\ArrayHelper as FArrayHelper;

/**
 * Cron Admin Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       4.0
 */
class CronModel extends FabrikAdminModel
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var  string
	 *
	 * @since 4.0
	 */
	protected $text_prefix = 'COM_FABRIK_CRON';

	/**
	 * Returns a reference to the a Table object, always creating it.
	 *
	 * @param   string $type   The table type to instantiate
	 * @param   string $prefix A prefix for the table class name. Optional.
	 * @param   array  $config Configuration array for model. Optional.
	 *
	 * @return  CronTable  A database object
	 *
	 * @since 4.0
	 */
	public function getTable($type = CronTable::class, $prefix = '', $config = array())
	{
		$config['dbo'] = Worker::getDbo(true);

		return FabrikTable::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to get the record form.
	 *
	 * @param   array $data     Data for the form.
	 * @param   bool  $loadData True if the form is to load its own data (default case), false if not.
	 *
	 * @return  mixed  A JForm object on success, false on failure
	 *
	 * @since 4.0
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_fabrik.cron', 'cron', array('control' => 'jform', 'load_data' => $loadData));

		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return  mixed  The data for the form.
	 *
	 * @since 4.0
	 */
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = $this->app->getUserState('com_fabrik.edit.cron.data', array());

		if (empty($data))
		{
			$data = $this->getItem();
		}

		return $data;
	}

	/**
	 * Get html form fields for a plugin (filled with
	 * current element's plugin data
	 *
	 * @param   string $plugin plugin name
	 *
	 * @return  string    html form fields
	 *
	 * @since 4.0
	 */
	public function getPluginHTML($plugin = null)
	{
		$item = $this->getItem();

		if (is_null($plugin))
		{
			$plugin = $item->plugin;
		}

		PluginHelper::importPlugin('fabrik_cron');

		// Trim old f2 cron prefix.
		$plugin = FStringHelper::ltrimiword($plugin, 'cron');

		if ($plugin == '')
		{
			$str = '<div class="alert">' . Text::_('COM_FABRIK_SELECT_A_PLUGIN') . '</div>';
		}
		else
		{
			$plugin = $this->pluginManager->getPlugIn($plugin, 'Cron');
			$mode   = 'nav-tabs';
			$str    = $plugin->onRenderAdminSettings(ArrayHelper::fromObject($item), null, $mode);
		}

		return $str;
	}

	/**
	 * Save the cron job - merging plugin parameters
	 *
	 * @param   array $data The form data.
	 *
	 * @return  boolean  True on success, False on error.
	 *
	 * @since 4.0
	 */
	public function save($data)
	{
		if (FArrayHelper::getValue($data, 'lastrun') == '')
		{
			$date            = Factory::getDate();
			$data['lastrun'] = $date->toSql();
		}
		else
		{
			$timeZone        = new \DateTimeZone($this->config->get('offset'));
			$data['lastrun'] = Factory::getDate($data['lastrun'], $timeZone)->toSql(false);
		}

		$data['params'] = json_encode($data['params']);

		return parent::save($data);
	}

	/**
	 * Method to validate the form data.
	 *
	 * @param   JForm  $form  The form to validate against.
	 * @param   array  $data  The data to validate.
	 * @param   string $group The name of the field group to validate.
	 *
	 * @see     JFormRule
	 * @see     JFilterInput
	 *
	 * @return  mixed  Array of filtered data if valid, false otherwise.
	 *
	 * @since   4.0
	 */
	public function validate($form, $data, $group = null)
	{
		$params = $data['params'];
		$ok     = parent::validate($form, $data);

		// Standard jform validation failed so we shouldn't test further as we can't be sure of the data

		if (!$ok)
		{
			return false;
		}
		// Hack - must be able to add the plugin xml fields file to $form to include in validation but cant see how at the moment
		$data['params'] = $params;

		return $data;
	}
}
