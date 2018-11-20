<?php
/**
 * Fabrik Admin Visualization Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

namespace Joomla\Component\Fabrik\Administrator\Model;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Worker;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormRule;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Component\Fabrik\Administrator\Table\FabTable;
use Joomla\Component\Fabrik\Administrator\Table\VisualizationTable;
use Joomla\Utilities\ArrayHelper;

/**
 * Fabrik Admin Visualization Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.0
 */
class VisualizationModel extends FabAdminModel
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var  string
	 *
	 * @since 4.0
	 */
	protected $text_prefix = 'COM_FABRIK_VISUALIZATION';

	/**
	 * Returns a reference to the a Table object, always creating it.
	 *
	 * @param   string $type   The table type to instantiate
	 * @param   string $prefix A prefix for the table class name. Optional.
	 * @param   array  $config Configuration array for model. Optional.
	 *
	 * @return  VisualizationTable|FabTable    A database object
	 *
	 * @since 4.0
	 */
	public function getTable($type = VisualizationTable::class, $prefix = '', $config = array())
	{
		$config['dbo'] = Worker::getDbo(true);

		return FabTable::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to get the record form.
	 *
	 * @param   array $data     Data for the form.
	 * @param   bool  $loadData True if the form is to load its own data (default case), false if not.
	 *
	 * @return  mixed  A JForm object on success, false on failure
	 *
	 * @since    1.6
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_fabrik.visualization', 'visualization', array('control' => 'jform', 'load_data' => $loadData));

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
	 * @return  mixed    The data for the form.
	 *
	 * @since    1.6
	 */
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = $this->app->getUserState('com_fabrik.edit.visualization.data', array());

		if (empty($data))
		{
			$data = $this->getItem();
		}

		return $data;
	}

	/**
	 * get html form fields for a plugin (filled with
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
		$input = $this->app->input;
		$item  = $this->getItem();

		if (is_null($plugin))
		{
			$plugin = $item->plugin;
		}

		$input->set('view', 'visualization');
		PluginHelper::importPlugin('fabrik_visualization', $plugin);

		if ($plugin == '')
		{
			$str = Text::_('COM_FABRIK_SELECT_A_PLUGIN');
		}
		else
		{
			$plugin = $this->pluginManager->getPlugIn($plugin, 'Visualization');
			$str    = $plugin->onRenderAdminSettings(ArrayHelper::fromObject($item), null, 'nav-tabs');
		}

		return $str;
	}

	/**
	 * Method to validate the form data.
	 *
	 * @param   Form   $form  The form to validate against.
	 * @param   array  $data  The data to validate.
	 * @param   string $group The name of the field group to validate.
	 *
	 * @return  mixed  Array of filtered data if valid, false otherwise.
	 *
	 * @see     FormRule
	 * @see     InputFilter
	 *
	 * @since   4.0
	 */
	public function validate($form, $data, $group = null)
	{
		parent::validate($form, $data);

		return $data;
	}

	/**
	 * Method to save the form data.
	 *
	 * @param   array $data The form data.
	 *
	 * @return  boolean  True on success, False on error.
	 *
	 * @since 4.0
	 */
	public function save($data)
	{
		parent::cleanCache('com_fabrik');

		return parent::save($data);
	}
}
