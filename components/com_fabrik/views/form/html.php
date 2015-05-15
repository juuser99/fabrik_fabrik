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

use Fabrik\Helpers\Worker;

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
			$app = JFactory::getApplication();

			if (!$app->isAdmin())
			{
				$this->state = $this->get('State');
				$this->document = JFactory::getDocument();
				$model = $this->getModel();
				$this->params = $this->state->get('params');
				$row = $model->getData();
				$w = new Worker;

				if ($this->params->get('menu-meta_description'))
				{
					$desc = $w->parseMessageForPlaceHolder($this->params->get('menu-meta_description'), $row);
					$this->document->setDescription($desc);
				}

				if ($this->params->get('menu-meta_keywords'))
				{
					$keywords = $w->parseMessageForPlaceHolder($this->params->get('menu-meta_keywords'), $row);
					$this->document->setMetadata('keywords', $keywords);
				}

				if ($this->params->get('robots'))
				{
					$this->document->setMetadata('robots', $this->params->get('robots'));
				}
			}
		}
	}
}
