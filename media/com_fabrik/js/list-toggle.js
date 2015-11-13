/**
 * List Toggle
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbListToggle = my.Class({

    constructor: function (form) {
        var self = this,
            selector = '#' + form.id + ' .togglecols .dropdown-menu a, #' + form.id + ' .togglecols .dropdown-menu li';
        // Stop dropdown closing on click
        $(selector).click(function (e) {
            e.stopPropagation();
        });

        // Set up toggle events for elements
        form.on('mouseup', 'a[data-toggle-col]', function () {
            var state = $(this).data('toggle-state'),
                col = $(this).data('toggle-col');
            self.toggleColumn(col, state, $(this));
        });

        // Toggle events for groups (toggles all elements in group)
        form.on('mouseup', 'a[data-toggle-group]', function () {
            var state = $(this).data('toggle-state'), muted,
                groupName = $(this).data('toggle-group'),
                links = $('a[data-toggle-parent-group=' + groupName + ']');

            links.each(function () {
                var col = $(this).data('toggle-col');
                self.toggleColumn(col, state, $(this));
            });

            state = state === 'open' ? 'close' : 'open';
            muted = state === 'open' ? '' : ' muted';
            $(this).find('i')[0].className = 'icon-eye-' + state + muted;
            $(this).data('toggle-state', state);
        });
    },

    /**
     * Toggle column
     *
     * @param {string} col   Element name
     * @param {string} state Open/closed
     * @param {jQuery} btn   Button/link which initiated the toggle
     */
    toggleColumn: function (col, state, btn) {
        var muted;
        state = state === 'open' ? 'close' : 'open';

        if (state === 'open') {
            $('.fabrik___heading .' + col).show();
            $('.fabrikFilterContainer .' + col).show();
            $('.fabrik_row  .' + col).show();
            muted = '';
        } else {
            $('.fabrik___heading .' + col).hide();
            $('.fabrikFilterContainer .' + col).hide();
            $('.fabrik_row  .' + col).hide();
            muted = ' muted';
        }

        btn.find('i')[0].className = 'icon-eye-' + state + muted;
        btn.data('toggle-state', state);
    }
});
