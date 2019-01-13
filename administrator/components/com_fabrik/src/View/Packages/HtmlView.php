<?php
/**
 * View class for a list of packages.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Component\Fabrik\Administrator\View\Packages;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Html;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\ListView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Fabrik\Component\Fabrik\Administrator\Helper\FabrikAdminHelper;

/**
 * View class for a list of packages.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       1.6
 */
class HtmlView extends ListView
{
	/**
	 * Display the view
	 *
	 * @param   string  $tpl  Template
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	public function display($tpl = null)
	{
		// Initialise variables.
		$app = Factory::getApplication();
		$input = $app->input;

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
	 * @since	1.6
	 *
	 * @return  void
	 */
	protected function addToolbar()
	{
		$canDo = FabrikAdminHelper::getActions($this->state->get('filter.category_id'));
		ToolbarHelper::title(Text::_('COM_FABRIK_MANAGER_PACKAGES'), 'box-add');

		/*
		if ($canDo->get('core.create'))
		{
			ToolbarHelper::addNew('package.add', 'JTOOLBAR_NEW');
		}

		if ($canDo->get('core.edit'))
		{
			ToolbarHelper::editList('package.edit', 'JTOOLBAR_EDIT');
		}

		ToolbarHelper::custom('package.export', 'export.png', 'export_f2.png', 'COM_FABRIK_MANAGER_PACKAGE_EXPORT', true);

		if ($canDo->get('core.edit.state'))
		{
			if ($this->state->get('filter.state') != 2)
			{
				ToolbarHelper::divider();
				ToolbarHelper::custom('packages.publish', 'publish.png', 'publish_f2.png', 'JTOOLBAR_PUBLISH', true);
				ToolbarHelper::custom('packages.unpublish', 'unpublish.png', 'unpublish_f2.png', 'JTOOLBAR_UNPUBLISH', true);
			}
		}

		if (Factory::getUser()->authorise('core.manage', 'com_checkin'))
		{
			ToolbarHelper::custom('packages.checkin', 'checkin.png', 'checkin_f2.png', 'JTOOLBAR_CHECKIN', true);
		}

		if ($this->state->get('filter.published') == -2 && $canDo->get('core.delete'))
		{
			ToolbarHelper::deleteList('', 'packages.delete', 'JTOOLBAR_EMPTY_TRASH');
		}
		elseif ($canDo->get('core.edit.state'))
		{
			ToolbarHelper::trash('packages.trash', 'JTOOLBAR_TRASH');
		}

		*/

		if ($canDo->get('core.admin'))
		{
			ToolbarHelper::divider();
			ToolbarHelper::preferences('com_fabrik');
		}

		ToolbarHelper::divider();
		ToolbarHelper::help('JHELP_COMPONENTS_FABRIK_PACKAGES', false, Text::_('JHELP_COMPONENTS_FABRIK_PACKAGES'));

		// @todo - append packages to filter form like this
		// $languageXml = new \SimpleXMLElement('<field name="package" type="hidden" default="' . $forcedLanguage . '" />');
		// $this->filterForm->setField($languageXml, 'filter', true);

		/*
		JHtmlSidebar::setAction('index.php?option=com_fabrik&view=packages');

		$publishOpts = JHtml::_('jgrid.publishedOptions', array('archived' => false));
		JHtmlSidebar::addFilter(
		Text::_('JOPTION_SELECT_PUBLISHED'),
		'filter_published',
		JHtml::_('select.options', $publishOpts, 'value', 'text', $this->state->get('filter.published'), true)
		);
		*/
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
			'p.id'        => Text::_('JGRID_HEADING_ID'),
			'p.label'     => Text::_('COM_FABRIK_LABEL'),
			'p.published' => Text::_('JPUBLISHED'),
		);
	}
}
