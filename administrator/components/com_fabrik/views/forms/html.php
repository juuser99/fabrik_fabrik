<?php
/**
 * View class for a list of forms.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Admin\Views\Forms;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\HTML as HelperHTML;
use JFactory;
use \JHtml as JHtml;
use \JToolBarHelper as JToolBarHelper;
use \JHtmlSidebar as JHtmlSidebar;
use \FText as FText;
use Fabrik\Admin\Helpers\Fabrik;

/**
 * View class for a list of forms.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.5
 */
class Html extends \Fabrik\Admin\Views\Html
{
	/**
	 * Form items
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
	 * Sidebar html
	 *
	 * @var string
	 */
	public $sidebar = '';

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
		Fabrik::addSubmenu('forms');

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
		JToolBarHelper::title(FText::_('COM_FABRIK_MANAGER_FORMS'), 'forms.png');

		if ($canDo->get('core.create'))
		{
			JToolBarHelper::addNew('form.add', 'JTOOLBAR_NEW');
		}

		if ($canDo->get('core.edit'))
		{
			JToolBarHelper::editList('form.edit', 'JTOOLBAR_EDIT');
		}

		if ($canDo->get('core.edit.state'))
		{
			if ($this->state->get('filter.state') != 2)
			{
				JToolBarHelper::divider();
				JToolBarHelper::custom('forms.publish', 'publish.png', 'publish_f2.png', 'JTOOLBAR_PUBLISH', true);
				JToolBarHelper::custom('forms.unpublish', 'unpublish.png', 'unpublish_f2.png', 'JTOOLBAR_UNPUBLISH', true);
			}
		}

		if (JFactory::getUser()->authorise('core.manage', 'com_checkin'))
		{
			JToolBarHelper::custom('forms.checkin', 'checkin.png', 'checkin_f2.png', 'JTOOLBAR_CHECKIN', true);
		}

		if ($this->state->get('filter.published') == -2 && $canDo->get('core.delete'))
		{
			JToolBarHelper::deleteList('', 'forms.delete', 'JTOOLBAR_EMPTY_TRASH');
		}
		elseif ($canDo->get('core.edit.state'))
		{
			JToolBarHelper::trash('forms.trash', 'JTOOLBAR_TRASH');
		}

		if ($canDo->get('core.admin'))
		{
			JToolBarHelper::divider();
			JToolBarHelper::preferences('com_fabrik');
		}

		JToolBarHelper::divider();
		JToolBarHelper::help('JHELP_COMPONENTS_FABRIK_FORMS', false, FText::_('JHELP_COMPONENTS_FABRIK_FORMS'));

		JHtmlSidebar::setAction('index.php?option=com_fabrik&view=forms');
		$opts = JHtml::_('jgrid.publishedOptions', array('archived' => false));
		JHtmlSidebar::addFilter(
		FText::_('JOPTION_SELECT_PUBLISHED'),
		'filter_published',
		JHtml::_('select.options', $opts, 'value', 'text', $this->state->get('filter.published'), true)
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
	}
}
