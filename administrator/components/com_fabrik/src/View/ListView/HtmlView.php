<?php
/**
 * View to edit a list.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2018  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Joomla\Component\Fabrik\Administrator\View\ListView;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Html;
use Fabrik\Helpers\Worker;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Component\Fabrik\Administrator\Helper\FabrikAdminHelper;
use Joomla\Component\Fabrik\Administrator\Model\ListModel;

/**
 * View to edit a list.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       4.0
 */
class HtmlView extends BaseHtmlView
{
	/**
	 * List form
	 *
	 * @var Form
	 *
	 * @since 4.0
	 */
	protected $form;

	/**
	 * List item
	 *
	 * @var Table
	 *
	 * @since 4.0
	 */
	protected $item;

	/**
	 * View state
	 *
	 * @var object
	 *
	 * @since 4.0
	 */
	protected $state;

	/**
	 * JS code
	 *
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $js;

	/**
	 * @var array
	 *
	 * @since 4.0
	 */
	public $order_by = [];

	/**
	 * @var string
	 *            
	 * @since 4.0
	 */
	public $group_by;

	/**
	 * @var string
	 *
	 * @since 4.0
	 */
	public $order_dir;

	/**
	 * @var ListModel
	 *
	 * @since 4.0
	 */
	protected $_defaultModel;

	/**
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $_name = 'list';

	/**
	 * Display the list
	 *
	 * @param   string $tpl template
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	public function display($tpl = null)
	{
		// Initialise variables.
		$model      = $this->getModel();
		$this->form = $this->get('Form');
		$this->item = $this->get('Item');
		$formModel  = $this->get('FormModel');
		$formModel->setId($this->item->form_id);
		$this->state = $this->get('State');
		$this->js    = $model->getJs();
		$this->addToolbar();

		if ($this->item->id == 0)
		{
			$this->order_by = array(Text::_('COM_FABRIK_AVAILABLE_AFTER_SAVE'));
			$this->group_by = Text::_('COM_FABRIK_AVAILABLE_AFTER_SAVE');
		}
		else
		{
			$this->order_by = array();
			$feListModel    = $formModel->getListModel();
			$orderBys       = $feListModel->getOrderBys();

			foreach ($orderBys as $orderBy)
			{
				$this->order_by[] = $formModel->getElementList('order_by[]', $orderBy, true, false, false, 'id');
			}

			if (empty($this->order_by))
			{
				$this->order_by[] = $formModel->getElementList('order_by[]', '', true, false, false, 'id');
			}

			$orderDir[] = HTMLHelper::_('select.option', 'ASC', Text::_('COM_FABRIK_ASCENDING'));
			$orderDir[] = HTMLHelper::_('select.option', 'DESC', Text::_('COM_FABRIK_DESCENDING'));

			$orderdirs       = Worker::JSONtoData($this->item->order_dir, true);
			$this->order_dir = array();
			$attribs         = 'class="inputbox" size="1" ';

			foreach ($orderdirs as $orderdir)
			{
				$this->order_dir[] = HTMLHelper::_('select.genericlist', $orderDir, 'order_dir[]', $attribs, 'value', 'text', $orderdir);
			}

			if (empty($this->order_dir))
			{
				$this->order_dir[] = HTMLHelper::_('select.genericlist', $orderDir, 'order_dir[]', $attribs, 'value', 'text', '');
			}

			$this->group_by = $formModel->getElementList('group_by', $this->item->group_by, true, false, false);
		}

		FabrikAdminHelper::setViewLayout($this);

		$srcs                  = Html::framework();
		$srcs['Fabrik']        = Html::mediaFile('fabrik.js');
		$srcs['NameSpace']     = 'administrator/components/com_fabrik/views/namespace.js';
		$srcs['PluginManager'] = 'administrator/components/com_fabrik/views/pluginmanager.js';
		$srcs['ListForm']      = 'administrator/components/com_fabrik/views/listform.js';
		$srcs['AdminList']     = 'administrator/components/com_fabrik/tmpl/list/js/adminlist.js';
		$srcs['adminFilters']  = 'administrator/components/com_fabrik/tmpl/list/js/admin-filters.js';

		$shim                              = array();
		$dep                               = new \stdClass;
		$dep->deps                         = array('admin/pluginmanager');
		$shim['admin/list/tmpl/adminlist'] = $dep;
		$shim['adminfields/tables']        = $dep;
		Html::formvalidation();
		Html::framework();
		Html::iniRequireJS($shim);
		Html::script($srcs, $this->js);

		parent::display($tpl);
	}

	/**
	 * Show the list's linked forms etc
	 *
	 * @param   string $tpl template
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	public function showLinkedElements($tpl = null)
	{
		$model = $this->getModel('form');
		$this->addLinkedElementsToolbar();
		$this->formGroupEls = $model->getFormGroups(false);
		$this->formTable    = $model->getForm();
		Html::formvalidation();
		Html::framework();
		Html::iniRequireJS();
		parent::display($tpl);
	}

	/**
	 * See if the user wants to rename the list/form/groups
	 *
	 * @param   string $tpl template
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	public function confirmCopy($tpl = null)
	{
		$app   = Factory::getApplication();
		$input = $app->input;
		$cid   = $input->get('cid', array(0), 'array');
		$lists = array();
		$model = $this->getModel();

		foreach ($cid as $id)
		{
			$model->setId($id);
			$table          = $model->getTable();
			$formModel      = $model->getFormModel();
			$row            = new \stdClass;
			$row->id        = $id;
			$row->formid    = $table->form_id;
			$row->label     = $table->label;
			$row->formlabel = $formModel->getForm()->label;
			$groups         = $formModel->getGroupsHiarachy();
			$row->groups    = array();

			foreach ($groups as $group)
			{
				$grouprow       = new \stdClass;
				$g              = $group->getGroup();
				$grouprow->id   = $g->id;
				$grouprow->name = $g->name;
				$row->groups[]  = $grouprow;
			}

			$lists[] = $row;
		}

		$this->lists = $lists;
		$this->addConfirmCopyToolbar();
		Html::formvalidation();
		Html::framework();
		Html::iniRequireJS();
		parent::display($tpl);
	}

	/**
	 * Once a list is saved - we need to display the select content type form.
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
		Html::formvalidation();
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
			ToolbarHelper::apply('list.doSave', 'JTOOLBAR_SAVE');
			ToolbarHelper::cancel('list.cancel', 'JTOOLBAR_CANCEL');
		}
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
		$title      = $isNew ? Text::_('COM_FABRIK_MANAGER_LIST_NEW') : Text::_('COM_FABRIK_MANAGER_LIST_EDIT') . ' "' . $this->item->label . '"';
		ToolbarHelper::title($title, 'list');

		if ($isNew)
		{
			// For new records, check the create permission.
			if ($canDo->get('core.create'))
			{
				ToolbarHelper::apply('list.apply', 'JTOOLBAR_APPLY');
				ToolbarHelper::save('list.save', 'JTOOLBAR_SAVE');
				ToolbarHelper::addNew('list.save2new', 'JTOOLBAR_SAVE_AND_NEW');
			}

			ToolbarHelper::cancel('list.cancel', 'JTOOLBAR_CANCEL');
		}
		else
		{
			// Can't save the record if it's checked out.
			if (!$checkedOut)
			{
				// Since it's an existing record, check the edit permission, or fall back to edit own if the owner.
				if ($canDo->get('core.edit') || ($canDo->get('core.edit.own') && $this->item->created_by == $userId))
				{
					ToolbarHelper::apply('list.apply', 'JTOOLBAR_APPLY');
					ToolbarHelper::save('list.save', 'JTOOLBAR_SAVE');

					// We can save this record, but check the create permission to see if we can return to make a new one.
					if ($canDo->get('core.create'))
					{
						ToolbarHelper::addNew('list.save2new', 'JTOOLBAR_SAVE_AND_NEW');
					}
				}
			}
			// If checked out, we can still save
			if ($canDo->get('core.create'))
			{
				ToolbarHelper::custom('list.save2copy', 'save-copy.png', 'save-copy_f2.png', 'JTOOLBAR_SAVE_AS_COPY', false);
			}

			ToolbarHelper::cancel('list.cancel', 'JTOOLBAR_CLOSE');
		}

		ToolbarHelper::divider();
		ToolbarHelper::help('JHELP_COMPONENTS_FABRIK_LISTS_EDIT', false, Text::_('JHELP_COMPONENTS_FABRIK_LISTS_EDIT'));
	}

	/**
	 * Add the page title and toolbar for the linked elements page
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	protected function addLinkedElementsToolbar()
	{
		$app   = Factory::getApplication();
		$input = $app->input;
		$input->set('hidemainmenu', true);
		ToolbarHelper::title(Text::_('COM_FABRIK_MANAGER_LIST_LINKED_ELEMENTS'), 'list');
		ToolbarHelper::cancel('list.cancel', 'JTOOLBAR_CLOSE');
		ToolbarHelper::divider();
		ToolbarHelper::help('JHELP_COMPONENTS_FABRIK_LISTS_EDIT');
	}

	/**
	 * Add the page title and toolbar for the confirm copy page
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	protected function addConfirmCopyToolbar()
	{
		$app   = Factory::getApplication();
		$input = $app->input;
		$input->set('hidemainmenu', true);
		ToolbarHelper::title(Text::_('COM_FABRIK_MANAGER_LIST_COPY'), 'list');
		ToolbarHelper::cancel('list.cancel', 'JTOOLBAR_CLOSE');
		ToolbarHelper::save('list.doCopy', 'JTOOLBAR_SAVE');
		ToolbarHelper::divider();
		ToolbarHelper::help('JHELP_COMPONENTS_FABRIK_LISTS_EDIT');
	}
}
