<?php
/**
 *  View class for a list of lists.
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Admin\Views\Lists;

// No direct access
defined('_JEXEC') or die('Restricted access');

use \JHtml as JHtml;
use \JToolBarHelper as JToolBarHelper;
use \JHtmlSidebar as JHtmlSidebar;
use \FText as FText;
use Fabrik\Admin\Helpers\Fabrik;
use \Fabrik\Helpers\HTML as HelperHTML;
use JFactory as JFactory;

/**
 * View class for a list of lists.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.5
 */
class Html extends \Fabrik\Admin\Views\Html
{
	/**
	 * List items
	 *
	 * @var  array
	 */
	protected $items;

	/**
	 * Pagination
	 *
	 * @var  JPagination
	 */
	protected $pagination;

	/**
	 * View state
	 *
	 * @var object
	 */
	protected $state;

	/**
	 * Sidebar
	 *
	 * @var string
	 */
	public $sidebar;

	/**
	 * Selected lists
	 *
	 * @var array
	 */
	public $lists = array();

	/**
	 * @var \JForm
	 */
	public $form = null;

	/**
	 * Render the view
	 *
	 * @return void
	 */
	public function render()
	{
		// @todo - test this - probaby should be moved into their own views

		switch ($this->getLayout())
		{
			case 'confirm_copy':
				return $this->confirmCopy();

				break;

			case 'confirmdelete':
				return $this->confirmDelete();

				break;
			case 'import':
				return $this->import();

				break;
		}
		// Initialise variables.
		$this->items      = $this->model->getItems();
		$this->pagination = $this->model->getPagination();
		$this->state      = $this->model->getState();

		$this->addToolbar();
		Fabrik::addSubmenu('lists');
		//$this->table_groups = $this->get('TableGroups');

		$this->sidebar = JHtmlSidebar::render();

		HelperHTML::iniRequireJS();
		$this->setLayout('bootstrap');

		return parent::render();
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 */
	protected function addToolbar()
	{
		$canDo = Fabrik::getActions($this->state->get('filter.category_id'));
		JToolBarHelper::title(FText::_('COM_FABRIK_MANAGER_LISTS'), 'lists.png');

		if ($canDo->get('core.create'))
		{
			JToolBarHelper::addNew('lizt.add', 'JTOOLBAR_NEW');
		}

		if ($canDo->get('core.edit'))
		{
			JToolBarHelper::editList('lizt.edit', 'JTOOLBAR_EDIT');
		}

		JToolBarHelper::custom('lists.copy', 'copy.png', 'copy_f2.png', 'COM_FABRIK_COPY');

		if ($canDo->get('core.edit.state'))
		{
			if ($this->state->get('filter.state') != 2)
			{
				JToolBarHelper::divider();
				JToolBarHelper::custom('lists.publish', 'publish.png', 'publish_f2.png', 'JTOOLBAR_PUBLISH', true);
				JToolBarHelper::custom('lists.unpublish', 'unpublish.png', 'unpublish_f2.png', 'JTOOLBAR_UNPUBLISH', true);
			}
		}

		JToolBarHelper::divider();

		if ($canDo->get('core.create'))
		{
			JToolBarHelper::custom('import.display', 'upload.png', 'upload_f2.png', 'COM_FABRIK_IMPORT', false);
		}

		JToolBarHelper::divider();

		if (JFactory::getUser()->authorise('core.manage', 'com_checkin'))
		{
			JToolBarHelper::custom('lists.checkin', 'checkin.png', 'checkin_f2.png', 'JTOOLBAR_CHECKIN', true);
		}

		JToolBarHelper::divider();

		if ($this->state->get('filter.published') == -2 && $canDo->get('core.delete'))
		{
			JToolBarHelper::deleteList('', 'lists.delete', 'JTOOLBAR_EMPTY_TRASH');
		}
		elseif ($canDo->get('core.edit.state'))
		{
			JToolBarHelper::trash('lists.trash', 'JTOOLBAR_TRASH');
		}

		if ($canDo->get('core.admin'))
		{
			JToolBarHelper::divider();
			JToolBarHelper::preferences('com_fabrik');
		}

		JToolBarHelper::divider();
		JToolBarHelper::help('JHELP_COMPONENTS_FABRIK_LISTS', false, FText::_('JHELP_COMPONENTS_FABRIK_LISTS'));

		JHtmlSidebar::setAction('index.php?option=com_fabrik&view=lists');

		$publishOpts = JHtml::_('jgrid.publishedOptions', array('archived' => false));
		JHtmlSidebar::addFilter(
			FText::_('JOPTION_SELECT_PUBLISHED'), 'filter_published',
			JHtml::_('select.options', $publishOpts, 'value', 'text', $this->state->get('filter.published'), true)
		);

		if (!empty($this->packageOptions))
		{
			array_unshift($this->packageOptions, JHtml::_('select.option', 'fabrik', FText::_('COM_FABRIK_SELECT_PACKAGE')));
			JHtmlSidebar::addFilter(
				FText::_('JOPTION_SELECT_PUBLISHED'), 'package',
				JHtml::_('select.options', $this->packageOptions, 'value', 'text', $this->state->get('com_fabrik.package'), true)
			);
		}
	}

	/**
	 * Add the page title and toolbar for confirming list deletion
	 *
	 * @todo - test this
	 *
	 * @return  void
	 */
	protected function addConfirmDeleteToolbar()
	{
		$app = JFactory::getApplication();
		$app->input->set('hidemainmenu', true);
		JToolBarHelper::title(FText::_('COM_FABRIK_MANAGER_LIST_CONFIRM_DELETE'), 'lizt.png');
		JToolBarHelper::save('lists.dodelete', 'JTOOLBAR_APPLY');
		JToolBarHelper::cancel('lizt.cancel', 'JTOOLBAR_CANCEL');
		JToolBarHelper::divider();
		JToolBarHelper::help('JHELP_COMPONENTS_FABRIK_LISTS_EDIT', true, 'http://fabrikar.com/wiki/index.php/List_delete_confirmation');
	}

	/**
	 * Add the page title and toolbar for List import
	 *
	 * @todo - test this
	 *
	 * @return  void
	 */
	protected function addImportToolBar()
	{
		$app = JFactory::getApplication();
		$app->input->set('hidemainmenu', true);
		JToolBarHelper::title(FText::_('COM_FABRIK_MANAGER_LIST_IMPORT'), 'lizt.png');
		JToolBarHelper::save('lists.doimport', 'JTOOLBAR_APPLY');
		JToolBarHelper::cancel('lizt.cancel', 'JTOOLBAR_CANCEL');
	}

	/**
	 * Confirm copy page
	 *
	 * @return string
	 */
	protected function confirmCopy()
	{
		$this->addConfirmCopyToolbar();
		$this->lists = $this->model->getItems();

		return parent::render();
	}

	/**
	 * Add the page title and toolbar for the confirm copy page
	 *
	 * @return  void
	 */
	protected function addConfirmCopyToolbar()
	{
		$app   = JFactory::getApplication();
		$input = $app->input;
		$input->set('hidemainmenu', true);
		JToolBarHelper::title(FText::_('COM_FABRIK_MANAGER_LIST_COPY'), 'list.png');
		JToolBarHelper::cancel('lists.cancel', 'JTOOLBAR_CLOSE');
		JToolBarHelper::save('lists.doCopy', 'JTOOLBAR_SAVE');
		JToolBarHelper::divider();
		JToolBarHelper::help('JHELP_COMPONENTS_FABRIK_LISTS_EDIT');
	}

	/**
	 * Show a screen asking if the user wants to delete the lists forms/groups/elements
	 * and if they want to drop the underlying database table
	 *
	 * FIXME - test this
	 *
	 * @return  string
	 */
	protected function confirmDelete()
	{
		$this->form  = $this->model->getConfirmDeleteForm('list');
		$model       = $this->getModel('lists');
		$this->items = $model->getDbTableNames();
		$this->addConfirmDeleteToolbar();
		$this->setLayout('confirmdeletebootstrap');

		return parent::render();
	}

	/**
	 * Show a screen allowing the user to import a csv file to create a fabrik table.
	 *
	 * FIXME - test this
	 *
	 * @return  string
	 */
	protected function import()
	{
		$this->form = $this->get('ImportForm');
		$this->addImportToolBar();

		return parent::render();
	}
}
