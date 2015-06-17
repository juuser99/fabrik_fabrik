<?php
/**
 * Fabrik List Controller
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Controllers;

// No direct access
defined('_JEXEC') or die('Restricted access');

use \Fabrik\Models\Lizt as Model;
use \JFactory as JFactory;
use \JURI as JURI;
use \Fabrik\Helpers\HTML;

/**
 * Fabrik List Controller
 *
 * @static
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.5
 */
class Lizt extends Controller
{
	/**
	 * Id used from content plugin when caching turned on to ensure correct element rendered
	 *
	 * @var  int
	 */
	public $cacheId = 0;

	public function execute()
	{
		$document  = JFactory::getDocument();
		$input     = $this->input;
		$viewName  = $input->get('view', 'list');
		$layout    = $input->getWord('layout', 'default');
		$viewFormat  = $document->getType();

		if ($viewFormat == 'pdf')
		{
			// In PDF view only shown the main component content.
			$input->set('tmpl', 'component');
		}

		// Register the layout paths for the view
		$paths = new \SplPriorityQueue;
		$paths->insert(JPATH_COMPONENT . '/views/' . $viewName . '/tmpl', 'normal');

		// FIXME - dont hard wire bootstrap tmpl!
		$paths->insert(JPATH_SITE . '/components/com_fabrik/views/lizt/tmpl/bootstrap', 'normal');

		// Push a model into the view
		$viewClass  = 'Fabrik\Views\Lizt\\' . ucfirst($viewFormat);
		$model = new \Fabrik\Admin\Models\Lizt;

		$view = new $viewClass($model, $paths);
		$view->setLayout($layout);
		$view->setModel($model, true);

		/**
		 * F3 cache with raw view gives error
		 * $$$ hugh - added list_disable_caching option, to disable caching on a per list basis, due to some funky behavior
		 * with pre-filtered lists and user ID's, which should be handled by the ID being in the $cacheId, but happens anyway.
		 * $$$ hugh @TODO - we really shouldn't cache for guests (user ID 0), unless we can come up with a way of creating a unique
		 * cache ID for guests.  We can't use their IP, as it could be two different machines behind a NAT'ing firewall.
		 */
		if ($model->getParams()->get('list_disable_caching', '0') === '1'
			|| in_array($input->get('format'), array('raw', 'csv', 'pdf', 'json', 'fabrikfeed'))
		)
		{
			$view->render();
		}
		else
		{
			// Build unique cache id on url, post and user id
			$user    = JFactory::getUser();
			$uri     = JURI::getInstance();
			$uri     = $uri->toString(array('path', 'query'));
			$cacheId = serialize(array($uri, $input->post, $user->get('id'), get_class($view), 'display', $this->cacheId));
			$cache   = JFactory::getCache('com_fabrik', 'view');
			$cache->get($view, 'render', $cacheId);
		}
	}

	/**
	 * Reorder the data in the list
	 *
	 * @return  null
	 */
	public function order()
	{
		$input     = $this->input;
		$model     = new Model;
		$model->setId($input->getString('listid'));
		$model->setOrderByAndDir();

		// $$$ hugh - unset 'resetfilters' in case it was set on QS of original list load.
		$input->set('resetfilters', 0);
		$input->set('clearfilters', 0);
		$this->display();
	}

	/**
	 * Clear filters
	 *
	 * @return  null
	 */
	public function clearfilter()
	{
		$this->app->enqueueMessage(FText::_('COM_FABRIK_FILTERS_CLEARED'));
		/**
		 * $$$ rob 28/12/20111 changed from clearfilters as clearfilters removes jpluginfilters (filters
		 * set by content plugin which we want to remain sticky. Otherwise list clear button removes the
		 * content plugin filters
		 * $this->app->input->set('resetfilters', 1);
		 */

		/**
		 * $$$ rob 07/02/2012 if reset filters set in the menu options then filters not cleared
		 * so instead use replacefilters which doesn't look at the menu item parameters.
		 */
		$this->input->set('replacefilters', 1);
		$this->filter();
	}

	/**
	 * Filter the list data
	 *
	 * @return null
	 */

	public function filter()
	{
		$model     = new Model;
		$model->setId($this->input->getString('listid'));
		HTML::debug('', 'list model: getRequestData');
		$request = $model->getRequestData();
		$model->storeRequestData($request);

		// $$$ rob pass in the model otherwise display() rebuilds it and the request data is rebuilt
		return $this->display($model);
	}

	/**
	 * Delete rows from list
	 *
	 * @return  null
	 */

	public function delete()
	{
		// Check for request forgeries
		JSession::checkToken() or die('Invalid Token');
		$package    = $this->app->getUserState('com_fabrik.package', 'fabrik');
		$input      = $this->input;
		$model      = new \Fabrik\Admin\Models\Lizt;
		$ids        = $input->get('ids', array(), 'array');
		$listId     = $input->getString('listid');
		$limitStart = $input->getInt('limitstart' . $listId);
		$length     = $input->getInt('limit' . $listId);

		$model->setId($listId);
		$oldTotal = $model->getTotalRecords();

		try
		{
			$ok      = $model->deleteRows($ids);
			$msg     = $ok ? count($ids) . ' ' . FText::_('COM_FABRIK_RECORDS_DELETED') : '';
			$msgType = 'message';
		} catch (Exception $e)
		{
			$msg     = $e->getMessage();
			$msgType = 'error';
			$ids     = array();
		}

		$total = $oldTotal - count($ids);

		$ref = $input->get('fabrik_referrer', 'index.php?option=com_' . $package . '&view=list&listid=' . $listId, 'string');

		// $$$ hugh - for some reason fabrik_referrer is sometimes empty, so a little defensive coding ...
		if (empty($ref))
		{
			$ref = $input->server->get('HTTP_REFERER', 'index.php?option=com_' . $package . '&view=list&listid=' . $listId, '', 'string');
		}

		if ($total >= $limitStart)
		{
			$newLimitStart = $limitStart - $length;

			if ($newLimitStart < 0)
			{
				$newLimitStart = 0;
			}

			$ref     = str_replace('limitstart' . $listId . '=  . $limitStart', 'limitstart' . $listId . '=' . $newLimitStart, $ref);
			$context = 'com_' . $package . '.list.' . $model->getRenderContext() . '.';
			$this->app->setUserState($context . 'limitstart', $newLimitStart);
		}

		if ($input->get('format') == 'raw')
		{
			$input->set('view', 'list');
			$this->display();
		}
		else
		{
			// @TODO: test this
			$this->app->enqueueMessage($msg, $msgType);
			$this->app->redirect($ref);
		}
	}

	/**
	 * Empty a table of records and reset its key to 0
	 *
	 * @return  null
	 */

	public function doempty()
	{
		$model = new \Fabrik\Admin\Models\Lizt;
		$model->truncate();
		$this->display();
	}

	/**
	 * Run a list plugin
	 *
	 * @return  null
	 */

	public function doPlugin()
	{
		$package = $this->app->getUserState('com_fabrik.package', 'fabrik');
		$input   = $this->input;
		$cid     = $input->get('cid', array(0), 'array');
		$cid     = $cid[0];
		$model   = new \Fabrik\Admin\Models\Lizt;
		$model->setId($input->getInt('listid', $cid));
		/**
		 * $$$ rob need to ask the model to get its data here as if the plugin calls $model->getData
		 * then the other plugins are recalled which makes the current plugins params incorrect.
		 */
		$model->setLimits();
		$model->getData();

		// If showing n tables in article page then ensure that only activated table runs its plugin
		if ($input->getInt('id') == $model->get('id') || $input->get('origid', '') == '')
		{
			$messages = $model->processPlugin();

			if ($input->get('format') == 'raw')
			{
				$input->set('view', 'list');
				$model->setRenderContext($model->getId());
				$context = 'com_' . $package . '.list' . $model->getRenderContext() . '.msg';
				$session = JFactory::getSession();
				$session->set($context, implode("\n", $messages));
			}
			else
			{
				foreach ($messages as $msg)
				{
					$this->app->enqueueMessage($msg);
				}
			}
		}
		// 3.0 use redirect rather than calling view() as that gave an sql error (joins seemed not to be loaded for the list)
		$format     = $input->get('format', 'html');
		$defaultRef = 'index.php?option=com_' . $package . '&view=list&listid=' . $model->getId() . '&format=' . $format;

		if ($format !== 'raw')
		{
			$ref = $input->post->get('fabrik_referrer', $defaultRef, 'string');

			// For some reason fabrik_referrer is sometimes empty, so a little defensive coding ...
			if (empty($ref))
			{
				$ref = $input->server->get('HTTP_REFERER', $defaultRef, 'string');
			}
		}
		else
		{
			$ref = $defaultRef;
		}

		$this->app->redirect($ref);
	}

	/**
	 * Called via ajax when element selected in advanced search popup window
	 *
	 * @return  null
	 */

	public function elementFilter()
	{
		$id    = $this->input->getInt('id');
		$model = new \Fabrik\Admin\Models\Lizt;
		$model->setId($id);
		echo $model->getAdvancedElementFilter();
	}
}
