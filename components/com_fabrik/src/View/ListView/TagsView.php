<?php
/**
 * Fabrik Raw Form View
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Component\Fabrik\Site\View\ListView;

// No direct access
use Fabrik\Component\Fabrik\Site\Model\GroupModel;

defined('_JEXEC') or die('Restricted access');

/**
 * Fabrik Raw Form View
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       4.0
 */
class TagsView extends BaseView
{
	/**
	 * Execute and display a template script.
	 *
	 * @param   string $tpl The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise a JError object.
	 *
	 * @since 4.0
	 */
	public function display($tpl = null)
	{
		$input = $this->app->input;

		$model = $this->getModel();
		$model->setId($input->getInt('listid'));
		// Get the active menu item

		if (!parent::access($model))
		{
			exit;
		}

		$form  = $model->getFormModel();
		$joins = $model->getJoins();
		$form->getJoinGroupIds($joins);

		//$params = $model->getParams();
		//$params->def('icons', $this->app->get('icons'));
		//$pop = ($input->get('tmpl') == 'component') ? 1 : 0;
		//$params->set('popup', $pop);
		$view = $input->get('view', 'list');

		$groups    = $form->getGroupsHiarachy();
		$gkeys     = array_keys($groups);
		$JSONarray = array();
		$JSONHtml  = array();

		for ($i = 0; $i < count($gkeys); $i++)
		{
			/** @var GroupModel $groupModel */
			$groupModel  = $groups[$gkeys[$i]];
			$groupTable  = $groupModel->getGroup();
			$group       = new \stdClass;
			$groupParams = $groupModel->getParams();
			$aElements   = array();

			// Check if group is actually a table join
			$repeatGroup   = 1;
			$foreignKey    = null;
			$elementModels = $groupModel->getPublishedElements();

			foreach ($elementModels as $elementModel)
			{
				$elId     = $elementModel->getElement()->id;
				$fullname = $elementModel->getFullName(true, false);

				if ($elementModel->getElement()->plugin == 'tags' && $elId == $input->get('elID'))
				{
					$data = $elementModel->allTagsJSON($elId);
					foreach ($data as $d)
					{
						if (stristr($d->text, $input->get('like')))
						{
							$tagdata[] = $d;
						}
					}
				}

			}
		}
		echo json_encode($tagdata);
	}
}
