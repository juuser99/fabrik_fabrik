<?php
/**
 * Raw Fabrik List view class
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Views\Lizt;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Utilities\ArrayHelper;
use \stdClass;

/**
 * Raw Fabrik List view class
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.5
 */
class Raw extends Base
{
	/**
	 * Display the template
	 *
	 * @return void
	 */
	public function render()
	{
		$app     = $this->app;
		$input   = $app->input;
		$package = $app->getUserState('com_fabrik.package', 'fabrik');
		$model   = $this->model;

		if (!parent::access($model))
		{
			exit;
		}

		$table  = $model->getTable();
		$params = $model->getParams();
		$rowId  = $input->getString('rowid', '', 'string');
		list($this->headings, $groupHeadings, $this->headingClass, $this->cellClass) = $model->getHeadings();
		$data                   = $model->render();
		$this->emptyDataMessage = $model->getEmptyDataMsg();
		$nav                    = $model->getPagination();
		$form                   = $model->getFormModel();
		$c                      = 0;

		foreach ($data as $groupk => $group)
		{
			foreach ($group as $i => $x)
			{
				$o = new stdClass;

				if (is_object($data[$groupk]))
				{
					$o->data = ArrayHelper::fromObject($data[$groupk]);
				}
				else
				{
					$o->data = $data[$groupk][$i];
				}

				if (array_key_exists($groupk, $model->groupTemplates))
				{
					$o->groupHeading = $model->groupTemplates[$groupk] . ' ( ' . count($group) . ' )';
				}

				$o->cursor = $i + $nav->limitstart;
				$o->total  = $nav->total;
				$o->id     = 'list_' . $model->getRenderContext() . '_row_' . @$o->data->__pk_val;
				$o->class  = 'fabrik_row oddRow' . $c;

				if (is_object($data[$groupk]))
				{
					$data[$groupk] = $o;
				}
				else
				{
					$data[$groupk][$i] = $o;
				}

				$c = 1 - $c;
			}
		}

		$groups = $model->getGroupsHierarchy();

		foreach ($groups as $groupModel)
		{
			$elementModels = $groupModel->getPublishedElements();

			foreach ($elementModels as $elementModel)
			{
				$elementModel->setContext($groupModel, $form, $model);
				$elementModel->setRowClass($data);
			}
		}

		$session = $this->session;

		$d = array('id' => $table->get('id'), 'listRef' => $input->get('listref'), 'rowid' => $rowId, 'model' => 'list', 'data' => $data,
			'headings' => $this->headings, 'formid' => $model->getTable()->get('form.id'),
			'lastInsertedRow' => $session->get('lastInsertedRow', 'test'));

		$d['nav']          = get_object_vars($nav);
		$template              = $input->get('tmpl', $this->getTmpl());
		$d['htmlnav']      = $params->get('show-table-nav', 1) ? $nav->getListFooter($model->getId(), $template) : '';
		$d['calculations'] = $model->getCalculations();

		// $$$ hugh - see if we have a message to include, set by a list plugin
		$context = 'com_' . $package . '.list' . $model->getRenderContext() . '.msg';

		if ($session->has($context))
		{
			$d['msg'] = $session->get($context);
			$session->clear($context);
		}

		echo json_encode($d);
	}

	/**
	 * Get the view template name
	 *
	 * @return  string template name
	 */
	private function getTmpl()
	{
		$app    = $this->app;
		$input  = $app->input;
		$model  = $this->getModel();
		$table  = $model->getTable();
		$params = $model->getParams();

		if ($app->isAdmin())
		{
			$template = $params->get('admin_template');

			if ($template == -1 || $template == '')
			{
				$template = $input->get('layout', $table->get('list.template'));
			}
		}
		else
		{
			$template = $input->get('layout',$table->get('list.template'));
		}

		return $template;
	}
}
