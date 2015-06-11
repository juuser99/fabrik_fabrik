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

use \JFactory as JFactory;

/**
 * HTML Fabrik List view class
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.5
 */

class Html extends Base
{
	private $state = null;
	private $document = null;
	private $tabs = null;

	/**
	 * Display the template
	 *
	 * @return void
	 */
	public function render()
	{
		echo "render";
		if (parent::render() !== false)
		{
			echo "here";
			$model = $this->model;
			$this->tabs = $model->loadTabs();
			$app = JFactory::getApplication();

			if (!$app->isAdmin() && isset($this->params))
			{
				$this->state = $this->get('State');
				$stateParams = $this->state->get('params');
				$this->document = JFactory::getDocument();

				if ($stateParams->get('menu-meta_description'))
				{
					$this->document->setDescription($stateParams->get('menu-meta_description'));
				}

				if ($stateParams->get('menu-meta_keywords'))
				{
					$this->document->setMetadata('keywords', $stateParams->get('menu-meta_keywords'));
				}

				if ($stateParams->get('robots'))
				{
					$this->document->setMetadata('robots', $stateParams->get('robots'));
				}
			}

			$this->output();
		}
	}
}
