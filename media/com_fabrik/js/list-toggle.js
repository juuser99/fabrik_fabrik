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
        jQuery(selector).click(function (e) {
            e.stopPropagation();
        });

        // Set up toggle events for elements
        form.on('mouseup', 'a[data-toggle-col]', function (e, btn) {
            var state = $(this).data('toggle-state'),
                col = $(this).data('toggle-col');
            self.toggleColumn(col, state, btn);
        });

        // Toggle events for groups (toggles all elements in group)
        form.on('mouseup', 'a[data-toggle-group]', function (e, group) {
            var state = $(this).data('toggle-state'), muted,
                groupName = $(this).data('toggle-group'),
                links = $('a[data-toggle-parent-group=' + groupName + ']');

            links.each(function (btn) {
                var col = btn.data('toggle-col');
                self.toggleColumn(col, state, btn);
            });

            state = state === 'open' ? 'close' : 'open';
            muted = state === 'open' ? '' : ' muted';
            group.find('i')[0].className = 'icon-eye-' + state + muted;
            group.data('toggle-state', state);

        }.bind(this));
    },

    /**
     * Toggle column
     *
     * @param col   Element name
     * @param state Open/closed
     * @param btn   Button/link which initiated the toggle
     */
    toggleColumn: function (col, state, btn) {
        var muted;
        state = state === 'open' ? 'close' : 'open';

        if (state === 'open') {
            document.find('.fabrik___heading .' + col).show();
            document.find('.fabrikFilterContainer .' + col).show();
            document.find('.fabrik_row  .' + col).show();
            muted = '';
        } else {
            document.find('.fabrik___heading .' + col).hide();
            document.find('.fabrikFilterContainer .' + col).hide();
            document.find('.fabrik_row  .' + col).hide();
            muted = ' muted';
        }

        btn.find('i')[0].className = 'icon-eye-' + state + muted;
        btn.data('toggle-state', state);
    }
});
