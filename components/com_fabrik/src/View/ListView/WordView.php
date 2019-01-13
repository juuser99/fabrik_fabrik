<?php
/**
 * MS Word/Open office .doc Fabrik List view class
 * Very rough go at implementing .doc rendering based on the fact that they can read HTML
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Component\Fabrik\Site\View\ListView;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\String\Normalise;

/**
 * MS Word/Open office .doc Fabrik List view class
 * Very rough go at implementing .doc rendering based on the fact that they can read HTML
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       4.0
 */
class WordView extends BaseView
{
	/**
	 * Display the template
	 *
	 * @param   string $tpl template
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	public function display($tpl = null)
	{
		if (parent::display($tpl) !== false)
		{
			if (!$this->app->isAdmin())
			{
				$state        = $this->get('State');
				$this->params = $state->get('params');

				if ($this->params->get('menu-meta_description'))
				{
					$this->doc->setDescription($this->params->get('menu-meta_description'));
				}

				if ($this->params->get('menu-meta_keywords'))
				{
					$this->doc->setMetadata('keywords', $this->params->get('menu-meta_keywords'));
				}

				if ($this->params->get('robots'))
				{
					$this->doc->setMetadata('robots', $this->params->get('robots'));
				}
			}

			// Set the response to indicate a file download
			$this->app->setHeader('Content-Type', 'application/vnd.ms-word');
			$name = $this->getModel()->getTable()->label;
			$name = Normalise::toDashSeparated($name);
			$this->app->setHeader('Content-Disposition', "attachment;filename=\"" . $name . ".doc\"");
			$this->doc->setMimeEncoding('text/html; charset=Windows-1252', false);
			$this->output();
		}
	}

	/**
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	public function layoutFilters()
	{
		return '';
	}
}
