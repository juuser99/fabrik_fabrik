<?php
/**
 * Fabrik Admin Visualization Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

namespace Fabrik\Admin\Models;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Utilities\ArrayHelper;
use \JPluginHelper as JPluginHelper;
use Fabrik\Admin\Models\Lizt;
use Fabrik\Helpers\Text;
use \Fabrik\Helpers\HTML;
use Fabrik\Helpers\String;
use Joomla\Registry\Registry;
use JRoute;
use Fabrik\Helpers\Worker;
use JSession;
use JFilterInput;

interface ModelVisualizationInterface
{

}

/**
 * Fabrik Admin Visualization Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.5
 */
class Visualization extends Base implements ModelVisualizationInterface
{
	protected $pluginParams = null;

	protected $row = null;

	/** @var object params*/
	protected $params = null;

	/**
	 * @var array
	 */
	protected $listids = array();

	/**
	 * Url for filter form
	 *
	 * @var string
	 */
	protected $getFilterFormURL = null;

	public $srcBase = "plugins/fabrik_visualization/";

	public $pathBase = null;

	/**
	 * JS code to ini list filters
	 *
	 * @var string
	 */
	protected $filterJs = null;
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var  string
	 */
	protected $text_prefix = 'COM_FABRIK_VISUALIZATION';

	/**
	 * Constructor
	 *
	 * @param   Registry  $config  An array of configuration options (name, state, dbo, table_path, ignore_request).
	 *
	 * @since   11.1
	 */
	public function __construct(Registry $config = null)
	{
		$this->pathBase = JPATH_SITE . '/plugins/fabrik_visualization/';

		parent::__construct($config);
	}

	/**
	 * Set an array of list id's whose data is used inside the visualization
	 *
	 * @return  void
	 */
	protected function setListIds()
	{
		if (!isset($this->listids))
		{
			$this->listids = array();
		}
	}

	/**
	 * Should the viz show the list filters
	 *
	 * @return boolean
	 */
	public function showFilters()
	{
		$input = $this->app->input;
		$params = $this->getParams();

		return (int) $input->get('showfilters', $params->get('show_filters')) === 1 ? true : false;
	}

	/**
	 * Get the item
	 *
	 * @param   string  $id
	 *
	 * @return  Registry
	 */
	public function getItem($id = null)
	{
		// FIXME 3.5 - load from json
		if (!isset($this->row))
		{
			$id = JFilterInput::getInstance()->clean($this->get('id'), 'WORD');
			// @TODO - save & load from session?

			if ($id === '')
			{
				$json = file_get_contents(JPATH_COMPONENT_ADMINISTRATOR . '/models/schemas/visualization.json');
			}
			else
			{
				$json = file_get_contents(JPATH_COMPONENT_ADMINISTRATOR . '/models/views/visualizations/' . $id . '.json');
			}

			$item = json_decode($json);

			$item = new Registry($item);

			$this->setListIds();

			// Needed to load the language file!
			if ($item->get('plugin', '') !== '')
			{
				$pluginManager = Worker::getPluginManager();
				$pluginManager->getPlugIn($item->get('plugin'), 'visualization');
			}

			$this->row = $item;
		}

		return $this->row;
	}

	/**
	 * Render the visualization
	 *
	 * @return  void
	 */
	public function render()
	{
		// Overwrite in plugin
	}

	/**
	 * Get the visualizations list models
	 *
	 * @return \Fabrik\Admin\Models\Lizt[] table objects
	 */
	public function getListModels()
	{
		if (!isset($this->tables))
		{
			$this->tables = array();
		}

		foreach ($this->listids as $id)
		{
			if (!array_key_exists($id, $this->tables))
			{
				$listModel = new Lizt;
				$listModel->setId($id);
				$listModel->getTable();
				$this->tables[$id] = $listModel;
			}
		}

		return $this->tables;
	}

	/**
	 * Get a list model
	 *
	 * @param   int  $id  list model id
	 *
	 * @return  object	fabrik list model
	 */
	public function getListModel($id = null)
	{
		$lists = $this->getListModels();

		return $lists[$id];
	}

	/**
	 * Make HTML container div id
	 *
	 * @return string
	 */
	public function getContainerId()
	{
		return $this->getJSRenderContext();
	}

	/**
	 * Get all list model's filters
	 *
	 * @return array table filters
	 */
	public function getFilters()
	{
		$params = $this->getParams();
		$name = String::strtolower(str_replace('fabrikModel', '', get_class($this)));
		$filters = array();
		$showFilters = $params->get($name . '_show_filters', array());
		$listModels = $this->getListModels();
		$js = array();
		$i = 0;

		foreach ($listModels as $listModel)
		{
			$show = (bool) ArrayHelper::getValue($showFilters, $i, true);

			if ($show)
			{
				$ref = $this->getRenderContext();
				$id = $this->getId();
				$listModel->getFilterArray();
				$filters[$listModel->getTable()->get('list.label')] = $listModel->getFilters($this->getContainerId(), 'visualization', $id, $ref);
				$js[] = $listModel->filterJs;
			}

			$i++;
		}

		$this->filterJs = implode("\n", $js);
		$this->getRequireFilterMsg();

		return $filters;
	}

	/**
	 * Build an array of the lists' query where statements
	 *
	 * @return  array  keyed on list id.
	 */
	public function buildQueryWhere()
	{
		$filters = array();
		$listModels = $this->getListModels();

		foreach ($listModels as $listModel)
		{
			$query = $listModel->getDb()->getQuery(true);
			$filters[$listModel->getId()] = (string) $listModel->buildQueryWhere(true, $query);
		}

		return $filters;
	}

	/**
	 * Get the JS code to ini the list filters
	 *
	 * @since   3.0.6
	 *
	 * @return  string  js code
	 */
	public function getFilterJs()
	{
		if (is_null($this->filterJs))
		{
			$this->getFilters();
		}

		return $this->filterJs;
	}

	/**
	 * Create advanced search links
	 *
	 * @since    3.0.7
	 *
	 * @return   string
	 */
	public function getAdvancedSearchLink()
	{
		$app = $this->app;
		$package = $app->getUserState('com_fabrik.package', 'fabrik');
		$links = array();
		$listModels = $this->getListModels();

		foreach ($listModels as $listModel)
		{
			$params = $listModel->getParams();

			if ($params->get('advanced-filter', '0'))
			{
				$table = $listModel->getTable();
				$url = COM_FABRIK_LIVESITE . 'index.php?option=com_' . $package . '&amp;view=list&amp;layout=_advancedsearch&amp;tmpl=component&amp;listid='
					. $table->get('id') . '&amp;nextview=' . $app->input->get('view', 'list')
					. '&scope&amp;=' . $app->scope;

				$url .= '&amp;tkn=' . JSession::getFormToken();
				$links[$table->get('list.label')] = $url;
			}
		}

		$title = '<span>' . Text::_('COM_FABRIK_ADVANCED_SEARCH') . '</span>';
		$opts = array('alt' => Text::_('COM_FABRIK_ADVANCED_SEARCH'), 'class' => 'fabrikTip', 'opts' => "{notice:true}", 'title' => $title);
		$img = HTML::image('find.png', 'list', '', $opts);

		if (count($links) === 1)
		{
			return '<a href="' . array_pop($links) . '" class="advanced-search-link">' . $img . '</a>';
		}
		else
		{
			$str = $img . '<ul>';

			foreach ($links as $label => $url)
			{
				$str .= '<li><a href="' . $url . '" class="advanced-search-link">' . $label . '</a></li>';
			}

			$str = '</ul>';

			return $str;
		}
	}

	/**
	 * Get Viz render context
	 *
	 * @since   3.0.6
	 *
	 * @return  string  render context
	 */
	public function getRenderContext()
	{
		$app = $this->app;
		$input = $app->input;
		$id = $this->getId();

		// Calendar in content plugin - choose event form needs to know its from a content plugin.
		return $input->get('renderContext', $id . '_' . $this->app->scope . '_' . $id);
	}

	/**
	 * Get the JS unique name that is assigned to the viz JS object
	 *
	 * @since   3.0.6
	 *
	 * @return  string  js viz id
	 */
	public function getJSRenderContext()
	{
		return 'visualization_' . $this->getRenderContext();
	}

	/**
	 * Set the url for the filter form's action
	 *
	 * @return  string	action url
	 */
	public function getFilterFormURL()
	{
		if (isset($this->getFilterFormURL))
		{
			return $this->getFilterFormURL;
		}

		$app = $this->app;
		$package = $app->getUserState('com_fabrik.package', 'fabrik');
		$input = $app->input;
		$option = $input->get('option');

		// Get the router
		$router = $app->getRouter();
		/**
		 * $$$ rob force these to be 0 once the menu item has been loaded for the first time
		 * subsequent loads of the link should have this set to 0. When the menu item is re-clicked
		 * rest filters is set to 1 again
		 */
		$router->setVar('resetfilters', 0);

		if ($option !== 'com_' . $package)
		{
			// $$$ rob these can't be set by the menu item, but can be set in {fabrik....}
			$router->setVar('clearordering', 0);
			$router->setVar('clearfilters', 0);
		}

		$queryVars = $router->getVars();
		$page = 'index.php?';
		$qs = array();

		foreach ($queryVars as $k => $v)
		{
			$qs[] = $k . '=' . $v;
		}

		$action = $page . implode("&amp;", $qs);

		// LimitStart gets added in the pagination model
		$action = preg_replace("/limitstart" . $this->getState('id') . "}=(.*)?(&|)/", '', $action);
		$action = String::rtrimword($action, "&");
		$this->getFilterFormURL = JRoute::_($action);

		return $this->getFilterFormURL;
	}

	/**
	 * Get List Model's Required Filter message
	 *
	 * @return  void
	 */
	protected function getRequireFilterMsg()
	{
		$app = $this->app;
		$listModels = $this->getListModels();

		foreach ($listModels as $model)
		{
			if (!$model->gotAllRequiredFilters())
			{
				$app->enqueueMessage($model->getRequiredMsg(), 'notice');
			}
		}
	}

	/**
	 * Set visualization prefilters
	 *
	 * @return  void
	 */
	public function setPrefilters()
	{
		$listModels = $this->getListModels();
		$params = $this->getParams();
		$prefilters = (array) $params->get('prefilters');
		$c = 0;

		foreach ($listModels as $listModel)
		{
			// Set prefilter params
			$listParams = $listModel->getParams();
			$prefilter = ArrayHelper::getValue($prefilters, $c);
			$prefilter = ArrayHelper::fromObject(json_decode($prefilter));
			$conditions = (array) $prefilter['filter-conditions'];

			if (!empty($conditions))
			{
				$fields = $prefilter['filter-fields'];

				foreach ($fields as &$f)
				{
					$f = String::safeColName($f);
				}

				$listParams->set('filter-fields', $fields);
				$listParams->set('filter-conditions', $prefilter['filter-conditions']);
				$listParams->set('filter-value', $prefilter['filter-value']);
				$listParams->set('filter-access', $prefilter['filter-access']);
				$listParams->set('filter-eval', $prefilter['filter-eval']);
			}

			$c ++;
		}
	}

	/**
	 * Should be overwritten in plugin viz model
	 *
	 * @return  bool
	 */
	public function getRequiredFiltersFound()
	{
		$listModels = $this->getListModels();

		foreach ($listModels as $listModel)
		{
			if (!$listModel->getRequiredFiltersFound())
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Load in any table plugin classes
	 * needed for radius search filter
	 *
	 * @param   array  &$scripts  existing src file
	 *
	 * @return  array	js file paths
	 */
	public function getPluginJsClasses(&$scripts = array())
	{
		$listModels = $this->getListModels();

		foreach ($listModels as $model)
		{
			$model->getPluginJsClasses($scripts);
		}

		return $scripts;
	}

	/**
	 * Get the js code to create instances of js list plugin classes
	 * needed for radius search filter
	 *
	 * @return  array
	 */
	public function getPluginJsObjects()
	{
		$str = array();
		$listModels = $this->getListModels();

		foreach ($listModels as $model)
		{
			$src = $model->getPluginJsClasses($src);
			$tmp = $model->getPluginJsObjects($this->getContainerId());
			$str = array_merge($str, $tmp);
		}

		return $str;
	}

	/**
	 * Get the require.js shim for the visualization
	 * Load all the list plugin requirements
	 *
	 * @since  3.1rc
	 *
	 * @return array
	 */
	public function getShim()
	{
		$listModels = $this->getListModels();
		$shim = array();

		foreach ($listModels as $model)
		{
			$src = $model->getPluginJsClasses($src, $shim);
		}

		return $shim;
	}

	/**
	 * Method to set the table id
	 *
	 * @param   int  $id  viz id
	 *
	 * @return  void
	 */
	public function setId($id)
	{
		$this->setState('id', $id);

		// $$$ rob not sure why but we need this getState() here when assinging id from admin view
		$this->getState();
	}

	/**
	 * Get viz params
	 *
	 * @return  object  params
	 */
	public function getParams()
	{
		if (is_null($this->params))
		{
			$v = $this->getItem();
			$app = $this->app;
			$input = $app->input;
			$this->params = new Registry($v->get('params'));
			$this->params->set('show-title', $input->getInt('show-title', $this->params->get('show-title', 1)));
		}

		return $this->params;
	}

	/**
	 * Get viz id
	 *
	 * @return  int  id
	 */
	public function getId()
	{
		return $this->getState('id');
	}

	/**
	 * Can the use view the visualization (checks published and access level)
	 *
	 * @return boolean
	 */
	public function canView()
	{
		$groups = $this->user->getAuthorisedViewLevels();
		$row = $this->getItem();

		if (! (bool) $row->get('published'))
		{
			return false;
		}

		return in_array($row->get('access'), $groups);
	}

	/****
	 * ADMIN METHOD ...
	 */
	/**
	 * get html form fields for a plugin (filled with
	 * current element's plugin data
	 *
	 * @param   string  $plugin  plugin name
	 *
	 * @return  string	html form fields
	 */
	public function getPluginHTML($plugin = null)
	{
		//$input = $this->app->input;
		$item = $this->getItem();

		if (is_null($plugin))
		{
			$plugin = $item->get('plugin');
		}

		//$input->set('view', 'visualization');
		JPluginHelper::importPlugin('fabrik_visualization', $plugin);
		$pluginManager = new PluginManager;

		if ($plugin == '')
		{
			$str = Text::_('COM_FABRIK_SELECT_A_PLUGIN');
		}
		else
		{
			$plugin = $pluginManager->getPlugIn($plugin, 'Visualization');
			$str = $plugin->onRenderAdminSettings(ArrayHelper::fromObject($item), null, 'nav-tabs');
		}

		return $str;
	}

	/**
	 * Method to save the form data.
	 *
	 * @param   Registry  $data  The form data.
	 *
	 * @return  boolean  True on success, False on error.
	 */
	public function save($data)
	{
		parent::cleanCache('com_fabrik');

		return parent::save($data);
	}
}
