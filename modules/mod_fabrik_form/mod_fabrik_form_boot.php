<?php
/**
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Component\Fabrik\Site\Controller\AbstractSiteController;
use Fabrik\Component\Fabrik\Site\Controller\DetailsController;
use Fabrik\Component\Fabrik\Site\Controller\FormController;
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

$input = $app->input;

$origLayout = $input->get('layout');
$origView   = $input->get('view');
$origAjax   = $input->get('ajax');
$origFormid = $input->getInt('formid');

Html::framework();

$input->set('layout', $origLayout);

$formId = (int) $params->get('formid');

if (empty($formId))
{
	throw new \InvalidArgumentException('No form selected in Fabrik form module!');
}

$readonly = $params->get('readonly', '0');
if ($readonly == 1)
{
	$controller = AbstractSiteController::createController(DetailsController::class);
	$input->set('view', 'details');
}
else
{
	$controller = AbstractSiteController::createController(FormController::class);
	$input->set('view', 'form');
}

$layout      = $params->get('template', 'default');
$usersConfig = ComponentHelper::getParams('com_fabrik');
$rowid       = (string) $params->get('row_id', '');
$usersConfig->set('rowid', $rowid);

$usekey = $params->get('usekey', '');

if (!empty($usekey))
{
	$input->set('usekey', $usekey);
}

$moduleclass_sfx = $params->get('moduleclass_sfx', '');
$moduleAjax      = $params->get('formmodule_useajax', true);


/* $$$rob for table views in category blog layouts when no layout specified in {} the blog layout
 * was being used to render the table - which was not found which gave a 500 error
*/
$input->set('layout', $layout);

// Display the view
$controller->isMambot = true;
$input->set('formid', $formId);

$input->set('ajax', $moduleAjax);
echo $controller->display();

// Reset the layout and view etc for when the component needs them
$input->set('formid', $origFormid);
$input->set('ajax', $origAjax);
$input->set('layout', $origLayout);
$input->set('view', $origView);
