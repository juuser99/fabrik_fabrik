<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Content.fabrik
 * @copyright   Copyright (C) 2005-2019  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Plugin\Content\Fabrik\Renderer;


use Fabrik\Component\Fabrik\Site\Controller\AbstractSiteController;
use Fabrik\Component\Fabrik\Site\Helper\ControllerHelper;
use Fabrik\Component\Fabrik\Site\Model\FabrikSiteModel;
use Fabrik\Plugin\Content\Fabrik\Parameter\ParameterBag;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\Input\Input;

abstract class AbstractRenderer implements RendererInterface
{
	/**
	 * @var ParameterBag
	 * @since 4.0
	 */
	protected $bag;

	/**
	 * @var CMSApplication
	 * @since 4.0
	 */
	protected $app;

	/**
	 * @var Input
	 * @since 4.0
	 */
	protected $input;

	/**
	 * @var array
	 * @since 4.0
	 */
	protected $controllerInputParameters = [];

	/**
	 * @var array
	 * @since 4.0
	 */
	protected $controllerPropertyValues = [];

	/**
	 * @var ControllerHelper
	 * @since 4.0
	 */
	private $controllerHelper;

	/**
	 * AbstractRenderer constructor.
	 *
	 * @param ParameterBag   $bag
	 * @param CMSApplication $app
	 *
	 * @since 4.0
	 */
	public function __construct(ParameterBag $bag, CMSApplication $app)
	{
		$this->bag   = $bag;
		$this->app   = $app;
		$this->input = $app->input;
	}

	/**
	 * @param AbstractSiteController $controller
	 * @param FabrikSiteModel|null   $model
	 * @param HtmlView               $view
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	abstract protected function renderContent(AbstractSiteController $controller, ?FabrikSiteModel $model, HtmlView $view): string;

	/**
	 * @param string $viewName
	 * @param string $cacheId
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	abstract protected function getControllerClass(string $viewName, string $cacheId): string;

	/**
	 * @param string $key
	 * @param        $value
	 *
	 * @return $this
	 *
	 * @since 4.0
	 */
	public function setControllerInputParameter(string $key, $value): self
	{
		$this->controllerInputParameters[$key] = $value;

		return $this;
	}

	/**
	 * @param string $property
	 * @param        $value
	 *
	 * @return $this
	 *
	 * @since 4.0
	 */
	public function setControllerPropertyValue(string $property, $value): self
	{
		$this->controllerPropertyValues[$property] = $value;

		return $this;
	}

	/**
	 * @return string
	 *
	 * @since 4.0
	 */
	public function render(): string
	{
		$this->setUnusedParameters();

		// For fabble
		$this->setControllerPropertyValue('origid', $this->input->get('id', '', 'string'));
		$this->setControllerInputParameter('origview', $this->input->get('view'));

		// Pass through fabrik_random
		$this->setControllerInputParameter('fabrik_random', $this->input->get('fabrik_random'));

		$id       = $this->bag->getId();
		$viewName = $this->bag->getViewName();
		$this->setControllerInputParameter('id', $id);
		$this->setControllerInputParameter('view', $viewName);

		// Typecast to string since appending a row ID can convert it to a string anyway
		$cacheId = (string) $id;

		if ($rowId = $this->bag->getRowId())
		{
			$cacheId .= '.' . $rowId;
		}

		$this->prepareControllerParameters($viewName, $cacheId);

		$this->controllerHelper = new ControllerHelper();
		$this->controllerHelper->setInputVars($this->controllerInputParameters)
			->setPropertyVars($this->controllerPropertyValues);

		$controller           = $this->getController($viewName, $cacheId);
		$controller->isMambot = true; // Hack for gallery viz as it may not use the default view

		$view           = $this->getView($controller, $viewName, $cacheId);
		$view->isMambot = true;

		/** @var FabrikSiteModel $model */
		if ($model = $this->getModel($controller, $viewName, $cacheId))
		{
			$view->setModel($model, true);

			if (method_exists($model, 'reset'))
			{
				$model->reset();
			}
		}

		try
		{
			$content = $this->renderContent($controller, $model, $view);
		}
		catch (\Exception $e)
		{
			$content = 'Fabrik encountered an error.';
			$this->app->getLogger()->error($e->getMessage(), ['exception' => $e]);
		}

		// Restore Factory::$application in case plugins were using the app/input from the isolated controller
		$this->controllerHelper->restoreFactoryApplication();

		return $content;
	}

	/**
	 * @since 4.0
	 */
	protected function setUnusedParameters()
	{
		if (!$unused = $this->bag->getUnused())
		{
			return;
		}

		// Ensure &gt; conditions set in {fabrik} are converted to >
		foreach ($unused as &$v)
		{
			if (is_string($v))
			{
				$v = htmlspecialchars_decode($v);
			}
		}

		/*
		 * $$$ hugh - in order to allow complex filters to work in lists, like ...
		 * foo___bar[value][]=1 foo___bar[value[]=9 foo___bar[condition]=BETWEEN
		 *we have to build a qs style array structure, using parse_str().
		 */
		$qs_arr = array();
		$qs_str = implode('&', $unused);
		parse_str($qs_str, $qs_arr);

		foreach ($qs_arr as $k => $v)
		{
			$this->setControllerInputParameter($k, $v);
		}

		/*
		 * $$$ rob set this array here - we will use in the listfilter::getQuerystringFilters()
		 * code to determine if the filter is a querystring filter or one set from the plugin
		 * if its set from here it becomes sticky and is not cleared from the session. So we basically
		 * treat all filters set up inside {fabrik.....} as prefilters
		 */
		$this->setControllerInputParameter('fabrik_sticky_filters', array_keys($qs_arr));
	}


	/**
	 * @param AbstractSiteController $controller
	 * @param string                 $viewName
	 * @param string                 $cacheId
	 *
	 * @return FabrikSiteModel
	 *
	 * @since 4.0
	 */
	protected function getModel(AbstractSiteController $controller, string $viewName, string $cacheId): FabrikSiteModel
	{
		if ($model = $controller->getContentPluginModel())
		{
			return $model;
		}

		/** @var FabrikSiteModel $model */
		if ($model = $controller->getModel($viewName, '', ['reuse_model' => true]))
		{
			$controller->setContentPluginModel($model);

			return $model;
		}

		throw new \RuntimeException('Fabrik Content Plug-in: could not create model');
	}

	/**
	 * Get a view
	 *
	 * @param AbstractSiteController $controller controller
	 * @param string                 $viewName   view name
	 * @param string                 $cacheId    item id
	 *
	 * @return  HtmlView
	 *
	 * @since 4.0
	 */
	protected function getView(AbstractSiteController $controller, string $viewName, string $cacheId): HtmlView
	{
		/** @var CMSApplication $app */
		$app      = Factory::getApplication();
		$viewType = $app->getDocument()->getType();

		/** @var HtmlView $view */
		$view = $controller->getView($viewName, $viewType);

		return $view;
	}

	/**
	 * Set input and property values for the isolated controller
	 *
	 * @param string $viewName
	 * @param string $cacheId
	 *
	 *
	 * @since 4.0
	 */
	protected function prepareControllerParameters(string $viewName, string $cacheId): void
	{

	}

	/**
	 * @param string $viewName
	 * @param string $cacheId
	 *
	 * @return AbstractSiteController
	 *
	 * @since 4.0
	 */
	protected function getController(string $viewName, string $cacheId): AbstractSiteController
	{
		$controller = $this->controllerHelper->getIsolatedController(
			$this->getControllerClass($viewName, $cacheId)
		);

		// Set a cacheId so that the controller grabs/creates unique caches for each form/table rendered
		$controller->cacheId = $cacheId;

		return $controller;
	}
}