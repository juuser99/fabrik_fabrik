<?php
/**
 * Import CSV class
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Joomla\Component\Fabrik\Site\Model;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Worker;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Path;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Fabrik\Administrator\Model\FabModel;
use Joomla\Component\Fabrik\Site\CSV\CsvParser;
use Joomla\String\StringHelper;
use Fabrik\Helpers\ArrayHelper as FArrayHelper;
use Fabrik\Helpers\StringHelper as FStringHelper;

/**
 * Import CSV class
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       4.0
 */
class ImportCsvModel extends FabModel
{
	/**
	 * Cleaned heading names
	 *
	 * @var array
	 *
	 * @since 4.0
	 */
	public $headings = null;

	/**
	 * CSV data
	 *
	 * @var array
	 *
	 * @since 4.0
	 */
	public $data = null;

	/**
	 * List of new headings found in csv file when importing
	 *
	 * @var array
	 *
	 * @since 4.0
	 */
	public $newHeadings = array();

	/**
	 * List of matched headings found in csv file when importing
	 *
	 * @var array
	 *
	 * @since 4.0
	 */
	public $matchedHeadings = array();

	/**
	 * Used to store the heading key for any heading deselected on admin import into a new list
	 *
	 * @var array
	 *
	 * @since 4.0
	 */
	protected $unmatchedKeys = array();

	/**
	 * List's join objects
	 *
	 * @var array
	 *
	 * @since 4.0
	 */
	public $joins = null;

	/**
	 * List model to import into
	 *
	 * @var ListModel
	 *
	 * @since 4.0
	 */
	public $listModel = null;

	/**
	 * Number of records added
	 *
	 * @var int
	 *
	 * @since 4.0
	 */
	public $updatedCount = 0;

	/**
	 * CSV file name
	 *
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $csvFile = null;

	/**
	 * Delimiter to split data by
	 *
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $fieldDelimiter = null;

	/**
	 * Directory to which the csv file is imported
	 *
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $baseDir = null;

	/**
	 * Import the csv file
	 *
	 * @return  boolean
	 *
	 * @since 4.0
	 */
	public function import()
	{
		$this->readCSV($this->getCSVFileName());
		$this->findExistingElements();
		$this->setSession();

		return true;
	}

	/**
	 * Gets the name of the csv file from the uploaded jForm
	 *
	 * @return string csv file name
	 *
	 * @since 4.0
	 */
	public function getCSVFileName()
	{
		if (is_null($this->csvFile))
		{
			$session = Factory::getApplication()->getSession();

			if ($session->has('com_fabrik.csv.filename'))
			{
				$this->csvFile = $session->get('com_fabrik.csv.filename');
			}
			else
			{
				$this->csvFile = 'fabrik_csv_' . md5(uniqid());
				$session->set('com_fabrik.csv.filename', $this->csvFile);
			}
		}

		return $this->csvFile;
	}

	/**
	 * Loads the Joomla form for importing the csv file
	 *
	 * @param   array $data     form data
	 * @param   bool  $loadData load form data
	 *
	 * @return  object    form
	 *
	 * @since 4.0
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		Form::addFormPath(COM_FABRIK_BASE . 'administrator/components/com_fabrik/models/forms');
		$form = $this->loadForm('com_fabrik.import', 'import', array('control' => 'jform', 'load_data' => $loadData));

		if (empty($form))
		{
			return false;
		}

		$form->model = $this;

		return $form;
	}

	/**
	 * Checks uploaded file, and uploads it
	 *
	 * @throws \Exception
	 *
	 * @return  true  csv file uploaded ok, false error (JError warning raised)
	 *
	 * @since 4.0
	 */
	public function checkUpload()
	{
		/* Track errors message- so if from frontend menu redirect 
		    to current url rather than throwing exception
		 */
		$errmsg = '';
		$app      = Factory::getApplication();
		$input    = $app->input;

		if (!(bool) ini_get('file_uploads'))
		{
            $errmsg = Text::_('COM_FABRIK_ERR_UPLOADS_DISABLED');
            $userFile = false;
		}
		else
		{

		    $userFile = $input->files->get('jform');
		}

		if (!$userFile)
		{
			if($errmsg == '')
			{
				$errmsg = Text::_('COM_FABRIK_IMPORT_CSV_NO_FILE_SELECTED');
			}
		} else {
    		jimport('joomla.filesystem.file');
            $allowedlist = Worker::getMenuOrRequestVar('csv_import_extensions','',false,'menu');
            $allowed = empty($allowedlist) ? array('txt','csv','tsv') : explode(',',$allowedlist);
            $ext = File::getExt($userFile['userfile']['name']);

			if (!in_array($ext, $allowed))
		    {
		        $errmsg = 'Import Failed! Invalid file format ('.$ext.'). Valid formats are ('.implode(', ',$allowed).')';
			}
			else
			{
        		$tmp_name  = $this->getCSVFileName();
		        $tmp_dir   = $this->getBaseDir();
		        $to        = Path::clean($tmp_dir . '/' . $tmp_name);
		        $resultDir = File::upload($userFile['userfile']['tmp_name'], $to);

		        if ($resultDir == false && !File::exists($to))
		        {
		            $errmsg = Text::_('Upload Error');	
		        }
		        else
		        {
		            $listid = $input->getInt('listid');
		            // Allows user-created post-processing script to be (optionally) run 
                    if (file_exists(JPATH_PLUGINS.'/fabrik_list/listcsv/scripts/list_'.$listid.'_csv_import.php'))
                    {
                        require(JPATH_PLUGINS.'/fabrik_list/listcsv/scripts/list_'.$listid.'_csv_import.php');
                    }				
		        }  
    		}
		}
        
        if (!empty($errmsg))
		{
            // If from frontend menu redirect back to list with displayed error message, else throw exception
            if(Worker::getMenuOrRequestVar('csv_import_extensions','',false,'menu') == '')
            {
                throw new \Exception(Text::_($errmsg));
            }
            else
            {
                $cururl = Uri::getInstance();
                $app = Factory::getApplication();
                $app->redirect($cururl, $errmsg, 'error');
            }    
		}    		

		return true;
	}

	/**
	 * Get the field delimiter from post
	 * and set in session 'com_fabrik.csv.fielddelimiter' for later use
	 *
	 * @return  string    delimiter character
	 *
	 * @since 4.0
	 */
	protected function getFieldDelimiter()
	{
		$data = $this->getFormData();

		if (is_null($this->fieldDelimiter))
		{
			$this->fieldDelimiter = ',';
			$session              = Factory::getApplication()->getSession();

			if ($session->has('com_fabrik.csv.fielddelimiter'))
			{
				$this->fieldDelimiter = $session->get('com_fabrik.csv.fielddelimiter');
			}

			$tabDelimiter         = FArrayHelper::getValue($data, 'tabdelimited');
			$this->fieldDelimiter = $tabDelimiter == 1 ? "\t" : FArrayHelper::getValue($data, 'field_delimiter', $this->fieldDelimiter);
			$session->set('com_fabrik.csv.fielddelimiter', $this->fieldDelimiter);
		}

		return $this->fieldDelimiter;
	}

	/**
	 * Get form data
	 *
	 * @return  array
	 *
	 * @since 4.0
	 */
	protected function getFormData()
	{
		$app    = Factory::getApplication();
		$filter = InputFilter::getInstance();
		$post   = $filter->clean($_POST, 'array');

		return $app->input->get('jform', $post, 'array');
	}

	/**
	 * Read the CSV file, store results in $this->headings and $this->data
	 *
	 * @param   string $file to read
	 *
	 * @return null
	 *
	 * @since 4.0
	 */
	public function readCSV($file)
	{
		$baseDir         = $this->getBaseDir();
		$this->headings  = array();
		$this->data      = array();
		$data            = $this->getFormData();
		$field_delimiter = $this->getFieldDelimiter();
		$text_delimiter  = stripslashes(FArrayHelper::getValue($data, 'text_delimiter', '"'));

		if (!File::exists($baseDir . '/' . $file))
		{
			throw new \UnexpectedValueException('Csv file : ' . $baseDir . '/' . $file . ' not found');
		}

		$origLineEnding = ini_get("auto_detect_line_endings");
		ini_set("auto_detect_line_endings", true);
		$origMaxExecution = ini_get("max_execution_time");
		ini_set("max_execution_time", 300);

		$csv              = new CsvParser($baseDir . '/' . $file, $field_delimiter, $text_delimiter, '\\');
		$csv->inPutFormat = FArrayHelper::getValue($data, 'inPutFormat', 'csv');

		// Will skip empty rows. TRUE by default. (Shown here for example only).
		$csv->SkipEmptyRows(true);

		// Remove leading and trailing \s and \t. TRUE by default.
		$csv->TrimFields(true);

		while ($row = $csv->NextLine())
		{
			if (empty($this->headings))
			{
				$this->sanitizeHeadings($row);

				if (!$this->getSelectKey())
				{
					// If no table loaded and the user asked to automatically add a key then put id at the beginning of the new headings
					$idHeading = 'id';

					if (in_array($idHeading, $row))
					{
						$idHeading .= rand(0, 9);
					}

					array_unshift($row, $idHeading);
				}

				$this->headings = $row;
			}
			else
			{
				if (function_exists('iconv'))
				{
					foreach ($row as &$d)
					{
						/**
						 * strip any none utf-8 characters from the import data
						 * if we don't do this then the site's session is destroyed and you are logged out
						 */
						$d = iconv("utf-8", "utf-8//IGNORE", $d);
					}
				}

				if (!$this->getSelectKey())
				{
					array_unshift($row, '');
				}
				
				// In admin import the user has deselected some columns for import. Remove them from the row
				if (!empty($this->unmatchedKeys))
				{
					$row = array_diff_key($row , $this->unmatchedKeys);
					$row = array_values($row);
				}

				if (count($row) == 1 && $row[0] == '')
				{
					// CSV import from excel saved as unicode has blank record @ end
				}
				else
				{
					$this->data[] = $row;
				}
			}
		}

		fclose($csv->mHandle);

		ini_set("auto_detect_line_endings", $origLineEnding);
		ini_set("max_execution_time", $origMaxExecution);
	}

	/**
	 * sanitize Headings
	 *
	 * @param  array &$row
	*
	 * @return void
	 *
	 * @since 4.0
	 */
	private function sanitizeHeadings(&$row)
	{
		$model       = $this->getListModel();
		$tableParams = $model->getParams();
		// note that when creating a new list via import, this will default to 0 (and get cleaned)
		$mode        = $tableParams->get('csvfullname', '0');

		foreach ($row as $key => &$heading)
		{
			// Remove UFT8 Byte-Order-Mark if present

			/*
			 * $$$ hugh - for some bizarre reason, this code was stripping the first two characters of the heading
			 * on one of my client sites, so "Foo Bar" was becoming "o_Bar" if the CSV had a BOM.  So I'm experimenting with just using a str_replace,
			 * which works on the CSV I'm having issues with.  I've left the original code in place as belt-and-braces.
			 */
			$heading = str_replace("\xEF\xBB\xBF", '', $heading);
			$bom     = pack("CCC", 0xef, 0xbb, 0xbf);

			if (0 === strncmp($heading, $bom, 3))
			{
				$heading = StringHelper::substr($heading, 3);
			}

			if ($mode == '0')
			{
				/*
				 * $$$ hugh - if we are creating a list from a CSV import, we need to
				 * totally sanitize the name into a valid element name.  So clean it, boil consecutive
				 * __ down to single (avoid accidentally getting a ___ in the name), and remove leading
				 * and trailing _.
				 *
				 * Also do this if mode is 0.
				 *
				 * Possible gotcha if they have element names with double __ created by hand.
				 */
				$heading = FStringHelper::clean($heading);
				$heading = preg_replace('/__+/', '_', $heading);
				$heading = trim($heading, '_');
			}
			else if ($mode != 2)
			{
				// $$$ rob replacing with this as per thread - http://fabrikar.com/forums/showthread.php?p=83304
				// $heading = str_replace(' ', '_', $heading);

			}

			if (!empty($this->matchedHeadings) && !in_array($heading, $this->matchedHeadings))
			{
				$this->unmatchedKeys[$key] = 1;
				unset($row[$key]);

			}

		}

		$row = array_values($row);
	}

	/**
	 * Return the first line of the imported data
	 *
	 * @return  array
	 *
	 * @since 4.0
	 */
	public function getSample()
	{
		return $this->data[0];
	}

	/**
	 * Possibly setting large data in the session is a bad idea
	 *
	 * @deprecated
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	public function setSession()
	{
		$session = Factory::getApplication()->getSession();
		$session->set('com_fabrik.csvdata', $this->data);
		$session->set('com_fabrik.matchedHeadings', $this->matchedHeadings);
	}

	/**
	 * Get the directory to which the csv file is imported
	 *
	 * @return  string    path
	 *
	 * @since 4.0
	 */
	protected function getBaseDir()
	{
		if (!isset($this->baseDir))
		{
			/** @var CMSApplication $app */
			$app = Factory::getApplication();
			$config        = $app->getConfig();
			$tmp_dir       = $config->get('tmp_path');
			$this->baseDir = Path::clean($tmp_dir);
		}

		return $this->baseDir;
	}

	/**
	 * Used by import csv cron plugin to override default base dir location
	 *
	 * @param   string $dir (folder path)
	 *
	 * @since    3.0.3.1
	 *
	 * @return  void
	 */
	public function setBaseDir($dir)
	{
		$this->baseDir = $dir;
	}

	/**
	 * Deletes the csv file and optionally removes its path from the session
	 *
	 * @param   bool $clearSession should we clear the session
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	public function removeCSVFile($clearSession = true)
	{
		$baseDir       = $this->getBaseDir();
		$userFile_path = $baseDir . '/' . $this->getCSVFileName();

		if (File::exists($userFile_path))
		{
			File::delete($userFile_path);
		}

		if ($clearSession)
		{
			$this->clearSession();
		}
	}

	/**
	 * Clear session
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	public function clearSession()
	{
		$session = Factory::getApplication()->getSession();
		$session->clear('com_fabrik.csv.filename');
		$session->clear('com_fabrik.csv.fielddelimiter');
	}

	/**
	 * Get the list model
	 *
	 * @return ListModel List model
	 *
	 * @since 4.0
	 */
	public function getListModel()
	{
		$app = Factory::getApplication();

		if (!isset($this->listModel))
		{
			/** @var ListModel listModel */
			$this->listModel = FabModel::getInstance(ListModel::class);
			$this->listModel->setId($app->input->getInt('listid'));
		}

		return $this->listModel;
	}

	/**
	 * Determine if the imported data has existing correlating elements
	 *
	 * @return  null
	 *
	 * @since 4.0
	 */
	public function findExistingElements()
	{
		$model = $this->getListModel();
		$model->getFormGroupElementData();

		/** @var PluginManagerModel $pluginManager */
		$pluginManager = FabModel::getInstance(PluginManagerModel::class);
		$pluginManager->getPlugInGroup('list');
		$formModel   = $model->getFormModel();
		$tableParams = $model->getParams();
		$mode        = $tableParams->get('csvfullname');
		$intKey      = 0;
		$groups      = $formModel->getGroupsHiarachy();
		$elementMap  = array();

		// $$ Phil - Get 'Show in List' elements from menu (if 'use show in list') or set as empty array if not
		$use_sil = Worker::getMenuOrRequestVar('csv_import_sil_only', '0', false, 'menu');
		$list_elements = $use_sil ? Worker::getMenuOrRequestVar('list_elements', '', false, 'menu') : '';
		$showinlist = !empty($list_elements) ? json_decode($list_elements, 1) : array();

		if(!empty($showinlist))
		{
			$showinlist = $showinlist['show_in_list'];
		}
        
		// $$ hugh - adding $rawMap so we can tell prepareCSVData() if data is already raw
		$rawMap = array();

		foreach ($this->headings as $heading)
		{
			$found = false;

			foreach ($groups as $groupModel)
			{
				$elementModels = $groupModel->getMyElements();

				foreach ($elementModels as $elementModel)
				{
					$element = $elementModel->getElement();

                    // $$ Phil - Only include elements set in menu show in list, or if not set at all
                    $elid = (int) $element->id;

					if(empty($showinlist) || in_array($elid,$showinlist)) {

					    switch ($mode)
					    {
						    case 0:
							    $name = $element->name;
							    break;
						    case 1:
    							$name = $elementModel->getFullName(false, false);
	    						break;
		    				case 2:
			    				$name = $element->label;
				    			break;
					    }

					    $paramsKey = $elementModel->getFullName(false, false);

					    if (StringHelper::strtolower(trim($heading)) == StringHelper::strtolower(trim($name)))
					    {
					    	if (!array_key_exists($paramsKey, $this->matchedHeadings))
						    {
							    // Heading found in table
							    $this->matchedHeadings[$paramsKey]         = $element->name;
							    $this->aUsedElements[strtolower($heading)] = $elementModel;
							    $elementMap[$intKey]                       = clone ($elementModel);
							    $rawMap[$intKey]                           = false;
							    $found                                     = true;

							    // Break out of the group foreach
							    break;
						    }
					    }

					    $paramsKey .= '_raw';

					    if (StringHelper::strtolower(trim($heading)) == StringHelper::strtolower(trim($name)) . '_raw')
					    {
						    if (!array_key_exists($paramsKey, $this->matchedHeadings))
						    {
							    // Heading found in table
							    $this->matchedHeadings[$paramsKey]                  = $element->name . '_raw';
							    $this->aUsedElements[strtolower($heading) . '_raw'] = $elementModel;
							    $found                                              = true;
							    $elementMap[$intKey]                                = clone ($elementModel);
							    $rawMap[$intKey]                                    = true;

							    // Break out of the group foreach
							    break;
						    }
					    }

					    // Joined element params
					    if ($elementModel->isJoin())
					    {
						    $paramsKey = $elementModel->getJoinParamsKey();
						    $idKey     = $elementModel->getJoinIdKey();

    						if ($paramsKey === $heading || $idKey === $heading)
	    					{
		    					if (!array_key_exists($paramsKey, $this->matchedHeadings))
			    				{
				    				$found = true;

					    			// Break out of the group foreach
						    		break;
							    }
						    }
					    }
				    }
				}    
			}

			// Moved after repeat group otherwise elements in second group are never found
			if (!$found && !in_array($heading, $this->newHeadings) && trim($heading) !== '')
			{
				$this->newHeadings[] = $heading;
			}

			$intKey++;
		}

		foreach ($elementMap as $key => $elementModel)
		{
			$element = $elementModel->getElement();
			$elementModel->prepareCSVData($this->data, $key, $rawMap[$key]);
		}
	}

	/**
	 * Work out which published elements are not included
	 *
	 * @return array element models whose defaults should be added to each of the imported
	 * data's array. Keyed on element name.
	 *
	 * @since 4.0
	 */
	protected function defaultsToAdd()
	{
		$model         = $this->getListModel();
		$elements      = $model->getElements();
		$defaultsToAdd = array();
		$elementKeys   = array_keys($elements);

		foreach ($elementKeys as $e)
		{
			$e2 = str_replace('`', '', $e);

			if (!array_key_exists($e2, $this->matchedHeadings) && !array_key_exists($e2 . '_raw', $this->matchedHeadings))
			{
				$elementModel                                           = $elements[$e];
				$defaultsToAdd[FStringHelper::safeColNameToArrayKey($e)] = $elementModel;
			}
		}

		return $defaultsToAdd;
	}

	/**
	 * Insert data into a fabrik table
	 *
	 * @deprecated use insertData instead
	 *
	 * @return null
	 *
	 * @since 4.0
	 */
	public function makeTableFromCSV()
	{
		$this->insertData();
	}

	/**
	 * Insert data into a Fabrik list
	 *
	 * @return null
	 *
	 * @since 4.0
	 */
	public function insertData()
	{
		$origMaxExecution = ini_get("max_execution_time");
		ini_set("max_execution_time", 300);

		$app                 = Factory::getApplication();
		$jForm               = $app->input->get('jform', array(), 'array');
		
		// Default to menu / request, allow override by UI (jform) options
        $dropData = Worker::getMenuOrRequestVar('csv_import_dropdata', '0', false, 'menu');
        $dropData = (int) FArrayHelper::getValue($jForm, 'drop_data', $dropData);

        $overWrite = Worker::getMenuOrRequestVar('csv_import_overwrite', '0', false, 'menu');
        $overWrite = (int) FArrayHelper::getValue($jForm, 'overwrite', $overWrite);
		
		$model               = $this->getListModel();
		$model->importingCSV = true;
		$formModel           = $model->getFormModel();

		// $$$ rob 27/17/212 we need to reset the form as it was first generated before its elements were created.
		$formModel->reset();

		Worker::getPluginManager(true)->runPlugins('onStartImportCSV', $model, 'list');

		if ($dropData && $model->canEmpty())
		{
			$model->truncate();
		}

		$item        = $model->getTable();
		$tableParams = $model->getParams();
		$csvFullName = $tableParams->get('csvfullname', 0);

		$key = FStringHelper::shortColName($item->db_primary_key);

		// Get a list of existing primary key values
		$db    = $model->getDb();
		$query = $db->getQuery(true);
		$query->select($item->db_primary_key)->from($item->db_table_name);
		$db->setQuery($query);
		$aExistingKeys = $db->loadColumn();
		$this->addedCount = 0;
		$updatedCount     = 0;

		// $$$ rob we are no longer removing the element joins from $joins
		// so lets see if any of $joins are table joins.
		$tableJoinsFound = $this->tableJoinsFound();

		$joinData      = array();
		$defaultsToAdd = $this->defaultsToAdd();

		foreach ($this->data as $data)
		{
			$aRow  = array();
			$pkVal = null;
			$i     = 0;

			foreach ($this->matchedHeadings as $headingKey => $heading)
			{
				switch ($csvFullName)
				{
					case 0:
						break;
					case 1:
						$heading = explode('.', $heading);
						$heading = array_pop($heading);
						break;
					case 2:
						break;
				}

				// Test _raw key and use that
				if (StringHelper::substr($heading, StringHelper::strlen($heading) - 4, StringHelper::strlen($heading)) == '_raw')
				{
					$pkTestHeading = StringHelper::substr($heading, 0, StringHelper::strlen($heading) - 4);
				}
				else
				{
					$pkTestHeading = $heading;
				}
				/*
				 * $$$rob isset($pkVal) because: It could be that you have two elements (short names) with the
				 * same name (if trying to import joined data, in this case I'm
				 * presuming that the master table's pkval is the first one you come to
				 */

				if ($pkTestHeading == $key && !isset($pkVal))
				{
					$pkVal = $data[$i];
				}

				$aRow[str_replace('.', '___', $headingKey)] = $data[$i];
				$i++;
			}

            /* $$ Phil moved down. Why would you addDefaults unless you were adding a new row???
             * If not new row, and not drop_data and/or if overwrite, this would overwrite 
             * existing fields that are not included in the import data with their default value!
             */
            // $this->addDefaults($aRow);
            
			$model->getFormGroupElementData();
			$this->setRawDataAsPriority($aRow);

			if ($overWrite && in_array($pkVal, $aExistingKeys))
			{
				$formModel->rowId = $pkVal;
				$updatedCount++;
				$model->csvOverwriting = true;
			}
			else
			{
			    // $$ Phil - Moved from above
			    $this->addDefaults($aRow);
			    
				if ($item->auto_inc)
				{
					// If not overwriting ensure the any existing PK's are removed and the form rowId set to ''
					$pk    = FStringHelper::safeColNameToArrayKey($item->db_primary_key);
					$rawPk = $pk . '_raw';
					unset($aRow[$pk]);
					unset($aRow[$rawPk]);
					$formModel->rowId = '';
					$formModel->setInsertId('');
					$model->csvOverwriting = false;
				}
				else
				{
					// If not auto-inc then we should keep the rowid value
					// but set the form model rowId to '' to enable inserts
					$formModel->rowId = '';

					// Set to true to avoid list model unsetting pk value
					$model->csvOverwriting = true;
				}

				$this->addedCount++;

			}

			// $$$ rob - if raw and none raw or just raw found then insert the raw data
			// into the none raw key. Otherwise if just importing raw data no data stored
			foreach ($aRow as $k => $val)
			{
				if (StringHelper::substr($k, StringHelper::strlen($k) - 4, StringHelper::strlen($k)) == '_raw')
				{
					$noneraw        = StringHelper::substr($k, 0, strlen($k) - 4);
					$aRow[$noneraw] = $val;
				}
			}

			if (!$tableJoinsFound)
			{
				$formModel->formData = $formModel->formDataWithTableName = $aRow;

				if (!in_array(false, Worker::getPluginManager(true)->runPlugins('onImportCSVRow', $model, 'list')))
				{
					$rowid = $formModel->processToDB();
					Worker::getPluginManager(true)->runPlugins('onAfterImportCSVRow', $model, 'list');
				}
			}
			else
			{
				// Merge multi line csv into one entry & defer till we've passed everything
				$joinData = $this->_fakeJoinData($joinData, $aRow, $pkVal, $formModel);
			}
		}

		if ($tableJoinsFound)
		{
			$this->insertJoinedData($joinData);
		}

		$this->removeCSVFile();
		$this->updatedCount = $updatedCount;

		Worker::getPluginManager(true)->runPlugins('onCompleteImportCSV', $model, 'list');

		ini_set('max_execution_time', $origMaxExecution);
	}

	/**
	 * Add in per row default values for missing elements
	 *
	 * @param   array &$aRow Import CSV data
	 *
	 * @since 4.0
	 */
	private function addDefaults(&$aRow)
	{
		$defaultsToAdd = $this->defaultsToAdd();

		foreach ($defaultsToAdd as $k => $elementModel)
		{
			/* Added check as defaultsToAdd ALSO contained element keys for those elements which
			 * are created from new csv columns, which previously didn't exist in the list
			 */
			if (!array_key_exists($k, $aRow))
			{
				$aRow[$k] = $elementModel->getDefaultValue($aRow);
			}

			if (!array_key_exists($k . '_raw', $aRow))
			{
				$aRow[$k . '_raw'] = $aRow[$k];
			}
		}
	}

	/**
	 * Take any _raw values and replace their real elements with their data
	 *
	 * @param   array &$aRow Importing CSV Data
	 *
	 * @since 4.0
	 */
	private function setRawDataAsPriority(&$aRow)
	{
		foreach ($aRow as $k => $val)
		{
			if (StringHelper::substr($k, StringHelper::strlen($k) - 4, StringHelper::strlen($k)) == '_raw')
			{
				$noneraw = StringHelper::substr($k, 0, StringHelper::strlen($k) - 4);

				if (array_key_exists($noneraw, $aRow))
				{
					// Complete madness for encoding issue with fileupload ajax + single upload max
					preg_match('/params":"(.*)"\}\]/', $val, $matches);

					if (count($matches) == 2)
					{
						$replace = addSlashes($matches[1]);
						$val     = preg_replace('/params":"(.*)\}\]/', 'params":"' . $replace . '"}]', $val, -1, $c);
					}
					$aRow[$noneraw] = $val;
					unset($aRow[$k]);
				}
			}
		}
	}

	/**
	 * Does the list contain table joins
	 *
	 * @return boolean
	 *
	 * @since 4.0
	 */
	private function tableJoinsFound()
	{
		$found = false;
		$joins = $this->getJoins();

		for ($x = 0; $x < count($joins); $x++)
		{
			if ((int) $joins[$x]->list_id !== 0 && $joins[$x]->element_id === 0)
			{
				$found = true;
			}
		}

		return $found;
	}

	/**
	 * Get the update message to show the user, # elements added, rows update and rows added
	 *
	 * @since   3.0.8
	 *
	 * @return  string
	 */
	public function updateMessage()
	{
		$elementsCreated = $this->countElementsCreated();

		if ($elementsCreated == 0)
		{
			$msg = Text::sprintf('COM_FABRIK_CSV_ADDED_AND_UPDATED', $this->addedCount, $this->updatedCount);
		}
		else
		{
			$msg = Text::sprintf('COM_FABRIK_CSV_ADD_ELEMENTS_AND_RECORDS_AND_UPDATED', $elementsCreated, $this->addedCount, $this->updatedCount);
		}

		return $msg;
	}

	/**
	 * Calculate the number of elements that have been added during the import
	 *
	 * @since  3.0.8
	 *
	 * @return number
	 */
	protected function countElementsCreated()
	{
		$app    = Factory::getApplication();
		$input  = $app->input;
		$listId = $input->getInt('fabrik_list', $input->get('listid'));

		if ($listId == 0)
		{
			$elementsCreated = count($this->newHeadings);
		}
		else
		{
			$elementsCreated = 0;
			$newElements     = $input->get('createElements', array(), 'array');

			foreach ($newElements as $k => $v)
			{
				if ($v == 1)
				{
					$elementsCreated++;
				}
			}
		}

		return $elementsCreated;
	}

	/**
	 * Once we have iterated over all of the csv file and recreated
	 * the join data, we can finally allow the lists form to process it
	 *
	 * @param   array $joinData data
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	private function insertJoinedData($joinData)
	{
		// Ensure that the main row data doesn't contain and joined data (keep [join][x] though
		$model  = $this->getListModel();
		$app    = Factory::getApplication();
		$table  = $model->getTable();
		$dbName = $table->db_table_name;

		foreach ($joinData as &$j)
		{
			foreach ($j as $k => $v)
			{
				if (!is_array($v))
				{
					if (array_shift(explode('___', $k)) != $table->db_table_name)
					{
						unset($j[$k]);
					}
				}
			}
		}

		$formModel = $model->getFormModel();
		$groups    = $formModel->getGroupsHiarachy();
		$groupIds  = array();

		foreach ($groups as $group)
		{
			if ($group->isJoin())
			{
				$groupIds[$group->getGroup()->join_id] = $group->getGroup()->id;
			}
		}

		foreach ($joinData as $data)
		{
			// Reset the table's name back to the main table
			$table->db_table_name = $dbName;
			$fabrik_repeat_group  = array();
			$js                   = FArrayHelper::getValue($data, 'join', array());

			foreach ($js as $jid => $jdata)
			{
				// Work out max num of repeated data to insert
				$counter = 0;

				foreach ($jdata as $v)
				{
					if (count($v) > $counter)
					{
						$counter = count($v);
					}
				}

				$groupId                       = $groupIds[$jid];
				$fabrik_repeat_group[$groupId] = $counter;
			}
			// $$$ rob here we're setting up fabrik_repeat_group to allow the form to 'know' how many repeated records to insert.
			$app->input->set('fabrik_repeat_group', $fabrik_repeat_group);
			$formModel->formData = $data;

			if (!in_array(false, Worker::getPluginManager(true)->runPlugins('onImportCSVRow', $model, 'list')))
			{
				$formModel->processToDB();
			}
		}
	}

	/**
	 * As each csv row is in a single line we need to fake the join data before
	 * sending it of to be processed by the form model
	 * Look at the list model and get all table joins
	 * then insert data into the row
	 * NOTE: will probably only work for a 1:1 join result
	 *
	 * @param   array  $joinData   Merged join data
	 * @param   array  $aRow       Row
	 * @param   mixed  $pkVal      Primary key value
	 * @param   object &$formModel Form model
	 *
	 * @return  array    updated join data
	 *
	 * @since 4.0
	 */
	private function _fakeJoinData($joinData, $aRow, $pkVal, &$formModel)
	{
		$origData     = $aRow;
		$app          = Factory::getApplication();
		
		// $$ Phil changed to let overwrite from menu take precidence
        $overWrite = Worker::getMenuOrRequestVar('csv_import_overwrite','',false,'menu');
		if($overWrite=='') $overWrite = $app->input->getInt('overwrite', 0, 'post');

		$joins        = $this->getJoins();
		$groups       = $formModel->getGroups();
		$updatedCount = 0;

		if (!empty($joins))
		{
			// A new record that will need to be inserted
			if (!array_key_exists($pkVal, $joinData))
			{
				$joinData[$pkVal] = array();
			}

			foreach ($aRow as $k => $v)
			{
				if (!array_key_exists($k, $joinData[$pkVal]))
				{
					$joinData[$pkVal][$k] = $v;
				}
			}

			if (!array_key_exists('join', $joinData[$pkVal]))
			{
				$joinData[$pkVal]['join'] = array();
			}

			foreach ($joins as $join)
			{
				// Only iterate over table joins (exclude element joins)
				if ((int) $join->element_id != 0)
				{
					continue;
				}

				$repeat = $groups[$join->group_id]->canRepeat();
				$keys   = $this->getJoinPkRecords($join);

				if ($overWrite && in_array($pkVal, $keys))
				{
					// Not sure 2nd test is right here
					$origData[$join->table_key] = $pkVal;
					$updatedCount++;
				}
				else
				{
					$origData[$join->table_join . '___' . $join->table_key] = 0;
					$this->addedCount++;
				}

				$origData[$join->table_join . '___' . $join->table_join_key] = $pkVal;

				foreach ($origData as $key => $val)
				{
					$t = array_shift(explode('___', $key));

					if ($t == $join->table_join)
					{
						if ($repeat)
						{
							$joinData[$pkVal]['join'][$join->id][$key][] = $val;
						}
						else
						{
							$joinData[$pkVal]['join'][$join->id][$key] = $val;
						}
					}
				}
			}
		}

		return $joinData;
	}

	/**
	 * Get Join Primary Key values
	 *
	 * @param   object $join join row
	 *
	 * @return  array
	 *
	 * @since 4.0
	 */

	private function getJoinPkRecords($join)
	{
		$model     = $this->getListModel();
		$formModel = $model->getFormModel();

		if (!isset($this->joinpkids))
		{
			$this->joinpkids = array();
		}

		if (!array_key_exists($join->id, $this->joinpkids))
		{
			$db    = $model->getDb();
			$query = $db->getQuery(true);
			$query->select($join->table_key)->from($join->table_join);
			$db->setQuery($query);
			$this->joinpkids[$join->id] = $db->loadColumn();
		}

		return $this->joinpkids[$join->id];
	}

	/**
	 * Get list model joins
	 *
	 * @return  array    joins
	 *
	 * @since 4.0
	 */
	public function getJoins()
	{
		if (!isset($this->joins))
		{
			$model = $this->getListModel();

			// Move the join table data into their own array space
			$this->joins = $model->getJoins();
		}

		return $this->joins;
	}

	/**
	 * Create an error message
	 *
	 * @return  string
	 *
	 * @since 4.0
	 */
	public function makeError()
	{
		$str = Text::_('COM_FABRIK_CSV_FIELDS_NOT_IN_TABLE');

		foreach ($this->newHeadings as $heading)
		{
			$str .= $heading . ', ';
		}

		return $str;
	}

	/**
	 * Get an array of headings that should be added as part of the  import
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function getNewHeadings()
	{
		return $this->newHeadings;
	}

	/**
	 * Determine if the choose-element-types view should contain a column where
	 * the user selects the field to be the pk.
	 * Should return false if the user has asked for the importer to automatically create a
	 * primary key
	 *
	 * @return  bool    true if column shown
	 *
	 * @since 4.0
	 */
	public function getSelectKey()
	{
		$app    = Factory::getApplication();
		$input  = $app->input;
		$post   = $input->get('jform', array(), 'array');
		$addKey = (int) FArrayHelper::getValue($post, 'addkey', 0);
		$task   = $input->get('task', '', 'string');

		// $$$ rob 30/01/2012 - if in csvimport cron plugin then we have to return true here
		// otherwise a blank column is added to the import data meaning overwrite date dunna workie
		if ($input->getBool('cron_csvimport'))
		{
			return true;
		}

		if ($addKey === 1)
		{
			return false;
		}

		// Admin import csv to new list: user not asking Fabrik to automatically create a pk
		if ($task === 'makeTableFromCSV' && $addKey === 0)
		{
			return true;
		}

		// Reimporting into existing list - should return true
    	// $$ Phil changed because if from frontend menu $task is 'input.doimport'
		// if ($input->getInt('listid') !== 0 && $task === 'doimport')
        if ($input->getInt('listid') !== 0 && strpos($task,'doimport') !== false)
    	{
			return true;
		}

		if (trim($this->getListModel()->getPrimaryKey()) !== '')
		{
			return false;
		}

		return true;
	}

	/**
	 * Get the csv files headings
	 *
	 * @return  array
	 *
	 * @since 4.0
	 */
	public function getHeadings()
	{
		return $this->headings;
	}
}