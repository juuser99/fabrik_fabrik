<?php
/**
 * View class for a list of groups.
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Joomla\Component\Fabrik\Administrator\View\Groups;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Html;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\ListView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Component\Fabrik\Administrator\Helper\FabrikAdminHelper;

/**
 * View class for a list of groups.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       4.0
 */
class HtmlView extends ListView
{
	/**
	 * @var array
	 * @since 4.0
	 */
	private $formOptions;

	/**
	 * @var array
	 * @since 4.0
	 */
	private $packageOptions;

	/**
	 * Display the view
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
		$app                  = Factory::getApplication();
		$input                = $app->input;
		$this->items          = $this->get('Items');
		$this->pagination     = $this->get('Pagination');
		$this->state          = $this->get('State');
		$this->formOptions    = $this->get('FormOptions');
		$this->packageOptions = $this->get('PackageOptions');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new \RuntimeException(implode("\n", $errors), 500);
		}

		FabrikAdminHelper::setViewLayout($this);
		FabrikAdminHelper::addSubmenu($input->getWord('view', 'lists'));

		Html::iniRequireJS();

		parent::display($tpl);
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
		$canDo = FabrikAdminHelper::getActions($this->state->get('filter.category_id'));
		ToolbarHelper::title(Text::_('COM_FABRIK_MANAGER_GROUPS'), 'stack');

		if ($canDo->get('core.create'))
		{
			ToolbarHelper::addNew('group.add', 'JTOOLBAR_NEW');
		}

		if ($canDo->get('core.edit'))
		{
			ToolbarHelper::editList('group.edit', 'JTOOLBAR_EDIT');
		}

		if ($canDo->get('core.edit.state'))
		{
			if ($this->state->get('filter.state') != 2)
			{
				ToolbarHelper::divider();
				ToolbarHelper::custom('groups.publish', 'publish.png', 'publish_f2.png', 'JTOOLBAR_PUBLISH', true);
				ToolbarHelper::custom('groups.unpublish', 'unpublish.png', 'unpublish_f2.png', 'JTOOLBAR_UNPUBLISH', true);
			}
		}

		if (Factory::getUser()->authorise('core.manage', 'com_checkin'))
		{
			ToolbarHelper::custom('groups.checkin', 'checkin.png', 'checkin_f2.png', 'JTOOLBAR_CHECKIN', true);
		}

		if ($this->state->get('filter.published') == -2 && $canDo->get('core.delete'))
		{
			ToolbarHelper::deleteList('', 'groups.delete', 'JTOOLBAR_EMPTY_TRASH');
		}
		elseif ($canDo->get('core.edit.state'))
		{
			ToolbarHelper::trash('groups.trash', 'JTOOLBAR_TRASH');
		}

		if ($canDo->get('core.admin'))
		{
			ToolbarHelper::divider();
			ToolbarHelper::preferences('com_fabrik');
		}

		ToolbarHelper::divider();
		ToolbarHelper::help('JHELP_COMPONENTS_FABRIK_GROUPS', false, Text::_('JHELP_COMPONENTS_FABRIK_GROUPS'));

		\JHtmlSidebar::setAction('index.php?option=com_fabrik&view=groups');

		$publishOpts = HTMLHelper::_('jgrid.publishedOptions', array('archived' => false));
		\JHtmlSidebar::addFilter(
			Text::_('JOPTION_SELECT_PUBLISHED'),
			'filter_published',
			HtmlHelper::_('select.options', $publishOpts, 'value', 'text', $this->state->get('filter.published'), true)
		);

		if (!empty($this->packageOptions))
		{
			// @todo - append packages to filter form like this
			// $languageXml = new \SimpleXMLElement('<field name="package" type="hidden" default="' . $forcedLanguage . '" />');
			// $this->filterForm->setField($languageXml, 'filter', true);

			/*
			array_unshift($this->packageOptions, HtmlHelper::_('select.option', 'fabrik', Text::_('COM_FABRIK_SELECT_PACKAGE')));
			\JHtmlSidebar::addFilter(
				Text::_('JOPTION_SELECT_PUBLISHED'),
				'package',
				HtmlHelper::_('select.options', $this->packageOptions, 'value', 'text', $this->state->get('com_fabrik.package'), true)
			);
			*/
		}

		\JHtmlSidebar::addFilter(
			Text::_('COM_FABRIK_SELECT_FORM'),
			'filter_form',
			HtmlHelper::_('select.options', $this->formOptions, 'value', 'text', $this->state->get('filter.form'), true)
		);
	}

	/**
	 * Returns an array of fields the table can be sorted by
	 *
	 * @return  array  Array containing the field name to sort by as the key and display text as value
	 *
	 * @since   4.0
	 */
	protected function getSortFields()
	{
		return array(
			'g.id'        => Text::_('JGRID_HEADING_ID'),
			'g.name'      => Text::_('COM_FABRIK_NAME'),
			'g.label'     => Text::_('COM_FABRIK_LABEL'),
			'f.label'     => Text::_('COM_FABRIK_FORM'),
			'g.published' => Text::_('JPUBLISHED'),
		);
	}
}
