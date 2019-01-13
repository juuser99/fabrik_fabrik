<?php
/**
 * View to edit a cron.
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Joomla\Component\Fabrik\Administrator\View\Cron;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Html;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\FormView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Component\Fabrik\Administrator\Helper\FabrikAdminHelper;

/**
 * View to edit a cron.
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
		// Initialiase variables.
		$this->pluginFields = $this->get('PluginHTML');
		$this->item         = $this->get('Item');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new \RuntimeException(implode("\n", $errors), 500);
		}

		FabrikAdminHelper::setViewLayout($this);

		$srcs                  = Html::framework();
		$srcs['Fabrik']        = Html::mediaFile('fabrik.js');
		$srcs['Namespace']     = 'administrator/components/com_fabrik/tmpl/namespace.js';
		$srcs['PluginManager'] = 'administrator/components/com_fabrik/tmpl/pluginmanager.js';
		$srcs['CronAdmin']     = 'administrator/components/com_fabrik/tmpl/cron/admincron.js';

		$shim                         = array();
		$dep                          = new \stdClass;
		$dep->deps                    = array('admin/pluginmanager');
		$shim['admin/cron/admincron'] = $dep;

		$opts         = new \stdClass;
		$opts->plugin = $this->item->plugin;

		$js   = array();
		$js[] = "\twindow.addEvent('domready', function () {";
		$js[] = "\t\tFabrik.controller = new CronAdmin(" . json_encode($opts) . ");";
		$js[] = "\t})";
		Html::iniRequireJS($shim);
		Html::script($srcs, implode("\n", $js));

		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	protected function addToolbar()
	{
		$app   = Factory::getApplication();
		$input = $app->input;
		$input->set('hidemainmenu', true);
		$user       = Factory::getUser();
		$userId     = $user->get('id');
		$isNew      = ($this->item->id == 0);
		$checkedOut = !($this->item->checked_out == 0 || $this->item->checked_out == $user->get('id'));
		$canDo      = FabrikAdminHelper::getActions($this->state->get('filter.category_id'));
		$title      = $isNew ? Text::_('COM_FABRIK_MANAGER_CRON_NEW') : Text::_('COM_FABRIK_MANAGER_CRON_EDIT') . ' "' . $this->item->label . '"';
		ToolbarHelper::title($title, 'clock');

		if ($isNew)
		{
			// For new records, check the create permission.
			if ($canDo->get('core.create'))
			{
				ToolbarHelper::apply('cron.apply', 'JTOOLBAR_APPLY');
				ToolbarHelper::save('cron.save', 'JTOOLBAR_SAVE');
				ToolbarHelper::addNew('cron.save2new', 'JTOOLBAR_SAVE_AND_NEW');
			}

			ToolbarHelper::cancel('cron.cancel', 'JTOOLBAR_CANCEL');
		}
		else
		{
			// Can't save the record if it's checked out.
			if (!$checkedOut)
			{
				// Since it's an existing record, check the edit permission, or fall back to edit own if the owner.
				if ($canDo->get('core.edit') || ($canDo->get('core.edit.own') && $this->item->created_by == $userId))
				{
					ToolbarHelper::apply('cron.apply', 'JTOOLBAR_APPLY');
					ToolbarHelper::save('cron.save', 'JTOOLBAR_SAVE');

					// We can save this record, but check the create permission to see if we can return to make a new one.
					if ($canDo->get('core.create'))
					{
						ToolbarHelper::addNew('cron.save2new', 'JTOOLBAR_SAVE_AND_NEW');
					}
				}
			}

			if ($canDo->get('core.create'))
			{
				ToolbarHelper::custom('cron.save2copy', 'save-copy.png', 'save-copy_f2.png', 'JTOOLBAR_SAVE_AS_COPY', false);
			}

			ToolbarHelper::cancel('cron.cancel', 'JTOOLBAR_CLOSE');
		}

		ToolbarHelper::divider();
		ToolbarHelper::help('JHELP_COMPONENTS_FABRIK_CRONS_EDIT', false, Text::_('JHELP_COMPONENTS_FABRIK_CRONS_EDIT'));
	}
}
