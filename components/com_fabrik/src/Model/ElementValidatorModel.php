<?php
/**
 * Fabrik Element Validator Model
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Component\Fabrik\Site\Model;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Html;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Fabrik\Component\Fabrik\Site\Plugin\AbstractElementPlugin;
use Fabrik\Component\Fabrik\Site\Plugin\AbstractValidationRulePlugin;
use Joomla\String\StringHelper;
use Fabrik\Helpers\ArrayHelper as FArrayHelper;
use Fabrik\Helpers\Worker;

/**
 * Fabrik Element Validator Model
 * - Helper class for dealing with groups of attached validation rules.
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       4.0
 */
class ElementValidatorModel extends FabrikSiteModel
{
	/**
	 * Validation objects associated with the element
	 *
	 * @var array
	 *
	 * @since 4.0
	 */
	protected $validations = null;

	/**
	 * Element model
	 *
	 * @var AbstractElementPlugin
	 *
	 * @since 4.0
	 */
	protected $elementPlugin = null;

	/**
	 * Icon image render options
	 *
	 * @var array
	 *
	 * @since 4.0
	 */
	protected $iconOpts = array('icon-class' => 'small');

	/**
	 * Set the element model - an instance of this class is linked to one element model
	 *
	 * @param   AbstractElementPlugin $elementPlugin Element model
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	public function setElementPlugin(AbstractElementPlugin $elementPlugin)
	{
		$this->elementPlugin = $elementPlugin;
	}

	/**
	 * Loads in element's published validation objects
	 *
	 * @return  AbstractValidationRulePlugin[]    Validation objects
	 *
	 * @since 4.0
	 */
	public function findAll()
	{
		if (isset($this->validations))
		{
			return $this->validations;
		}

		$params         = $this->elementPlugin->getParams();
		$validations    = (array) $params->get('validations', 'array');
		$usedPlugins    = (array) FArrayHelper::getValue($validations, 'plugin', array());
		$published      = FArrayHelper::getValue($validations, 'plugin_published', array());
		$showIcon       = FArrayHelper::getValue($validations, 'show_icon', array());
		$validateIn     = FArrayHelper::getValue($validations, 'validate_in', array());
		$validationOn   = FArrayHelper::getValue($validations, 'validation_on', array());
		$mustValidate   = FArrayHelper::getValue($validations, 'must_validate', array());
		$validateHidden = FArrayHelper::getValue($validations, 'validate_hidden', array());

		$pluginManager = Worker::getPluginManager();
		$pluginManager->getPlugInGroup('validationrule');
		$c                 = 0;
		$this->validations = array();
		/** @var CMSApplication $app */
		$app        = Factory::getApplication();
		$dispatcher = $app->getDispatcher();
		PluginHelper::importPlugin('fabrik_validationrule');
		$i = 0;

		foreach ($usedPlugins as $usedPlugin)
		{
			if ($usedPlugin !== '')
			{
				$isPublished = FArrayHelper::getValue($published, $i, true);

				if ($isPublished)
				{
					$class        = 'PlgFabrik_Validationrule' . StringHelper::ucfirst($usedPlugin);
					$conf         = array();
					$conf['name'] = StringHelper::strtolower($usedPlugin);
					$conf['type'] = StringHelper::strtolower('fabrik_Validationrule');

					/** @var AbstractValidationRulePlugin $plugIn */
					$plugIn = new $class($dispatcher, $conf);
					PluginHelper::getPlugin('fabrik_validationrule', $usedPlugin);
					$plugIn->elementPlugin = $this->elementPlugin;
					$this->validations[]   = $plugIn;

					// Set params relative to plugin render order
					$plugIn->setParams($params, $i);

					$plugIn->getParams()->set('show_icon', FArrayHelper::getValue($showIcon, $i, true));
					$plugIn->getParams()->set('validate_in', FArrayHelper::getValue($validateIn, $i, 'both'));
					$plugIn->getParams()->set('validation_on', FArrayHelper::getValue($validationOn, $i, 'both'));
					$plugIn->getParams()->set('must_validate', FArrayHelper::getValue($mustValidate, $i, '0'));
					$plugIn->getParams()->set('validate_hidden', FArrayHelper::getValue($validateHidden, $i, '1'));
					$plugIn->js();
					$c++;
				}
			}

			$i++;
		}

		return $this->validations;
	}

	/**
	 * Should the icon be shown
	 *
	 * @return boolean
	 *
	 * @since 4.0
	 */
	private function showIcon()
	{
		$validations = $this->findAll();

		foreach ($validations as $v)
		{
			if ($v->getParams()->get('show_icon'))
			{
				return true;
			}
		}

		$internal = $this->elementPlugin->internalValidationIcon();

		if ($internal !== '')
		{
			return true;
		}

		return false;
	}

	/**
	 * Get the icon
	 * - If showIcon() false - show question-sign for hover tip txt indicator
	 * - If one validation - use the icon specified in the J fabrik_validation settings (default to star)
	 * - If more than one return default j2.5/j3 img
	 *
	 * @param   int $c Validation plugin render order
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	public function getIcon($c = null)
	{
		$validations = $this->findAll();

		if (!$this->showIcon())
		{
			return '';
		}

		if (!empty($validations))
		{

			if (is_null($c))
			{
				return $validations[0]->iconImage();
			}
			else
			{
				return $validations[$c]->iconImage();
			}
		}

		$internal = $this->elementPlugin->internalValidationIcon();

		if ($internal !== '')
		{
			return $internal;
		}

		return 'star.png';
	}

	/**
	 * Get the array data use to set up the javascript watch element
	 *
	 * @param   int $repeatCounter Repeat group counter
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function jsWatchElements($repeatCounter = 0)
	{
		$validationEls = array();
		$validations   = $this->findAll();

		if (!empty($validations) && $this->elementPlugin->isEditable())
		{
			$watchElements = $this->elementPlugin->getValidationWatchElements($repeatCounter);

			foreach ($watchElements as $watchElement)
			{
				$o               = new \stdClass;
				$o->id           = $watchElement['id'];
				$o->triggerEvent = $watchElement['triggerEvent'];
				$validationEls[] = $o;
			}
		}

		return $validationEls;
	}

	/**
	 * Get the main validation icon to show next to the element's label
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	public function labelIcons()
	{
		$tmpl        = $this->elementPlugin->getFormModel()->getTmpl();
		$validations = array_unique($this->findAll());
		$emptyIcon   = $this->getIcon();
		$icon        = empty($emptyIcon) && empty($validations) ? "" : Html::image($emptyIcon, 'form', $tmpl, $this->iconOpts) . ' ';

		return $icon;
	}

	/**
	 * Does the element have validations - checks assigned and internal validations
	 *
	 * @return boolean
	 *
	 * @since 4.0
	 */
	public function hasValidations()
	{
		$validations = $this->findAll();

		if (!empty($validations) || $this->elementPlugin->internalValidationText() !== '')
		{
			return true;
		}

		return false;
	}

	/**
	 * Create hover tip text for validations
	 *
	 * @return  array  Messages
	 *
	 * @since 4.0
	 */
	public function hoverTexts()
	{
		$texts = array();

		if ($this->elementPlugin->isEditable())
		{
			$tmpl        = $this->elementPlugin->getFormModel()->getTmpl();
			$validations = array_unique($this->findAll());

			foreach ($validations as $c => $validation)
			{
				$texts[] = $validation->getHoverText($c, $tmpl);
			}

			$internal = $this->elementPlugin->internalValidationText();

			if ($internal !== '')
			{
				$i       = $this->elementPlugin->internalValidationIcon();
				$icon    = Html::image($i, 'form', $tmpl, $this->iconOpts);
				$texts[] = $icon . ' ' . $internal;
			}
		}

		return $texts;
	}
}
