<?php
/**
 * Admin form module
 *
 * @package     Joomla.Administrator
 * @subpackage  mod_fabrik_form
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Component\Fabrik\Site\Controller\FormController;
use Fabrik\Component\Fabrik\Site\Helper\PluginControllerHelper;
use Fabrik\Helpers\Html;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;

/** @var CMSApplication $app */
$app   = Factory::getApplication();
$input = $app->input;

// Load front end language file as well
$lang = $app->getLanguage();
$lang->load('com_fabrik', JPATH_BASE . '/components/com_fabrik');

if (!defined('COM_FABRIK_FRONTEND'))
{
	throw new \RuntimeException(FText::_('COM_FABRIK_SYSTEM_PLUGIN_NOT_ACTIVE'), 400);
}

Html::framework();

// $$$rob looks like including the view does something to the layout variable
$origLayout = $input->get('layout');
$input->set('layout', $origLayout);

$formId      = (int) $params->get('formid', 1);
$rowid       = (int) $params->get('row_id', 0);
$layout      = $params->get('template', '');
$usersConfig = ComponentHelper::getParams('com_fabrik');
$usersConfig->set('rowid', $rowid);
$moduleAjax = $params->get('formmodule_useajax', true);

$inputVars = [
	'formid' => $formId,
	'view'   => 'form',
	'ajax'   => $moduleAjax
];

/*
 * For table views in category blog layouts when no layout specified in {} the blog layout
 * was being used to render the table - which was not found which gave a 500 error
 */
if ($layout !== '')
{
	$inputVars['layout'] = $layout;
}

(new PluginControllerHelper())
	->setInputVars($inputVars)
	->setPropertyVars(
		[
			'isMambot' => true,
			'cacheId'  => 'admin_module'
		]
	)
	->dispatchController(FormController::class);
