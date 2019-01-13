<?php
/**
 * CSV Fabrik List view class
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Component\Fabrik\Site\View\ListView;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Language\Text;
use Fabrik\Component\Fabrik\Administrator\Model\FabrikModel;
use Fabrik\Component\Fabrik\Site\Model\CsvExportModel;
use Fabrik\Component\Fabrik\Site\Model\ListModel;

/**
 * CSV Fabrik List view class
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       4.0
 */
class CsvView extends BaseView
{
	/**
	 * Execute and display a template script.
	 *
	 * @param   string $tpl The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise a JError object.
	 *
	 * @since 4.0
	 */
	public function display($tpl = null)
	{
		$input = $this->app->input;

		/** @var CsvExportModel $exporter */
		$exporter = FabrikModel::getInstance(CsvExportModel::class);

		/** @var ListModel $model */
		$model = FabrikModel::getInstance(ListModel::class);
		$model->setId($input->getInt('listid'));

		if (!parent::access($model))
		{
			exit;
		}

		$model->setOutPutFormat('csv');
		$exporter->model = $model;
		$input->set('limitstart' . $model->getId(), $input->getInt('start', 0));
		$limit = $exporter->getStep();
		$input->set('limit' . $model->getId(), $limit);

		// $$$ rob moved here from csvimport::getHeadings as we need to do this before we get
		// the list total
		$selectedFields = $input->get('fields', array(), 'array');
		$model->setHeadingsForCSV($selectedFields);

		if (empty($model->asfields))
		{
			throw new \LengthException('CSV Export - no fields found', 500);
		}

		$request = $model->getRequestData();
		$model->storeRequestData($request);

		$key   = 'fabrik.list.' . $model->getId() . 'csv.total';
		$start = $input->getInt('start', 0);

		// If we are asking for a new export - clear previous total as list may be filtered differently
		if ($start === 0)
		{
			$this->session->clear($key);
		}

		if (!$this->session->has($key))
		{
			// Only get the total if not set - otherwise causes memory issues when we downloading
			$total = $model->getTotalRecords();
			$this->session->set($key, $total);
		}
		else
		{
			$total = $this->session->get($key);
		}

		if ((int) $total === 0)
		{
			$notice      = new \stdClass;
			$notice->err = Text::_('COM_FABRIK_CSV_EXPORT_NO_RECORDS');
			echo json_encode($notice);

			return;
		}

		if ($start < $total)
		{
			$download    = (bool) $input->getInt('download', true);
			$canDownload = ($start + $limit >= $total) && $download;
			$exporter->writeFile($total, $canDownload);

			if ($canDownload)
			{
				$this->download($model, $exporter, $key);
			}
		}
		else
		{
			$this->download($model, $exporter, $key);
		}

		return;
	}

	/**
	 * Start the download process
	 *
	 * @param   ListModel      $model
	 * @param   CsvExportModel $exporter
	 * @param   string         $key
	 *
	 * @throws \Exception
	 *
	 * @since 4.0
	 */
	protected function download(ListModel $model, CsvExportModel $exporter, $key)
	{
		$input = $this->app->input;
		$input->set('limitstart' . $model->getId(), 0);

		// Remove the total from the session
		$this->session->clear($key);
		$exporter->downloadFile();
	}
}
