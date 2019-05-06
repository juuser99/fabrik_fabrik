<?php
/**
 * Abstract Site Controller
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2019 Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Component\Fabrik\Site\Controller;


use Fabrik\Helpers\Html;
use Fabrik\Helpers\Worker;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

class DisplayController extends AbstractSiteController
{
	/**
	 * Display the view
	 *
	 * @param bool  $cachable  If true, the view output will be cached - NOTE not actually used to control caching!!!
	 * @param array $urlparams An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
	 *
	 * @return  null
	 * @since 4.0
	 */
	public function display($cachable = false, $urlparams = false)
	{
		/** @var CMSApplication $app */
		$app     = Factory::getApplication();
		$input   = $app->input;
		$package = $app->getUserState('com_fabrik.package', 'fabrik');

		// Menu links use fabriklayout parameters rather than layout
		$flayout = $input->get('fabriklayout');

		if ($flayout != '')
		{
			$input->set('layout', $flayout);
		}

		$document = $app->getDocument();

		$viewName  = $input->get('view', 'form');
		$modelName = $viewName;

		if ($viewName == 'emailform')
		{
			$modelName = 'form';
		}

		if ($viewName == 'details')
		{
			$viewName  = 'form';
			$modelName = 'form';
		}

		$viewType = $document->getType();

		// Set the default view name from the Request
		$view = $this->getView($viewName, $viewType);

		// Push a model into the view
		if ($model = $this->getModel($modelName))
		{
			$view->setModel($model, true);
		}

		// Display the view

		$view->error = $this->getError();

		if (Worker::useCache() && !$this->isMambot)
		{
			$user    = $this->app->getSession()->get('user');
			$uri     = Uri::getInstance();
			$uri     = $uri->toString(array('path', 'query'));
			$cacheid = serialize(array($uri, $input->post, $user->get('id'), get_class($view), 'display', $this->cacheId));
			$cache   = Factory::getCache('com_' . $package, 'view');
			Html::addToSessionCacheIds($this->cacheId);
			echo $cache->get($view, 'display', $cacheid);
		}
		else
		{
			return $view->display();
		}
	}
}