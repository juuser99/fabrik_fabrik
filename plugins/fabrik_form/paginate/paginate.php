<?php
/**
 * Form record next/prev scroll plugin
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.paginate
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Plugins\Form;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\ArrayHelper;
use Fabrik\Helpers\Html;
use Fabrik\Helpers\StringHelper;
use \stdClass;
use \JLayoutFile;
use \JRoute;

/**
 * Form record next/prev scroll plugin
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.paginate
 * @since       3.0
 */
class Paginate extends Form
{
	/**
	 * Output
	 * @var string
	 */
	protected $data = '';

	/**
	 * Navigation ids.
	 *
	 * @var Object
	 */
	protected $ids;

	/**
	 * Inject custom html into the bottom of the form
	 *
	 * @param   int  $c  Plugin counter
	 *
	 * @return  string  html
	 */
	public function getBottomContent_result($c)
	{
		return $this->data;
	}

	/**
	 * Sets up HTML to be injected into the form's bottom
	 *
	 * @return void
	 */
	public function getBottomContent()
	{
		$formModel = $this->getModel();

		if (!$this->show())
		{
			$this->data = '';
			return;
		}

		$input = $this->app->input;
		$formId = $formModel->getForm()->get('id');
		$mode = StringHelper::strtolower($input->get('view', 'form'));
		$this->ids = $this->getNavIds();
		$linkStartPrev = $this->ids->index == 0 ? ' disabled' : '';
		$linkNextEnd = $this->ids->index == $this->ids->lastKey ? ' disabled' : '';

		if ($this->app->isAdmin())
		{
			$url = 'index.php?option=com_fabrik&task=' . $mode . '.view&formid=' . $formId . '&rowid=';
		}
		else
		{
			$url = 'index.php?option=com_' . $this->package . '&view=' . $mode . '&formid=' . $formId . '&rowid=';
		}

		$links = array();
		$links['first'] = JRoute::_($url . $this->ids->first);
		$links['first-active'] = $linkStartPrev;
		$links['last-active'] = $linkNextEnd;
		$links['prev'] = JRoute::_($url . $this->ids->prev);
		$links['next'] = JRoute::_($url . $this->ids->next);
		$links['last'] = JRoute::_($url . $this->ids->last);

		$layout = new JLayoutFile('plugins.fabrik_form.paginate.layouts.default_paginate', JPATH_SITE);
		$this->data = $layout->render($links);

		Html::stylesheet('plugins/fabrik_form/paginate/paginate.css');
	}

	/**
	 * Get the first last, prev and next record ids
	 *
	 * @return  object
	 */
	protected function getNavIds()
	{
		$formModel = $this->getModel();
		$listModel = $formModel->getListModel();
		$table = $listModel->getTable();
		$db = $listModel->getDb();
		$query = $db->getQuery(true);

		// As we are selecting on primary key we can select all rows - 3000 records load in 0.014 seconds
		$query->select($table->db_primary_key)->from($table->db_table_name);
		$query = $listModel->buildQueryJoin($query);
		$query = $listModel->buildQueryWhere(true, $query);
		$query = $listModel->buildQueryOrder($query);

		foreach ($listModel->orderEls as $orderName)
		{
			$orderName = StringHelper::safeColNameToArrayKey($orderName);
			$query->select(StringHelper::safeColName($orderName) . ' AS ' . $orderName);
		}

		$db->setQuery($query);
		$rows = $db->loadColumn();
		$keys = array_flip($rows);
		$o = new stdClass;
		$o->index = ArrayHelper::getValue($keys, $formModel->getRowId(), 0);
		$o->first = $rows[0];
		$o->lastKey = count($rows) - 1;
		$o->last = $rows[$o->lastKey];
		$o->next = $o->index + 1 > $o->lastKey ? $o->lastKey : $rows[$o->index + 1];
		$o->prev = $o->index - 1 < 0 ? 0 : $rows[$o->index - 1];

		return $o;
	}

	/**
	 * Show we show the pagination
	 *
	 * @return  bool
	 */
	protected function show()
	{
		/* Nobody except form model constructor sets editable property yet -
		 * it sets in view.html.php only and after render() - too late I think
		 * so no pagination output for frontend details view for example.
		 * Let's set it here before use it
		 */
		$params = $this->getParams();
		$formModel = $this->getModel();
		$formModel->checkAccessFromListSettings();
		$where = $params->get('paginate_where');

		switch ($where)
		{
			case 'both':
				return true;
				break;
			case 'form':
				return (bool) $formModel->isEditable() == 1;
				break;
			case 'details':
				return (bool) $formModel->isEditable() == 0;
				break;
		}

		return false;
	}

	/**
	 * Need to do this rather than on onLoad as otherwise in chrome form.js addevents is fired
	 * before auto-complete class ini'd so then the auto-complete class never sets itself up
	 *
	 * @return  void
	 */
	public function onAfterJSLoad()
	{
		$formModel = $this->getModel();
		$params = $this->getParams();

		if (!$this->show())
		{
			return;
		}

		if ($params->get('paginate_ajax') == 0)
		{
			return;
		}

		$input = $this->app->input;
		$opts = new stdClass;
		$opts->liveSite = COM_FABRIK_LIVESITE;
		$opts->view = $input->get('view');
		$opts->ids = $this->ids;
		$opts->pkey = $formModel->getListModel()->getPrimaryKey(true);
		$opts = json_encode($opts);
		$container = $formModel->jsKey();
		$this->formJavascriptClass();
		$formModel->formPluginJS['FabRecordSet'] = "var " . $container . "_paginate = new FabRecordSet($container, $opts);";
	}

	/**
	 * Called from plugins ajax call
	 *
	 * @return  void
	 */
	public function onXRecord()
	{
		$input = $this->app->input;
		$formId = $input->getInt('formid');
		$rowId = $input->get('rowid', '', 'string');
		$mode = $input->get('mode', 'details');

		/** @var \FabrikFEModelForm $model */
		$model = JModelLegacy::getInstance('Form', 'FabrikFEModel');
		$model->setId($formId);
		$this->setModel($model);
		$model->rowId = $rowId;
		$ids = $this->getNavIds();
		$url = COM_FABRIK_LIVESITE . 'index.php?option=com_' . $this->package . '&view=' . $mode . '&formid=' . $formId . '&rowid=' . $rowId . '&format=raw';
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $url);
		$data = curl_exec($ch);
		curl_close($ch);

		// Append the ids to the json array
		$data = json_decode($data);
		$data->ids = $ids;
		echo json_encode($data);
	}
}
