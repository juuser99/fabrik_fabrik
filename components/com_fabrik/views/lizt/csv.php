<?php
/**
 * CSV Fabrik List view class
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Views\Lizt;

use \Fabrik\Admin\Models\CsvExport;
use \Fabrik\Admin\Models\Lizt;
use \JFilterInput;
use \JResponse as JResponse;
use \JFile as JFile;

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * CSV Fabrik List view class
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.5
 */
class CSV extends Base
{
	/**
	 * Execute and display a template script.
	 *
	 * @throws LengthException
	 *
	 * @return  mixed  A string if successful, otherwise a JError object.
	 */
	public function render()
	{
		$app      = $this->app;
		$input    = $app->input;
		$session  = $this->session;
		$exporter = new CsvExport;
		$filter   = JFilterInput::getInstance();
		$model    = $this->model;

		if (!parent::access($model))
		{
			echo "no access";
			exit;
		}

		$ref = $filter->clean($model->getId());
		$model->setOutPutFormat('csv');
		$exporter->model = $model;
		$input->set('limitstart' . $ref, $input->getInt('start', 0));
		$input->set('limit' . $ref, $exporter->getStep());

		// $$$ rob moved here from csvimport::getHeadings as we need to do this before we get
		// the list total
		$selectedFields = $input->get('fields', array(), 'array');
		$model->setHeadingsForCSV($selectedFields);

		if (empty($model->asfields))
		{
			throw new LengthException('CSV Export - no fields found', 500);
		}

		$request = $model->getRequestData();
		$model->storeRequestData($request);

		$key   = 'fabrik.list.' . $ref . 'csv.total';
		$start = $input->getInt('start', 0);

		// If we are asking for a new export - clear previous total as list may be filtered differently
		if ($start === 0)
		{
			$session->clear($key);
		}

		if (!$session->has($key))
		{
			// Only get the total if not set - otherwise causes memory issues when we downloading
			$total = $model->getTotalRecords();
			$session->set($key, $total);
		}
		else
		{
			$total = $session->get($key);
		}

		if ($start <= $total)
		{
			if ((int) $total === 0)
			{
				$notice      = new stdClass;
				$notice->err = FText::_('COM_FABRIK_CSV_EXPORT_NO_RECORDS');
				echo json_encode($notice);

				return;
			}

			$exporter->writeFile($total);
		}
		else
		{
			$input->set('limitstart' . $ref, 0);

			// Remove the total from the session
			$session->clear($key);
			$this->downloadFile($exporter);
		}

		return;
	}

	/**
	 * Start the download of the completed csv file
	 *
	 * @return null
	 */
	public function downloadFile($exporter)
	{
		// To prevent long file from getting cut off from     //max_execution_time
		//error_reporting(0);
		@set_time_limit(0);
		jimport('joomla.filesystem.file');
		$filename = $exporter->getFileName();
		$filePath = $exporter->getFilePath();
		$document = $this->doc;
		$document->setMimeEncoding('application/zip');

		if (JFile::exists($filePath))
		{
			$str = file_get_contents($filePath);
		}
		else
		{
			// If we cant find the file then don't try to auto download it
			return false;
		}

		JResponse::clearHeaders();
		$encoding = $exporter->getEncoding();

		// Set the response to indicate a file download
		JResponse::setHeader('Content-Type', 'application/zip');
		JResponse::setHeader('Content-Disposition', "attachment;filename=\"" . $filename . "\"");

		// Xls formatting for accents
		if ($exporter->outPutFormat == 'excel')
		{
			JResponse::setHeader('Content-Type', 'application/vnd.ms-excel');
		}

		JResponse::setHeader('charset', $encoding);
		JResponse::setBody($str);
		echo JResponse::toString(false);
		JFile::delete($filePath);

		// $$$ rob 21/02/2012 - need to exit otherwise Chrome give 349 download error
		exit;
	}
}
