<?php
/**
 * Email list plugin view
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.email
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Plugin\FabrikList\Email\View\Popupwin;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Html;
use Fabrik\Helpers\Worker;
use Fabrik\Plugin\FabrikList\Email\Model\EmailModel;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseView;

/**
 * Email list plugin view
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.email
 * @since       4.0
 */
class HtmlView extends BaseView
{
	/**
	 * Display the view
	 *
	 * @param   string $tmpl Template
	 *
	 * @return  $this
	 *
	 * @since 4.0
	 */
	public function display($tmpl = null)
	{
		$w      = new Worker();
		$params = $this->getParams();
		/** @var CMSApplication $app */
		$app = Factory::getApplication();
		/** @var EmailModel $model */
		$model       = $this->getModel();
		$input       = $app->input;
		$renderOrder = $input->getInt('renderOrder');

		$path = JPATH_ROOT . '/plugins/fabrik_list/email/tmpl/popupwin/' . $tmpl;
		$this->_setPath('template', $path);

		$this->showToField = $model->getShowToField();
		$records           = $model->getRecords();

		if (count($records) == 0)
		{
			$app->enqueueMessage(Text::_('PLG_LIST_EMAIL_ERR_NONE_MAILED'), 'notice');

			return $this;
		}

		$this->recordcount     = count($records);
		$this->renderOrder     = $renderOrder;
		$this->recordids       = implode(',', $records);
		$this->listid          = $this->get('id', 'list');
		$this->showSubject     = $model->getShowSubject();
		$this->subject         = $model->getSubject();
		$this->message         = $model->getMessage();
		$this->allowAttachment = $model->getAllowAttachment();
		$this->editor          = $model->getEditor();
		$this->toType          = $model->_toType();
		$this->emailTo         = $model->_emailTo();
		$this->params          = $model->getParams();
		$this->listEmailTo     = $model->formModel->getElementList('list_email_to');
		$this->addressBook     = $model->addressBook();
		$this->additionalQS    = $w->parseMessageForPlaceHolder($params->get('list_email_additional_qs', ''));


		$srcs = Html::framework();
		Html::iniRequireJs();
		Html::script($srcs);

		return parent::display();
	}
}
