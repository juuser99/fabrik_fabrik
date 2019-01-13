<?php
/**
 * Display the template
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Joomla\Component\Fabrik\Site\View\ListView;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Component\ComponentHelper;

/**
 * List YQL view class
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       4.0
 */
class YqlView extends BaseView
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
		$model       = $this->getModel();
		$input       = $this->app->input;
		$usersConfig = ComponentHelper::getParams('com_fabrik');
		$model->setId($input->getInt('listid', $usersConfig->get('listid')));
		$model->render();
		$table                  = $model->getTable();
		$this->doc->title       = $table->label;
		$this->doc->description = $table->introduction;
		$this->doc->copyright   = '';
		$this->doc->listid      = $table->id;
		$this->doc->items       = $model->getData();
	}
}
