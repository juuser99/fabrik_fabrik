<?php
/**
 * View to edit a list.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Admin\Views\Lizt;

// No direct access
defined('_JEXEC') or die('Restricted access');

use \JFactory as JFactory;
use Fabrik\Admin\Helpers\Fabrik;
use \FText as FText;
use \JToolBarHelper as JToolBarHelper;
use \FabrikHelperHTML as FabrikHelperHTML;
use \stdClass as stdClass;
use Fabrik\Helpers\Worker;
use \JHTML as JHTML;

/**
 * View to edit a list.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.5
 */
class Html extends \Fabrik\Admin\Views\Html
{
	/**
	 * List form
	 *
	 * @var JForm
	 */
	protected $form;

	/**
	 * List item
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
	 * JS code
	 *
	 * @var string
	 */
	protected $js;

	/**
	 * Render the list
	 *
	 * @return  string
	 */

	public function render()
	{
		// Initialise variables.
		$model      = $this->model;
		$this->form = $model->getForm();
		$this->item = $model->getItem();
		$this->state = $model->getState();
		$this->js    = $model->getJs();
		$this->addToolbar();

		if ($this->item->get('view') == '')
		{
			$this->order_by = array(FText::_('COM_FABRIK_AVAILABLE_AFTER_SAVE'));
			$this->group_by = FText::_('COM_FABRIK_AVAILABLE_AFTER_SAVE');
		}
		else
		{
			$this->order_by = array();
			$orderbys       = $model->getOrderBys();

			foreach ($orderbys as $orderby)
			{
				$this->order_by[] = $model->getElementList('order_by[]', $orderby, true, false, false, 'id');
			}

			if (empty($this->order_by))
			{
				$this->order_by[] = $model->getElementList('order_by[]', '', true, false, false, 'id');
			}

			$orderDir[] = JHTML::_('select.option', 'ASC', FText::_('COM_FABRIK_ASCENDING'));
			$orderDir[] = JHTML::_('select.option', 'DESC', FText::_('COM_FABRIK_DESCENDING'));

			$orderdirs       = Worker::JSONtoData($this->item->get('list.order_dir'), true);
			$this->order_dir = array();
			$attribs         = 'class="inputbox" size="1" ';

			foreach ($orderdirs as $orderdir)
			{
				$this->order_dir[] = JHTML::_('select.genericlist', $orderDir, 'order_dir[]', $attribs, 'value', 'text', $orderdir);
			}

			if (empty($this->order_dir))
			{
				$this->order_dir[] = JHTML::_('select.genericlist', $orderDir, 'order_dir[]', $attribs, 'value', 'text', '');
			}

			$this->group_by = $model->getElementList('group_by', $this->item->get('list.group_by'), true, false, false);
		}

		$srcs   = FabrikHelperHTML::framework();
		$srcs[] = 'media/com_fabrik/js/fabrik.js';
		$srcs[] = 'administrator/components/com_fabrik/views/namespace.js';
		$srcs[] = 'administrator/components/com_fabrik/views/pluginmanager.js';
		$srcs[] = 'administrator/components/com_fabrik/views/lizt/tmpl/adminlist.js';

		$shim                              = array();
		$dep                               = new stdClass;
		$dep->deps                         = array('admin/pluginmanager');
		$shim['admin/list/tmpl/adminlist'] = $dep;
		$shim['adminfields/tables']        = $dep;
		FabrikHelperHTML::iniRequireJS($shim);
		FabrikHelperHTML::script($srcs, $this->js);

		return parent::render();
	}

	/**
	 * Show the list's linked forms etc
	 *
	 * @param   string $tpl template
	 *
	 * @return  void
	 */

	public function showLinkedElements($tpl = null)
	{
		$model = $this->getModel('Form');
		$this->addLinkedElementsToolbar();
		$this->formGroupEls = $model->getFormGroups(false);
		$this->formTable    = $model->getForm();
		FabrikHelperHTML::iniRequireJS();
		parent::display($tpl);
	}

	/**
	 * See if the user wants to rename the list/form/groups
	 *
	 * @param   string $tpl template
	 *
	 * @return  void
	 */

	public function confirmCopy($tpl = null)
	{
		$app   = JFactory::getApplication();
		$input = $app->input;
		$cid   = $input->get('cid', array(0), 'array');
		$lists = array();
		$model = $this->getModel();

		foreach ($cid as $id)
		{
			$model->setId($id);
			$table          = $model->getTable();
			$formModel      = $model->getFormModel();
			$row            = new stdClass;
			$row->id        = $id;
			$row->formid    = $table->form_id;
			$row->label     = $table->label;
			$row->formlabel = $formModel->getForm()->label;
			$groups         = $formModel->getGroupsHierarchy();
			$row->groups    = array();

			foreach ($groups as $group)
			{
				$grouprow       = new stdClass;
				$g              = $group->getGroup();
				$grouprow->id   = $g->id;
				$grouprow->name = $g->name;
				$row->groups[]  = $grouprow;
			}

			$lists[] = $row;
		}

		$this->lists = $lists;
		$this->addConfirmCopyToolbar();
		FabrikHelperHTML::iniRequireJS();
		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 */

	protected function addToolbar()
	{
		$app   = JFactory::getApplication();
		$input = $app->input;
		$input->set('hidemainmenu', true);
		$user       = JFactory::getUser();
		$userId     = $user->get('id');
		$isNew      = ($this->item->get('view', '') == '');
		$checkedOut = !($this->item->get('checked_out') == 0 || $this->item->get('checked_out') == $user->get('id'));
		$canDo      = Fabrik::getActions($this->state->get('filter.category_id'));
		$title      = $isNew ? FText::_('COM_FABRIK_MANAGER_LIST_NEW') : FText::_('COM_FABRIK_MANAGER_LIST_EDIT') . ' "' . $this->item->get('list.label') . '"';
		JToolBarHelper::title($title, 'list.png');

		if ($isNew)
		{
			// For new records, check the create permission.
			if ($canDo->get('core.create'))
			{
				JToolBarHelper::apply('lizt.apply', 'JTOOLBAR_APPLY');
				JToolBarHelper::save('lizt.save', 'JTOOLBAR_SAVE');
				JToolBarHelper::addNew('lizt.save2new', 'JTOOLBAR_SAVE_AND_NEW');
			}

			JToolBarHelper::cancel('lizt.cancel', 'JTOOLBAR_CANCEL');
		}
		else
		{
			// Can't save the record if it's checked out.
			if (!$checkedOut)
			{
				// Since it's an existing record, check the edit permission, or fall back to edit own if the owner.
				if ($canDo->get('core.edit') || ($canDo->get('core.edit.own') && $this->item->created_by == $userId))
				{
					JToolBarHelper::apply('lizt.apply', 'JTOOLBAR_APPLY');
					JToolBarHelper::save('lizt.save', 'JTOOLBAR_SAVE');

					// We can save this record, but check the create permission to see if we can return to make a new one.
					if ($canDo->get('core.create'))
					{
						JToolBarHelper::addNew('lizt.save2new', 'JTOOLBAR_SAVE_AND_NEW');
					}
				}
			}
			// If checked out, we can still save
			if ($canDo->get('core.create'))
			{
				JToolBarHelper::custom('lizt.save2copy', 'save-copy.png', 'save-copy_f2.png', 'JTOOLBAR_SAVE_AS_COPY', false);
			}

			JToolBarHelper::cancel('lizt.cancel', 'JTOOLBAR_CLOSE');
		}

		JToolBarHelper::divider();
		JToolBarHelper::help('JHELP_COMPONENTS_FABRIK_LISTS_EDIT', false, FText::_('JHELP_COMPONENTS_FABRIK_LISTS_EDIT'));
	}

	/**
	 * Add the page title and toolbar for the linked elements page
	 *
	 * @return  void
	 */
	protected function addLinkedElementsToolbar()
	{
		$app   = JFactory::getApplication();
		$input = $app->input;
		$input->set('hidemainmenu', true);
		JToolBarHelper::title(FText::_('COM_FABRIK_MANAGER_LIST_LINKED_ELEMENTS'), 'list.png');
		JToolBarHelper::cancel('list.cancel', 'JTOOLBAR_CLOSE');
		JToolBarHelper::divider();
		JToolBarHelper::help('JHELP_COMPONENTS_FABRIK_LISTS_EDIT');
	}

}
