<?php
/**
 * View to edit a connection.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0
 */

namespace Fabrik\Component\Fabrik\Administrator\View\Connection;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Html;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\FormView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Fabrik\Component\Fabrik\Administrator\Helper\FabrikAdminHelper;

/**
 * View to edit a connection.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       4.0
 */
class HtmlView extends FormView
{
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
		// Initialiase variables.
		$model      = $this->getModel();
		$this->item = $this->get('Item');
		$model->checkDefault($this->item);
		$this->form = $this->get('Form');
		$this->form->bind($this->item);
		$this->state = $this->get('State');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new \RuntimeException(implode("\n", $errors), 500);
		}

		FabrikAdminHelper::setViewLayout($this);

		$srcs           = Html::framework();
		$srcs['Fabrik'] = 'media/com_fabrik/js/fabrik.js';

		Html::iniRequireJS();
		Html::script($srcs);

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
		$app   = Factory::getApplication();
		$input = $app->input;
		$input->set('hidemainmenu', true);
		$user       = Factory::getUser();
		$userId     = $user->get('id');
		$isNew      = ($this->item->id == 0);
		$checkedOut = !($this->item->checked_out == 0 || $this->item->checked_out == $user->get('id'));
		$canDo      = FabrikAdminHelper::getActions($this->state->get('filter.category_id'));
		$title      = $isNew ? Text::_('COM_FABRIK_MANAGER_CONNECTION_NEW') : Text::_('COM_FABRIK_MANAGER_CONNECTION_EDIT') . ' "' . $this->item->description . '"';
		ToolbarHelper::title($title, 'tree-2');

		if ($isNew)
		{
			// For new records, check the create permission.
			if ($canDo->get('core.create'))
			{
				ToolbarHelper::apply('connection.apply', 'JTOOLBAR_APPLY');
				ToolbarHelper::save('connection.save', 'JTOOLBAR_SAVE');
				ToolbarHelper::addNew('connection.save2new', 'JTOOLBAR_SAVE_AND_NEW');
			}

			ToolbarHelper::cancel('connection.cancel', 'JTOOLBAR_CANCEL');
		}
		else
		{
			// Can't save the record if it's checked out.
			if (!$checkedOut)
			{
				// Since it's an existing record, check the edit permission, or fall back to edit own if the owner.
				if ($canDo->get('core.edit') || ($canDo->get('core.edit.own') && $this->item->created_by == $userId))
				{
					ToolbarHelper::apply('connection.apply', 'JTOOLBAR_APPLY');
					ToolbarHelper::save('connection.save', 'JTOOLBAR_SAVE');

					// We can save this record, but check the create permission to see if we can return to make a new one.
					if ($canDo->get('core.create'))
					{
						ToolbarHelper::addNew('connection.save2new', 'JTOOLBAR_SAVE_AND_NEW');
					}
				}
			}

			if ($canDo->get('core.create'))
			{
				ToolbarHelper::custom('connection.save2copy', 'save-copy.png', 'save-copy_f2.png', 'JTOOLBAR_SAVE_AS_COPY', false);
			}

			ToolbarHelper::cancel('connection.cancel', 'JTOOLBAR_CLOSE');
		}

		ToolbarHelper::divider();
		ToolbarHelper::help('JHELP_COMPONENTS_FABRIK_CONNECTIONS_EDIT', false, Text::_('JHELP_COMPONENTS_FABRIK_CONNECTIONS_EDIT'));
	}
}
