<?php
/**
 * View class for a list of groups.
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Admin\Views\Groups;

// No direct access
defined('_JEXEC') or die('Restricted access');

use FabrikHelperHTML;
use \JHtml as JHtml;
use \JToolBarHelper as JToolBarHelper;
use \JHtmlSidebar as JHtmlSidebar;
use \FText as FText;
use Fabrik\Admin\Helpers\Fabrik;

/**
 * View class for a list of groups.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.5
 */
class Html extends \Fabrik\Admin\Views\Html
{
	/**
	 * Group items
	 *
	 * @var  array
	 */
	protected $items;

	/**
	 * Pagination
	 *
	 * @var  \JPagination
	 */
	protected $pagination;

	/**
	 * View state
	 *
	 * @var object
	 */
	protected $state;

	/**
	 * Display the view
	 *
	 * @return  void
	 */

	public function render()
	{
		$this->items       = $this->model->getItems();
		$this->pagination  = $this->model->getPagination();
		$this->state       = $this->model->getState();
		$this->formOptions = $this->model->getFormOptions();
		//$this->packageOptions = $this->get('PackageOptions');

		$this->addToolbar();
		Fabrik::addSubmenu('groups');

		$this->sidebar = JHtmlSidebar::render();
		$this->setLayout('bootstrap');
		FabrikHelperHTML::iniRequireJS();

		return parent::render();
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
		require_once JPATH_COMPONENT . '/helpers/fabrik.php';
		$canDo = Fabrik::getActions($this->state->get('filter.category_id'));
		JToolBarHelper::title(FText::_('COM_FABRIK_MANAGER_GROUPS'), 'groups.png');

		if ($canDo->get('core.create'))
		{
			JToolBarHelper::addNew('group.add', 'JTOOLBAR_NEW');
		}

		if ($canDo->get('core.edit'))
		{
			JToolBarHelper::editList('group.edit', 'JTOOLBAR_EDIT');
		}

		if ($canDo->get('core.edit.state'))
		{
			if ($this->state->get('filter.state') != 2)
			{
				JToolBarHelper::divider();
				JToolBarHelper::custom('groups.publish', 'publish.png', 'publish_f2.png', 'JTOOLBAR_PUBLISH', true);
				JToolBarHelper::custom('groups.unpublish', 'unpublish.png', 'unpublish_f2.png', 'JTOOLBAR_UNPUBLISH', true);
			}
		}

		if (\JFactory::getUser()->authorise('core.manage', 'com_checkin'))
		{
			JToolBarHelper::custom('groups.checkin', 'checkin.png', 'checkin_f2.png', 'JTOOLBAR_CHECKIN', true);
		}

		if ($this->state->get('filter.published') == -2 && $canDo->get('core.delete'))
		{
			JToolBarHelper::deleteList('', 'groups.delete', 'JTOOLBAR_EMPTY_TRASH');
		}
		elseif ($canDo->get('core.edit.state'))
		{
			JToolBarHelper::trash('groups.trash', 'JTOOLBAR_TRASH');
		}

		if ($canDo->get('core.admin'))
		{
			JToolBarHelper::divider();
			JToolBarHelper::preferences('com_fabrik');
		}

		JToolBarHelper::divider();
		JToolBarHelper::help('JHELP_COMPONENTS_FABRIK_GROUPS', false, FText::_('JHELP_COMPONENTS_FABRIK_GROUPS'));

		JHtmlSidebar::setAction('index.php?option=com_fabrik&view=groups');

		$publishOpts = JHtml::_('jgrid.publishedOptions', array('archived' => false));
		JHtmlSidebar::addFilter(
			FText::_('JOPTION_SELECT_PUBLISHED'),
			'filter_published',
			JHtml::_('select.options', $publishOpts, 'value', 'text', $this->state->get('filter.published'), true)
		);

		if (!empty($this->packageOptions))
		{
			array_unshift($this->packageOptions, JHtml::_('select.option', 'fabrik', FText::_('COM_FABRIK_SELECT_PACKAGE')));
			JHtmlSidebar::addFilter(
				FText::_('JOPTION_SELECT_PUBLISHED'),
				'package',
				JHtml::_('select.options', $this->packageOptions, 'value', 'text', $this->state->get('com_fabrik.package'), true)
			);
		}

		JHtmlSidebar::addFilter(
			FText::_('COM_FABRIK_SELECT_FORM'),
			'filter_form',
			JHtml::_('select.options', $this->formOptions, 'value', 'text', $this->state->get('filter.form'), true)
		);
	}
}
