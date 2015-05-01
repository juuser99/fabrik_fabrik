<?php
/**
 * Cron Admin Model
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

use Fabrik\Helpers\ArrayHelper;
use \JPluginHelper as JPluginHelper;
use \JModelLegacy as JModelLegacy;
use \FabrikString as FabrikString;
use \FText as FText;

interface CronInterface
{
}

/**
 * Cron Admin Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.5
 */
class Cron extends Base implements CronInterface
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var  string
	 */
	protected $text_prefix = 'COM_FABRIK_CRON';

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
		parent::populateState($ordering, $direction);
		$this->state->set('plugin', $this->app->input->get('plugin', ''));
	}
	/**
	 * Get html form fields for a plugin (filled with
	 * current element's plugin data
	 *
	 * @param   string  $plugin  plugin name
	 *
	 * @return  string	html form fields
	 */

	public function getPluginHTML($plugin = null)
	{
		$item = $this->getItem();

		if (is_null($plugin))
		{
			$plugin = $item->plugin ? $item->plugin : $this->state->get('plugin');
		}

		JPluginHelper::importPlugin('fabrik_cron');
		$pluginManager = JModelLegacy::getInstance('Pluginmanager', 'FabrikFEModel');

		// Trim old f2 cron prefix.
		$plugin = FabrikString::ltrimiword($plugin, 'cron');

		if ($plugin == '')
		{
			$str = '<div class="alert">' . FText::_('COM_FABRIK_SELECT_A_PLUGIN') . '</div>';
		}
		else
		{
			$plugin = $pluginManager->getPlugIn($plugin, 'Cron');
			$str = $plugin->onRenderAdminSettings(ArrayHelper::fromObject($item), null, 'nav-tabs');
		}

		return $str;
	}

	/**
	 * Save the cron job - merging plugin parameters
	 *
	 * @param   array  $data  The form data.
	 *
	 * @return  boolean  True on success, False on error.
	 */

	public function save($data)
	{
		if (ArrayHelper::getValue($data, 'lastrun') == '')
		{
			$date = JFactory::getDate();
			$data['lastrun'] = $date->toSql();
		}

		$data['params'] = json_encode($data['params']);

		return parent::save($data);
	}

	/**
	 * Method to validate the form data.
	 *
	 * @param   array   $data   The data to validate.
	 *
	 * @return  mixed  Array of filtered data if valid, false otherwise.
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
}
