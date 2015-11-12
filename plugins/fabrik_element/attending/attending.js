var FbAttending = my.Class(FbElement, {
    constructor: function (element, options) {
        FbAttending.Super.call(this, element, options);
        this.watchJoin();
        this.spinner = new Asset.image(Fabrik.liveSite + 'media/com_fabrik/images/ajax-loader.gif', {
            'alt'  : 'loading',
            'class': 'ajax-loader'
        });
        this.message = this.element.find('.msg');
    },

    watchJoin: function () {
        var self = this,
            b = this.getContainer().find('*[data-action="add"]');

        // If duplicated remove old events

        b.removeEvent('click', function (e) {
            self.join(e);
        });

        b.on('click', function (e) {
            self.join(e);
        });
    },

    join: function () {
        this.save('join');
    },

    leave: function () {
        this.save('leave');
    },

    save: function (state) {
        this.spinner.inject(this.message);
        var self = this,
            data = {
                'option'     : 'com_fabrik',
                'format'     : 'raw',
                'task'       : 'plugin.pluginAjax',
                'plugin'     : 'attending',
                'method'     : state,
                'g'          : 'element',
                'element_id' : this.options.elid,
                'formid'     : this.options.formid,
                'row_id'     : this.options.row_id,
                'elementname': this.options.elid,
                'userid'     : this.options.userid,
                'rating'     : this.rating,
                'listid'     : this.options.listid
            };
        console.log(data);

        $.ajax({
            url   : '',
            'data': data,
        }).done(function () {
            self.spinner.remove();
        });
    }
});