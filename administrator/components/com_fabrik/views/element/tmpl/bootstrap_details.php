<?php
/**
 * Admin Element Edit - Details Tmpl
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

?>
    <div class="row">
        <div class="col-md-12">
            <fieldset class="form-horizontal">
                <legend><?php echo FText::_('COM_FABRIK_DETAILS');?></legend>
                <input type="hidden" id="name_orig" name="name_orig" value="<?php echo $this->item->name; ?>" />
                <input type="hidden" id="plugin_orig" name="plugin_orig" value="<?php echo $this->item->plugin; ?>" />

                <?php
                foreach ($this->form->getFieldset('details') as $this->field) :
                    echo $this->loadTemplate('control_group');
                endforeach;

                foreach ($this->form->getFieldset('details2') as $this->field) :
                    echo $this->loadTemplate('control_group');
                endforeach;
                ?>

            </fieldset>

            <fieldset class="form-horizontal">
                <div id="plugin-container">
                <?php echo $this->pluginFields; ?>
                </div>
            </fieldset>
        </div>
    </div>

