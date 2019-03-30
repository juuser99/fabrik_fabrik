<?php
/**
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Component\Fabrik\Site\Controller\DetailsController;
use Fabrik\Component\Fabrik\Site\Controller\FormController;
use Fabrik\Component\Fabrik\Site\Helper\ControllerHelper;
use Fabrik\Helpers\Html;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

// Load front end language file as well
/** @var CMSApplication $app */
$app  = Factory::getApplication();

$lang = $app->getLanguage();
$lang->load('com_fabrik', JPATH_BASE . '/components/com_fabrik');

if (!defined('COM_FABRIK_FRONTEND'))
{
	throw new \Exception(Text::_('COM_FABRIK_SYSTEM_PLUGIN_NOT_ACTIVE'));
}

Html::framework();

$formId = (int) $params->get('formid');

if (empty($formId))
{
	throw new \InvalidArgumentException('No form selected in Fabrik form module!');
}

$readonly = $params->get('readonly', '0');
$layout      = $params->get('template', 'default');

$moduleAjax      = $params->get('formmodule_useajax', true);

$inputVars   = [
	// $$$rob for table views in category blog layouts when no layout specified in {} the blog layout
	// was being used to render the table - which was not found which gave a 500 error
	'layout' => $layout,
	'formid' => $formId,
	'ajax'   => $moduleAjax,

];

$usersConfig = ComponentHelper::getParams('com_fabrik');
$rowid       = (string) $params->get('row_id', '');
$usersConfig->set('rowid', $rowid);

$usekey = $params->get('usekey', '');
if (!empty($usekey))
{
	$inputVars['usekey'] = $usekey;
}

$controllerHelper = new ControllerHelper();
$controllerHelper->setPropertyVars(['isMambot' => true]);

if ($readonly == 1)
{
	$inputVars['view'] = 'details';

	$controllerHelper
		->setInputVars($inputVars)
		->dispatchController(DetailsController::class);
}
else
{
	$inputVars['view'] = 'form';

	$controllerHelper
		->setInputVars($inputVars)
		->dispatchController(FormController::class);
}
