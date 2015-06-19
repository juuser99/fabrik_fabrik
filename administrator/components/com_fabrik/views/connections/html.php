<?php
/**
 * View class for a list of connections.
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Admin\Views\Connections;

// No direct access
defined('_JEXEC') or die('Restricted access');

use \JHtml as JHtml;
use \JToolBarHelper as JToolBarHelper;
use \JHtmlSidebar as JHtmlSidebar;
use Fabrik\Admin\Helpers\Fabrik;
use \JFactory as JFactory;
use Fabrik\Helpers\HTMLHelper;
use Fabrik\Helpers\Text;

/**
 * View class for a list of connections.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.5
 */
class Html extends \Fabrik\Admin\Views\Html
{
	/**
	 * Connection items
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
	 * Render the view
	 *
	 * @return  void
	 */

	public function render()
	{
		// Initialise variables.
		$this->items = $this->model->getItems();
		$this->pagination = $this->model->getPagination();
		$this->state = $this->model->getState();

		$this->addToolbar();
		Fabrik::addSubmenu('connections');

		$this->sidebar = JHtmlSidebar::render();
		$this->setLayout('bootstrap');

		HTMLHelper::iniRequireJS();

		return parent::render();
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @since	1.6
	 *
	 * @return  void
	 */

	protected function addToolbar()
	{
		require_once JPATH_COMPONENT . '/helpers/fabrik.php';
		$canDo	= Fabrik::getActions($this->state->get('filter.category_id'));
		JToolBarHelper::title(Text::_('COM_FABRIK_MANAGER_CONNECTIONS'), 'connections.png');

		if ($canDo->get('core.create'))
		{
			JToolBarHelper::addNew('connection.add', 'JTOOLBAR_NEW');
		}

		if ($canDo->get('core.edit'))
		{
			JToolBarHelper::editList('connection.edit', 'JTOOLBAR_EDIT');
		}

		if ($canDo->get('core.edit.state'))
		{
			if ($this->state->get('filter.state') != 2)
			{
				JToolBarHelper::divider();
				JToolBarHelper::custom('connections.publish', 'publish.png', 'publish_f2.png', 'JTOOLBAR_PUBLISH', true);
				JToolBarHelper::custom('connections.unpublish', 'unpublish.png', 'unpublish_f2.png', 'JTOOLBAR_UNPUBLISH', true);
			}
		}

		if (JFactory::getUser()->authorise('core.manage', 'com_checkin'))
		{
			JToolBarHelper::custom('connections.checkin', 'checkin.png', 'checkin_f2.png', 'JTOOLBAR_CHECKIN', true);
		}

		if ($this->state->get('filter.published') == -2 && $canDo->get('core.delete'))
		{
			JToolBarHelper::deleteList('', 'connections.delete', 'JTOOLBAR_EMPTY_TRASH');
		}
		elseif ($canDo->get('core.edit.state'))
		{
			JToolBarHelper::trash('connections.trash', 'JTOOLBAR_TRASH');
		}

		if ($canDo->get('core.admin'))
		{
			JToolBarHelper::divider();
			JToolBarHelper::preferences('com_fabrik');
		}

		JToolBarHelper::divider();
		JToolBarHelper::help('JHELP_COMPONENTS_FABRIK_CONNECTIONS', false, Text::_('JHELP_COMPONENTS_FABRIK_CONNECTIONS'));

		JHtmlSidebar::setAction('index.php?option=com_fabrik&view=connections');

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
