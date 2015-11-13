/**
 * FilterToggle
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

/*jshint mootools: true */
/*global Fabrik:true, fconsole:true, Joomla:true, CloneObject:true, $H:true,unescape:true */

/* can be used to hide filters and show then when the list title is clicked
 * also puts the clear filter and go button underneath the focused filter
 */
FabFilterToggle = my.Class({
    constructor: function (ref) {
        var list = $('#list_' + ref),
            form = $('#listform_' + ref);
        Fabrik.addEvent('fabrik.list.update', function (l) {
            if (l.id === ref) {
                list.find('.fabrik___heading span.filter').hide();
            }
            return true;
        });

        list.find('span.heading').each(function (h) {
            var f = $(this).next(), i;
            if (f.length > 0) {
                $(this).addClass('filtertitle');
                $(this).css('cursor', 'pointer');
                f.find('input').data('placeholder', $(this).get('text'));
                f.hide();
            }
        });
        list.on('click', 'span.heading', function () {
            var f = $(this).next();
            if (f.length > 0) {
                f.toggle();
                var i = form.find('.fabrikFilterContainer');
                var offsetP = list.getOffsetParent() ? list.getOffsetParent() : document.body;
                var p = f.getPosition(offsetP);
                i.setPosition({'x': p.x - 5, 'y': p.y + f.getSize().y});
                if (f.css('display') === 'none') {
                    i.hide();
                } else {
                    i.show();
                }
            }
        });

        form.find('.clearFilters').on('click', function () {
            form.find('.fabrikFilterContainer').hide();
            form.find('.fabrik___heading .filter').hide();
        });
        form.find('.fabrik_filter_submit').on('click', function () {
            form.find('.fabrikFilterContainer').hide();
            form.find('.fabrik___heading .filter').hide();
        });
    }
});