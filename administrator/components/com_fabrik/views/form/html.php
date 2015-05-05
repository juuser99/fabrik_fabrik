<?php
/**
 * View to edit a form.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Admin\Views\Form;

// No direct access
defined('_JEXEC') or die('Restricted access');

use \FabrikHelperHTML as FabrikHelperHTML;
use \stdClass as stdClass;
use \JFactory as JFactory;
use Fabrik\Admin\Helpers\Fabrik;
use \FText as FText;
use \JToolBarHelper as JToolBarHelper;

/**
 * View to edit a form.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.5
 */
class Html extends \Fabrik\Admin\Views\Html
{
	/**
	 * Form
	 *
	 * @var JForm
	 */
	protected $form;

	/**
	 * Form item
	 *
	 * @var JTable
	 */
	protected $item;

	/**
	 * View state
	 *
	 * @var object
	 */
	protected $state;

	/**
	 * Js code for controlling plugins
	 * @var string
	 */
	protected $js;

	/**
	 * Display the view
	 *
	 * @return  string
	 */
	public function render()
	{
		// Initialise variables.
		$this->form = $this->model->getForm();
		$this->item = $this->model->getItem()->toObject();
		$this->state = $this->model->getState();
		$this->js = $this->model->getJs();

		$this->addToolbar();

		// Set up the script shim
		$shim = array();
		$dep = new stdClass;
		$dep->deps = array('fab/fabrik');
		$shim['admin/pluginmanager'] = $dep;
		FabrikHelperHTML::iniRequireJS($shim);

		$srcs = FabrikHelperHTML::framework();
		$srcs[] = 'administrator/components/com_fabrik/views/namespace.js';
		$srcs[] = 'administrator/components/com_fabrik/views/pluginmanager.js';

		FabrikHelperHTML::script($srcs, $this->js);

		return parent::render();
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 */

	protected function addToolbar()
	{
		$input = JFactory::getApplication()->input;
		$input->set('hidemainmenu', true);
		$user = JFactory::getUser();
		$userId = $user->get('id');
		$isNew = ($this->item->id == 0);
		$checkedOut = !($this->item->checked_out == 0 || $this->item->checked_out == $user->get('id'));
		$canDo = Fabrik::getActions($this->state->get('filter.category_id'));
		$title = $isNew ? FText::_('COM_FABRIK_MANAGER_FORM_NEW') : FText::_('COM_FABRIK_MANAGER_FORM_EDIT') . ' "' . $this->item->label . '"';
		JToolBarHelper::title($title, 'form.png');

		if ($isNew)
		{
			// For new records, check the create permission.
			if ($canDo->get('core.create'))
			{
				JToolBarHelper::apply('form.apply', 'JTOOLBAR_APPLY');
				JToolBarHelper::save('form.save', 'JTOOLBAR_SAVE');
				JToolBarHelper::addNew('form.save2new', 'JTOOLBAR_SAVE_AND_NEW');
			}

			JToolBarHelper::cancel('form.cancel', 'JTOOLBAR_CANCEL');
		}
		else
		{
			// Can't save the record if it's checked out.
			if (!$checkedOut)
			{
				// Since it's an existing record, check the edit permission, or fall back to edit own if the owner.
				if ($canDo->get('core.edit') || ($canDo->get('core.edit.own') && $this->item->created_by == $userId))
				{
					JToolBarHelper::apply('form.apply', 'JTOOLBAR_APPLY');
					JToolBarHelper::save('form.save', 'JTOOLBAR_SAVE');

					// We can save this record, but check the create permission to see if we can return to make a new one.
					if ($canDo->get('core.create'))
					{
						JToolBarHelper::addNew('form.save2new', 'JTOOLBAR_SAVE_AND_NEW');
					}
				}
			}
			// $$$ No 'save as copy' as this gets complicated due to renaming lists, groups etc. Users should copy from list view.
			JToolBarHelper::cancel('form.cancel', 'JTOOLBAR_CLOSE');
		}

		JToolBarHelper::divider();
		JToolBarHelper::help('JHELP_COMPONENTS_FABRIK_FORMS_EDIT', false, FText::_('JHELP_COMPONENTS_FABRIK_FORMS_EDIT'));
	}
}
