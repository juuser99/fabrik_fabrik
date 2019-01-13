<?php
/**
 * Admin List CSV controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Joomla\Component\Fabrik\Administrator\Controller;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Component\Fabrik\Administrator\Model\FabrikModel;
use Joomla\Component\Fabrik\Site\Model\ListModel;

/**
 * Admin List CSV controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       4.0
 */
class ListCsvController extends AbstractFormController
{
    /**
     * The prefix to use with controller messages.
     *
     * @var	string
     *
     * @since 4.0
     */
    protected $text_prefix = 'COM_FABRIK_LIST';

	/**
	 * @var string
	 *
	 * @since 4.0
	 */
    protected $context = 'list';

    /**
     * Show the lists data in the admin
     *
     * @return  void
     *
     * @since 4.0
     */
    public function view()
    {
        $app = Factory::getApplication();
        $input = $app->input;
        $cid = $input->get('cid', array(0), 'array');
        $cid = $cid[0];
        $cid = $input->getInt('listid', $cid);

        // Grab the model and set its id
        $model = FabrikModel::getInstance(ListModel::class);
        $model->setState('list.id', $cid);
        $viewType = Factory::getDocument()->getType();

        // Use the front end list renderer
	    // @todo refactor to j4
        $this->setPath('view', COM_FABRIK_FRONTEND . '/views');
        $viewLayout	= $input->get('layout', 'default');
        $view = $this->getView($this->view_item, $viewType, 'FabrikView');
        $view->setModel($model, true);

        // Set the layout
        $view->setLayout($viewLayout);
        ToolbarHelper::title(Text::_('COM_FABRIK_MANAGER_LISTS'), 'list');
        $view->display();
    }
}