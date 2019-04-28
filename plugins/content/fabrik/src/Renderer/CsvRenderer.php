<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Content.fabrik
 * @copyright   Copyright (C) 2005-2019  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Plugin\Content\Fabrik\Renderer;


use Fabrik\Component\Fabrik\Site\Controller\AbstractSiteController;
use Fabrik\Component\Fabrik\Site\Model\FabrikSiteModel;

class CsvRenderer extends ListRenderer
{
	/**
	 * @param AbstractSiteController $controller
	 * @param string                 $viewName
	 * @param string                 $cacheId
	 *
	 * @return FabrikSiteModel
	 *
	 * @since 4.0
	 */
	protected function getModel(AbstractSiteController $controller, string $viewName, string $cacheId): FabrikSiteModel
	{
		return parent::getModel($controller, 'list', $cacheId);
	}
}