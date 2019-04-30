<?php
/**
 * Insert Fabrik Content into Joomla Articles
 *
 * @package     Joomla.Plugin
 * @subpackage  Content.fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Plugin\Content\Fabrik\Parameter\ParameterBag;
use Fabrik\Plugin\Content\Fabrik\Parameter\ParameterParser;
use Fabrik\Plugin\Content\Fabrik\Renderer\CsvRenderer;
use Fabrik\Plugin\Content\Fabrik\Renderer\DetailsRenderer;
use Fabrik\Plugin\Content\Fabrik\Renderer\ElementRenderer;
use Fabrik\Plugin\Content\Fabrik\Renderer\FormCssRenderer;
use Fabrik\Plugin\Content\Fabrik\Renderer\FormRenderer;
use Fabrik\Plugin\Content\Fabrik\Renderer\ListRenderer;
use Fabrik\Plugin\Content\Fabrik\Renderer\TableRenderer;
use Fabrik\Plugin\Content\Fabrik\Renderer\ViewRenderer;
use Fabrik\Plugin\Content\Fabrik\Renderer\VisualizationRenderer;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;
use Joomla\String\StringHelper;

/**
 * Fabrik content plugin - renders forms, lists and visualizations
 *
 * @package     Joomla.Plugin
 * @subpackage  Content.fabrik
 * @since       1.5
 */
class PlgContentFabrik extends CMSPlugin
{
	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 * @since  3.7.0
	 */
	protected $autoloadLanguage = true;

	/**
	 * @var CMSApplication
	 * @since 4.0
	 */
	protected $app;

	/**
	 *  Prepare content method
	 *
	 * Method is called by the view
	 *
	 * @param string  $context The context of the content being passed to the plugin.
	 * @param object &$row     The article object.  Note $article->text is also available
	 * @param object &$params  The article params
	 * @param int     $page    The 'page' number
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	public function onContentPrepare($context, &$row, &$params, $page = 0)
	{
		// Load fabrik language
		$lang = $this->app->getLanguage();
		$lang->load('com_fabrik', JPATH_BASE . '/components/com_fabrik');

		if (!defined('COM_FABRIK_FRONTEND'))
		{
			throw new \RuntimeException(Text::_('COM_FABRIK_SYSTEM_PLUGIN_NOT_ACTIVE'), 400);
		}

		// Get plugin info
		$plugin = PluginHelper::getPlugin('content', 'fabrik');

		// $$$ hugh had to rename this, it was stomping on com_content and friends $params
		// $$$ which is passed by reference to us!
		$fParams = new Registry($plugin->params);

		// Simple performance check to determine whether bot should process further
		$botRegex = $fParams->get('botRegex') != '' ? $fParams->get('botRegex') : 'fabrik';

		if (StringHelper::strpos($row->text, '{' . $botRegex) === false)
		{
			return true;
		}

		/* $$$ hugh - hacky fix for nasty issue with IE, which (for gory reasons) doesn't like having our JS content
		 * wrapped in P tags.  But the default WYSIWYG editor in J! will automagically wrap P tags around everything.
		 * So let's just look for obvious cases of <p>{fabrik ...}</p>, and replace the P's with DIV's.
		 * Yes, it's hacky, but it'll save us a buttload of support work.
		 */
		$pregex    = "/<p>\s*{" . $botRegex . "\s*.*?}\s*<\/p>/i";
		$row->text = preg_replace_callback($pregex, array($this, 'preplace'), $row->text);

		// $$$ hugh - having to change this to use {[]}
		$regex     = "/{" . $botRegex . "\s+.*?}/i";
		$row->text = preg_replace_callback($regex, array($this, 'replace'), $row->text);
	}

	/**
	 * Unwrap placeholder text from possible <p> tags
	 *
	 * @param array $match preg matched {fabrik} tag
	 *
	 * @return  string
	 *
	 * @since 4.0
	 */
	protected function preplace($match)
	{
		$match = $match[0];
		$match = StringHelper::str_ireplace('<p>', '<div>', $match);
		$match = StringHelper::str_ireplace('</p>', '</div>', $match);

		return $match;
	}


	/**
	 * the function called from the preg_replace_callback - replace the {} with the correct HTML
	 *
	 * @param array $match plug-in match
	 *
	 * @return  string
	 *
	 * @since 4.0
	 */
	protected function replace(array $match): string
	{
		$parameterBag    = new ParameterBag($this->app->input);
		$parameterParser = new ParameterParser($this->app->input, Factory::getUser());

		$parameterParser->parse($match, $parameterBag);

		$usersConfig = ComponentHelper::getParams('com_fabrik');
		$usersConfig->set('rowid', '');

		if ($rowId = $parameterBag->getRowId())
		{
			$usersConfig->set('rowid', $rowId);

			// Set the rowid in the session so that print pages can grab it again
			$this->app->getSession()->set('fabrik.plgcontent.rowid', $rowId);
		}

		if ($parameterBag->getElement())
		{
			return (new ElementRenderer($parameterBag, $this->app))->render();
		}

		switch ($parameterBag->getViewName())
		{
			case 'csv':
				return (new CsvRenderer($parameterBag, $this->app))->render();
			case 'details':
				return (new DetailsRenderer($parameterBag, $this->app))->render();
			case 'form':
				return (new FormRenderer($parameterBag, $this->app))->render();
			case 'form_css':
				return (new FormCssRenderer($parameterBag, $this->app))->render();
			case 'list':
				return (new ListRenderer($parameterBag, $this->app))->render();
			case 'table':
				return (new TableRenderer($parameterBag, $this->app))->render();
			case 'view':
				return (new ViewRenderer($parameterBag, $this->app))->render();
			case 'visualization':
				return (new VisualizationRenderer($parameterBag, $this->app))->render();
			default:
				return '';
		}
	}
}
