<?php
/**
 * MS Word/Open office .doc Fabrik Form view class
 * Very rough go at implementing .doc rendering based on the fact that they can read HTML
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Component\Fabrik\Site\View\Form;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Worker;
use Joomla\String\Normalise;

/**
 * MS Word/Open office .doc Fabrik Form view class
 * Very rough go at implementing .doc rendering based on the fact that they can read HTML
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       4.0
 */
class WordView extends BaseView
{
	/**
	 * Main setup routine for displaying the form/detail view
	 *
	 * @param   string $tpl template
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	public function display($tpl = null)
	{
		if (parent::display($tpl) !== false)
		{
			$this->output();

			if (!$this->app->isAdmin())
			{
				$this->state  = $this->get('State');
				$model        = $this->getModel();
				$this->params = $this->state->get('params');
				$row          = $model->getData();
				$w            = new Worker();

				if ($this->params->get('menu-meta_description'))
				{
					$desc = $w->parseMessageForPlaceHolder($this->params->get('menu-meta_description'), $row);
					$this->doc->setDescription($desc);
				}

				if ($this->params->get('menu-meta_keywords'))
				{
					$keywords = $w->parseMessageForPlaceHolder($this->params->get('menu-meta_keywords'), $row);
					$this->doc->setMetadata('keywords', $keywords);
				}

				if ($this->params->get('robots'))
				{
					$this->doc->setMetadata('robots', $this->params->get('robots'));
				}

				// Set the response to indicate a file download
				$this->app->setHeader('Content-Type', 'application/vnd.ms-word');
				$name = $this->getModel()->getTable()->label;
				$name = Normalise::toDashSeparated($name);
				$this->app->setHeader('Content-Disposition', "attachment;filename=\"" . $name . ".doc\"");
				$this->doc->setMimeEncoding('text/html; charset=Windows-1252', false);
			}
		}
	}
}
