<?php
/**
 *  View class for a list of lists.
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2018  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Joomla\Component\Fabrik\Administrator\View\Lists;

use Fabrik\Helpers\Html;
use Fabrik\Helpers\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\MVC\View\ListView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Component\Fabrik\Administrator\Helper\FabrikAdminHelper;

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * View class for a list of lists.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       4.0
 */
class HtmlView extends ListView
{
	/**
	 * List items
	 *
	 * @var  array
	 *
	 * @since 4.0
	 */
	protected $items;

	/**
	 * Pagination
	 *
	 * @var  JPagination
	 *
	 * @since 4.0
	 */
	protected $pagination;

	/**
	 * View state
	 *
	 * @var object
	 *
	 * @since 4.0
	 */
	protected $state;

	/**
	 * @var
	 *
	 * @since 4.0
	 */
	protected $packageOptions;

	/**
	 * @var
	 *
	 * @since 4.0
	 */
	public $filterForm;

	/**
	 * @var
	 *
	 * @since 4.0
	 */
	public $tableGroups;

	/**
	 * @var
	 *
	 * @since 4.0
	 */
	public $sidebar;

	/**
	 * Display the view
	 *
	 * @param   string $tpl Template name
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	public function display($tpl = null)
	{
		switch ($this->getLayout())
		{
			case 'confirmdelete':
				$this->confirmdelete();

				return;
			case 'import':
				$this->import($tpl);

				return;
		}
		// Initialise variables.
		$app                  = Factory::getApplication();
		$input                = $app->input;
		$this->items          = $this->get('Items');
		$this->pagination     = $this->get('Pagination');
		$this->state          = $this->get('State');
		$this->packageOptions = $this->get('PackageOptions');
		$this->filterForm     = $this->get('FilterForm');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new \RuntimeException(implode("\n", $errors), 500);

			return false;
		}

		$this->tableGroups = $this->get('TableGroups');

		FabrikAdminHelper::addSubmenu($input->getWord('view', 'lists'));

		$this->sidebar = \JHtmlSidebar::render();

		Html::iniRequireJS();
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
		$canDo = FabrikAdminHelper::getActions($this->state->get('filter.category_id'));
		ToolbarHelper::title(Text::_('COM_FABRIK_MANAGER_LISTS'), 'list');

		if ($canDo->get('core.create'))
		{
			ToolBarHelper::addNew('list.add', 'JTOOLBAR_NEW');
		}

		if ($canDo->get('core.edit'))
		{
			ToolBarHelper::editList('list.edit', 'JTOOLBAR_EDIT');
		}

		ToolBarHelper::custom('list.copy', 'copy.png', 'copy_f2.png', 'COM_FABRIK_COPY');

		if ($canDo->get('core.edit.state'))
		{
			if ($this->state->get('filter.state') != 2)
			{
				ToolBarHelper::divider();
				ToolBarHelper::custom('lists.publish', 'publish.png', 'publish_f2.png', 'JTOOLBAR_PUBLISH', true);
				ToolBarHelper::custom('lists.unpublish', 'unpublish.png', 'unpublish_f2.png', 'JTOOLBAR_UNPUBLISH', true);
			}
		}

		ToolBarHelper::divider();

		if ($canDo->get('core.create'))
		{
			ToolBarHelper::custom('import.display', 'upload.png', 'upload_f2.png', 'COM_FABRIK_IMPORT', false);
		}

		ToolBarHelper::divider();

		if (Factory::getUser()->authorise('core.manage', 'com_checkin'))
		{
			ToolBarHelper::custom('lists.checkin', 'checkin.png', 'checkin_f2.png', 'JTOOLBAR_CHECKIN', true);
		}

		ToolBarHelper::divider();

		if ($this->state->get('filter.published') == -2 && $canDo->get('core.delete'))
		{
			ToolBarHelper::deleteList('', 'lists.delete', 'JTOOLBAR_EMPTY_TRASH');
		}
		elseif ($canDo->get('core.edit.state'))
		{
			ToolBarHelper::trash('lists.trash', 'JTOOLBAR_TRASH');
		}

		if ($canDo->get('core.admin'))
		{
			ToolBarHelper::divider();
			ToolBarHelper::preferences('com_fabrik');
		}

		ToolBarHelper::divider();
		ToolBarHelper::help('JHELP_COMPONENTS_FABRIK_LISTS', false, Text::_('JHELP_COMPONENTS_FABRIK_LISTS'));

		\JHtmlSidebar::setAction('index.php?option=com_fabrik&view=lists');

		if (!empty($this->packageOptions))
		{
			array_unshift($this->packageOptions, HTMLHelper::_('select.option', 'fabrik', Text::_('COM_FABRIK_SELECT_PACKAGE')));
			\JHtmlSidebar::addFilter(
				Text::_('JOPTION_SELECT_PUBLISHED'),
				'package',
				HTMLHelper::_('select.options', $this->packageOptions, 'value', 'text', $this->state->get('com_fabrik.package'), true)
			);
		}
	}

	/**
	 * Add the page title and toolbar for confirming list deletion
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	protected function addConfirmDeleteToolbar()
	{
		$app = Factory::getApplication();
		$app->input->set('hidemainmenu', true);
		ToolBarHelper::title(Text::_('COM_FABRIK_MANAGER_LIST_CONFIRM_DELETE'), 'list');
		ToolBarHelper::save('lists.delete', 'JTOOLBAR_APPLY');
		ToolBarHelper::cancel('list.cancel', 'JTOOLBAR_CANCEL');
		ToolBarHelper::divider();
		ToolBarHelper::help('JHELP_COMPONENTS_FABRIK_LISTS_EDIT', true, 'http://fabrikar.com/wiki/index.php/List_delete_confirmation');
	}

	/**
	 * Add the page title and toolbar for List import
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	protected function addImportToolBar()
	{
		$app = Factory::getApplication();
		$app->input->set('hidemainmenu', true);
		ToolBarHelper::title(Text::_('COM_FABRIK_MANAGER_LIST_IMPORT'), 'list');
		ToolBarHelper::save('lists.doimport', 'JTOOLBAR_APPLY');
		ToolBarHelper::cancel('list.cancel', 'JTOOLBAR_CANCEL');
	}

	/**
	 * Show a screen asking if the user wants to delete the lists forms/groups/elements
	 * and if they want to drop the underlying database table
	 *
	 * @param   string $tpl Template
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	protected function confirmdelete($tpl = null)
	{
		$this->form  = $this->get('ConfirmDeleteForm', 'list');
		$model       = $this->getModel('lists');
		$this->items = $model->getDbTableNames();
		$this->addConfirmDeleteToolbar();
		$this->setLayout('confirmdeletebootstrap');

		parent::display($tpl);
	}

	/**
	 * Show a screen allowing the user to import a csv file to create a fabrik table.
	 *
	 * @param   string $tpl Template
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	protected function import($tpl = null)
	{
		$this->form = $this->get('ImportForm');
		$this->addImportToolBar();
		parent::display($tpl);
	}
}
