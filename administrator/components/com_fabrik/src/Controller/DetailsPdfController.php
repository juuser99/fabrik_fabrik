<?php
/**
 * Details controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

namespace Fabrik\Component\Fabrik\Administrator\Controller;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Fabrik\Component\Fabrik\Administrator\Helper\FabrikAdminHelper;
use Fabrik\Component\Fabrik\Administrator\Model\FabModel;
use Fabrik\Component\Fabrik\Site\Model\FormModel;

/**
 * Details controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       4.0
 */
class DetailsPdfController extends AbstractFormController
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var	 string
	 *
	 * @since 4.0
	 */
	protected $text_prefix = 'COM_FABRIK_FORM';

	/**
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $context = 'details';

	/**
	 * Show the form in the admin
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	public function view()
	{
		$document = Factory::getDocument();
		/** @var FormModel $model */
		$model = FabModel::getInstance(FormModel::class);
		$app = Factory::getApplication();
		$input = $app->input;
		$input->set('tmpl', 'component');
		$input->set('view', 'details');
		$viewType = $document->getType();
		// @todo refactor to j4
		$this->setPath('view', COM_FABRIK_FRONTEND . '/views');
		$viewLayout	= $input->get('layout', 'default');
		$this->name = 'Fabrik';
		$view = $this->getView('Form', $viewType, '');
		$view->setModel($model, true);

		// Set the layout
		$view->setLayout($viewLayout);

		// @TODO check for cached version
		ToolbarHelper::title(Text::_('COM_FABRIK_MANAGER_FORMS'), 'file-2');

		$view->display();
		FabrikAdminHelper::addSubmenu($input->getWord('view', 'lists'));
	}
}
