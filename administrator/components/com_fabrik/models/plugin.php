<?php
/**
 * Fabrik Admin Plugin Model
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

use \JModelLegacy as JModelLegacy;
use Fabrik\Helpers\ArrayHelper;


interface PluginInterface
{
}

/**
 * Fabrik Admin Plugin Model
 * Used for loading via ajax form plugins
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.0.6
 */
class Plugin extends Base implements PluginInterface
{
	/**
	 * Render the plugins fields
	 *
	 * @return string
	 */
	public function render()
	{
		$input                     = $this->app->input;
		$pluginManager             = JModelLegacy::getInstance('Pluginmanager', 'FabrikFEModel');
		$plugin                    = $pluginManager->getPlugIn($this->get('plugin'), $this->get('type'));
		$feModel                   = $this->getPluginModel();
		$plugin->getJForm()->model = $feModel;

		$data = $this->getData();
		$input->set('view', $this->get('type'));

		$str = $plugin->onRenderAdminSettings($data, $this->get('c'), 'nav-tabs');
		$input->set('view', 'plugin');

		return $str;
	}

	/**
	 * Get the plugins data to bind to the form
	 *
	 * @return  array
	 */
	public function getData()
	{
		$type = $this->get('type');
		$data = array();
		if ($type === 'validationrule')
		{
			$item = FabTable::getInstance('Element', 'FabrikTable');
			$item->load($this->get('id'));
		}
		elseif ($type === 'elementjavascript')
		{
			$item = FabTable::getInstance('Jsaction', 'FabrikTable');
			$item->load($this->get('id'));
			$data = $item->getProperties();
		}
		else
		{
			$feModel = $this->getPluginModel();
			$item    = $feModel->getTable();
		}
		$data                                       = $data + (array) json_decode($item->params);
		$data['plugin']                             = $this->get('plugin');
		$data['params']                             = (array) ArrayHelper::getValue($data, 'params', array());
		$data['params']['plugins']                  = $this->get('plugin');
		$data['validationrule']['plugin']           = $this->get('plugin');
		$data['validationrule']['plugin_published'] = $this->get('plugin_published');
		$data['validationrule']['show_icon']        = $this->get('show_icon');
		$data['validationrule']['validate_in']      = $this->get('validate_in');
		$data['validationrule']['validation_on']    = $this->get('validation_on');
		$c                                          = $this->get('c') + 1;
		// Add plugin published state, locations, descriptions and events
		$state                          = (array) ArrayHelper::getValue($data, 'plugin_state');
		$locations                      = (array) ArrayHelper::getValue($data, 'plugin_locations');
		$events                         = (array) ArrayHelper::getValue($data, 'plugin_events');
		$descriptions                   = (array) ArrayHelper::getValue($data, 'plugin_description');
		$data['params']['plugin_state'] = ArrayHelper::getValue($state, $c, 1);
		$data['plugin_locations']       = ArrayHelper::getValue($locations, $c);
		$data['plugin_events']          = ArrayHelper::getValue($events, $c);
		$data['plugin_description']     = ArrayHelper::getValue($descriptions, $c);

		return $data;
	}

	/**
	 * Get the plugin model
	 *
	 * @return  object
	 */
	protected function getPluginModel()
	{
		$feModel = null;
		$type    = $this->get('type');

		if ($type === 'elementjavascript')
		{
			return null;
		}

		if ($type !== 'validationrule')
		{
			// Set the parent model e.g. form/list
			$feModel = JModelLegacy::getInstance($type, 'FabrikFEModel');
			$feModel->setId($this->get('id'));
		}

		return $feModel;
	}

}
