<?php
/**
 * HTML Fabrik List view class
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Views\Lizt;

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * HTML Fabrik List view class
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.5
 */
class Html extends Base
{
	private $tabs = null;

	/**
	 * Display the template
	 *
	 * @return void
	 */
	public function render()
	{
		if (parent::render() !== false)
		{
			$model      = $this->model;
			$this->tabs = $model->loadTabs();
			$app        = $this->app;

			if (!$app->isAdmin() && isset($this->params))
			{
				$this->state = $model->getState();
				$stateParams = $this->state->get('params');

				if ($stateParams->get('menu-meta_description'))
				{
					$this->doc->setDescription($stateParams->get('menu-meta_description'));
				}

				if ($stateParams->get('menu-meta_keywords'))
				{
					$this->doc->setMetadata('keywords', $stateParams->get('menu-meta_keywords'));
				}

				if ($stateParams->get('robots'))
				{
					$this->doc->setMetadata('robots', $stateParams->get('robots'));
				}
			}

			$this->output();
		}
	}
}
