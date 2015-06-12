<?php
/**
 * HTML Form view class
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Views\Form;

// No direct access
defined('_JEXEC') or die('Restricted access');

use JFactory;
use \Fabrik\Helpers\Worker;

/**
 * HTML Form view class
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.5
 */
class Html extends Base
{
	/**
	 * Main setup routine for displaying the form/detail view
	 *
	 * @return  void
	 */
	public function render()
	{
		if (parent::render() !== false)
		{
			$this->output();
			$app = $this->model->app;

			if (!$app->isAdmin())
			{
				$this->state = $this->model->getState();
				$doc = JFactory::getDocument();
				$model = $this->getModel();
				$params = $this->state->get('params');
				$row = $model->getData();
				$w = new Worker;

				if ($params->get('menu-meta_description'))
				{
					$desc = $w->parseMessageForPlaceHolder($params->get('menu-meta_description'), $row);
					$doc->setDescription($desc);
				}

				if ($params->get('menu-meta_keywords'))
				{
					$keywords = $w->parseMessageForPlaceHolder($params->get('menu-meta_keywords'), $row);
					$doc->setMetadata('keywords', $keywords);
				}

				if ($params->get('robots'))
				{
					$doc->setMetadata('robots', $params->get('robots'));
				}
			}
		}
	}
}
