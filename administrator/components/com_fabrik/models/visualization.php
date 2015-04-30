<?php
/**
 * Fabrik Admin Visualization Model
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
use \JPluginHelper as JPluginHelper;
use \JModelLegacy as JModelLegacy;
use \FText as FText;


require_once 'fabmodeladmin.php';

interface ModelVisualizationInterface
{

}

/**
 * Fabrik Admin Visualization Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.5
 */
class Visualization extends Base implements ModelVisualizationInterface
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var  string
	 */
	protected $text_prefix = 'COM_FABRIK_VISUALIZATION';

	/**
	 * get html form fields for a plugin (filled with
	 * current element's plugin data
	 *
	 * @param   string  $plugin  plugin name
	 *
	 * @return  string	html form fields
	 */

	public function getPluginHTML($plugin = null)
	{
		//$input = $this->app->input;
		$item = $this->getItem();

		if (is_null($plugin))
		{
			$plugin = $item->plugin;
		}

		//$input->set('view', 'visualization');
		JPluginHelper::importPlugin('fabrik_visualization', $plugin);
		$pluginManager = JModelLegacy::getInstance('Pluginmanager', 'FabrikFEModel');

		if ($plugin == '')
		{
			$str = FText::_('COM_FABRIK_SELECT_A_PLUGIN');
		}
		else
		{
			$plugin = $pluginManager->getPlugIn($plugin, 'Visualization');
			$str = $plugin->onRenderAdminSettings(ArrayHelper::fromObject($item), null, 'nav-tabs');
		}

		return $str;
	}

	/**
	 * Method to validate the form data.
	 *
	 * @param   JForm   $form   The form to validate against.
	 * @param   array   $data   The data to validate.
	 * @param   string  $group  The name of the field group to validate.
	 *
	 * @return  mixed  Array of filtered data if valid, false otherwise.
	 *
	 * @see     JFormRule
	 * @see     JFilterInput
	 */

	public function validate($form, $data, $group = null)
	{
		parent::validate($form, $data);

		return $data;
	}

	/**
	 * Method to save the form data.
	 *
	 * @param   array  $data  The form data.
	 *
	 * @return  boolean  True on success, False on error.
	 */

	public function save($data)
	{
		parent::cleanCache('com_fabrik');

		return parent::save($data);
	}
}
