<?php
/**
 * Import view
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Joomla\Component\Fabrik\Administrator\View\Import;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Html;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\FormView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Component\Fabrik\Administrator\Helper\FabrikAdminHelper;

/**
 * View class for importing csv file.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       4.0
 */
class HtmlView extends FormView
{
	/**
	 * Display the view
	 *
	 * @param   string $tpl Template
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	public function display($tpl = null)
	{
        $srcs = Html::framework();
        Html::script($srcs);
        Html::iniRequireJs();

		FabrikAdminHelper::setViewLayout($this);

		parent::display($tpl);
	}

	/**
	 * CSV file has been uploaded but we need to ask the user what to do with the new fields
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	public function chooseElementTypes()
	{
		$app             = Factory::getApplication();
		$this->drop_data = 0;
		$this->overwrite = 0;
		$input           = $app->input;
		$input->set('hidemainmenu', true);
		$this->chooseElementTypesToolBar();
		$session               = $app->getSession();
		$this->data            = $session->get('com_fabrik.csvdata');
		$this->matchedHeadings = $session->get('com_fabrik.matchedHeadings');
		$model                 = $this->getModel();
		$this->newHeadings     = $model->getNewHeadings();
		$this->headings        = $model->getHeadings();
		$pluginManager         = $this->getModel('pluginmanager');
		$this->table           = $model->getListModel()->getTable();
		$this->elementTypes    = $pluginManager->getElementTypeDd('field', 'plugin[]');
		$this->sample          = $model->getSample();
		$this->selectPKField   = $model->getSelectKey();
		$jform                 = $input->get('jform', array(), 'array');

		foreach ($jform as $key => $val)
		{
			$this->$key = $val;
		}

		parent::display('chooseElementTypes');
	}

	/**
	 * Add the 'choose element type' page toolbar
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	protected function chooseElementTypesToolBar()
	{
		$app   = Factory::getApplication();
		$input = $app->input;
		$input->set('hidemainmenu', true);
		ToolbarHelper::title(Text::_('COM_FABRIK_MANAGER_LIST_IMPORT'), 'list');
		$icon    = 'arrow-right-2';
		ToolbarHelper::custom('import.makeTableFromCSV', $icon, $icon, 'COM_FABRIK_CONTINUE', false);
		ToolbarHelper::cancel('import.cancel', 'JTOOLBAR_CANCEL');
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @since    1.6
	 *
	 * @return  void
	 */
	protected function addToolBar()
	{
		$app   = Factory::getApplication();
		$input = $app->input;
		$input->set('hidemainmenu', true);
		ToolbarHelper::title(Text::_('COM_FABRIK_MANAGER_LIST_IMPORT'), 'list');
		$icon    = 'arrow-right-2';
		ToolbarHelper::custom('import.doimport', $icon, $icon, 'COM_FABRIK_CONTINUE', false);
		ToolbarHelper::cancel('import.cancel', 'JTOOLBAR_CANCEL');
	}
}
