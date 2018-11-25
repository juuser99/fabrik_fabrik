<?php
/**
 * View to edit a form.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Joomla\Component\Fabrik\Administrator\View\Form;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Html;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\FormView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Component\Fabrik\Administrator\Helper\FabrikAdminHelper;

/**
 * View to edit a form.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       4.0
 */
class HtmlView extends BaseHtmlView
{
	/**
	 * Js code for controlling plugins
	 *
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $js;

	/**
	 * Display the view
	 *
	 * @param   string $tpl template
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	public function display($tpl = null)
	{
		$this->js = $this->get('Js');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new \RuntimeException(implode("\n", $errors), 500);
		}

		// Set up the script shim
		$shim                        = array();
		$dep                         = new \stdClass;
		$dep->deps                   = array('fab/fabrik');
		$shim['admin/pluginmanager'] = $dep;
		Html::iniRequireJS($shim);

		HTMLHelper::_('jquery.framework');
		HTMLHelper::_('script', 'jui/cms.js', array('version' => 'auto', 'relative' => true));

		$srcs                  = Html::framework();
		$srcs['Fabrik']        = Html::mediaFile('fabrik.js');
		$srcs['Namespace']     = 'administrator/components/com_fabrik/tmpl/namespace.js';
		$srcs['PluginManager'] = 'administrator/components/com_fabrik/tmpl/pluginmanager.js';

		Html::script($srcs, $this->js);

		parent::display($tpl);
	}

	/**
	 * Alias to display
	 *
	 * @param   string $tpl Template
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	public function form($tpl = null)
	{
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
		/** @var CMSApplication $app */
		$app   = Factory::getApplication();
		$input = $app->input;
		$input->set('hidemainmenu', true);
		$user       = Factory::getUser();
		$userId     = $user->get('id');
		$isNew      = ($this->item->id == 0);
		$checkedOut = !($this->item->checked_out == 0 || $this->item->checked_out == $user->get('id'));
		$canDo      = FabrikAdminHelper::getActions($this->state->get('filter.category_id'));
		$title      = $isNew ? Text::_('COM_FABRIK_MANAGER_FORM_NEW') : Text::_('COM_FABRIK_MANAGER_FORM_EDIT') . ' "'
			. Text::_($this->item->label) . '"';
		ToolbarHelper::title($title, 'file-2');

		if ($isNew)
		{
			// For new records, check the create permission.
			if ($canDo->get('core.create'))
			{
				ToolbarHelper::apply('form.apply', 'JTOOLBAR_APPLY');
				ToolbarHelper::save('form.save', 'JTOOLBAR_SAVE');
				ToolbarHelper::addNew('form.save2new', 'JTOOLBAR_SAVE_AND_NEW');
			}

			ToolbarHelper::cancel('form.cancel', 'JTOOLBAR_CANCEL');
		}
		else
		{
			// Can't save the record if it's checked out.
			if (!$checkedOut)
			{
				// Since it's an existing record, check the edit permission, or fall back to edit own if the owner.
				if ($canDo->get('core.edit') || ($canDo->get('core.edit.own') && $this->item->created_by == $userId))
				{
					ToolbarHelper::apply('form.apply', 'JTOOLBAR_APPLY');
					ToolbarHelper::save('form.save', 'JTOOLBAR_SAVE');

					// We can save this record, but check the create permission to see if we can return to make a new one.
					if ($canDo->get('core.create'))
					{
						ToolbarHelper::addNew('form.save2new', 'JTOOLBAR_SAVE_AND_NEW');
					}
				}
			}
			// $$$ No 'save as copy' as this gets complicated due to renaming lists, groups etc. Users should copy from list view.
			ToolbarHelper::cancel('form.cancel', 'JTOOLBAR_CLOSE');
		}

		ToolbarHelper::divider();
		ToolbarHelper::help('JHELP_COMPONENTS_FABRIK_FORMS_EDIT', false, Text::_('JHELP_COMPONENTS_FABRIK_FORMS_EDIT'));
	}

	/**
	 * Once a form is saved - we need to display the select content type form.
	 *
	 * @param null $tpl
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	public function selectContentType($tpl = null)
	{
		$model      = $this->getModel();
		$this->form = $model->getContentTypeForm();
		$input      = Factory::getApplication()->input;
		$this->data = $input->post->get('jform', array(), 'array');
		$this->addSelectSaveToolBar();
		Html::framework();
		Html::iniRequireJS();

		parent::display($tpl);
	}

	/**
	 * Add select content type tool bar
	 *
	 * @throws \Exception
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	protected function addSelectSaveToolBar()
	{
		$app         = Factory::getApplication();
		$this->state = $this->get('State');
		$input       = $app->input;
		$input->set('hidemainmenu', true);
		$canDo = FabrikAdminHelper::getActions($this->state->get('filter.category_id'));
		ToolbarHelper::title(Text::_('COM_FABRIK_MANAGER_SELECT_CONTENT_TYPE'), 'puzzle');

		// For new records, check the create permission.
		if ($canDo->get('core.create'))
		{
			ToolbarHelper::apply('form.doSave', 'JTOOLBAR_SAVE');
			ToolbarHelper::cancel('form.cancel', 'JTOOLBAR_CANCEL');
		}
	}
}
