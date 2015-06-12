<?php
/**
 * MS Word/Open office .doc Fabrik List view class
 * Very rough go at implementing .doc rendering based on the fact that they can read HTML
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Views\Lizt;

// No direct access
defined('_JEXEC') or die('Restricted access');

use \JResponse;
use \JStringNormalise;

/**
 * MS Word/Open office .doc Fabrik List view class
 * Very rough go at implementing .doc rendering based on the fact that they can read HTML
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.5
 */
class Word extends Base
{
	/**
	 * Display the template
	 *
	 * @return void
	 */
	public function render()
	{
		if (parent::render() !== false)
		{
			$app = $this->app;

			if (!$app->isAdmin())
			{
				$this->state = $this->model->getState();
				$params = $this->state->get('params');

				if ($params->get('menu-meta_description'))
				{
					$this->doc->setDescription($params->get('menu-meta_description'));
				}

				if ($params->get('menu-meta_keywords'))
				{
					$this->doc->setMetadata('keywords', $params->get('menu-meta_keywords'));
				}

				if ($params->get('robots'))
				{
					$this->doc->setMetadata('robots', $params->get('robots'));
				}
			}

			// Set the response to indicate a file download
			JResponse::setHeader('Content-Type', 'application/vnd.ms-word');
			$name = $this->model->getTable()->get('list.label');
			$name = JStringNormalise::toDashSeparated($name);
			JResponse::setHeader('Content-Disposition', "attachment;filename=\"" . $name . ".doc\"");
			$this->doc->setMimeEncoding('text/html; charset=Windows-1252', false);
			$this->output();
		}
	}
}
