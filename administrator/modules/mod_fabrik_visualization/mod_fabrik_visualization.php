<?php
/**
 * Fabrik Visualization module - display a fabrik visualization within a Joomla page
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Component\Fabrik\Site\Controller\ControllerFactory;
use Fabrik\Component\Fabrik\Site\Helper\PluginControllerHelper;
use Fabrik\Helpers\Worker;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

// Load front end language file as well
/** @var CMSApplication $app */
$app  = Factory::getApplication();
$lang = $app->getLanguage();
$lang->load('com_fabrik', JPATH_SITE . '/components/com_fabrik');

if (!defined('COM_FABRIK_FRONTEND'))
{
	throw new \RuntimeException(Text::_('COM_FABRIK_SYSTEM_PLUGIN_NOT_ACTIVE'), 400);
}

$input = $app->input;

// $$$rob looks like including the view does something to the layout variable
$origLayout = $input->get('layout', '', 'string');
$input->set('layout', $origLayout);
$document = $app->getDocument();

$id = intval($params->get('id', 1));

/*
 * This all works fine for a list
 * going to try to load a package so u can access the form and list
 */
$moduleclass_sfx = $params->get('moduleclass_sfx', '');

$viewName = 'visualization';
$db       = Worker::getDbo();
$query    = $db->getQuery(true);
$query->select('plugin')->from('#__{package}_visualizations')->where('id = ' . (int) $id);
$db->setQuery($query);
$name = str_replace('_', '', ucwords($db->loadResult(), '_'));

$controllerClass = sprintf('Fabrik\\Plugin\\FabrikVisualization\\%s\\Controller\\%sController', $name, $name);
(new PluginControllerHelper())
	->setInputVars(
		[
			'view'            => $viewName,
			'visualizationid' => $id
		]
	)
	->dispatchController($controllerClass);
