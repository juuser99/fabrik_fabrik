<?php
/**
 * Base List view class
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Views\Lizt;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Worker;
use \JComponentHelper as JComponentHelper;
use \JProfiler as JProfiler;
use \JRegistry as JRegistry;
use \Fabrik\Helpers\HTML as HelperHTML;
use \stdClass as stdClass;
use Fabrik\Helpers\String;
use \JRoute as JRoute;
use \JHtml as JHtml;
use \JFile as JFile;
use Fabrik\Helpers\Text;

/**
 * Base List view class
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.5
 */
class Base extends \Fabrik\Admin\Views\Html
{
	public $isMambot = null;

	/**
	 * Admin table picker select list
	 *
	 * @var string
	 */
	public $tablePicker = '';

	/**
	 * Can the user delete
	 *
	 * @var bool
	 */
	public $canDelete = false;

	/**
	 * Current row
	 *
	 * @var stdClass
	 */
	public $_row = null;

	/**
	 * Does the list have buttons
	 *
	 * @var bool
	 */
	public $hasButtons = false;
	/**
	 * Data
	 *
	 * @var array
	 */
	public $rows = array();

	protected $buttons = null;

	/**
	 * Get JS objects
	 *
	 * @param   array $data list data
	 *
	 * @return  void
	 */

	protected function getManagementJS($data = array())
	{
		$app                = $this->app;
		$input              = $app->input;
		$itemId             = Worker::itemId();
		$model              = $this->model;
		$params             = $model->getParams();
		$item               = $model->getTable();
		$listRef            = $model->getRenderContext();
		$listId = \JFilterInput::getInstance()->clean($model->getId(), 'WORD');
		$formModel          = $model->getFormModel();
		$elementsNotInTable = $formModel->getElementsNotInTable();
		$toggleCols         = (bool) $params->get('toggle_cols', false);

		if ($model->requiresSlimbox())
		{
			HelperHTML::slimbox();
		}

		if ($model->requiresSlideshow())
		{
			HelperHTML::slideshow();
		}

		$src  = HelperHTML::framework();
		$shim = array();

		$dep       = new stdClass;
		$dep->deps = array('fab/fabrik', 'fab/listfilter', 'fab/advanced-search', 'fab/encoder');

		if ($toggleCols)
		{
			$dep->deps[] = 'fab/list-toggle';
		}

		$shim['fab/list'] = $dep;
		$src              = $model->getPluginJsClasses($src, $shim);
		HelperHTML::addToFrameWork($src, 'media/com_fabrik/js/list');
		$model->getCustomJsAction($src);

		$template   = $model->getTmpl();
		$this->tmpl = $template;

		$model->getListCss();

		// Check for a custom js file and include it if it exists
		$aJsPath = JPATH_SITE . '/components/com_fabrik/views/list/tmpl/' . $template . '/javascript.js';

		if (JFile::exists($aJsPath))
		{
			$src[] = 'components/com_fabrik/views/list/tmpl/' . $template . '/javascript.js';
		}

		$origRows   = $this->rows;
		$this->rows = array(array());

		$tmpItemid = !isset($itemId) ? 0 : $itemId;

		$this->_row       = new stdClass;
		$script           = array();
		$params           = $model->getParams();
		$opts             = new stdClass;
		$opts->admin      = $app->isAdmin();
		$opts->ajax       = (int) $model->isAjax();
		$opts->ajax_links = (bool) $params->get('list_ajax_links', $opts->ajax);

		$opts->links           = array('detail' => $params->get('detailurl', ''), 'edit' => $params->get('editurl', ''), 'add' => $params->get('addurl', ''));
		$opts->filterMethod    = $this->filter_action;
		$opts->advancedFilters = $model->getAdvancedFilterValues();
		$opts->form            = 'listform_' . $listRef;
		$this->listref         = $listRef;
		$opts->headings        = $model->jsonHeadings();
		$labels                = $this->headings;

		foreach ($labels as &$l)
		{
			$l = strip_tags($l);
		}

		$opts->labels         = $labels;
		$opts->primaryKey     = $item->get('list.db_primary_key');
		$opts->Itemid         = $tmpItemid;
		$opts->listRef        = $listRef;
		$opts->formid         = $model->getFormModel()->getId();
		$opts->canEdit        = $model->canEdit() ? "1" : "0";
		$opts->canView        = $model->canView() ? "1" : "0";
		$opts->page           = JRoute::_('index.php');
		$opts->isGrouped      = $this->isGrouped;
		$opts->toggleCols     = $toggleCols;
		$opts->singleOrdering = (bool) $model->singleOrdering();

		$formEls = array();

		foreach ($elementsNotInTable as $tmpElement)
		{
			$oo        = new stdClass;
			$oo->name  = $tmpElement->get('name');
			$oo->label = $tmpElement->get('label');
			$formEls[] = $oo;
		}

		$opts->formels             = $formEls;
		$opts->fabrik_show_in_list = $input->get('fabrik_show_in_list', array(), 'array');
		$opts->csvChoose           = (bool) $params->get('csv_frontend_selection');
		$opts->popup_width         = $params->get('popup_width', '');

		$opts->popup_height = $params->get('popup_height', '');

		$xOffset = $params->get('popup_offset_x', '');
		$yOffset = $params->get('popup_offset_y', '');

		if ($xOffset !== '')
		{
			$opts->popup_offset_x = (int) $xOffset;
		}

		if ($yOffset !== '')
		{
			$opts->popup_offset_y = (int) $yOffset;
		}

		/**
		 * Added the $noData object as we now weed something to pass in just to keep editLabel
		 * and viewLabel happy, after adding placeholder replacement to the labels for a Pro user,
		 * because the tooltips said we did that, which we never actually did.
		 *
		 * http://fabrikar.com/forums/index.php?threads/placeholders-in-list-links-and-labels.37726/#post-191081
		 *
		 * However, this means that using placeholders will yield funky labels for the popups, as
		 * this isn't per row.  So we may need to not use editLabel / viewLabel here any more,
		 * and just use the default COM_FABRIK_VIEW/EDIT.  Or add YAFO's, ::sigh::.
		 *
		 * But for now, it's too corner case to worry about!
		 */
		$noData                 = new stdClass();
		$opts->popup_edit_label = $model->editLabel($noData);
		$opts->popup_view_label = $model->viewLabel($noData);
		$opts->popup_add_label  = $model->addLabel();
		$opts->limitLength      = $model->limitLength;
		$opts->limitStart       = $model->limitStart;
		$opts->tmpl             = $template;
		$csvOpts                = new stdClass;
		$csvOpts->excel         = (int) $params->get('csv_format');
		$csvOpts->inctabledata  = (int) $params->get('csv_include_data');
		$csvOpts->incraw        = (int) $params->get('csv_include_raw_data');
		$csvOpts->inccalcs      = (int) $params->get('csv_include_calculations');
		$csvOpts->custom_qs     = $params->get('csv_custom_qs', '');
		$opts->csvOpts          = $csvOpts;

		$opts->csvFields     = $model->getCsvFields();
		$csvOpts->incfilters = (int) $params->get('incfilters');

		$opts->data = $data;

		$opts->groupByOpts                 = new stdClass;
		$opts->groupByOpts->isGrouped      = (bool) $this->isGrouped;
		$opts->groupByOpts->collapseOthers = (bool) $params->get('group_by_collapse_others', false);
		$opts->groupByOpts->startCollapsed = (bool) $params->get('group_by_start_collapsed', false);

		// If table data starts as empty then we need the html from the row
		// template otherwise we can't add a row to the table
		ob_start();
		$this->_row        = new stdClass;
		$this->_row->id    = '';
		$this->_row->class = 'fabrik_row';
		$opts->rowtemplate = ob_get_contents();
		ob_end_clean();

		// $$$rob if you are loading a table in a window from a form db join select record option
		// then we want to know the id of the window so we can set its showSpinner() method
		$opts->winid = $input->get('winid', '');
		$opts        = json_encode($opts);

		Text::script('COM_FABRIK_PREV');
		Text::script('COM_FABRIK_SELECT_ROWS_FOR_DELETION');
		Text::script('JYES');
		Text::script('JNO');
		Text::script('COM_FABRIK_SELECT_COLUMNS_TO_EXPORT');
		Text::script('COM_FABRIK_INCLUDE_FILTERS');
		Text::script('COM_FABRIK_INCLUDE_DATA');
		Text::script('COM_FABRIK_INCLUDE_RAW_DATA');
		Text::script('COM_FABRIK_INCLUDE_CALCULATIONS');
		Text::script('COM_FABRIK_EXPORT');
		Text::script('COM_FABRIK_START');
		Text::script('COM_FABRIK_NEXT');
		Text::script('COM_FABRIK_END');
		Text::script('COM_FABRIK_PAGE');
		Text::script('COM_FABRIK_OF');
		Text::script('COM_FABRIK_LOADING');
		Text::script('COM_FABRIK_RECORDS');
		Text::script('COM_FABRIK_SAVING_TO');
		Text::script('COM_FABRIK_CONFIRM_DROP');
		Text::script('COM_FABRIK_CONFIRM_DELETE_1');
		Text::script('COM_FABRIK_NO_RECORDS');
		Text::script('COM_FABRIK_CSV_COMPLETE');
		Text::script('COM_FABRIK_CSV_DOWNLOAD_HERE');
		Text::script('COM_FABRIK_CONFIRM_DELETE');
		Text::script('COM_FABRIK_CSV_DOWNLOADING');
		Text::script('COM_FABRIK_FILE_TYPE');
		Text::script('COM_FABRIK_ADVANCED_SEARCH');
		Text::script('COM_FABRIK_FORM_FIELDS');
		Text::script('COM_FABRIK_VIEW');

		// Keyboard short cuts
		Text::script('COM_FABRIK_LIST_SHORTCUTS_ADD');
		Text::script('COM_FABRIK_LIST_SHORTCUTS_EDIT');
		Text::script('COM_FABRIK_LIST_SHORTCUTS_DELETE');
		Text::script('COM_FABRIK_LIST_SHORTCUTS_FILTER');

		$script[] = "window.addEvent('domready', function () {";
		$script[] = "\tvar list = new FbList('$listId',";
		$script[] = "\t" . $opts;
		$script[] = "\t);";
		$script[] = "\tFabrik.addBlock('list_{$listRef}', list);";

		// Add in plugin objects
		$pluginManager = Worker::getPluginManager();

		$pluginManager->runPlugins('onLoadJavascriptInstance', $model, 'list');
		$pluginJs = $pluginManager->data;

		if (!empty($pluginJs))
		{
			$script[] = "list.addPlugins([\n";
			$script[] = "\t" . implode(",\n  ", $pluginJs);
			$script[] = "]);";
		}

		// @since 3.0 inserts content before the start of the list render (currently on f3 tmpl only)
		$pluginManager->runPlugins('onGetContentBeforeList', $model, 'list');
		$this->pluginBeforeList = $pluginManager->data;
		$script[]               = $model->filterJs;

		// Was separate but should now load in with the rest of the require js code
		$model    = $this->model;
		$script[] = $model->getElementJs($src);

		// End domReady wrapper
		$script[] = '})';
		$script   = implode("\n", $script);
		HelperHTML::iniRequireJS($shim);
		HelperHTML::script($src, $script);

		// Reset data back to original settings
		$this->rows = $origRows;
	}

	/**
	 * Display the template
	 *
	 * @param   sting $tpl template
	 *
	 * @return void
	 */

	public function render($tpl = null)
	{
		if ($this->getLayout() == '_advancedsearch')
		{
			$this->advancedSearch($tpl);

			return;
		}

		$fbConfig = JComponentHelper::getParams('com_fabrik');
		$profiler = JProfiler::getInstance('Application');
		$app      = $this->app;
		$input    = $app->input;

		/* @var $model \Fabrik\Admin\Models\Lizt */
		$model    = $this->model;

		// Force front end templates
		//$template = $model->getTmpl();
		//$this->_basePath = COM_FABRIK_FRONTEND . '/views';
		//$this->addTemplatePath($this->_basePath . '/' . $this->_name . '/tmpl/' . $template);

		//$root = $app->isAdmin() ? JPATH_ADMINISTRATOR : JPATH_SITE;
		//$this->addTemplatePath($root . '/templates/' . $app->getTemplate() . '/html/com_fabrik/list/' . $template);
		$user     = $this->user;
		$document = $this->doc;
		$item     = $model->getTable();
		$data     = $model->render();

		$w = new Worker;

		// Add in some styling short cuts
		$c    = 0;
		$form = $model->getFormModel();
		$nav  = $model->getPagination();

		foreach ($data as $groupkKey => $group)
		{
			$num_rows = 1;

			foreach (array_keys($group) as $i)
			{
				$o = new stdClass;

				// $$$ rob moved merge wip code to FabrikModelTable::formatForJoins() - should contain fix for pagination
				$o->data              = $data[$groupkKey][$i];
				$o->cursor            = $num_rows + $nav->limitstart;
				$o->total             = $nav->total;
				$o->id                = 'list_' . $model->getRenderContext() . '_row_' . @$o->data->__pk_val;
				$o->class             = 'fabrik_row oddRow' . $c;
				$data[$groupkKey][$i] = $o;
				$c                    = 1 - $c;
				$num_rows++;
			}
		}

		$groups = $form->getGroupsHierarchy();

		foreach ($groups as $groupModel)
		{
			$elementModels = $groupModel->getPublishedElements();

			foreach ($elementModels as $elementModel)
			{
				$elementModel->setContext($groupModel, $form, $model);
				$elementModel->setRowClass($data);
			}
		}

		$this->rows = $data;
		reset($this->rows);

		// Cant use numeric key '0' as group by uses grouped name as key
		$firstRow                   = current($this->rows);
		$this->requiredFiltersFound = $model->getRequiredFiltersFound();
		$this->advancedSearch       = $model->getAdvancedSearchLink();
		$this->advancedSearchURL    = $model->getAdvancedSearchURL();
		$this->nodata               = (empty($this->rows) || (count($this->rows) == 1 && empty($firstRow)) || !$this->requiredFiltersFound) ? true : false;
		$this->tableStyle           = $this->nodata ? 'display:none' : '';
		$this->emptyStyle           = $this->nodata ? '' : 'display:none';
		$params                     = $model->getParams();

		if (!$this->access($model))
		{
			return false;
		}

		if (!class_exists('JSite'))
		{
			require_once JPATH_ROOT . '/includes/application.php';
		}

		$app     = $this->app;
		$package = $app->getUserState('com_fabrik.package', 'fabrik');
		$this->setTitle($w, $params, $model);

		// Deprecated (keep in case people use them in old templates)
		$this->table                = new stdClass;
		$this->table->label         = String::translate($w->parseMessageForPlaceHolder($item->get('list.label'), $_REQUEST));
		$this->table->intro         = $params->get('show_into', 1) == 0 ? '' : String::translate($w->parseMessageForPlaceHolder($item->get('list.introduction')));
		$this->table->outro         = $params->get('show_outro', 1) == 0 ? '' : String::translate($w->parseMessageForPlaceHolder($params->get('outro')));
		$this->table->id            = \JFilterInput::getInstance()->clean($item->get('id'), 'WORD');
		$this->table->renderid      = $model->getRenderContext();
		$this->table->db_table_name = $item->get('list.db_table_name');

		// End deprecated
		$this->list           = $this->table;
		$this->list->class    = $model->htmlClass();
		$this->group_by       = $item->get('list.group_by');
		$this->form           = new stdClass;
		$this->form->id       = $item->get('list.form_id');
		$this->renderContext  = $model->getRenderContext();
		$this->formid         = 'listform_' . $this->renderContext;
		$form                 = $model->getFormModel();
		$this->table->action  = $model->getTableAction();
		$this->showCSV        = $model->canCSVExport();
		$this->showCSVImport  = $model->canCSVImport();
		$this->toggleCols     = $model->toggleCols();
		$this->showToggleCols = (bool) $params->get('toggle_cols', false);
		$this->canGroupBy     = $model->canGroupBy();
		$this->navigation     = $nav;
		$this->nav            = $input->getInt('fabrik_show_nav', $params->get('show-table-nav', 1))
			? $nav->getListFooter($this->renderContext, $model->getTmpl()) : '';
		$this->nav            = '<div class="fabrikNav">' . $this->nav . '</div>';
		$this->fabrik_userid  = $user->get('id');
		$this->canDelete      = $model->deletePossible() ? true : false;
		$this->limitLength    = $model->limitLength;
		$this->ajax           = $model->isAjax();

		// 3.0 observed in list.js & html moved into fabrik_actions rollover
		$this->showPDF = $params->get('pdf', $fbConfig->get('list_pdf', false));

		if ($this->showPDF)
		{
			Worker::canPdf();
		}

		$this->emptyLink     = $model->canEmpty() ? '#' : '';
		$this->csvImportLink = $this->showCSVImport ? JRoute::_('index.php?option=com_' . $package . '&view=import&filetype=csv&listid=' . $item->get('id')) : '';
		$this->showAdd       = $model->canAdd();

		if ($this->showAdd)
		{
			if ($params->get('show-table-add', 1))
			{
				$this->addRecordLink = $model->getAddRecordLink();
			}
			else
			{
				$this->showAdd = false;
			}
		}

		$this->addLabel = $model->addLabel();
		$this->showRSS  = $params->get('rss', 0) == 0 ? 0 : 1;

		if ($this->showRSS)
		{
			$this->rssLink = $model->getRSSFeedLink();

			if ($this->rssLink != '')
			{
				$attributes = array('type' => 'application/rss+xml', 'title' => 'RSS 2.0');

				if (method_exists($document, 'addHeadLink'))
				{
					$document->addHeadLink($this->rssLink, 'alternate', 'rel', $attributes);
				}
			}
		}

		if ($app->isAdmin())
		{
			// Admin always uses com_fabrik option
			$this->pdfLink = JRoute::_('index.php?option=com_fabrik&task=list.view&id=' . $item->get('id') . '&format=pdf&tmpl=component');
		}
		else
		{
			$pdfLink = 'index.php?option=com_' . $package . '&view=list&format=pdf&id=' . $item->get('id');

			if (!$this->nodata)
			{
				// If some data is shown then ensure that menu links reset filters (combined with require filters) doesn't produce an empty data set for the pdf
				$pdfLink .= '&resetfilters=0';
			}

			$this->pdfLink = JRoute::_($pdfLink);
		}

		list($this->headings, $groupHeadings, $this->headingClass, $this->cellClass) = $model->getHeadings();

		$this->groupByHeadings = $model->getGroupByHeadings();
		$this->filter_action   = $model->getFilterAction();
		JDEBUG ? $profiler->mark('fabrik getfilters start') : null;
		$this->filters = $model->getFilters('listform_' . $this->renderContext);

		$fKeys                 = array_keys($this->filters);
		$this->bootShowFilters = count($fKeys) === 1 && $fKeys[0] === 'all' ? false : true;

		$this->clearFliterLink = $model->getClearButton();
		JDEBUG ? $profiler->mark('fabrik getfilters end') : null;
		$this->filterMode       = (int) $params->get('show-table-filters');
		$this->toggleFilters    = $this->filterMode == 2 || $this->filterMode == 4;
		$this->showFilters      = $model->getShowFilters();
		$this->filterCols       = (int) $params->get('list_filter_cols', '1');
		$this->showClearFilters = ($this->showFilters || $params->get('advanced-filter')) ? true : false;

		$this->emptyDataMessage = $model->getEmptyDataMsg();
		$this->groupheadings    = $groupHeadings;
		$this->calculations     = $this->_getCalculations($this->headings);
		$this->isGrouped        = !($model->getGroupBy() == '');
		$this->colCount         = count($this->headings);

		$this->hasButtons     = $model->getHasButtons();
		$this->grouptemplates = $model->groupTemplates;
		$this->params         = $params;
		$this->loadTemplateBottom();
		$this->getManagementJS($this->rows);

		// Get dropdown list of other tables for quick nav in admin
		$this->tablePicker = $params->get('show-table-picker', $input->get('list-picker', true)) && $app->isAdmin() && $app->input->get('format') !== 'pdf' ? HelperHTML::tableList($this->table->id) : '';

		$this->buttons();
		$this->pluginTopButtons = $model->getPluginTopButtons();
	}

	/**
	 * Model check for publish/access
	 *
	 * @param   JModel $model List model
	 *
	 * @return boolean
	 */
	protected function access($model)
	{
		if (!$model->canPublish())
		{
			echo Text::_('COM_FABRIK_LIST_NOT_PUBLISHED');

			return false;
		}

		if (!$model->canView())
		{
			echo Text::_('JERROR_ALERTNOAUTHOR');

			return false;
		}

		return true;
	}

	/**
	 * Set page title
	 *
	 * @param   object $w       Worker
	 * @param   object &$params List params
	 * @param   object $model   List model
	 *
	 * @return  void
	 */

	protected function setTitle($w, &$params, $model)
	{
		$app      = $this->app;
		$input    = $app->input;
		$document = $this->doc;
		$menus    = $app->getMenu();
		$menu     = $menus->getActive();

		/**
		 * Because the application sets a default page title, we need to get it
		 * right from the menu item itself
		 * if there is a menu item available AND the form is not rendered in a content plugin or module
		 */
		if (is_object($menu) && !$this->isMambot)
		{
			$menu_params = new JRegistry((string) $menu->params);
			$params->set('page_heading', $menu_params->get('page_heading'));
			$params->set('show_page_heading', $menu_params->get('show_page_heading'));
			$params->set('pageclass_sfx', $menu_params->get('pageclass_sfx'));
			$params->set('page_title', $menu_params->get('page_title', $menu->title));
		}
		else
		{
			$params->set('show_page_heading', $input->getInt('show_page_heading', 0));
			$params->set('page_heading', $input->get('title', '', 'string'));
		}

		$params->set('show-title', $input->getInt('show-title', $params->get('show-title')));
		$title = $params->get('page_title');

		if (empty($title))
		{
			$title = $app->getCfg('sitename');
		}

		if (!$this->isMambot)
		{
			$document->setTitle($w->parseMessageForPlaceHolder($title, $_REQUEST, false));
		}
	}

	/**
	 * Actually load the template and echo the view html
	 * will process jplugins if required.
	 *
	 * @return  void
	 */
	protected function output()
	{
		$profiler = JProfiler::getInstance('Application');
		$text     = $this->loadTemplate();
		JDEBUG ? $profiler->mark('template loaded') : null;
		$model  = $this->model;
		$params = $model->getParams();

		if ($params->get('process-jplugins'))
		{
			HelperHTML::runContentPlugins($text);
		}

		JDEBUG ? $profiler->mark('end fabrik display') : null;

		// $$$ rob 09/06/2011 no need for isMambot test? should use ob_start() in module / plugin to capture the output
		echo $text;
	}

	/**
	 * Build an object with the button icons based on the current tmpl
	 *
	 * @return  void
	 */

	protected function buttons()
	{
		$model                     = $this->model;
		$this->buttons             = new stdClass;
		$buttonProperties          = array('class' => 'fabrikTip', 'opts' => "{notice:true}",
			'title' => '<span>' . Text::_('COM_FABRIK_EXPORT_TO_CSV') . '</span>');
		$buttonProperties['alt']   = Text::_('COM_FABRIK_EXPORT_TO_CSV');
		$this->buttons->csvexport  = HelperHTML::image('csv-export.png', 'list', $this->tmpl, $buttonProperties);
		$buttonProperties['title'] = '<span>' . Text::_('COM_FABRIK_IMPORT_FROM_CSV') . '</span>';
		$buttonProperties['alt']   = Text::_('COM_FABRIK_IMPORT_TO_CSV');
		$this->buttons->csvimport  = HelperHTML::image('csv-import.png', 'list', $this->tmpl, $buttonProperties);
		$buttonProperties['title'] = '<span>' . Text::_('COM_FABRIK_SUBSCRIBE_RSS') . '</span>';
		$buttonProperties['alt']   = Text::_('COM_FABRIK_SUBSCRIBE_RSS');
		$this->buttons->feed       = HelperHTML::image('feed.png', 'list', $this->tmpl, $buttonProperties);
		$buttonProperties['title'] = '<span>' . Text::_('COM_FABRIK_EMPTY') . '</span>';
		$buttonProperties['alt']   = Text::_('COM_FABRIK_EMPTY');
		$this->buttons->empty      = HelperHTML::image('trash.png', 'list', $this->tmpl, $buttonProperties);

		$buttonProperties['title'] = '<span>' . Text::_('COM_FABRIK_GROUP_BY') . '</span>';
		$buttonProperties['alt']   = Text::_('COM_FABRIK_GROUP_BY');
		$this->buttons->groupby    = HelperHTML::image('group_by.png', 'list', $this->tmpl, $buttonProperties);

		unset($buttonProperties['title']);
		$buttonProperties['alt'] = Text::_('COM_FABRIK_FILTER');
		$this->buttons->filter   = HelperHTML::image('filter.png', 'list', $this->tmpl, $buttonProperties);

		$addLabel                  = $model->addLabel();
		$buttonProperties['title'] = '<span>' . $addLabel . '</span>';
		$buttonProperties['alt']   = $addLabel;
		$this->buttons->add        = HelperHTML::image('plus-sign.png', 'list', $this->tmpl, $buttonProperties);

		$buttonProperties['title'] = '<span>' . Text::_('COM_FABRIK_PDF') . '</span>';
		$buttonProperties['alt']   = Text::_('COM_FABRIK_PDF');
		$this->buttons->pdf        = HelperHTML::image('pdf.png', 'list', $this->tmpl, $buttonProperties);
	}

	/**
	 * Get the list calculations
	 *
	 * @param   array $aCols Columns
	 *
	 * @return  array
	 */

	protected function _getCalculations($aCols)
	{
		$aData     = array();
		$found     = false;
		$model     = $this->model;
		$modelCals = $model->getCalculations();

		foreach ($aCols as $key => $val)
		{
			$calc            = '';
			$res             = '';
			$oCalcs          = new stdClass;
			$oCalcs->grouped = array();

			if (array_key_exists($key, $modelCals['sums']))
			{
				$found = true;
				$res   = $modelCals['sums'][$key];
				$calc .= $res;
				$tmpKey          = str_replace('.', '___', $key) . '_calc_sum';
				$oCalcs->$tmpKey = $res;
			}

			if (array_key_exists($key . '_obj', $modelCals['sums']))
			{
				$found = true;
				$res   = $modelCals['sums'][$key . '_obj'];

				foreach ($res as $k => $v)
				{
					if ($k != 'calc')
					{
						$oCalcs->grouped[$k] .= '<span class="calclabel">' . $v->calLabel . ':</span> ' . $v->value . '<br />';
					}
				}
			}

			if (array_key_exists($key, $modelCals['avgs']))
			{
				$found = true;
				$res   = $modelCals['avgs'][$key];
				$calc .= $res;
				$tmpKey          = str_replace('.', '___', $key) . '_calc_average';
				$oCalcs->$tmpKey = $res;
			}

			if (array_key_exists($key . '_obj', $modelCals['avgs']))
			{
				$found = true;
				$res   = $modelCals['avgs'][$key . '_obj'];

				foreach ($res as $k => $v)
				{
					if ($k != 'calc')
					{
						$oCalcs->grouped[$k] .= '<span class="calclabel">' . $v->calLabel . ':</span> ' . $v->value . '<br />';
					}
				}
			}

			if (array_key_exists($key . '_obj', $modelCals['medians']))
			{
				$found = true;
				$res   = $modelCals['medians'][$key . '_obj'];

				foreach ($res as $k => $v)
				{
					if ($k != 'calc')
					{
						$oCalcs->grouped[$k] .= '<span class="calclabel">' . $v->calLabel . ':</span> ' . $v->value . '<br />';
					}
				}
			}

			if (array_key_exists($key, $modelCals['medians']))
			{
				$found = true;
				$res   = $modelCals['medians'][$key];
				$calc .= $res;
				$tmpKey          = str_replace('.', '___', $key) . "_calc_median";
				$oCalcs->$tmpKey = $res;
			}

			if (array_key_exists($key . '_obj', $modelCals['count']))
			{
				$found = true;
				$res   = $modelCals['count'][$key . '_obj'];

				foreach ($res as $k => $v)
				{
					if ($k != 'calc')
					{
						$oCalcs->grouped[$k] .= '<span class="calclabel">' . $v->calLabel . ':</span> ' . $v->value . '<br />';
					}
				}
			}

			if (array_key_exists($key, $modelCals['count']))
			{
				$res = $modelCals['count'][$key];
				$calc .= $res;
				$tmpKey          = str_replace('.', '___', $key) . "_calc_count";
				$oCalcs->$tmpKey = $res;
				$found           = true;
			}

			if (array_key_exists($key . '_obj', $modelCals['custom_calc']))
			{
				$found = true;
				$res   = $modelCals['custom_calc'][$key . '_obj'];

				foreach ($res as $k => $v)
				{
					if ($k != 'calc')
					{
						$oCalcs->grouped[$k] .= '<span class="calclabel">' . $v->calLabel . ':</span> ' . $v->value . '<br />';
					}
				}
			}

			if (array_key_exists($key, $modelCals['custom_calc']))
			{
				$res = $modelCals['custom_calc'][$key];
				$calc .= $res;
				$tmpKey          = str_replace('.', '___', $key) . "_calc_custom_calc";
				$oCalcs->$tmpKey = $res;
				$found           = true;
			}

			$key          = str_replace('.', '___', $key);
			$oCalcs->calc = $calc;
			$aData[$key]  = $oCalcs;
		}

		$this->hasCalculations = $found;

		return $aData;
	}

	/**
	 * Get the table's forms hidden fields
	 *
	 * @return  string  hidden fields
	 */

	protected function loadTemplateBottom()
	{
		$app    = $this->app;
		$input  = $app->input;
		$itemId = Worker::itemId();
		$model  = $this->model;
		$item   = $model->getTable();

		$referer           = str_replace('&', '&amp;', $input->server->get('REQUEST_URI', '', 'string'));
		$referer           = String::removeQSVar($referer, 'fabrik_incsessionfilters');
		$this->hiddenFields = array();

		// $$$ rob 15/12/2011 - if in com_content then doing this means you cant delete rows
		$this->hiddenFields[] = '<input type="hidden" name="option" value="' . $input->get('option', 'com_fabrik') . '" />';

		// $$$ rob 28/12/2011 but when using com_content as a value you cant filter!
		// $this->hiddenFields[] = '<input type="hidden" name="option" value="com_fabrik" />';
		$this->hiddenFields[] = '<input type="hidden" name="orderdir" value="' . $input->get('orderdir') . '" />';
		$this->hiddenFields[] = '<input type="hidden" name="orderby" value="' . $input->get('orderby') . '" />';

		// $$$ rob if the content plugin has temporarily set the view to list then get view from origview var, if that doesn't exist
		// revert to view var. Used when showing table in article/blog layouts
		$view                 = $input->get('origview', $input->get('view', 'list'));
		$this->hiddenFields[] = '<input type="hidden" name="view" value="' . $view . '" />';
		$this->hiddenFields[] = '<input type="hidden" name="listid" value="' . $item->get('id') . '"/>';
		$this->hiddenFields[] = '<input type="hidden" name="listref" value="' . $this->renderContext . '"/>';
		$this->hiddenFields[] = '<input type="hidden" name="Itemid" value="' . $itemId . '"/>';

		// Removed in favour of using list_{id}_limit dropdown box
		$this->hiddenFields[] = '<input type="hidden" name="fabrik_referrer" value="' . $referer . '" />';
		$this->hiddenFields[] = JHTML::_('form.token');
		$this->hiddenFields[] = '<input type="hidden" name="format" value="html" />';

		// $packageId = $input->getInt('packageId', 0);
		// $$$ rob testing for ajax table in module
		$packageId            = $model->packageId;
		$this->hiddenFields[] = '<input type="hidden" name="packageId" value="' . $packageId . '" />';

		/*if ($app->isAdmin())
		{
			$this->hiddenFields[] = '<input type="hidden" name="task" value="list.view" />';
		}
		else
		{
			$this->hiddenFields[] = '<input type="hidden" name="task" value="" />';
		}*/

		$this->hiddenFields[] = '<input type="hidden" name="fabrik_listplugin_name" value="" />';
		$this->hiddenFields[] = '<input type="hidden" name="fabrik_listplugin_renderOrder" value="" />';

		// $$$ hugh - added this so plugins have somewhere to stuff any random data they need during submit
		$this->hiddenFields[] = '<input type="hidden" name="fabrik_listplugin_options" value="" />';
		$this->hiddenFields[] = '<input type="hidden" name="incfilters" value="1" />';

		// $$$ hugh - testing social profile hash stuff
		if ($input->get('fabrik_social_profile_hash', '') != '')
		{
			$this->hiddenFields[] = '<input type="hidden" name="fabrik_social_profile_hash" value="' . $input->get('fabrik_social_profile_hash', '', 'string')
				. '" />';
		}

		$this->hiddenFields = implode("\n", $this->hiddenFields);
	}

	/**
	 * Set the advanced search template
	 *
	 * @param   string $tpl template
	 *
	 * @return  void
	 */
	protected function advancedSearch($tpl)
	{
		$app        = $this->app;
		$package    = $app->getUserState('com_fabrik.package', 'fabrik');
		$input      = $app->input;
		$model      = $this->model;
		$id         = $model->getState('list.id');
		$this->tmpl = $model->getTmpl();
		$model->setRenderContext($id);
		$this->listref = $model->getRenderContext();

		// Advanced search script loaded in list view - avoids timing issues with i.e. loading the ajax content and script
		$this->rows   = $model->getAdvancedSearchRows();
		$action       = $input->server->get('HTTP_REFERER', 'index.php?option=com_' . $package, 'string');
		$this->action = $action;
		$this->listid = $id;
	}
}
