<?php
/**
 * View to make ajax json object reporting csv file creation progress.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2018  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Component\Fabrik\Administrator\View\ListView;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\FormView as BaseHtmlView;
use Fabrik\Component\Fabrik\Administrator\Model\FabModel;
use Fabrik\Component\Fabrik\Site\Model\CsvExportModel;
use Fabrik\Component\Fabrik\Site\Model\ListModel;

/**
 * View to make ajax json object reporting csv file creation progress.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       4.0
 */
class CsvView extends BaseHtmlView
{
	/**
	 * Display the list
	 *
	 * @param   string $tpl Template
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	public function display($tpl = null)
	{
		$app      = Factory::getApplication();
		$session  = $app->getSession();
		$input    = $app->input;
		$exporter = FabModel::getInstance(CsvExportModel::class);
		$model    = FabModel::getInstance(ListModel::class);
		$model->setId($input->getInt('listid'));
		$model->setOutPutFormat('csv');
		$exporter->model = $model;
		$input->set('limitstart' . $model->getId(), $input->getInt('start', 0));
		$input->set('limit' . $model->getId(), $exporter->getStep());

		// $$$ rob moved here from csvimport::getHeadings as we need to do this before we get
		// the table total
		$selectedFields = $input->get('fields', array(), 'array');
		$model->setHeadingsForCSV($selectedFields);

		$total = $model->getTotalRecords();

		$key = 'fabrik.list.' . $model->getId() . 'csv.total';

		if (is_null($session->get($key)))
		{
			$session->set($key, $total);
		}

		$start = $input->getInt('start', 0);

		if ($start < $total)
		{
			$exporter->writeFile($total);
		}
		else
		{
			$session->clear($key);
			$exporter->downloadFile();
		}

		return;
	}
}
