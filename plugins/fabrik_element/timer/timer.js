/**
 * Timer Element
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbTimer = my.Class(FbElement, {

    options: {
        defaultVal    : '',
        editable      : false,
        startCrono    : '15:00',
        endCrono      : '00:00',
        div           : false,
        stopOnComplete: true,
        onComplete    : function () {
        },
        onEveryMinute : function () {
        },
        onEveryHour   : function () {
        }
    },

    constructor: function (element, options) {
        var self = this;
        this.plugin = 'fabriktimer';
        this.parent(element, options);
        var b = $('#' + this.options.element + '_button');
        this.seg = 0;
        this.min = 0;
        this.hour = 0;
        if (this.options.autostart === true) {
            b.find('span').text(Joomla.JText._('PLG_ELEMENT_TIMER_STOP'));
            this.start();
        } else {
            this.state = 'paused';
        }

        this.incremental = 1;
        b.on('click', function (e) {
            e.stop();
            if (self.state === 'started') {
                self.pause();
                //b.value = Joomla.JText._('PLG_ELEMENT_TIMER_START');
                b.find('span').text(Joomla.JText._('PLG_ELEMENT_TIMER_START'));
            } else {
                var v = this.element.val().split(':');
                switch (v.length) {
                    case 3:
                        this.hour = (v[0] === '') ? 0 : parseInt(v[0], 10);
                        this.min = (v[1] === '') ? 0 : parseInt(v[1], 10);
                        this.seg = (v[2] === '') ? 0 : parseInt(v[2], 10);
                        break;
                    case 2:
                        this.min = (v[0] === '') ? 0 : parseInt(v[0], 10);
                        this.seg = (v[1] === '') ? 0 : parseInt(v[1], 10);
                        break;
                    case 1:
                        this.seg = (v[0] === '') ? 0 : parseInt(v[0], 10);
                        break;
                }
                self.start();
                b.find('span').text(Joomla.JText._('PLG_ELEMENT_TIMER_STOP'));
            }
        });
    },

    start: function () {
        if (this.state !== 'started') {
            this.timer = setInterval(function () {
                this.count.call(this);
            }, 1000);
            this.state = 'started';
        }
    },

    pause: function () {
        if (this.state !== 'paused') {
            clearInterval(this.timer);
            this.state = 'paused';
        }
    },

    count: function () {
        this.seg += this.incremental;
        if ((this.seg === -1) || (this.seg === 60)) {
            this.seg = (this.incremental > 0) ? 0 : 59;
            this.min += this.incremental;
            if (this.min === -1 || this.min === 60) {
                this.min = (this.incremental > 0) ? 0 : 59;
                this.hour += this.incremental;
            }
        }
        this.element.value = this.time();
        if ((this.min === this.endMin) && (this.seg === this.endSeg)) {
            this.trigger('onComplete', '');
            if (this.options.stopOnComplete) {
                this.pause();
            }
        }
    },

    time: function () {
        var time_to_show = (this.hour < 10) ? "0" + this.hour : this.hour;
        time_to_show += ((this.min < 10) ? ":0" : ":") + this.min;
        time_to_show += ((this.seg < 10) ? ":0" : ":") + this.seg;
        return time_to_show;
    },

    reset: function () {
        //reset time to initial values
        start_array = this.options.startCrono.split(":");
        end_array = this.options.endCrono.split(":");

        this.startMin = start_array[0].toInt();
        this.startSeg = start_array[1].toInt();

        this.endMin = end_array[0].toInt();
        this.endSeg = end_array[1].toInt();

        if (this.endMin !== this.startMin) {
            this.incremental = (this.endMin > this.startMin) ? 1 : -1;
        } else {
            this.incremental = (this.endSeg > this.startSeg) ? 1 : -1;
        }
        this.min = this.startMin;
        this.seg = this.startSeg;

        if (this.options.div !== false) {
            $('#' + this.options.div).text(this.time());
        }
    }
});