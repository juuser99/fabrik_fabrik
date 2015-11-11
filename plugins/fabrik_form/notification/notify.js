/**
 * List Notification
 *
 * @copyright: Copyright (C) 2005-2013, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var Notify = my.Class({

    constructor: function (el, options) {
        var self = self,
            target = $('#' + el),
            notify;
        this.options = options;
        if (target.css('display') === 'none') {
            target = target.parent();
        }

        target.on('change', function (e) {
            notify = target.prop('checked') ? 1 : 0;
            Fabrik.loader.start(target, Joomla.JText._('COM_FABRIK_LOADING'));
            $.ajax({
                url : 'index.php?option=com_fabrik&task=plugin.pluginAjax&plugin=notification&method=toggleNotification',
                data: {
                    g                  : 'form',
                    format             : 'raw',
                    fabrik_notification: 1,
                    listid             : self.options.listid,
                    formid             : self.options.formid,
                    rowid              : self.options.rowid,
                    notify             : notify
                },

            }).done(function (r) {
                window.alert(r);
                Fabrik.loader.stop(target);
            });

        });
    }
});
