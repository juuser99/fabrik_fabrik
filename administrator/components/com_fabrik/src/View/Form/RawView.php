<?php
/**
 * View to edit a form.
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Joomla\Component\Fabrik\Administrator\View\Form;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Component\Fabrik\Administrator\Helper\FabrikAdminHelper;
use Joomla\Component\Fabrik\Administrator\Model\FabModel;
use Joomla\Component\Fabrik\Administrator\Table\FormTable;
use Joomla\Component\Fabrik\Site\Model\FormModel;
use Joomla\Component\Fabrik\Site\Model\GroupModel;
use Joomla\Registry\Registry;

/**
 * View to edit a form.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       4.0
 */
class RawView extends BaseHtmlView
{
	/**
	 * Form
	 *
	 * @var Form
	 *
	 * @since 4.0
	 */
	protected $form;

	/**
	 * Form item
	 *
	 * @var FormTable
	 *
	 * @since 4.0
	 */
	protected $item;

	/**
	 * View state
	 *
	 * @var Registry
	 *
	 * @since 4.0
	 */
	protected $state;

	/**
	 * Display the view
	 *
	 * @param   string $tpl Template
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	public function display($tpl = null)
	{
		/** @var FormModel $model */
		$model = FabModel::getInstance(FormModel::class);
		$model->render();

		if (!$this->canAccess())
		{
			return false;
		}

		$model->getJoinGroupIds();
		$groups    = $model->getGroupsHiarachy();
		$gkeys     = array_keys($groups);
		$JsonArray = array();
		$JSONHtml  = array();

		for ($i = 0; $i < count($gkeys); $i++)
		{
			/** @var GroupModel $groupModel */
			$groupModel = $groups[$gkeys[$i]];

			// Check if group is actually a table join
			$repeatGroup = 1;

			if ($groupModel->canRepeat())
			{
				if ($groupModel->isJoin())
				{
					$joinModel = $groupModel->getJoinModel();
					$joinTable = $joinModel->getJoin();

					if (is_object($joinTable))
					{
						// $$$ rob test!!!
						if (!$groupModel->canView())
						{
							continue;
						}

						$elementModels = $groupModel->getPublishedElements();
						reset($elementModels);
						$tmpElement        = current($elementModels);
						$smallerElHTMLName = $tmpElement->getFullName(true, false);
						$repeatGroup       = count($model->data[$smallerElHTMLName]);
					}
				}
			}

			$groupModel->repeatTotal = $repeatGroup;

			for ($c = 0; $c < $repeatGroup; $c++)
			{
				$elementModels = $groupModel->getPublishedElements();

				foreach ($elementModels as $elementModel)
				{
					if (!$model->isEditable())
					{
						/* $$$ rob 22/03/2011 changes element keys by appending "_id" to the end, means that
						 * db join add append data doesn't work if for example the pop-up form is set to allow adding,
						 * but not editing records
						 * $elementModel->inDetailedView = true;
						 */
						$elementModel->setEditable(false);
					}

					// Force reload?
					$elementModel->HTMLids = null;
					$elementHTMLId         = $elementModel->getHTMLId($c);

					if (!$model->isEditable())
					{
						$JsonArray[$elementHTMLId] = $elementModel->getROValue($model->data, $c);
					}
					else
					{
						$JsonArray[$elementHTMLId] = $elementModel->getValue($model->data, $c);
					}
					// Test for paginate plugin
					if (!$model->isEditable())
					{
						$elementModel->HTMLids        = null;
						$elementModel->inDetailedView = true;
					}

					$JSONHtml[$elementHTMLId] = htmlentities($elementModel->render($model->data, $c), ENT_QUOTES, 'UTF-8');
				}
			}
		}

		$data = array("id"   => $model->getId(), 'model' => 'table', "errors" => $model->errors, "data" => $JsonArray, 'html' => $JSONHtml,
		              'post' => $_REQUEST);
		echo json_encode($data);
	}

	/**
	 * Alias for display()
	 *
	 * @param   string $tpl Template
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	public function form($tpl = null)
	{
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
		$title      = $isNew ? Text::_('COM_FABRIK_MANAGER_FORM_NEW') : Text::_('COM_FABRIK_MANAGER_FORM_EDIT') . ' "' . $this->item->label . '"';
		ToolbarHelper::title($title, 'file-2');

		if ($isNew)
		{
			// For new records, check the create permission.
			if ($canDo->get('core.create'))
			{
				ToolbarHelper::apply('form.apply', 'JTOOLBAR_APPLY');
				ToolbarHelper::save('form.save', 'JTOOLBAR_SAVE');
				ToolbarHelper::addNew('form.save2new', 'JTOOLBAR_SAVE_AND_NEW');
			}

			ToolbarHelper::cancel('form.cancel', 'JTOOLBAR_CANCEL');
		}
		else
		{
			// Can't save the record if it's checked out.
			if (!$checkedOut)
			{
				// Since it's an existing record, check the edit permission, or fall back to edit own if the owner.
				if ($canDo->get('core.edit') || ($canDo->get('core.edit.own') && $this->item->created_by == $userId))
				{
					ToolbarHelper::apply('form.apply', 'JTOOLBAR_APPLY');
					ToolbarHelper::save('form.save', 'JTOOLBAR_SAVE');

					// We can save this record, but check the create permission to see if we can return to make a new one.
					if ($canDo->get('core.create'))
					{
						ToolbarHelper::addNew('form.save2new', 'JTOOLBAR_SAVE_AND_NEW');
					}
				}
			}

			if ($canDo->get('core.create'))
			{
				ToolbarHelper::custom('form.save2copy', 'save-copy.png', 'save-copy_f2.png', 'JTOOLBAR_SAVE_AS_COPY', false);
			}

			ToolbarHelper::cancel('form.cancel', 'JTOOLBAR_CLOSE');
		}

		ToolbarHelper::divider();
		ToolbarHelper::help('JHELP_COMPONENTS_FABRIK_FORMS_EDIT');
	}
}
