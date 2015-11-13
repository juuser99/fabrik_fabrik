/**
 * Textarea Element
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbTextarea = my.Class(FbElement, {
    constructor: function (element, options) {

        this.plugin = 'fabriktextarea';
        FbTextarea.Super.call(this, element, options);

        var self = this;
        // $$$ rob need to slightly delay this as if lots of js loaded (eg maps)
        // before the editor then the editor may not yet be loaded

        var periodFn = function () {

            // Seems that tinyMCE isn't created if FbLike element published in form
            self.getTextContainer();
            if (typeof tinyMCE !== 'undefined') {
                if (self.container !== false) {
                    clearInterval(p);
                    self.watchTextContainer();
                }
            } else {
                clearInterval(p);
                self.watchTextContainer();
            }
        };

        var p = setInterval(function () {
            periodFn.call(this);
        }, 200);

        Fabrik.addEvent('fabrik.form.page.change.end', function (form) {
            this.refreshEditor();
        }.bind(this));

        Fabrik.addEvent('fabrik.form.elements.added', function (form) {
            if (form.isMultiPage()) {
                this.refreshEditor();
            }
        }.bind(this));

        Fabrik.addEvent('fabrik.form.submit.start', function (form) {
            if (this.options.wysiwyg && form.options.ajax) {
                if (typeof tinyMCE !== 'undefined') {
                    tinyMCE.triggerSave();
                }
            }
        }.bind(this));

    },

    unclonableProperties: function () {
        var props = FbTextarea.Super.prototype.unclonableProperties(this);
        props.push('container');
        return props;
    },

    /**
     * Set names/ids/elements ect when the elements group is cloned
     *
     * @param   int  id  element id
     * @since   3.0.7
     */

    cloneUpdateIds: function (id) {
        this.element = $('#' + id);
        this.options.element = id;
        this.options.htmlId = id;
    },

    watchTextContainer: function () {
        var self = this;
        if (this.element.length === 0) {
            this.element = $('#' + this.options.element);
        }
        if (this.element.lenth === 0) {
            this.element = $('#' + this.options.htmlId);
            if (this.element.length === 0) {
                // Can occur when element is part of hidden first group
                return;
            }
        }
        if (this.options.editable === true) {
            var c = this.getContainer();
            if (c === false) {
                fconsole('no fabrikElementContainer class found for textarea');
                return;
            }
            var element = c.find('.fabrik_characters_left');

            if (element.length > 0) {
                this.warningFX = new Fx.Morph(element, {duration: 1000, transition: Fx.Transitions.Quart.easeOut});
                this.origCol = element.css('color');
                if (this.options.wysiwyg && typeof(tinymce) !== 'undefined') {

                    // Joomla 3.2 + usess tinyMce 4
                    if (tinymce.majorVersion >= 4) {
                        var inst = this._getTinyInstance();
                        inst.on('keyup', function (e) {
                            self.informKeyPress(e);
                        });

                        inst.on('focus', function (e) {
                            var c = self.element.closest('.fabrikElementContainer');
                            c.find('span.badge').addClass('badge-info');
                            c.find('.fabrik_characters_left').removeClass('muted');
                        });

                        inst.on('blur', function (e) {
                            var c = self.element.closest('.fabrikElementContainer');
                            c.find('span.badge').removeClass('badge-info');
                            c.find('.fabrik_characters_left').addClass('muted');
                        });

                        inst.on('blur', function (e) {
                            self.forwardEvent('blur');
                        });

                    } else {
                        tinymce.dom.Event.add(this.container, 'keyup', function (e) {
                            self.informKeyPress(e);
                        });
                        tinymce.dom.Event.add(this.container, 'blur', function (e) {
                            self.forwardEvent('blur');
                        });
                    }
                } else {
                    this.container.on('keydown', function (e) {
                        self.informKeyPress(e);
                    });

                    this.container.on('blur', function (e) {
                        self.blurCharsLeft(e);
                    });

                    this.container.on('focus', function (e) {
                        self.focusCharsLeft(e);
                    });
                }
            }
        }
    },

    /**
     * Forward an event from tinyMce to the text editor - useful for triggering ajax validations
     *
     * @param   string  event  Event name
     */
    forwardEvent: function (event) {
        var textarea = tinyMCE.activeEditor.getElement(),
            c = this.getContent();
        textarea.set('value', c);
        textarea.trigger('blur');
    },

    focusCharsLeft: function () {
        var c = this.element.closest('.fabrikElementContainer');
        c.find('span.badge').addClass('badge-info');
        c.find('.fabrik_characters_left').removeClass('muted');
    },

    blurCharsLeft: function () {
        var c = this.element.closest('.fabrikElementContainer');
        c.find('span.badge').removeClass('badge-info');
        c.find('.fabrik_characters_left').addClass('muted');
    },

    /**
     * Used to find element when form clones a group
     * WYSIWYG text editor needs to return something specific as options.element has to use name
     * and not id.
     */
    getCloneName: function () {
        var name = this.options.isGroupJoin ? this.options.htmlId : this.options.element;
        return name;
    },

    /**
     * Run when element cloned in repeating group
     *
     * @param   int  c  repeat group counter
     */

    cloned: function (c) {
        if (this.options.wysiwyg) {
            var p = this.element.closest('.fabrikElement');
            var txt = p.find('textarea').clone(true, true);
            var charLeft = p.find('.fabrik_characters_left');
            p.empty();
            p.append(txt);
            if (charLeft.length > 0) {
                p.append(charLeft.clone());
            }
            txt.removeClass('mce_editable');
            txt.css('display', '');
            this.element = txt;
            var id = this.options.isGroupJoin ? this.options.htmlId : this.options.element;
            //tinyMCE.execCommand('mceAddControl', false, id);
            this._addTinyEditor(id);
        }
        this.getTextContainer();
        this.watchTextContainer();
        FbTextarea.Super.prototype.clone(this, c);
    },

    /**
     * run when the element is decloned from the form as part of a deleted repeat group
     */
    decloned: function (groupid) {
        if (this.options.wysiwyg) {
            var id = this.options.isGroupJoin ? this.options.htmlId : this.options.element;
            tinyMCE.execCommand('mceFocus', false, id);
            this._removeTinyEditor(id);
        }
    },

    getTextContainer: function () {
        if (this.options.wysiwyg && this.options.editable) {
            var name = this.options.isGroupJoin ? this.options.htmlId : this.options.element;
            $('#' + name).addClass('fabrikinput');
            var instance = typeof(tinyMCE) !== 'undefined' ? tinyMCE.get(name) : false;
            if (instance) {
                this.container = instance.getDoc();
            } else {
                this.contaner = false;
            }
        } else {
            // Regrab the element for inline editing (otherwise 2nd col you edit doesnt pickup the textarea.
            this.element = $('#' + this.options.element);
            this.container = this.element;
        }
        return this.container;
    },

    getContent: function () {
        if (this.options.wysiwyg) {
            return tinyMCE.activeEditor.getContent().replace(/<\/?[^>]+(>|$)/g, '');
        } else {
            return this.container.value;
        }
    },

    /**
     * On ajax loaded page need to re-load the editor
     * For Chrome
     */
    refreshEditor: function () {
        if (this.options.wysiwyg) {
            if (typeof WFEditor !== 'undefined') {
                WFEditor.init(WFEditor.settings);
            } else if (typeof tinymce !== 'undefined') {
                tinyMCE.init(tinymce.settings);
            }
            // Need to re-observe the editor
            this.watchTextContainer();
        }
    },

    _getTinyInstance: function () {
        var id = this.element.prop('id');
        return parseInt(tinyMCE.majorVersion, 10) >= 4 ? tinyMCE.get(id) : tinyMCE.getInstanceById(id);
    },

    _addTinyEditor: function (id) {
        if (parseInt(tinyMCE.majorVersion, 10) >= 4) {
            tinyMCE.execCommand('mceAddEditor', false, id);
        } else {
            tinyMCE.execCommand('mceAddControl', false, id);
        }
    },

    _removeTinyEditor: function (id) {
        if (parseInt(tinyMCE.majorVersion, 10) >= 4) {
            tinyMCE.execCommand('mceRemoveEditor', false, id);
        } else {
            tinyMCE.execCommand('mceRemoveControl', false, id);
        }
    },

    setContent: function (c) {
        if (this.options.wysiwyg) {
            var ti = this._getTinyInstance(),
                r = ti.setContent(c);
            this.moveCursorToEnd();
            return r;
        } else {
            this.getTextContainer();
            this.container.val(c);
        }
        return null;
    },

    /**
     * For tinymce move the cursor to the end
     */
    moveCursorToEnd: function () {
        var inst = this._getTinyInstance();
        inst.selection.select(inst.getBody(), true);
        inst.selection.collapse(false);
    },

    informKeyPress: function () {
        var charsleftEl = this.getContainer().find('.fabrik_characters_left'),
            charsLeft = this.itemsLeft();
        if (this.limitReached()) {
            this.limitContent();
            this.warningFX.start({'opacity': 0, 'color': '#FF0000'}).chain(function () {
                this.start({'opacity': 1, 'color': '#FF0000'}).chain(function () {
                    this.start({'opacity': 0, 'color': this.origCol}).chain(function () {
                        this.start({'opacity': 1});
                    });
                });
            });
        } else {
            charsleftEl.css('color', this.origCol);
        }
        charsleftEl.find('span').html(charsLeft);
    },

    /**
     * How many content items left (e.g 1 word, 100 characters)
     *
     * @return int
     */

    itemsLeft: function () {
        var i = 0,
            content = this.getContent();
        if (this.options.maxType === 'word') {
            i = this.options.max - content.split(' ').length;
        } else {
            i = this.options.max - (content.length + 1);
        }
        if (i < 0) {
            i = 0;
        }
        return i;
    },

    /**
     * Limit the content based on maxType and max e.g. 100 words, 2000 characters
     */

    limitContent: function () {
        var c,
            content = this.getContent();
        if (this.options.maxType === 'word') {
            c = content.split(' ').splice(0, this.options.max);
            c = c.join(' ');
            c += (this.options.wysiwyg) ? '&nbsp;' : ' ';
        } else {
            c = content.substring(0, this.options.max);
        }
        this.setContent(c);
    },

    /**
     * Has the max content limit been reached?
     *
     * @return bool
     */

    limitReached: function () {
        var content = this.getContent();
        if (this.options.maxType === 'word') {
            var words = content.split(' ');
            return words.length > this.options.max;
        } else {
            var charsLeft = this.options.max - (content.length + 1);
            return charsLeft < 0 ? true : false;
        }
    },

    reset: function () {
        this.update(this.options.defaultVal);
    },

    update: function (val) {
        this.getElement();
        this.getTextContainer();
        if (!this.options.editable) {
            this.element.html(val);
            return;
        }
        this.setContent(val);
    }
});
