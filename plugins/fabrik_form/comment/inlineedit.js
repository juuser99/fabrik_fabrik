/**File:    inlineEdit.v3.js
 Title:Mootools inlineEdit Plugin
 Author:Justin Maier
 Url:http://justinmaier.com
 Date:2008-06-06
 Ver:1*/

var InlineEdit = my.Class({
    options: {
        onComplete: function () {
        },
        onLoad    : function () {
        },
        onKeyup   : function () {
        },
        inputClass: 'input',
        stripHtml : true
    },

    constructor: function (element, options) {
        this.options = $.extend(this.options, options);
        this.element = element;
        this.originalText = element.get('html').replace(/<br>/gi, '\n');
        this.input = $(document.createElement('textarea')).addClass(this.options.inputClass)
            .css(this.element.getStyles('width', 'height', 'padding-top', 'padding-right', 'padding-bottom', 'padding-left', 'margin-top', 'margin-right', 'margin-bottom', 'margin-left', 'font-family', 'font-size', 'font-weight', 'line-height', 'border-top', 'border-right', 'border-bottom', 'border-left', 'background-color', 'color'))
            .on('keyup', this.keyup.bind(this))
            .on('blur', this.complete.bind(this))
            .attr({'value': this.originalText});

        this.input.css('margin-left', this.input.css('margin-left').toInt() - 1);
        this.originalWidth = this.element.css('width');
        this.element.setStyles({'visibility': 'hidden', 'position': 'absolute', 'width': this.element.offsetWidth});
        this.input.inject(this.element, 'after');
        this.input.focus();
        this.trigger('onLoad', [this.element, this.input]);
    },

    keyup: function (e) {
        if (!e) {
            return;
        }
        this.trigger('onKeyup', [this.element, this.input, e]);
        this.element.html((e.key === 'enter') ? this.getContent() + '&nbsp;' : this.getContent());
        if (e.key === 'enter') {
            this.input.on('keydown', this.newLine.bind(this));
        }
        this.input.css('height', this.element.offsetHeight);
        if (e.key === 'esc') {
            this.element.text(this.originalText);
            this.end();
        }
    },

    getContent: function () {
        var content = this.input.value;
        if (this.options.stripHtml) {
            content = content.replace(/(<([^>]+)>)/ig, '');
        }
        return (content.replace(/\n/gi, "<br>"));
    },

    newLine: function () {
        this.element.innerHTML = this.element.innerHTML.replace('&nbsp;', '');
        this.input.removeEvents('keydown');
    },

    complete: function () {
        this.element.html(this.getContent());
        this.trigger('onComplete', this.element);
        this.end();
    },

    end: function () {
        this.input.destroy();
        this.element.setStyles({'visibility': 'visible', 'position': 'relative', 'width': this.originalWidth});
    }
});

Element.implement({
    inlineEdit: function (options) {
        return new InlineEdit(this, options);
    }
});