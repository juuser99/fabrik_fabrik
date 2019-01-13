<?php
/**
 * PDF Form view class
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Component\Fabrik\Site\View\Form;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Html;
use Fabrik\Helpers\Worker;
use Joomla\CMS\Document\PdfDocument;
use Fabrik\Component\Fabrik\Site\Model\FormModel;

/**
 * PDF Form view class
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       4.0
 */
class PdfView extends BaseView
{
	/**
	 * Main setup routine for displaying the form/detail view
	 *
	 * @param   string $tpl template
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	public function display($tpl = null)
	{
		Worker::canPdf(true);

		if (parent::display($tpl) !== false)
		{
			Html::loadBootstrapCSS(true);

			/** @var PdfDocument $document */
			$document = $this->doc;

			/** @var FormModel $model */
			$model       = $this->getModel();
			$params      = $model->getParams();
			$size        = $this->app->input->get('pdf_size', $params->get('pdf_size', 'A4'));
			$orientation = $this->app->input->get('pdf_orientation', $params->get('pdf_orientation', 'portrait'));
			$document->setPaper($size, $orientation);
			$this->output();
		}
	}

	/**
	 * Set the page title
	 *
	 * @param   object  $w      parent worker
	 * @param   object $params parameters
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	protected function setTitle($w, $params)
	{
		parent::setTitle($w, $params);

		$model = $this->getModel();

		// Set the download file name based on the document title

		$layout             = $model->getLayout('form.fabrik-pdf-title');
		$displayData        = new \stdClass;
		$displayData->doc   = $this->doc;
		$displayData->model = $model;

		$this->doc->setName($layout->render($displayData));
	}
}
