<?php
/**
 * View class for a list of visualizations.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Admin\Admin;
use Fabrik\Helpers\Html;
use Fabrik\Helpers\Text;

jimport('joomla.application.component.view');

/**
 * View class for a list of visualizations.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.0
 */
class FabrikAdminViewVisualizations extends JViewLegacy
{
	/**
	 * Visualization items
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
	 * Display the view
	 *
	 * @param   string  $tpl  Template
	 *
	 * @return  void
	 */

	public function display($tpl = null)
	{
		// Initialise variables.
		$app = JFactory::getApplication();
		$input = $app->input;
		$this->items = $this->get('Items');
		$this->pagination = $this->get('Pagination');
		$this->state = $this->get('State');
		$this->packageOptions = $this->get('PackageOptions');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new RuntimeException(implode("\n", $errors), 500);
		}

		Admin::setViewLayout($this);
		$this->addToolbar();
		Admin::addSubmenu($input->getWord('view', 'lists'));
		$this->sidebar = JHtmlSidebar::render();

		Html::iniRequireJS();
		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 */
	protected function addToolbar()
	{
		$canDo = JHelperContent::getActions('com_fabrik', '', $this->state->get('filter.category_id'));
		JToolBarHelper::title(Text::_('COM_FABRIK_MANAGER_VISUALIZATIONS'), 'chart');

		if ($canDo->get('core.create'))
		{
			JToolBarHelper::addNew('visualization.add', 'JTOOLBAR_NEW');
		}

		if ($canDo->get('core.edit'))
		{
			JToolBarHelper::editList('visualization.edit', 'JTOOLBAR_EDIT');
		}

		if ($canDo->get('core.edit.state'))
		{
			if ($this->state->get('filter.state') != 2)
			{
				JToolBarHelper::divider();
				JToolBarHelper::custom('visualizations.publish', 'publish.png', 'publish_f2.png', 'JTOOLBAR_PUBLISH', true);
				JToolBarHelper::custom('visualizations.unpublish', 'unpublish.png', 'unpublish_f2.png', 'JTOOLBAR_UNPUBLISH', true);
			}
		}

		if (JFactory::getUser()->authorise('core.manage', 'com_checkin'))
		{
			JToolBarHelper::custom('visualizations.checkin', 'checkin.png', 'checkin_f2.png', 'JTOOLBAR_CHECKIN', true);
		}

		if ($this->state->get('filter.published') == -2 && $canDo->get('core.delete'))
		{
			JToolBarHelper::deleteList('', 'visualizations.delete', 'JTOOLBAR_EMPTY_TRASH');
		}
		elseif ($canDo->get('core.edit.state'))
		{
			JToolBarHelper::trash('visualizations.trash', 'JTOOLBAR_TRASH');
		}

		if ($canDo->get('core.admin'))
		{
			JToolBarHelper::divider();
			JToolBarHelper::preferences('com_fabrik');
		}

		JToolBarHelper::divider();
		JToolBarHelper::help('JHELP_COMPONENTS_FABRIK_VISUALIZATIONS', false, Text::_('JHELP_COMPONENTS_FABRIK_VISUALIZATIONS'));

		JHtmlSidebar::setAction('index.php?option=com_fabrik&view=visualizations');

		$publishOpts = JHtml::_('jgrid.publishedOptions', array('archived' => false));
		JHtmlSidebar::addFilter(
		Text::_('JOPTION_SELECT_PUBLISHED'),
		'filter_published',
		JHtml::_('select.options', $publishOpts, 'value', 'text', $this->state->get('filter.published'), true)
		);

		if (!empty($this->packageOptions))
		{
			array_unshift($this->packageOptions, JHtml::_('select.option', 'fabrik', Text::_('COM_FABRIK_SELECT_PACKAGE')));
			JHtmlSidebar::addFilter(
			Text::_('JOPTION_SELECT_PUBLISHED'),
			'package',
			JHtml::_('select.options', $this->packageOptions, 'value', 'text', $this->state->get('com_fabrik.package'), true)
			);
		}
	}
}
