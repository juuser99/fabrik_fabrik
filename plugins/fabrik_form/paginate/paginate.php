<?php
/**
 * Form record next/prev scroll plugin
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.paginate
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Html;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Fabrik\Component\Fabrik\Site\Model\FormModel;
use Fabrik\Component\Fabrik\Site\Plugin\AbstractFormPlugin;
use Joomla\String\StringHelper;
use Fabrik\Helpers\Worker;
use Fabrik\Helpers\StringHelper as FStringHelper;
use Fabrik\Component\Fabrik\Administrator\Model\FabModel;

/**
 * Form record next/prev scroll plugin
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.paginate
 * @since       3.0
 */
class PlgFabrik_FormPaginate extends AbstractFormPlugin
{
	/**
	 * Output
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $data = '';

	/**
	 * Navigation ids.
	 *
	 * @var Object
	 *
	 * @since 4.0
	 */
	protected $ids;

	/**
	 * Inject custom html into the bottom of the form
	 *
	 * @param   int $c Plugin counter
	 *
	 * @return  string  html
	 *
	 * @since 4.0
	 */
	public function getBottomContent_result($c)
	{
		return $this->data;
	}

	/**
	 * Sets up HTML to be injected into the form's bottom
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	public function getBottomContent()
	{
		$formModel = $this->getModel();

		if (!$this->show())
		{
			$this->data = '';

			return;
		}

		$j3            = Worker::j3();
		$input         = $this->app->input;
		$formId        = $formModel->getForm()->id;
		$mode          = StringHelper::strtolower($input->get('view', 'form'));
		$this->ids     = $this->getNavIds();
		$linkStartPrev = $this->ids->index == 0 ? ' disabled' : '';
		$linkNextEnd   = $this->ids->index == $this->ids->lastKey ? ' disabled' : '';

		if ($this->app->isAdmin())
		{
			$url = 'index.php?option=com_fabrik&task=' . $mode . '.view&formid=' . $formId . '&rowid=';
		}
		else
		{
			$url = 'index.php?option=com_' . $this->package . '&view=' . $mode . '&formid=' . $formId . '&rowid=';
		}

		$links                 = array();
		$links['first']        = Route::_($url . $this->ids->first);
		$links['first-active'] = $linkStartPrev;
		$links['last-active']  = $linkNextEnd;
		$links['prev']         = Route::_($url . $this->ids->prev);
		$links['next']         = Route::_($url . $this->ids->next);
		$links['last']         = Route::_($url . $this->ids->last);

		if ($j3)
		{
			$layout     = $this->getLayout('form');
			$this->data = $layout->render($links);
		}
		else
		{
			$firstLink = ($linkStartPrev) ? '<span>&lt;&lt;</span>' . Text::_('COM_FABRIK_START')
				: '<a href="' . $links['first'] . '" class="pagenav paginateFirst ' . $linkStartPrev . '"><span>&lt;&lt;</span>'
				. Text::_('COM_FABRIK_START') . '</a>';
			$prevLink  = ($linkStartPrev) ? '<span>&lt;</span>' . Text::_('COM_FABRIK_PREV')
				: '<a href="' . $links['prev'] . '" class="pagenav paginatePrevious ' . $linkStartPrev . '"><span>&lt;</span>'
				. Text::_('COM_FABRIK_PREV') . '</a>';

			$nextLink   = ($linkNextEnd) ? Text::_('COM_FABRIK_NEXT') . '<span>&gt;</span>'
				: '<a href="' . $links['next'] . '" class="pagenav paginateNext' . $linkNextEnd . '">' . Text::_('COM_FABRIK_NEXT')
				. '<span>&gt;</span></a>';
			$endLink    = ($linkNextEnd) ? Text::_('COM_FABRIK_END') . '<span>&gt;&gt;</span>'
				: '<a href="' . $links['last'] . '" class="pagenav paginateLast' . $linkNextEnd . '">' . Text::_('COM_FABRIK_END')
				. '<span>&gt;&gt;</span></a>';
			$this->data = '<ul id="fabrik-from-pagination" class="pagination">
					<li>' . $firstLink . '</li>
					<li>' . $prevLink . '</li>
					<li>' . $nextLink . '</li>
					<li>' . $endLink . '</li>
			</ul>';
		}

		Html::stylesheet('plugins/fabrik_form/paginate/paginate.css');
	}

	/**
	 * Get the first last, prev and next record ids
	 *
	 * @return  object
	 *
	 * @since 4.0
	 */
	protected function getNavIds()
	{
		$formModel = $this->getModel();
		$listModel = $formModel->getListModel();
		$table     = $listModel->getTable();
		$db        = $listModel->getDb();
		$query     = $db->getQuery(true);

		// As we are selecting on primary key we can select all rows - 3000 records load in 0.014 seconds
		$query->select($table->db_primary_key)->from($table->db_table_name);
		$query = $listModel->buildQueryJoin($query);
		$query = $listModel->buildQueryWhere(true, $query);
		$query = $listModel->buildQueryOrder($query);

		foreach ($listModel->orderEls as $orderName)
		{
			$orderName = FStringHelper::safeColNameToArrayKey($orderName);
			$query->select(FStringHelper::safeColName($orderName) . ' AS ' . $orderName);
		}

		$db->setQuery($query);
		$rows       = $db->loadColumn();
		$keys       = array_flip($rows);
		$o          = new \stdClass;
		$o->index   = FArrayHelper::getValue($keys, $formModel->getRowId(), 0);
		$o->first   = $rows[0];
		$o->lastKey = count($rows) - 1;
		$o->last    = $rows[$o->lastKey];
		$o->next    = $o->index + 1 > $o->lastKey ? $o->lastKey : $rows[$o->index + 1];
		$o->prev    = $o->index - 1 < 0 ? 0 : $rows[$o->index - 1];

		return $o;
	}

	/**
	 * Show we show the pagination
	 *
	 * @return  bool
	 *
	 * @since 4.0
	 */
	protected function show()
	{
		/* Nobody except form model constructor sets editable property yet -
		 * it sets in view.html.php only and after render() - too late I think
		 * so no pagination output for frontend details view for example.
		 * Let's set it here before use it
		 */
		$params    = $this->getParams();
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
	 *
	 * @since 4.0
	 */
	public function onAfterJSLoad()
	{
		$formModel = $this->getModel();
		$params    = $this->getParams();

		if (!$this->show())
		{
			return;
		}

		if ($params->get('paginate_ajax') == 0)
		{
			return;
		}

		$input          = $this->app->input;
		$opts           = new \stdClass;
		$opts->liveSite = COM_FABRIK_LIVESITE;
		$opts->view     = $input->get('view');
		$opts->ids      = $this->ids;
		$opts->pkey     = $formModel->getListModel()->getPrimaryKey(true);
		$opts           = json_encode($opts);
		$container      = $formModel->jsKey();
		$this->formJavascriptClass();
		$formModel->formPluginJS['Paginate'] = "var " . $container . "_paginate = new Paginate($container, $opts);";
	}

	/**
	 * Called from plugins ajax call
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	public function onXRecord()
	{
		$input  = $this->app->input;
		$formId = $input->getInt('formid');
		$rowId  = $input->get('rowid', '', 'string');
		$mode   = $input->get('mode', 'details');
		$model  = FabModel::getInstance(FormModel::class);
		$model->setId($formId);
		$this->setModel($model);
		$model->rowId = $rowId;
		$ids          = $this->getNavIds();
		$url          = COM_FABRIK_LIVESITE . 'index.php?option=com_' . $this->package . '&view=' . $mode . '&formid=' . $formId . '&rowid=' . $rowId . '&format=raw';
		$ch           = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $url);
		$data = curl_exec($ch);
		curl_close($ch);

		// Append the ids to the json array
		$data      = json_decode($data);
		$data->ids = $ids;
		echo json_encode($data);
	}
}
