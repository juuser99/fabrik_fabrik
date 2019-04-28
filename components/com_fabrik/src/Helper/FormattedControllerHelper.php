<?php
/**
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2019  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Component\Fabrik\Site\Helper;


use Joomla\CMS\Application\CMSApplication;
use Joomla\Input\Input;
use Joomla\String\StringHelper;

class FormattedControllerHelper
{
	/**
	 * @param Input  $input
	 * @param string $defaultController
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	public static function getControllerName(CMSApplication $app, string $defaultController): string
	{
		$input = $app->input;
		$format              = $input->get('format');
		$requestedController = $input->get('controller');
		$controllerName      = ($requestedController) ? $requestedController : $defaultController;

		// Special handling of formats - J4 hack since it no longer recognizes formatted controllers
		// @todo - refactor these to views
		if ($format)
		{
			$testControllerName = StringHelper::ucfirst($controllerName) . StringHelper::ucfirst($format);
			$testClassName      = sprintf(
				'Fabrik\Component\Fabrik\%s\Controller\%sController',
				StringHelper::ucfirst($app->getName()),
				$testControllerName
			);

			if (class_exists($testClassName))
			{
				$controllerName .= StringHelper::ucfirst($format);
			}
		}

		return $controllerName;
	}
}