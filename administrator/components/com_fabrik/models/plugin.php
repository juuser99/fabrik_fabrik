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

use Fabrik\Helpers\ArrayHelper;
use \JForm as JForm;

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
		JForm::addFieldPath(JPATH_COMPONENT_ADMINISTRATOR . '/models/fields');
		$input                     = $this->app->input;
		$pluginManager             = new PluginManager;
		$plugin                    = $pluginManager->getPlugIn($this->get('plugin'), $this->get('type'));
		$feModel                   = $this->getPluginModel();
		$plugin->getJForm()->model = $feModel;

		$data = $this->getData();
		print_r($data);
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

		if ($type === 'validation')
		{
			$form = new \Fabrik\Admin\Models\Form;
			$form->setId($this->get('id'));

			// FIXME - pass the elemetn id in as part of the ajax call.
			$element = $form->getElement('55488ad55c073', true);

			$validations = $element->getItem()->get('validations');;
			$index = $this->get('c');
			$data['params'] = \Joomla\Utilities\ArrayHelper::getValue($validations, $index, array());
		}
		elseif ($type === 'elementjavascript')
		{
			/*$item = FabTable::getInstance('Jsaction', 'FabrikTable');
			$item->load($this->get('id'));
			$data = $item->getProperties();*/
		}
		else
		{
			$model = $this->getPluginModel();
			$item  = $model->getItem();
		}

		$data['plugin']                             = $this->get('plugin');
		$data['params']                             = (array) ArrayHelper::getValue($data, 'params', array());
		$data['params']['plugins']                  = $this->get('plugin');
		$data['validation']['plugin']           = $this->get('plugin');
		$data['validation']['plugin_published'] = $this->get('plugin_published');
		$data['validation']['show_icon']        = $this->get('show_icon');
		$data['validation']['validate_in']      = $this->get('validate_in');
		$data['validation']['validation_on']    = $this->get('validation_on');
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
	 * @return  Lizt
	 */
	protected function getPluginModel()
	{
		$model = new Lizt;
		$model->set('id', $this->get('id'));

		return $model;
	}

}
