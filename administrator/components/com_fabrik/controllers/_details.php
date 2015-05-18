<?php
/**
 * Details controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Admin\Helpers\Fabrik;

require_once 'fabcontrollerform.php';

/**
 * Details controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.0
 */
class FabrikAdminControllerDetails extends FabControllerForm
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var	 string
	 */
	protected $text_prefix = 'COM_FABRIK_FORM';

	/**
	 * Show the form in the admin
	 *
	 * @return  void
	 */

	public function view()
	{
		$document = JFactory::getDocument();
		$model = new \Fabrik\Admin\Models\Form;
		$this->input->set('view', 'details');
		$viewType = $document->getType();
		$this->setPath('view', COM_FABRIK_FRONTEND . '/views');
		$viewLayout	= $this->input->get('layout', 'default');
		$this->name = 'Fabrik';
		$view = $this->getView('Form', $viewType, '');
		$view->setModel($model, true);

		// Set the layout
		$view->setLayout($viewLayout);

		// @TODO check for cached version
		JToolBarHelper::title(FText::_('COM_FABRIK_MANAGER_FORMS'), 'forms.png');

		$view->display();
		Fabrik::addSubmenu($this->getName());
	}
}
