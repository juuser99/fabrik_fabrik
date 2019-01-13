<?php
/**
 * Allow processing of CSV import / export on a per row basis
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.listcsv
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Component\Fabrik\Site\Plugin\AbstractListPlugin;
use Joomla\CMS\Filter\InputFilter;
use Fabrik\Helpers\Worker;

/**
 * Allow processing of CSV import / export on a per row basis
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.listcsv
 * @since       3.0
 */
class PlgFabrik_ListListcsv extends AbstractListPlugin
{
	/**
	 * for use by user code
	 *
	 * @since 4.0
	 */
	public $userClass = null;

	/**
	 * for use by user code
	 *
	 * @since 4.0
	 */
	public $userData = null;

	/**
	 * determine if the table plugin is a button and can be activated only when rows are selected
	 *
	 * @return bool
	 *
	 * @since 4.0
	 */
	public function canSelectRows()
	{
		return false;
	}

	/**
	 * Prep the button if needed
	 *
	 * @param   array  &$args Arguments
	 *
	 * @return  bool
	 *
	 * @since 4.0
	 */
	public function button(&$args)
	{
		parent::button($args);

		return false;
	}

	/**
	 * Called when we import a csv row
	 *
	 * @return boolean
	 *
	 * @since 4.0
	 */
	public function onImportCSVRow()
	{
		$params = $this->getParams();
		$filter = InputFilter::getInstance();
		$file   = $params->get('listcsv_import_php_file');
		$file   = $filter->clean($file, 'CMD');

		if ($file != -1 && $file != '')
		{

			require JPATH_ROOT . '/plugins/fabrik_list/listcsv/scripts/' . $file;
		}

		$code = trim($params->get('listcsv_import_php_code', ''));

		if (!empty($code))
		{
			$ret = @eval($code);
			Worker::logEval($ret, 'Caught exception on eval in onImportCSVRow : %s');

			if ($ret === false)
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Called after we import a csv row
	 *
	 * @return boolean
	 *
	 * @since 4.0
	 */
	public function onAfterImportCSVRow()
	{
		$params = $this->getParams();
		$filter = InputFilter::getInstance();
		$file   = $params->get('listcsv_after_import_php_file');
		$file   = $filter->clean($file, 'CMD');

		if ($file != -1 && $file != '')
		{

			require JPATH_ROOT . '/plugins/fabrik_list/listcsv/scripts/' . $file;
		}

		$code = trim($params->get('listcsv_after_import_php_code', ''));

		if (!empty($code))
		{
			$ret = @eval($code);
			Worker::logEval($ret, 'Caught exception on eval in onAfterImportCSVRow : %s');

			if ($ret === false)
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Called when import is complete
	 *
	 * @return boolean
	 *
	 * @since 4.0
	 */
	public function onCompleteImportCSV()
	{
		$params = $this->getParams();
		$filter = InputFilter::getInstance();
		$file   = $params->get('listcsv_import_complete_php_file');
		$file   = $filter->clean($file, 'CMD');

		if ($file != -1 && $file != '')
		{

			require JPATH_ROOT . '/plugins/fabrik_list/listcsv/scripts/' . $file;
		}

		$code = trim($params->get('listcsv_import_complete_php_code', ''));

		if (!empty($code))
		{
			$ret = @eval($code);
			Worker::logEval($ret, 'Caught exception on eval in onCompleteImportCSV : %s');

			if ($ret === false)
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Called before import is started
	 *
	 * @return boolean
	 *
	 * @since 4.0
	 */
	public function onStartImportCSV()
	{
		$params = $this->getParams();
		$filter = InputFilter::getInstance();
		$file   = $params->get('listcsv_import_start_php_file');
		$file   = $filter->clean($file, 'CMD');

		if ($file != -1 && $file != '')
		{

			require JPATH_ROOT . '/plugins/fabrik_list/listcsv/scripts/' . $file;
		}

		$code = trim($params->get('listcsv_import_start_php_code', ''));

		if (!empty($code))
		{
			$ret = @eval($code);
			Worker::logEval($ret, 'Caught exception on eval in onStartImportCSV : %s');

			if ($ret === false)
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Called when we import a csv row
	 *
	 * As PHP doesn't support pass by reference for func_get_args, can't pass heading array in
	 * as an arg, so plugin must modify $listModel->cavExportRow
	 *
	 * @return boolean
	 *
	 * @since 4.0
	 */
	public function onExportCSVRow()
	{
		$listModel = $this->getModel();
		$params    = $this->getParams();
		$filter    = InputFilter::getInstance();
		$file      = $params->get('listcsv_export_php_file');
		$file      = $filter->clean($file, 'CMD');

		if ($file != -1 && $file != '')
		{
			require JPATH_ROOT . '/plugins/fabrik_list/listcsv/scripts/' . $file;
		}

		$code = trim($params->get('listcsv_export_php_code', ''));

		if (!empty($code))
		{
			$ret = @eval($code);
			Worker::logEval($ret, 'Caught exception on eval in onExportCSVRow : %s');

			if ($ret === false)
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Called when we export the csv headings
	 *
	 * As PHP doesn't support pass by reference for func_get_args, can't pass heading array in
	 * as an arg, so plugin musr modify $listModel->cavExportHeadings
	 *
	 * @return boolean
	 *
	 * @since 4.0
	 */
	public function onExportCSVHeadings()
	{
		$listModel = $this->getModel();
		$params    = $this->getParams();
		$filter    = InputFilter::getInstance();
		$file      = $params->get('listcsv_export_headings_php_file');
		$file      = $filter->clean($file, 'CMD');

		if ($file != -1 && $file != '')
		{
			require JPATH_ROOT . '/plugins/fabrik_list/listcsv/scripts/' . $file;
		}

		$code = trim($params->get('listcsv_export_headings_php_code', ''));

		if (!empty($code))
		{
			$ret = @eval($code);
			Worker::logEval($ret, 'Caught exception on eval in onExportCSVHeadings : %s');

			if ($ret === false)
			{
				return false;
			}
		}

		return true;
	}
}
