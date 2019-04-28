<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Content.fabrik
 * @copyright   Copyright (C) 2005-2019  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Plugin\Content\Fabrik\Renderer;


use Fabrik\Component\Fabrik\Site\Controller\AbstractSiteController;
use Fabrik\Component\Fabrik\Site\Controller\ListController;
use Fabrik\Component\Fabrik\Site\Model\FabrikSiteModel;
use Fabrik\Component\Fabrik\Site\Model\ListModel;
use Fabrik\Helpers\ArrayHelper;
use Joomla\CMS\MVC\View\HtmlView;

class ElementRenderer extends AbstractRenderer
{
	/**
	 * @param AbstractSiteController         $controller
	 * @param FabrikSiteModel|ListModel|null $model
	 * @param HtmlView                       $view
	 *
	 * @return string
	 *
	 * @since version
	 */
	protected function renderContent(AbstractSiteController $controller, ?FabrikSiteModel $model, HtmlView $view): string
	{
		if (!$model)
		{
			return '';
		}

		$element       = $this->bag->getElement();
		$rowId         = $this->bag->getRowId();
		$repeatCounter = $this->bag->getRepeatCounter();

		$model->setId($this->bag->getListId());
		$formModel = $model->getFormModel();
		$groups    = $formModel->getGroupsHiarachy();

		foreach ($groups as $groupModel)
		{
			$elements = $groupModel->getMyElements();

			foreach ($elements as $elementModel)
			{
				// $$$ rob 26/05/2011 changed it so that you can pick up joined elements without specifying plugin
				// param 'element' as joinx[x][fullname] but simply 'fullname'
				if ($element == $elementModel->getFullName(true, false))
				{
					$activeEl = $elementModel;
					continue 2;
				}
			}
		}

		// $$$ hugh in case they have a typo in their elementname
		if (empty($activeEl) || !$activeEl->isPublished())
		{
			throw new \RuntimeException('You are trying to embed an element called ' . $element . ' which is not present in the list or has been unpublished');
		}

		if ($rowId === '')
		{
			$rows  = $model->getData();
			$group = array_shift($rows);
			$row   = array_shift($group);

			if (empty($row))
			{
				return '';
			}

			return $row->$element;
		}

		$row = $model->getRow($rowId, false, true);

//      `$element` doesn't seem to be used after this?
//		if (substr($element, StringHelper::strlen($element) - 4, StringHelper::strlen($element)) !== '_raw')
//		{
//			$element = $element . '_raw';
//		}

		// $$$ hugh - need to pass all row data, or calc elements that use {placeholders} won't work
		//$defaultData = is_object($row) ? get_object_vars($row) : $row;
		$defaultData = is_object($row) ? ArrayHelper::fromObject($row, true) : $row;

		/* $$$ hugh - if we don't do this, our passed data gets blown away when render() merges the form data
		 * not sure why, but apparently if you do $foo =& $bar and $bar is NULL ... $foo ends up NULL
		 */
		$activeEl->getFormModel()->data = $defaultData;
		$activeEl->editable             = false;

		$defaultData = (array) $defaultData;
		unset($activeEl->defaults);

		if ($repeatCounter === 'all')
		{
			$repeat = $activeEl->getGroupModel()->repeatCount();
			$res    = array();

			for ($j = 0; $j < $repeat; $j++)
			{
				$res[] = $activeEl->render($defaultData, $j);
			}

			return count($res) > 1 ? '<ul><li>' . implode('</li><li>', $res) . '</li></ul>' : $res[0];
		}

		$activeEl->elementJavascript($repeatCounter);

		return $activeEl->render($defaultData, $repeatCounter);
	}

	/**
	 * @param string $viewName
	 * @param string $cacheId
	 *
	 * @throws \Exception
	 * @since 4.0
	 */
	protected function prepareControllerParameters(string $viewName, string $cacheId): void
	{
		// Set row id for things like user element
		$this->setControllerInputParameter('rowid', $this->bag->getRowId());

		// Set detail view for things like youtube element
		$this->setControllerInputParameter('view', 'details');
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
		return parent::getModel($controller, 'list', $this->bag->getListId());
	}

	/**
	 * @param string $viewName
	 * @param string $cacheId
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	protected function getControllerClass(string $viewName, string $cacheId): string
	{
		return ListController::class;
	}

	/**
	 * @param string $viewName
	 * @param string $cacheId
	 *
	 * @return AbstractSiteController
	 *
	 * @throws \Exception
	 * @since 4.0
	 */
	protected function getController(string $viewName, string $cacheId): AbstractSiteController
	{
		return parent::getController($viewName, $this->bag->getListId());
	}
}