<?php
/**
 * View to edit a package.
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Joomla\Component\Fabrik\Administrator\View\Package;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Html;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\FormView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Component\Fabrik\Administrator\Helper\FabrikAdminHelper;
use Joomla\Utilities\ArrayHelper;
use Fabrik\Helpers\ArrayHelper as FArrayHelper;

/**
 * View to edit a package.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       4.0
 */
class HtmlView extends FormView
{
	/**
	 * List forms in a modal?
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	public function listform()
	{
		$srcs = Html::framework();
		Html::script($srcs);
		$this->listform	= $this->get('PackageListForm');
		HTMLHelper::_('behavior.modal', 'a.modal');

		parent::display('list');
	}

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
		HTMLHelper::_('behavior.modal', 'a.modal');
		$model = $this->getModel();
		$this->form = $this->get('Form');
		$this->item = $this->get('Item');
		$this->state = $this->get('State');
		$this->listform	= $this->get('PackageListForm');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new \RuntimeException(implode("\n", $errors), 500);
		}

		$this->addToolbar();
		$canvas = FArrayHelper::getValue($this->item->params, 'canvas', array());
		$blocks = new \stdClass;
		$b = FArrayHelper::getValue($canvas, 'blocks', array());
		$blocks->form = FArrayHelper::getValue($b, 'form', array());
		$blocks->list = FArrayHelper::getValue($b, 'list', array());
		$blocks->visualization = FArrayHelper::getValue($b, 'visualization', array());

		$opts = ArrayHelper::getvalue($canvas, 'options', array());
		$d = new \stdClass;
		$layout = FArrayHelper::getValue($canvas, 'layout', $d);
		$document = Factory::getDocument();

		$opts = new \stdClass;

		$opts->blocks = $blocks;
		$opts->layout = $layout;
		$opts = json_encode($opts);
		$this->js = "PackageCanvas = new AdminPackage($opts);";
		$srcs[] = 'administrator/components/com_fabrik/views/package/adminpackage.js';

		Html::iniRequireJS();
		Html::script($srcs, $this->js);

		// Simple layout
		$this->listOpts = $model->getListOpts();
		$this->formOpts = $model->getFormOpts();
		$this->selFormOpts = $model->getSelFormOpts();
		$this->selListOpts = $model->getSelListOpts();

		FabrikAdminHelper::setViewLayout($this);

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
		$app = Factory::getApplication();
		$app->input->set('hidemainmenu', true);
		$user = Factory::getUser();
		$userId = $user->get('id');
		$isNew = ($this->item->id == 0);
		$checkedOut	= !($this->item->checked_out == 0 || $this->item->checked_out == $user->get('id'));
		$canDo = FabrikAdminHelper::getActions($this->state->get('filter.category_id'));
		ToolbarHelper::title($isNew ? Text::_('COM_FABRIK_MANAGER_PACKAGE_NEW') : Text::_('COM_FABRIK_MANAGER_PACKAGE_EDIT') . ' "' . $this->item->label . '"', 'box-add');

		if ($isNew)
		{
			// For new records, check the create permission.
			if ($canDo->get('core.create'))
			{
				ToolbarHelper::apply('package.apply', 'JTOOLBAR_APPLY');
				ToolbarHelper::save('package.save', 'JTOOLBAR_SAVE');
				ToolbarHelper::addNew('package.save2new', 'JTOOLBAR_SAVE_AND_NEW');
			}

			ToolbarHelper::cancel('package.cancel', 'JTOOLBAR_CANCEL');
		}
		else
		{
			// Can't save the record if it's checked out.
			if (!$checkedOut)
			{
				// Since it's an existing record, check the edit permission, or fall back to edit own if the owner.
				if ($canDo->get('core.edit') || ($canDo->get('core.edit.own') && $this->item->created_by == $userId))
				{
					ToolbarHelper::apply('package.apply', 'JTOOLBAR_APPLY');
					ToolbarHelper::save('package.save', 'JTOOLBAR_SAVE');

					// We can save this record, but check the create permission to see if we can return to make a new one.
					if ($canDo->get('core.create'))
					{
						ToolbarHelper::addNew('package.save2new', 'JTOOLBAR_SAVE_AND_NEW');
					}
				}
			}

			if ($canDo->get('core.create'))
			{
				ToolbarHelper::custom('package.save2copy', 'save-copy.png', 'save-copy_f2.png', 'JTOOLBAR_SAVE_AS_COPY', false);
			}

			ToolbarHelper::cancel('package.cancel', 'JTOOLBAR_CLOSE');
		}

		ToolbarHelper::divider();
		ToolbarHelper::help('JHELP_COMPONENTS_FABRIK_PACKAGE_EDIT', false, Text::_('JHELP_COMPONENTS_FABRIK_PACKAGE_EDIT'));
	}
}
