<?php
/**
 * View to edit a visualization.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Joomla\Component\Fabrik\Administrator\View\Visualization;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Html;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\FormView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Component\Fabrik\Administrator\Helper\FabrikAdminHelper;


/**
 * View to edit a visualization.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       4.0
 */
class HtmlView extends FormView
{
	/**
	 * Plugin HTML
	 *
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $pluginFields;

	/**
	 * Display the view
	 *
	 * @param   string $tpl Template
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	public function display($tpl = null)
	{
		// Initialise variables.
		$this->pluginFields = $this->get('PluginHTML');
		$this->item         = $this->get('Item');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new \RuntimeException(implode("\n", $errors), 500);
		}

		FabrikAdminHelper::setViewLayout($this);

		$source                       = Html::framework();
		$source['Fabrik']             = Html::mediaFile('fabrik.js');
		$source['Namespace']          = 'administrator/components/com_fabrik/tmpl/namespace.js';
		$source['PluginManager']      = 'administrator/components/com_fabrik/tmpl/pluginmanager.js';
		$source['AdminVisualization'] = 'administrator/components/com_fabrik/tmpl/visualization/adminvisualization.js';

		$shim                                           = array();
		$dep                                            = new \stdClass;
		$dep->deps                                      = array('admin/pluginmanager');
		$shim['admin/visualization/adminvisualization'] = $dep;

		Html::iniRequireJS($shim);

		$opts         = new \stdClass;
		$opts->plugin = $this->item->plugin;

		$js = "
	var options = " . json_encode($opts) . ";
		Fabrik.controller = new AdminVisualization(options);
";

		Html::script($source, $js, '-min.js');

		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @since    1.6
	 *
	 * @return  null
	 */
	protected function addToolbar()
	{
		$app = Factory::getApplication();
		$app->input->set('hidemainmenu', true);
		$user         = Factory::getUser();
		$isNew        = ($this->item->get('id') == 0);
		$userId       = $user->get('id');
		$checkedOutBy = $this->item->get('checked_out');
		$checkedOut   = !($checkedOutBy == 0 || $checkedOutBy == $user->get('id'));
		$canDo        = FabrikAdminHelper::getActions($this->state->get('filter.category_id'));
		$title        = $isNew ? Text::_('COM_FABRIK_MANAGER_VISUALIZATION_NEW') : Text::_('COM_FABRIK_MANAGER_VISUALIZATION_EDIT');
		$title .= $isNew ? '' : ' "' . $this->item->get('label') . '"';
		ToolbarHelper::title($title, 'chart');

		if ($isNew)
		{
			// For new records, check the create permission.
			if ($canDo->get('core.create'))
			{
				ToolbarHelper::apply('visualization.apply', 'JTOOLBAR_APPLY');
				ToolbarHelper::save('visualization.save', 'JTOOLBAR_SAVE');
				ToolbarHelper::addNew('visualization.save2new', 'JTOOLBAR_SAVE_AND_NEW');
			}

			ToolbarHelper::cancel('visualization.cancel', 'JTOOLBAR_CANCEL');
		}
		else
		{
			// Can't save the record if it's checked out.
			if (!$checkedOut)
			{
				// Since it's an existing record, check the edit permission, or fall back to edit own if the owner.
				if ($canDo->get('core.edit') || ($canDo->get('core.edit.own') && $this->item->get('created_by') == $userId))
				{
					ToolbarHelper::apply('visualization.apply', 'JTOOLBAR_APPLY');
					ToolbarHelper::save('visualization.save', 'JTOOLBAR_SAVE');

					// We can save this record, but check the create permission to see if we can return to make a new one.
					if ($canDo->get('core.create'))
					{
						ToolbarHelper::addNew('visualization.save2new', 'JTOOLBAR_SAVE_AND_NEW');
					}
				}
			}

			if ($canDo->get('core.create'))
			{
				ToolbarHelper::custom('visualization.save2copy', 'save-copy.png', 'save-copy_f2.png', 'JTOOLBAR_SAVE_AS_COPY', false);
			}

			ToolbarHelper::cancel('visualization.cancel', 'JTOOLBAR_CLOSE');
		}

		ToolbarHelper::divider();
		ToolbarHelper::help('JHELP_COMPONENTS_FABRIK_VISUALIZATIONS_EDIT', false, Text::_('JHELP_COMPONENTS_FABRIK_VISUALIZATIONS_EDIT'));
	}
}
