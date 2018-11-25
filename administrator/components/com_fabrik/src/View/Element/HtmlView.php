<?php
/**
 * View to edit an element.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Joomla\Component\Fabrik\Administrator\View\Element;

use Fabrik\Helpers\Html;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\FormView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Component\Fabrik\Administrator\Helper\FabrikAdminHelper;
use Joomla\Component\Fabrik\Administrator\Model\ElementModel;

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * View to edit an element.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       4.0
 * @method  ElementModel getModel
 */
class HtmlView extends FormView
{
	/**
	 * @var mixed
	 *
	 * @since 4.0
	 */
	public $parent;

	/**
	 * Plugin HTML
	 *
	 * @var string
	 *
	 * @sinc 4.0
	 */
	protected $pluginFields;

	/**
	 * JavaScript Events
	 *
	 * @var array
	 *
	 * @since 4.0
	 */
	protected $jsevents;

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
		if ($this->getLayout() == 'confirmupdate')
		{
			$this->confirmupdate();

			return;
		}

		// Initialiase variables.
		$model = $this->getModel();

		$this->pluginFields = $model->getPluginHTML();
		$this->js           = $model->getJs();

		// Check for errors.
		if (count($errors = $model->getErrors()))
		{
			throw new \RuntimeException(implode("\n", $errors), 500);
		}

		$this->parent = $model->getParent();
		FabrikAdminHelper::setViewLayout($this);
		Text::script('COM_FABRIK_ERR_ELEMENT_JS_ACTION_NOT_DEFINED');

		$srcs                       = Html::framework();
		$srcs['Fabrik']             = Html::mediaFile('fabrik.js');
		$srcs['NameSpace']          = 'administrator/components/com_fabrik/tmpl/namespace.js';
		$srcs['fabrikAdminElement'] = 'administrator/components/com_fabrik/tmpl/element/tmpl/adminelement.js';

		$shim                                    = array();
		$dep                                     = new \stdClass;
		$dep->deps                               = array('admin/pluginmanager');
		$shim['admin/element/tmpl/adminelement'] = $dep;
		$shim['adminfields/tables']              = $dep;

		$plugManagerDeps             = new \stdClass;
		$plugManagerDeps->deps       = array('admin/namespace');
		$shim['admin/pluginmanager'] = $plugManagerDeps;
		Html::iniRequireJS($shim);
		Html::script($srcs, $this->js);

		parent::display($tpl);
	}

	/**
	 * Ask the user if they really want to alter the element fields structure/name
	 *
	 * @param   string $tpl Template
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	protected function confirmupdate($tpl = null)
	{
		$model       = $this->getModel();
		$this->state = $model->getState();
		$app         = Factory::getApplication();
		$this->addConfirmToolbar();
		$this->item       = $model->getItem();
		$this->oldName    = $app->getUserState('com_fabrik.oldname');
		$this->origDesc   = $app->getUserState('com_fabrik.origDesc');
		$this->newDesc    = $app->getUserState('com_fabrik.newdesc');
		$this->origPlugin = $app->getUserState('com_fabrik.origplugin');
		$this->origtask   = $app->getUserState('com_fabrik.origtask');
		$app->setUserState('com_fabrik.confirmUpdate', 0);
		parent::display($tpl);
	}

	/**
	 * Add the confirmation tool bar
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	protected function addConfirmToolbar()
	{
		ToolbarHelper::title(Text::_('COM_FABRIK_MANAGER_ELEMENT_EDIT'), 'checkbox-unchecked');
		$app   = Factory::getApplication();
		$input = $app->input;
		$input->set('hidemainmenu', true);
		ToolbarHelper::save('element.updatestructure', 'JTOOLBAR_SAVE');
		ToolbarHelper::cancel('element.cancelUpdatestructure', 'JTOOLBAR_CANCEL');
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @since    1.6
	 *
	 * @return  void
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
		$title      = $isNew ? Text::_('COM_FABRIK_MANAGER_ELEMENT_NEW') : Text::_('COM_FABRIK_MANAGER_ELEMENT_EDIT') . ' "' . $this->item->name . '"';
		ToolbarHelper::title($title, 'checkbox-unchecked');

		if ($isNew)
		{
			// For new records, check the create permission.
			if ($canDo->get('core.create'))
			{
				ToolbarHelper::apply('element.apply', 'JTOOLBAR_APPLY');
				ToolbarHelper::save('element.save', 'JTOOLBAR_SAVE');
				ToolbarHelper::addNew('element.save2new', 'JTOOLBAR_SAVE_AND_NEW');
			}

			ToolbarHelper::cancel('element.cancel', 'JTOOLBAR_CANCEL');
		}
		else
		{
			// Can't save the record if it's checked out.
			if (!$checkedOut)
			{
				// Since it's an existing record, check the edit permission, or fall back to edit own if the owner.
				if ($canDo->get('core.edit') || ($canDo->get('core.edit.own') && $this->item->created_by == $userId))
				{
					ToolbarHelper::apply('element.apply', 'JTOOLBAR_APPLY');
					ToolbarHelper::save('element.save', 'JTOOLBAR_SAVE');

					// We can save this record, but check the create permission to see if we can return to make a new one.
					if ($canDo->get('core.create'))
					{
						ToolbarHelper::addNew('element.save2new', 'JTOOLBAR_SAVE_AND_NEW');
					}
				}
			}

			if ($canDo->get('core.create'))
			{
				ToolbarHelper::custom('element.save2copy', 'save-copy.png', 'save-copy_f2.png', 'JTOOLBAR_SAVE_AS_COPY', false);
			}

			ToolbarHelper::cancel('element.cancel', 'JTOOLBAR_CLOSE');
		}

		ToolbarHelper::divider();
		ToolbarHelper::help('JHELP_COMPONENTS_FABRIK_ELEMENTS_EDIT', false, Text::_('JHELP_COMPONENTS_FABRIK_ELEMENTS_EDIT'));
	}
}
