<?php
/**
 * List Article update plugin
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.article
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

use Fabrik\Component\Fabrik\Administrator\Model\FabrikModel;
use Fabrik\Component\Fabrik\Site\Model\PluginManagerModel;
use Fabrik\Component\Fabrik\Site\Plugin\AbstractListPlugin;
use Joomla\CMS\Language\Text;

/**
 * Add an action button to the list to enable update of content articles
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.article
 * @since       3.0
 */
class PlgFabrik_ListArticle extends AbstractListPlugin
{
	/**
	 * Button prefix
	 *
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $buttonPrefix = 'file';

	/**
	 * Prep the button if needed
	 *
	 * @param   array  &$args Arguments
	 *
	 * @return  bool
	 *
	 * @since 4.0
	 */
	public function button(&$args)
	{
		parent::button($args);

		return true;
	}

	/**
	 * Get the parameter name that defines the plugins acl access
	 *
	 * @return  string
	 *
	 * @since 4.0
	 */
	protected function getAclParam()
	{
		return 'access';
	}

	/**
	 * Can the plug-in select list rows
	 *
	 * @return  bool
	 *
	 * @since 4.0
	 */
	public function canSelectRows()
	{
		return true;
	}

	/**
	 * Get the button label
	 *
	 * @return  string
	 *
	 * @since 4.0
	 */
	protected function buttonLabel()
	{
		return Text::_('PLG_LIST_ARTICLE_UPDATE_ARTICLE');
	}

	/**
	 * Do the plug-in action
	 *
	 * @param   array $opts Custom options
	 *
	 * @return  bool
	 *
	 * @since 4.0
	 */
	public function process($opts = array())
	{
		$model     = $this->getModel();
		$input     = $this->app->input;
		$ids       = $input->get('ids', array(), 'array');
		$origRowId = $input->get('rowid');
		/** @var PluginManagerModel $pluginManager */
		$pluginManager = FabrikModel::getInstance(PluginManagerModel::class);

		// Abstract version of the form article plugin
		/** @var PlgFabrik_FormArticle $articlePlugin */
		$articlePlugin = $pluginManager->getPlugin('article', 'form');

		$formModel  = $model->getFormModel();
		$formParams = $formModel->getParams();
		$plugins    = $formParams->get('plugins');

		foreach ($plugins as $c => $type)
		{
			if ($type === 'article')
			{
				// Iterate over the records - load row & update articles
				foreach ($ids as $id)
				{
					$input->set('rowid', $id);
					$formModel->setRowId($id);
					$formModel->unsetData();
					$formModel->formData = $formModel->formDataWithTableName = $formModel->getData();
					$articlePlugin->setModel($formModel);
					$articlePlugin->setParams($formParams, $c);
					unset($articlePlugin->images);
					$articlePlugin->onAfterProcess();
				}
			}
		}

		$input->set('rowid', $origRowId);

		return true;
	}

	/**
	 * Get the message generated in process()
	 *
	 * @param   int $c plugin render order
	 *
	 * @return  string
	 *
	 * @since 4.0
	 */
	public function process_result($c)
	{
		$input = $this->app->input;
		$ids   = $input->get('ids', array(), 'array');

		return Text::sprintf('PLG_LIST_ARTICLES_UPDATED', count($ids));
	}

	/**
	 * Return the javascript to create an instance of the class defined in formJavascriptClass
	 *
	 * @param   array $args array [0] => string table's form id to contain plugin
	 *
	 * @return bool
	 *
	 * @since 4.0
	 */
	public function onLoadJavascriptInstance($args)
	{
		parent::onLoadJavascriptInstance($args);
		$opts             = $this->getElementJSOptions();
		$opts             = json_encode($opts);
		$this->jsInstance = "new FbListArticle($opts)";

		return true;
	}

	/**
	 * Load the AMD module class name
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	public function loadJavascriptClassName_result()
	{
		return 'FbListArticle';
	}
}
