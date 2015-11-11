/**
 * Slider Element
 *
 * @copyright: Copyright (C) 2005-2013, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbSlider = my.Class(FbElement, {
    constructor: function (element, options) {
        FbSlider.Super.call(this, element, options);
        this.plugin = 'slider';
        this.makeSlider();
    },

    makeSlider: function () {
        var isNull = false;
        if (typeOf(this.options.value) === 'null' || this.options.value === '') {
            this.options.value = '';
            isNull = true;
        }
        this.options.value = this.options.value === '' ? '' : this.options.value.toInt();
        var v = this.options.value;
        if (this.options.editable === true) {
            if (typeOf(this.element) === 'null') {
                fconsole('no element found for slider');
                return;
            }
            this.output = this.element.find('.fabrikinput');
            this.output2 = this.element.find('.slider_output');

            this.output.value = this.options.value;
            this.output2.text(this.options.value);

            this.mySlide = new Slider(
                this.element.find('.fabrikslider-line'),
                this.element.find('.knob'),
                {
                    onChange  : function (pos) {
                        this.output.value = pos;
                        this.options.value = pos;
                        this.output2.text(pos);
                        this.output.trigger('blur');
                        this.callChange();
                    }.bind(this),
                    onComplete: function (pos) {
                        // Fire for validations
                        this.output.trigger('blur');
                        this.element.trigger('change');
                    }.bind(this),
                    steps     : this.options.steps
                }
            ).set(v);

            if (isNull) {
                this.output.value = '';
                this.output2.text('');
                this.options.value = '';
            }
            this.watchClear();
        }
    },

    watchClear: function () {
        var self = this;
        this.element.on('click', '.clearslider', function (e, target) {
            e.preventDefault();
            self.mySlide.set(0);
            self.output.val('');
            self.output.trigger('blur');
            self.output2.text('');
        });
    },

    getValue: function () {
        return this.options.value;
    },

    callChange: function () {
        typeOf(this.changejs) === 'function' ? this.changejs.delay(0) : eval(this.changejs);
    },

    addNewEvent: function (action, js) {
        if (action === 'load') {
            this.loadEvents.push(js);
            this.runLoadEvent(js);
            return;
        }
        if (action === 'change') {
            this.changejs = js;
        }
    },

    cloned: function (c) {
        delete this.mySlide;
        this.makeSlider();
        FbSlider.Super.prototype.cloned(this, c);
    }

});
