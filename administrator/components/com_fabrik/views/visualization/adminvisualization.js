/**
 * Admin Visualization Editor
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var AdminVisualization = my.Class(PluginManager, {

    options: {},

    initialize: function (options, lang) {
        this.options = $.extend(this.options, options);
        this.watchSelector();
    },

    watchSelector: function () {
        var self = this;
        $('#jform_plugin').on('change', function (e) {
            e.stopPropagation();
            self.changePlugin(e);
        });
    },

    changePlugin: function (e) {
        $.ajax({
            url           : 'index.php',
            'evalResponse': false,
            'evalScripts' : function (script, text) {
                this.script = script;
            }.bind(this),
            'data'        : {
                'option': 'com_fabrik',
                'task'  : 'visualization.getPluginHTML',
                'format': 'raw',
                'plugin': $(e.target).val()
            },
        }).done(function (r) {
            $('#plugin-container').html(r);
            Browser.exec(this.script);
            this.updateBootStrap();
        });
    }
});