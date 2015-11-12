/**
 * Colour Picker Element
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var SliderField = my.Class({
    constructor: function (field, slider) {
        var self = this;
        this.field = $('#' + field);
        this.slider = slider;
        this.field.on('change', function (e) {
            self.update(e);
        });
    },

    destroy: function () {
        var self = this;
        this.field.removeEvent('change', function (e) {
            self.update(e);
        });
    },

    update: function () {
        if (!this.options.editable) {
            this.element.html(val);
            return;
        }
        this.slider.set(parseInt(this.field.value, 10));
    }
});

var ColourPicker = my.Class(FbElement, {

    options: {
        red             : 0,
        green           : 0,
        blue            : 0,
        value           : [0, 0, 0, 1],
        showPicker      : true,
        swatchSizeWidth : '10px',
        swatchSizeHeight: '10px',
        swatchWidth     : '160px'
    },

    constructor: function (element, options) {
        this.plugin = 'colourpicker';
        if (options.value === null || options.value[0] === undefined) {
            options.value = [0, 0, 0, 1];
        }

        ColourPicker.Super.call(this, element, options);
        options.outputs = this.outputs;
        this.element = $('#' + element);
        this.ini();
    },

    ini: function () {
        var self = this;

        this.options.callback = function (v, caller) {
            v = this.update(v);
            if (caller !== self.grad && self.grad) {
                self.grad.update(v);
            }
        };
        this.widget = this.element.closest('.fabrikSubElementContainer').find('.colourpicker-widget');
        this.setOutputs();
        var d = new Drag.Move(this.widget, {'handle': this.widget.find('.draggable')});

        if (this.options.showPicker) {
            this.createSliders(this.strElement);
        }
        this.swatch = new ColourPickerSwatch(this.options.element, this.options, this);
        this.widget.find('#' + this.options.element + '-swatch').empty().adopt(this.swatch);
        this.widget.hide();

        if (this.options.showPicker) {
            this.grad = new ColourPickerGradient(this.options.element, this.options, this);
            this.widget.find('#' + this.options.element + '-picker').empty().adopt(this.grad.square);
        }
        this.update(this.options.value);

        var close = this.widget.find('.modal-header a');
        close.on('click', function (e) {
            e.stopPropagation();
            self.widget.hide();
        });
    },

    cloned: function (c) {
        ColourPicker.Super.prototype.cloned(this, c);

        // Recreate the tabs
        var widget = this.element.closest('.fabrikSubElementContainer').find('.colourpicker-widget'),
            panes = widget.find('.tab-pane'),
            tabs = widget.find('a[data-toggle=tab]');
        tabs.each(function (tab) {
            var href = tab.get('href').split('-');
            var name = href[0].split('_');
            name[name.length - 1] = c;
            name = name.join('_');
            name += '-' + href[1];
            tab.href = name;
        });

        panes.each(function (tab) {
            var href = tab.get('id').split('-');
            var name = href[0].split('_');
            name[name.length - 1] = c;
            name = name.join('_');
            name += '-' + href[1];
            tab.id = name;
        });
        tabs.each(function (tab) {
            tab.on('click', function (e) {
                e.stopPropagation();
                $(tab).tab('show');
            });
        });

        // Initialize the widget
        this.ini();
    },

    setOutputs: function (output) {
        var self = this;
        this.outputs = {};
        this.outputs.backgrounds = this.getContainer().find('.colourpicker_bgoutput');
        this.outputs.foregrounds = this.getContainer().find('.colourpicker_output');

        this.outputs.backgrounds.each(function () {

            // Copy group, delete group add group - set outputs seems to be called twice
            $(this).off('click');
            $(this).on('click', function (e) {
                self.toggleWidget(e);
            });
        });

        this.outputs.foregrounds.each(function (i) {
            $(this).off('click');
            $(this).on('click', function (e) {
                self.toggleWidget(e);
            });
        });
    },

    createSliders: function (element) {
        var self = this;
        this.sliderRefs = [];

        // Create the table to hold the scroller
        this.table = $(document.createElement('table'));
        this.tbody = $(document.createElement('tbody'));
        this.createColourSlideHTML(element, 'red', 'Red:', this.options.red);
        this.createColourSlideHTML(element, 'green', 'Green:', this.options.green);
        this.createColourSlideHTML(element, 'blue', 'Blue:', this.options.blue);
        this.table.appendChild(this.tbody);
        this.widget.find('.sliders').empty().appendChild(this.table);

        Fabrik.addEvent('fabrik.colourpicker.slider', function (o, col, pos) {
            if (self.sliderRefs.contains(o.element.id)) {
                self.options.colour[col] = pos;
                self.update(self.options.colour.red + ',' + self.options.colour.green + ',' + self.options.colour.blue);
            }

        });
        // this makes the class update when someone enters a value into

        this.redField.on('change', function (e) {
            self.updateFromField(e, 'red');
        });

        this.greenField.on('change', function (e) {
            self.updateFromField(e, 'green');
        }.bind(this));

        this.blueField.on('change', function (e) {
            self.updateFromField(e, 'blue');
        });
    },

    createColourSlideHTML: function (element, colour, label, value) {

        var sliderField = $(document.createElement('input')).addClass('input-mini input ' + colour + 'SliderField')
            .attr({
                'type' : 'text',
                'id'   : element + colour + 'redField',
                'size' : '3',
                'value': value
            });

        var tds = [$(document.createElement('td')).text(label), $(document.createElement('td')).adopt(sliderField)];
        var tr1 = $(document.createElement('tr')).adopt(tds);

        this.tbody.appendChild(tr1);
        this[colour + 'Field'] = sliderField;
    },

    updateAll: function (red, green, blue) {
        red = red ? red.toInt() : 0;
        green = green ? green.toInt() : 0;
        blue = blue ? blue.toInt() : 0;

        if (this.options.showPicker) {
            this.redField.value = red;
            this.greenField.value = green;
            this.blueField.value = blue;
        }

        this.options.colour.red = red;
        this.options.colour.green = green;
        this.options.colour.blue = blue;
        this.updateOutputs();
    },

    updateOutputs: function () {
        var c = new Color([this.options.colour.red, this.options.colour.green, this.options.colour.blue, 1]);
        this.outputs.backgrounds.each(function (output) {
            output.css('background-color', c);
        });
        this.outputs.foregrounds.each(function (output) {
            output.css('background-color', c);
        });
        if (c.red) {
            this.element.value = c.red + ',' + c.green + ',' + c.blue;
        } else {
            this.element.value = c.rgb.join(',');
        }
    },

    /**
     * @param   mixed  val  RGB string or array
     */
    update: function (val) {
        if (this.options.editable === false) {
            this.element.html(val);
            return;
        }
        if (typeOf(val) === 'null') {
            val = [0, 0, 0];
        } else {
            if (typeOf(val) === 'string') {
                val = val.split(",");
            }
        }
        this.updateAll(val[0], val[1], val[2]);
        return val;
    },

    updateFromField: function (evt, col) {
        var val = Math.min(255, evt.target.value.toInt());
        evt.target.value = val;
        if (isNaN(val)) {
            val = 0;
        } else {
            this.options.colour[col] = val;
            this.options.callback(this.options.colour.red + ',' + this.options.colour.green + ',' + this.options.colour.blue);
        }
    },

    toggleWidget: function (e) {
        e.stopPropagation();
        this.widget.toggle();
    }
});

var ColourPickerSwatch = my.Class({

    options: {},

    initialize: function (element, options) {
        this.element = $('#' + element);
        this.options = $.extend(this.options, options);
        this.callback = this.options.callback;
        this.outputs = this.options.outputs;
        this.redField = null;
        this.widget = $(document.createElement('div'));
        this.colourNameOutput = $(document.createElement('span')).css({'padding': '3px'}).inject(this.widget);
        this.createColourSwatch(element);
        return this.widget;
    },

    createColourSwatch: function (element) {
        var j, self = this, i, swatchLine, line,
        swatchDiv = $(document.createElement('div')).css({
            'float'      : 'left',
            'margin-left': '5px',
            'class'      : 'swatchBackground'
        });

        for (i = 0; i < this.options.swatch.length; i++) {
            swatchLine = $(document.createElement('div')).css({
                'width': this.options.swatchWidth
            });
            line = this.options.swatch[i];
            j = 0;
            jQuery.each(line, function (colour, colname) {
                var swatchId = element + 'swatch-' + i + '-' + j;
                swatchLine.adopt($(document.createElement('div'))
                    .attr({
                        'id'    : swatchId
                    })
                    .addClass(colname)
                    .css({
                        'float'           : 'left',
                        'width'           : self.options.swatchSizeWidth,
                        'cursor'          : 'crosshair',
                        'height'          : self.options.swatchSizeHeight,
                        'background-color': 'rgb(' + colour + ')'
                    }).on('click', function (e) {
                        self.updateFromSwatch(e);
                    }).on('mouseenter', function (e) {
                        self.showColourName(e);
                    }).on('mouseleave', function (e) {
                        self.clearColourName(e);
                    }));
                j++;
            });

            swatchDiv.adopt(swatchLine);
        }
        this.widget.adopt(swatchDiv);
    },

    updateFromSwatch: function (e) {
        e.stopPropagation();
        var c = new Color($(e.target).css('background-color'));
        this.options.colour.red = c[0];
        this.options.colour.green = c[1];
        this.options.colour.blue = c[2];
        this.showColourName(e);
        this.callback(c, this);
    },

    showColourName: function (e) {
        this.colourName = e.target.className;
        this.colourNameOutput.text(this.colourName);
    },

    clearColourName: function (e) {
        this.colourNameOutput.text('');
    }

});

var ColourPickerGradient = my.Class({

    options: {
        size: 125
    },

    constructor: function (id, opts) {
        var self = this;
        this.brightness = 0;
        this.saturation = 0;
        this.options = $.append(this.options, opts);
        this.callback = this.options.callback;
        this.container = $('#' + id);
        if (this.container.length === 0) {
            return;
        }
        this.offset = 0;

        // Distance between the colour square and the vertical strip
        this.margin = 10;

        this.borderColour = "rgba(155, 155, 155, 0.6)";

        // Width of the hue vertical strip
        this.hueWidth = 40;

        this.colour = new Color(this.options.value);

        this.square = $(document.createElement('canvas')).attr({
            'width' : (this.options.size + 65) + 'px',
            'height': this.options.size + 'px'
        });
        this.square.inject(this.container);

        this.square.on('click', function (e) {
            self.doIt(e);
        });

        this.down = false;
        this.square.on('mousedown', function (e) {
            self.down = true;
        });
        this.square.on('mouseup', function (e) {
            self.down = false;
        });

        $(document).on('mousemove', function (e) {
            if (self.down) {
                self.doIt(e);
            }
        });

        this.drawCircle();
        this.drawHue();
        this.arrow = this.drawArrow();
        this.positionCircle(this.options.size, 0);

        this.update(this.options.value);
    },

    doIt: function (e) {
        var squareBound = {x: 0, y: 0, w: this.options.size, h: this.options.size};
        var containerPosition = this.square.getPosition();
        var x = e.page.x - containerPosition.x;
        var y = e.page.y - containerPosition.y;
        if (x < squareBound.w && y < squareBound.h) {
            this.setColourFromSquareSelection(x, y);
        } else if (x > this.options.size + this.margin && x <= this.options.size + this.hueWidth) {
            // Hue selection
            this.setHueFromSelection(x, y);
        }
    },

    update: function (c) {
        var colour = new Color(c);

        // Store the brightness and saturation for positioning the circle picker in the square selector
        this.brightness = colour.hsb[2];
        this.saturation = colour.hsb[1];

        // Our this.colour is only interested in setting the hue from the update colour
        this.colour = this.colour.setHue(colour.hsb[0]);
        this.colour = this.colour.setSaturation(100);
        this.colour = this.colour.setBrightness(100);
        this.render();
        this.positionCircleFromColour(colour);
    },

    /**
     * Position the circle based on a colour. As we are looking at HSB. saturation is defined on the x axis
     * and brightness on the left axis (both defined as percentages)
     *
     * @param  Color  c
     */
    positionCircleFromColour: function (c) {
        this.saturarion = c.hsb[1];
        this.brightness = c.hsb[2];
        var x = Math.floor(this.options.size * (this.saturarion / 100));
        var y = Math.floor(this.options.size - (this.options.size * (this.brightness / 100)));
        this.positionCircle(x, y);
    },

    /**
     * Draw the picker circle
     */
    drawCircle: function () {
        this.circle = $(document.createElement('canvas')).attr({'width': '10px', 'height': '10px'});
        var ctx = this.circle.getContext('2d');
        ctx.lineWidth = 1;
        ctx.beginPath();
        var x = this.circle.width / 2;
        var y = this.circle.width / 2;
        ctx.arc(x, y, 4.5, 0, Math.PI * 2, true);
        ctx.strokeStyle = '#000';
        ctx.stroke();
        ctx.beginPath();
        ctx.arc(x, y, 3.5, 0, Math.PI * 2, true);
        ctx.strokeStyle = '#FFF';
        ctx.stroke();
    },

    setHueFromSelection: function (x, y) {
        y = Math.min(1, y / this.options.size);
        y = Math.max(0, y);
        var hue = 360 - (y * 360);
        this.colour = this.colour.setHue(hue);
        this.render();
        this.positionCircle();

        // Apply the brightness/saturation to the color before sending the callback
        var c = this.colour;
        c = c.setBrightness(this.brightness);
        c = c.setSaturation(this.saturation);
        this.callback(c, this);
    },

    setColourFromSquareSelection: function (x, y) {
        var c = this.square.getContext('2d');
        this.positionCircle(x, y);
        var p = c.getImageData(x, y, 1, 1).data;
        var colour = new Color([p[0], p[1], p[2]]);

        // Store the brightness and saturation
        this.brightness = colour.hsb[2];
        this.saturation = colour.hsb[1];
        this.callback(colour, this);
    },

    positionCircle: function (x, y) {
        x = x ? x : this.circleX;
        this.circleX = x;
        y = y ? y : this.circleY;
        this.circleY = y;

        // Removes the old circle
        this.render();
        var ctx = this.square.getContext('2d');
        var offset = this.offset - 5;
        x = Math.max(-5, Math.round(x) + offset);
        y = Math.max(-5, Math.round(y) + offset);
        ctx.drawImage(this.circle, x, y);
    },

    drawHue: function () {

        // Drawing hue selector
        var ctx = this.square.getContext('2d');
        var left = this.options.size + this.margin + this.offset;
        var gradient = ctx.createLinearGradient(0, 0, 0, this.options.size + this.offset);
        gradient.addColorStop(0, 'rgba(255, 0, 0, 1)');
        gradient.addColorStop(5 / 6, 'rgba(255, 255, 0, 1)');
        gradient.addColorStop(4 / 6, 'rgba(0, 255, 0, 1)');
        gradient.addColorStop(3 / 6, 'rgba(0, 255, 255, 1)');
        gradient.addColorStop(2 / 6, 'rgba(0, 0, 255, 1)');
        gradient.addColorStop(1 / 6, 'rgba(255, 0, 255, 1)');
        gradient.addColorStop(1, 'rgba(255, 0, 0, 1)');
        ctx.fillStyle = gradient;
        ctx.fillRect(left, this.offset, this.hueWidth - 10, this.options.size);

        // Drawing outer bounds
        ctx.strokeStyle = this.borderColour;
        ctx.strokeRect(left + 0.5, this.offset + 0.5, this.hueWidth - 11, this.options.size - 1);
    },

    render: function () {
        var ctx = this.square.getContext('2d');
        var offset = this.offset;
        ctx.clearRect(0, 0, this.square.width, this.square.height);
        var size = this.options.size;

        // Drawing color
        ctx.fillStyle = this.colour.hex;
        ctx.fillRect(offset, offset, size, size);

        // Overlaying saturation
        var gradient = ctx.createLinearGradient(offset, offset, size + offset, 0);
        gradient.addColorStop(0, 'rgba(255, 255, 255, 1)');
        gradient.addColorStop(1, 'rgba(255, 255, 255, 0)');
        ctx.fillStyle = gradient;
        ctx.fillRect(offset, offset, size, size);

        // Overlaying value
        gradient = ctx.createLinearGradient(0, offset, 0, size + offset);
        gradient.addColorStop(0.0, 'rgba(0, 0, 0, 0)');
        gradient.addColorStop(1.0, 'rgba(0, 0, 0, 1)');
        ctx.fillStyle = gradient;
        ctx.fillRect(offset, offset, size, size);

        // Drawing outer bounds
        ctx.strokeStyle = this.borderColour;
        ctx.strokeRect(offset + 0.5, offset + 0.5, size - 1, size - 1);

        this.drawHue();

        // Arrow-selection
        var y = ((360 - this.colour.hsb[0]) / 362) * this.options.size - 2;

        var arrowX = size + this.hueWidth + offset + 2;
        var arrowY = Math.max(0, Math.round(y) + offset - 1);
        ctx.drawImage(this.arrow, arrowX, arrowY);
        /*if (doAlpha) {
         var y = ((255 - colour.rgba[4]) / 255) * options.size - 2;
         ctx.drawImage(arrow, size + this.hueWidth * 2 + offset + 2, Math.round(y) + offset - 1);
         }*/

    },

    drawArrow: function () {
        var arrow = $(document.createElement('canvas'));
        var ctx = arrow.getContext('2d');
        var size = 16;
        var width = size / 3;
        arrow.width = size;
        arrow.height = size;
        var top = -size / 4;
        var left = 0;
        for (var n = 0; n < 20; n++) { // multiply anti-aliasing
            ctx.beginPath();
            ctx.fillStyle = '#000';
            ctx.moveTo(left, size / 2 + top);
            ctx.lineTo(left + size / 4, size / 4 + top);
            ctx.lineTo(left + size / 4, size / 4 * 3 + top);
            ctx.fill();
        }
        ctx.translate(-width, -size);
        return arrow;
    }
});
