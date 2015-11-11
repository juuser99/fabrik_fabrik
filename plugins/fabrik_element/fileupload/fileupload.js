/**
 * File Upload Element
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license: GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbFileUpload = my.Class(FbFileElement, {
    constructor: function (element, options) {
        this.plugin = 'fileupload';
        FbFileUpload.Super.call(this, element, options);
        if (this.options.folderSelect === '1' && this.options.editable === true) {
            this.ajaxFolder();
        }

		this.doBrowseEvent = null;
		this.watchBrowseButton();

		if (this.options.ajax_upload && this.options.editable !== false) {
			Fabrik.fireEvent('fabrik.fileupload.plupload.build.start', this);
			this.watchAjax();

            if (Object.keys(this.options.files).length !== 0) {
                this.uploader.trigger('FilesAdded', this.options.files);
                this.startbutton.addClass('plupload_disabled');
                jQuery.each(this.options.files, function (key, file) {
                    var response = {
                        'filepath': file.path,
                        uri       : file.url
                    };
                    this.uploader.trigger('UploadProgress', file);
                    this.uploader.trigger('FileUploaded', file, {
                        response: JSON.encode(response)
                    });
                    var newBar = jQuery(Fabrik.jLayouts['fabrik-progress-bar-success'])[0];
                    var bar = $('#' + file.id).find('.bar');
                    newBar.replaces(bar);
                }.bind(this));
            }
            this.redraw();
        }

		this.doDeleteEvent = null;
		this.watchDeleteButton();
		this.watchTab();
	},

    /**
     * Reposition the hidden input field over the 'add' button. Called on initiate and if in a tab
     * and the tab is activated. Triggered from element.watchTab()
     */
    redraw: function () {
        if (this.options.ajax_upload) {
            var browseButton = $('#' + this.element.id + '_browseButton'),
                c = $('#' + this.options.element + '_container'),
                diff = browseButton.getPosition().y - c.getPosition().y;
            // $$$ hugh - working on some IE issues
            var file_element = c.closest('.fabrikElement').find('input[type=file]');
            if (file_element.length > 0) {
                var fileContainer = file_element.parent();
                var size = browseButton.getSize();
                fileContainer.css({
                    'width' : size.x,
                    'height': size.y
                });
                fileContainer.css('top', diff);
            }
        }
    },

	doBrowse: function (evt) {
		if (window.File && window.FileReader && window.FileList && window.Blob) {
			var reader;
			var files = evt.target.files;
			var f = files[0];

			// Only process image files.
			if (f.type.match('image.*')) {
				reader = new FileReader();
				// Closure to capture the file information.
				reader.onload = (function (theFile) {
					return function (e) {
						var c = this.getContainer();
						if (!c) {
							return;
						}
						var b = c.find('img');
						b[0]src = e.target.result;
						var d = b.closest('fabrikHide');
						d.removeClass('fabrikHide');
						var db = c.find('[data-file]');
			            db.addClass('fabrikHide');

					}.bind(this);
				}.bind(this))(f);
				// Read in the image file as a data URL.
				reader.readAsDataURL(f);
			}
			else if (f.type.match('video.*'))
			{
				var c = this.getContainer();
				if (!c) {
					return;
				}

				var video = c.find('video');
				if (video.length > 0) {
					video = this.makeVideoPreview();
					video.inject(c, 'inside');
				}

				reader = new window.FileReader();
				var url;

				reader = window.URL || window.webKitURL;

				if (reader && reader.createObjectURL) {
					url = reader.createObjectURL(f);
					video.src = url;
					return;
				}

				if (!window.FileReader) {
					console.log('Sorry, not so much');
					return;
				}

				reader = new window.FileReader();
				reader.onload = function (eo) {
					video.src = eo.target.result;
				};
				reader.readAsDataURL(f);
			}
		}
	},

	watchBrowseButton: function () {
		if (this.options.useWIP && !this.options.ajax_upload && this.options.editable !== false) {
			$(this.element.id).removeEvent('change', this.doBrowseEvent);
			this.doBrowseEvent = this.doBrowse.bind(this);
			$(this.element.id).on('change', this.doBrowseEvent);
		}
	},

	/**
	 * Called from watchDeleteButton
	 */
	doDelete: function (e) {
		e.stop();
		var c = this.getContainer();
		if (!c) {
			return;
		}
		var b = c.getElement('[data-file]');
		if (window.confirm(Joomla.JText._('PLG_ELEMENT_FILEUPLOAD_CONFIRM_SOFT_DELETE'))) {
			var joinPkVal = b.get('data-join-pk-val');
			new $.ajax({
				url: '',
				data: {
					'option': 'com_fabrik',
					'format': 'raw',
					'task': 'plugin.pluginAjax',
					'plugin': 'fileupload',
					'method': 'ajax_clearFileReference',
					'element_id': this.options.id,
					'formid': this.form.id,
					'rowid': this.form.options.rowid,
					'joinPkVal': joinPkVal
				}
			}).done(function () {
				Fabrik.trigger('fabrik.fileupload.clearfileref.complete', self);
			});

                    if (window.confirm(Joomla.JText._('PLG_ELEMENT_FILEUPLOAD_CONFIRM_HARD_DELETE'))) {
                        this.makeDeletedImageField(this.groupid, b.get('data-file')).inject(this.getContainer(), 'inside');
                        Fabrik.trigger('fabrik.fileupload.delete.complete', this);
                    }

			b.destroy();
		}
	},

	/**
	 * Single file uploads can allow the user to delee the reference and/or file
	 */
	watchDeleteButton: function () {
		var c = this.getContainer();
		if (!c) {
			return;
		}
		var b = c.getElement('[data-file]');
		if (typeOf(b) !== 'null') {
			b.removeEvent('click', this.doDeleteEvent);
			this.doDeleteEvent = this.doDelete.bind(this);
			b.addEvent('click', this.doDeleteEvent);
		}
	},

    /**
     * Sets the element key used in Fabrik.blocks.form_X.formElements overwritten by dbjoin rendered as checkbox
     *
     * @since 3.0.7
     *
     * @return string
     */

    getFormElementsKey: function (elId) {
        this.baseElementId = elId;
        if (this.options.ajax_upload && this.options.ajax_max > 1) {
            return this.options.listName + '___' + this.options.elementShortName;
        } else {
            return FbFileUpload.Super.prototype.getFormElementsKey(this, elId);
        }
    },

    /**
     * when in ajax form, on submit the list will call this, so we can remove the submit event if we dont do that, upon a second form submission the
     * original submitEvent is used causing a js error as it still references the files uploaded in the first form
     */
    removeCustomEvents: function () {
        // Fabrik.removeEvent('fabrik.form.submit.start', this.submitEvent);
    },

    cloned: function (c) {
        // replaced cloned image with default image
        if (this.element.closest('.fabrikElement').length === 0) {
            return;
        }
        var i = this.element.closest('.fabrikElement').find('img');
        if (i) {
            i.src = Fabrik.liveSite + this.options.defaultImage;
        }

        this.getContainer().find('[data-file]').destroy();
        this.watchBrowseButton();

        return FbFileUpload.Super.prototype.cloned(this, c);
    },

	decloned: function (groupid) {
		var i = $('#form_' + this.form.id).find('input[name=fabrik_deletedimages[' + groupid + ']');
		if (i.length > 0) {
			this.makeDeletedImageField(groupid, this.options.value).inject(this.form.form);
		}
	},

    /**
     * Create a hidden input which will tell fabrik, upon form submission, to delete the file
     *
     * @param int groupid group id
     * @param string value file to delete
     *
     * @return Element DOM Node - hidden input
     */
    makeDeletedImageField: function (groupid, value) {
        return $(document.createElement('input')).attr({
            'type' : 'hidden',
            'name' : 'fabrik_fileupload_deletedfile[' + groupid + '][]',
            'value': value
        });
    },

    makeVideoPreview: function () {
        return $(document.createElement('video')).attr({
            'id'      : this.element.id + '_video_preview',
            'controls': true
        });
    },

    update: function (val) {
        if (this.element) {
            if (val === '') {
                if (this.options.ajax_upload) {
                    this.uploader.files = [];
                    this.element.parent().find('[id$=_dropList] tr').destroy();
                } else {
                    this.element.set('value', '');
                }
            } else {
                var i = this.element.find('img');
                if (i.length !== 0) {
                    i.prop('src', val);
                }
            }
        }
    },

    addDropArea: function () {
        if (!Fabrik.bootstraped) {
            return;
        }
        var dropTxt = this.container.find('tr.plupload_droptext'), tr;
        if (typeOf(dropTxt) !== 'null') {
            dropTxt.show();
        } else {
            tr = $(document.createElement('tr')).addClass('plupload_droptext').html('<td colspan="4"><i class="icon-move"></i> ' +
                Joomla.JText._('PLG_ELEMENT_FILEUPLOAD_DRAG_FILES_HERE') + ' </td>');
            this.container.find('tbody').adopt(tr);
        }
        this.container.find('thead').hide();
    },

    removeDropArea: function () {
        var dropTxt = this.container.find('tr.plupload_droptext');
        if (typeOf(dropTxt) !== 'null') {
            dropTxt.hide();
        }
    },

    watchAjax: function () {
        if (this.options.editable === false) {
            return;
        }
        var a, self = this;
        var el = this.find();
        if (el.length === 0) {
            return;
        }
        var c = el.closest('.fabrikSubElementContainer');
        this.container = c;
        var canvas = c.find('canvas');
        if (canvas.length === 0) {
            return;
        }
        if (this.options.canvasSupport !== false) {
            this.widget = new ImageWidget(canvas, {

                'imagedim': {
                    x: 200,
                    y: 200,
                    w: this.options.winWidth,
                    h: this.options.winHeight
                },

                'cropdim': {
                    w: this.options.cropwidth,
                    h: this.options.cropheight,
                    x: this.options.winWidth / 2,
                    y: this.options.winHeight / 2
                },
                crop     : this.options.crop,
                quality  : this.options.quality
            });
        }
        this.pluploadContainer = c.find('.plupload_container');
        this.pluploadFallback = c.find('.plupload_fallback');
        this.droplist = c.find('.plupload_filelist');
        this.startbutton = c.find('*[data-action=plupload_start]');

        var plupopts = {
            runtimes           : this.options.ajax_runtime,
            browse_button      : this.element.id + '_browseButton',
            container          : this.element.id + '_container',
            drop_element       : this.element.id + '_dropList_container',
            url                : 'index.php?option=com_fabrik&format=raw&task=plugin.pluginAjax&plugin=fileupload&method=ajax_upload&element_id=' + this.options.elid,
            max_file_size      : this.options.max_file_size + 'kb',
            unique_names       : false,
            flash_swf_url      : this.options.ajax_flash_path,
            silverlight_xap_url: this.options.ajax_silverlight_path,
            chunk_size         : this.options.ajax_chunk_size + 'kb',
            dragdrop           : true,
            multipart          : true,
            filters            : this.options.filters,
            page_url           : this.options.page_url
        };
        this.uploader = new plupload.Uploader(plupopts);

        // (1) INIT ACTIONS
        this.uploader.bind('Init', function (up, params) {
            // FORCEFULLY NUKE GRACEFUL DEGRADING FALLBACK ON INIT
            self.pluploadFallback.destroy();
            self.pluploadContainer.removeClass('fabrikHide');

            if (up.features.dragdrop && up.settings.dragdrop) {
                self.addDropArea();
            }

        });

        /*
         * this.uploader.bind('PostInit', function (up, params) { debugger; this.pluploadContainer.find('input').setStyle('width', '1px');
         * }.bind(this));
         */
        this.uploader.bind('FilesRemoved', function (up, files) {
        });

        // (2) ON FILES ADDED ACTION
        this.uploader.bind('FilesAdded', function (up, files) {
            self.removeDropArea();
            var rElement = 'tr', count;
            self.lastAddedFiles = files;
            self.container.find('thead').style.display = '';
            self.count = self.droplist.find(rElement).length;
            self.startbutton.removeClass('disabled');
            files.each(function (file, idx) {
                if (file.size > self.options.max_file_size * 1000) {
                    window.alert(Joomla.JText._('PLG_ELEMENT_FILEUPLOAD_FILE_TOO_LARGE_SHORT'));
                } else {
                    if (count >= self.options.ajax_max) {
                        window.alert(Joomla.JText._('PLG_ELEMENT_FILEUPLOAD_MAX_UPLOAD_REACHED'));
                    } else {
                        count++;
                        var a, title, innerLi;
                        if (self.isImage(file)) {
                            a = self.editImgButton();
                            if (self.options.crop) {
                                a.html(self.options.resizeButton);
                            } else {
                                a.html(self.options.previewButton);
                            }
                            title = $(document.createElement('span')).text(file.name);
                        } else {
                            a = $(document.createElement('span'));
                            title = $(document.createElement('a')).attr({
                                'href': file.url
                            }).text(file.name);
                        }

                        innerLi = self.imageCells(file, title, a);

                        self.droplist.adopt($(document.createElement(rElement)).attr({
                            id     : file.id,
                            'class': 'plupload_delete'
                        }).adopt(innerLi));
                    }
                }
            });
        });

        // (3) ON FILE UPLOAD PROGRESS ACTION
        this.uploader.bind('UploadProgress', function (up, file) {
            var f = $('#' + file.id);
            if (f.length > 0) {
                var bar = $('#' + file.id).find('.plupload_file_status .bar');
                bar.css('width', file.percent + '%');
                if (file.percent === 100) {
                    var newBar = $(Fabrik.jLayouts['fabrik-progress-bar-success'])[0];
                    newBar.replaces(bar);
                }
            }
        });

        this.uploader.bind('Error', function (up, err) {
            self.lastAddedFiles.each(function (file) {
                var row = $('#' + file.id);
                if (row.length > 0) {
                    row.destroy();
                    window.alert(err.message);
                }
                self.addDropArea();
            });
        });

        this.uploader.bind('ChunkUploaded', function (up, file, response) {
            response = JSON.decode(response.response);
            if (typeof(response) === 'object') {
                if (response.error) {
                    fconsole(response.error.message);
                }
            }
        });

        this.uploader.bind('FileUploaded', function (up, file, response) {
            response = JSON.decode(response.response);
            var f = $('#' + file.id),
                self = this;
            if (response.error) {
                window.alert(response.error);
                f.destroy();
                return;
            }
            if (f.length === 0) {
                fconsole('Filuploaded didnt find: ' + file.id);
                return;
            }
            var resizebutton = f.find('.plupload_resize a');
            if (resizebutton) {
                resizebutton.show();
                resizebutton.href = response.uri;
                resizebutton.id = 'resizebutton_' + file.id;
                resizebutton.store('filepath', response.filepath);
            }
            if (this.widget) {
                this.widget.setImage(response.uri, response.filepath, file.params);
            }

            // Stores the cropparams which we need to reload the crop widget in the correct state (rotation, zoom, loc etc)
            $(document.createElement('input')).attr({
                'type' : 'hidden',
                name   : this.options.elementName + '[crop][' + response.filepath + ']',
                'id'   : 'coords_' + file.id,
                'value': JSON.encode(file.params)
            }).inject(this.pluploadContainer, 'after');

            // Stores the actual crop image data retrieved from the canvas
            $(document.createElement('input')).attr({
                type: 'hidden',
                name: this.options.elementName + '[cropdata][' + response.filepath + ']',
                'id': 'data_' + file.id
            }).inject(this.pluploadContainer, 'after');

            // Stores the image id if > 1 fileupload
            var idvalue = [file.recordid, '0'].pick();
            $(document.createElement('input')).attr({
                'type' : 'hidden',
                name   : this.options.elementName + '[id][' + response.filepath + ']',
                'id'   : 'id_' + file.id,
                'value': idvalue
            }).inject(this.pluploadContainer, 'after');

            f.removeClass('plupload_file_action').addClass('plupload_done');

            self.isSumbitDone();
        });

        // (4) UPLOAD FILES FIRE STARTER
        this.startbutton.on('click', function (e) {
            e.stopPropagation();
            self.uploader.start();
        });
        // (5) KICK-START PLUPLOAD
        this.uploader.init();
    },

    /**
     * Create an array of the dom elements to inject into a row representing an uploaded file
     *
     * @return array
     */
    imageCells: function (file, title, a) {
        var del = this.deleteImgButton(), filename, status;
        var icon = $(document.createElement('td')).addClass('span1 plupload_resize').adopt(a);

        var progress = Fabrik.jLayouts['fabrik-progress-bar'];
        status = $(document.createElement('td')).addClass('span5 plupload_file_status').html(progress);
        filename = $(document.createElement('td')).addClass('span6 plupload_file_name').adopt(title);

        return [filename, icon, status, del];

    },

    /**
     * Create edit image button
     *
     * @return {jQuery}
     */
    editImgButton: function () {
        var self = this;
        return $(document.createElement('a')).addClass('editImage').attr({
            'href': '#',
            alt   : Joomla.JText._('PLG_ELEMENT_FILEUPLOAD_RESIZE')
        }).css({
            'display': 'none'
        }).on('click', function (e) {
            self.pluploadResize(e);
        });
    },

    /**
     * Create delete image button
     *
     * @return {jQuery}
     */
    deleteImgButton: function () {
        var icon = Fabrik.jLayouts['fabrik-icon-delete'],
            self = this;
        return $(document.createElement('td')).addClass('span1 plupload_file_action').adopt(
            $(document.createElement('a'))
                .html(icon)
                .attr({
                    'href' : '#',
                    'class': 'icon-delete'
                })
                .on('click', function (e) {
                    e.stopPropagation();
                    self.pluploadRemoveFile(e);
                })
        );
    },

    isImage: function (file) {
        if (file.type === undefined) {
            return file.type === 'image';
        }
        var ext = file.name.split('.').getLast().toLowerCase();
        return ['jpg', 'jpeg', 'png', 'gif'].contains(ext);
    },

    pluploadRemoveFile: function (e) {
        e.stopPropagation();
        if (!window.confirm(Joomla.JText._('PLG_ELEMENT_FILEUPLOAD_CONFIRM_HARD_DELETE'))) {
            return;
        }

        var id = $(e.target).closest('tr').prop('id').split('_').getLast();// alreadyuploaded_8_13
        // $$$ hugh - removed ' span' from the find(), as this blows up on some templates
        var f = $(e.target).closest('tr').find('.plupload_file_name').get('text');

        // Get a list of all of the uploaders files except the one to be deleted
        var newFiles = [];
        this.uploader.files.each(function (f) {
            if (f.id !== id) {
                newFiles.push(f);
            }
        });

        // Update the uploader's files with the new list.
        this.uploader.files = newFiles;

        // Send a request to delete the file from the server.
        $.ajax({
            url : '',
            data: {
                'option'       : 'com_fabrik',
                'format'       : 'raw',
                'task'         : 'plugin.pluginAjax',
                'plugin'       : 'fileupload',
                'method'       : 'ajax_deleteFile',
                'element_id'   : this.options.id,
                'file'         : f,
                'recordid'     : id,
                'repeatCounter': this.options.repeatCounter
            }
        });
        var li = e.target.closest('.plupload_delete');
        li.destroy();

        // Remove hidden fields as well
        $('#id_alreadyuploaded_' + this.options.id + '_' + id).destroy();
        $('#coords_alreadyuploaded_' + this.options.id + '_' + id).destroy();

        if (this.getContainer().find('table tbody tr.plupload_delete').length === 0) {
            this.addDropArea();
        }
    },

    pluploadResize: function (e) {
        e.stopPropagation();
        var a = e.target.closest();
        if (this.widget) {
            this.widget.setImage(a.href, a.retrieve('filepath'));
        }
    },

    /**
     * Once the upload fires a FileUploaded bound function we test if all images for this element have been uploaded If they have then we save the
     * crop widget state and fire the callback - which is handled by FbFormSubmit()
     */
    isSumbitDone: function () {
        if (this.allUploaded() && typeof (this.submitCallBack) === 'function') {
            this.saveWidgetState();
            this.submitCallBack(true);
            delete this.submitCallBack;
        }
    },

    /**
     * Called from FbFormSubmit.submit() handles testing. If not yet uploaded, triggers the upload and defers the callback until the upload is
     * complete. If complete then saves widget state and calls parent onsubmit().
     */
    onsubmit: function (cb) {
        this.submitCallBack = cb;
        if (!this.allUploaded()) {
            this.uploader.start();
            // alert(Joomla.JText._('PLG_ELEMENT_FILEUPLOAD_UPLOAD_ALL_FILES'));
        } else {
            this.saveWidgetState();
            return FbFileUpload.Super.prototype.onsubmit(this, cb);
        }
    },

    /**
     * Save the crop widget state as a json object
     */
    saveWidgetState: function () {
        if (typeOf(this.widget) !== 'null') {
            this.widget.images.each(function (image, key) {
                key = key.split('\\').getLast();
                var f = document.find('input[name*=' + key + ']').filter(function (fld) {
                    return fld.name.contains('[crop]');
                });
                f = f.getLast();

                // $$$ rob - seems reloading ajax fileupload element in ajax form (e.g. from db join add record)
                // is producing odd effects where old fileupload object constains info to previously uploaded image?
                if (typeOf(f) !== 'null') {

                    // Avoid circular reference in chrome when saving in ajax form
                    var i = image.img;
                    delete (image.img);
                    f.value = JSON.encode(image);
                    image.img = i;
                }
            });
        }
    },

    allUploaded: function () {
        var uploaded = true;
        if (this.uploader) {
            this.uploader.files.each(function (file) {
                if (file.loaded === 0) {
                    uploaded = false;
                }
            }.bind(this));
        }
        return uploaded;
    }
});

var ImageWidget = my.Class({

    constructor: function (canvas, opts) {
        // When element is in modal window it renders fine the first time. But the second time
        // the original window is still there - so we end up with 2 dom structures and one window object.
        // To get round this set the first window to be destroyed and close it.
        if (Fabrik.Windows[canvas.id + '-mocha']) {
            Fabrik.Windows[canvas.id + '-mocha'].options.destroy = true;
            Fabrik.Windows[canvas.id + '-mocha'].close();
        }

        this.canvas = canvas;

        this.imageDefault = {
            'rotation': 0,
            'scale'   : 100,
            'imagedim': {
                x: 200,
                y: 200,
                w: 400,
                h: 400
            },
            'cropdim' : {
                x: 75,
                y: 25,
                w: 150,
                h: 50
            }
        };

        $.extend(this.imageDefault, opts);

        this.windowopts = {
            'id'             : this.canvas.id + '-mocha',
            'type'           : 'modal',
            content          : this.canvas.parent(),
            loadMethod       : 'html',
            width            : this.imageDefault.imagedim.w.toInt() + 40,
            height           : this.imageDefault.imagedim.h.toInt() + 150,
            storeOnClose     : true,
            createShowOverLay: false,
            crop             : opts.crop,
            destroy          : false,
            quality          : opts.quality,
            onClose          : function () {
                this.storeActiveImageData();
            }.bind(this),
            onContentLoaded  : function () {
                this.center();
            },
            onOpen           : function () {
                this.center();
            }
        };
        this.windowopts.title = opts.crop ? Joomla.JText._('PLG_ELEMENT_FILEUPLOAD_CROP_AND_SCALE') : Joomla.JText
            ._('PLG_ELEMENT_FILEUPLOAD_PREVIEW');
        this.showWin();
        this.images = {};
        this.CANVAS = new FbCanvas({
            canvasElement: $('#' + this.canvas.id),
            enableMouse  : true,
            cacheCtxPos  : false
        });

        this.CANVAS.layers.add(new Layer({
            id: 'bg-layer'
        }));
        this.CANVAS.layers.add(new Layer({
            id: 'image-layer'
        }));
        if (opts.crop) {
            this.CANVAS.layers.add(new Layer({
                id: 'overlay-layer'
            }));
            this.CANVAS.layers.add(new Layer({
                id: 'crop-layer'
            }));
        }
        var bg = new CanvasItem({
            id    : 'bg',
            scale : 1,
            events: {
                onDraw: function (ctx) {
                    if (typeOf(ctx) === 'null') {
                        // return;
                        ctx = this.CANVAS.ctx;
                    }
                    ctx.fillStyle = '#DFDFDF';
                    ctx.fillRect(0, 0, this.imageDefault.imagedim.w / this.scale, this.imageDefault.imagedim.h / this.scale);
                }.bind(this)
            }
        });

        this.CANVAS.layers.get('bg-layer').add(bg);
        if (opts.crop) {
            this.overlay = new CanvasItem({
                id    : 'overlay',
                events: {
                    onDraw: function (ctx) {
                        if (typeOf(ctx) === 'null') {
                            ctx = this.CANVAS.ctx;
                        }
                        this.withinCrop = true;
                        if (this.withinCrop) {
                            var top = {
                                x: 0,
                                y: 0
                            };
                            var bottom = {
                                x: this.imageDefault.imagedim.w,
                                y: this.imageDefault.imagedim.h
                            };
                            ctx.fillStyle = "rgba(0, 0, 0, 0.3)";
                            var cropper = this.cropperCanvas;
                            ctx.fillRect(top.x, top.y, bottom.x, cropper.y - (cropper.h / 2));// top
                            ctx.fillRect(top.x - (cropper.w / 2), top.y + cropper.y - (cropper.h / 2), top.x + cropper.x, cropper.h);// left
                            ctx.fillRect(top.x + cropper.x + cropper.w - (cropper.w / 2), top.y + cropper.y - (cropper.h / 2), bottom.x, cropper.h);// right
                            ctx.fillRect(top.x, top.y + (cropper.y + cropper.h) - (cropper.h / 2), bottom.x, bottom.y);// bottom
                        }
                    }.bind(this)
                }
            });

            this.CANVAS.layers.get('overlay-layer').add(this.overlay);
        }

        this.imgCanvas = this.makeImgCanvas();

        this.CANVAS.layers.get('image-layer').add(this.imgCanvas);

        this.cropperCanvas = this.makeCropperCanvas();
        if (opts.crop) {
            // add an item
            this.CANVAS.layers.get('crop-layer').add(this.cropperCanvas);
        }
        this.makeThread();
        this.watchZoom();
        this.watchRotate();
        this.watchClose();
        this.win.close();
    },

    /**
     * Add or make active an image in the editor
     *
     * @param string uri Image URI
     * @param string filepath Path to file
     * @param object params Initial parameters
     */

    setImage: function (uri, filepath, params) {
        this.activeFilePath = filepath;
        if (!this.images.hasOwnProperty(filepath)) {

            // Needed to ensure they are available in onLoad
            var tmpParams = params;

            // New image
            var img = Asset.image(uri, {
                onLoad: function () {

                    var params = this.storeImageDimensions(filepath, img, tmpParams);
                    this.img = params.img;
                    this.setInterfaceDimensions(params);
                    this.showWin();
                    this.storeActiveImageData(filepath);
                    this.win.close();
                }.bind(this)
            });
        } else {

            // Previously set up image
            params = this.images[filepath];
            this.img = params.img;
            this.setInterfaceDimensions(params);
            this.showWin();
        }
    },

    /**
     * Set rotate, scale, image and crop values for a given image
     *
     * @param object params Image parameters
     */
    setInterfaceDimensions: function (params) {
        if (this.scaleSlide) {
            this.scaleSlide.set(params.scale);
        }
        if (this.rotateSlide) {
            this.rotateSlide.set(params.rotation);
        }

        if (this.cropperCanvas && params.cropdim) {
            this.cropperCanvas.x = params.cropdim.x;
            this.cropperCanvas.y = params.cropdim.y;
            this.cropperCanvas.w = params.cropdim.w;
            this.cropperCanvas.h = params.cropdim.h;
        }
        this.imgCanvas.w = params.mainimagedim.w;
        this.imgCanvas.h = params.mainimagedim.h;
        this.imgCanvas.x = typeOf(params.imagedim) !== 'null' ? params.imagedim.x : 0;
        this.imgCanvas.y = typeOf(params.imagedim) !== 'null' ? params.imagedim.y : 0;
    },

    /**
     * One time call to store initial image crop info in this.images
     *
     * @param string filepath Path to image
     * @param DOMnode img Image - just created
     * @param params object Image parameters
     *
     * @return object Update image parameters
     */

    storeImageDimensions: function (filepath, img, params) {
        img.inject(document.body).hide();
        params = params ? params : new CloneObject(this.imageDefault, true, []);
        var s = img.getDimensions(true);
        if (!params.imagedim) {
            params.mainimagedim = {};
        } else {
            params.mainimagedim = params.imagedim;
        }
        params.mainimagedim.w = s.width;
        params.mainimagedim.h = s.height;
        params.img = img;
        this.images[filepath] = params;
        return params;
    },

    makeImgCanvas: function () {
        var parent = this;
        return new CanvasItem({
            id         : 'imgtocrop',
            w          : this.imageDefault.imagedim.w,
            h          : this.imageDefault.imagedim.h,
            x          : 200,
            y          : 200,
            interactive: true,
            rotation   : 0,
            scale      : 1,
            offset     : [0, 0],
            events     : {
                onMousemove: function (x, y) {
                    if (this.dragging) {
                        var w = this.w * this.scale;
                        var h = this.h * this.scale;
                        this.x = x - this.offset[0] + w * 0.5;
                        this.y = y - this.offset[1] + h * 0.5;
                    }
                },
                onDraw     : function (ctx) {
                    ctx = parent.CANVAS.ctx;
                    if (typeOf(parent.img) === 'null') {
                        // console.log('no parent img', parent);
                        return;
                    }

                    var w = this.w * this.scale;
                    var h = this.h * this.scale;
                    var x = this.x - w * 0.5;
                    var y = this.y - h * 0.5;

                    // standard Canvas rotation operation
                    ctx.save();
                    ctx.translate(this.x, this.y);
                    ctx.rotate(this.rotation * Math.PI / 180);

                    this.hover ? ctx.strokeStyle = '#f00' : ctx.strokeStyle = '#000';
                    ctx.strokeRect(w * -0.5, h * -0.5, w, h);
                    if (typeOf(parent.img) !== 'null') {
                        try {
                            ctx.drawImage(parent.img, w * -0.5, h * -0.5, w, h);
                        } catch (err) {
                            // only show this for debugging as if we upload a pdf then we get shown lots of these errors.
                            // fconsole(err, parent.img, w * -0.5, h * -0.5, w, h);
                        }
                    }
                    ctx.restore();
                    if (typeOf(parent.img) !== 'null' && parent.images.get(parent.activeFilePath)) {
                        parent.images.get(parent.activeFilePath).imagedim = {
                            x: this.x,
                            y: this.y,
                            w: w,
                            h: h
                        };

                    }
                    this.setDims(x, y, w, h);
                },

                onMousedown: function (x, y) {
                    parent.CANVAS.setDrag(this);
                    this.offset = [x - this.dims[0], y - this.dims[1]];
                    this.dragging = true;
                },

                onMouseup: function () {
                    parent.CANVAS.clearDrag();
                    this.dragging = false;
                },

                onMouseover: function () {
                    parent.overImg = true;
                    document.body.style.cursor = "move";
                },

                onMouseout: function () {
                    parent.overImg = false;
                    if (!parent.overCrop) {
                        document.body.style.cursor = "default";
                    }
                }
            }
        });
    },

    makeCropperCanvas: function () {
        var parent = this;
        return new CanvasItem({
            id         : 'item',
            x          : 175,
            y          : 175,
            w          : 150,
            h          : 50,
            interactive: true,
            offset     : [0, 0],
            events     : {
                onDraw: function (ctx) {
                    ctx = parent.CANVAS.ctx;
                    if (typeOf(ctx) === 'null') {
                        return;
                    }
                    /*
                     * calculate dimensions locally because they are have to be translated in order to use translate and rotate with the desired
                     * effect: rotate the item around its visual center
                     */

                    var w = this.w;
                    var h = this.h;
                    var x = this.x - w * 0.5;
                    var y = this.y - h * 0.5;

                    // standard Canvas rotation operation

                    ctx.save();
                    ctx.translate(this.x, this.y);

                    this.hover ? ctx.strokeStyle = '#f00' : ctx.strokeStyle = '#000';
                    ctx.strokeRect(w * -0.5, h * -0.5, w, h);
                    ctx.restore();

                    /*
                     * used to determine the whether the mouse is over an item or not.
                     */

                    if (typeOf(parent.img) !== 'null' && parent.images.get(parent.activeFilePath)) {
                        parent.images.get(parent.activeFilePath).cropdim = {
                            x: this.x,
                            y: this.y,
                            w: w,
                            h: h
                        };
                    }
                    this.setDims(x, y, w, h);
                },

                onMousedown: function (x, y) {
                    parent.CANVAS.setDrag(this);
                    this.offset = [x - this.dims[0], y - this.dims[1]];
                    this.dragging = true;
                    parent.overlay.withinCrop = true;
                },

                onMousemove: function (x, y) {
                    document.body.style.cursor = "move";
                    if (this.dragging) {
                        var w = this.w;
                        var h = this.h;
                        this.x = x - this.offset[0] + w * 0.5;
                        this.y = y - this.offset[1] + h * 0.5;
                    }
                },

                onMouseup: function () {
                    parent.CANVAS.clearDrag();
                    this.dragging = false;
                    parent.overlay.withinCrop = false;
                },

                onMouseover: function () {
                    this.hover = true;
                    parent.overCrop = true;

                },

                onMouseout: function () {
                    if (!parent.overImg) {
                        document.body.style.cursor = "default";
                    }
                    parent.overCrop = false;
                    this.hover = false;
                }
            }
        });
    },

    makeThread: function () {
        this.CANVAS.addThread(new Thread({
            id    : 'myThread',
            onExec: function () {
                if (typeOf(this.CANVAS) !== 'null') {
                    if (typeOf(this.CANVAS.ctxEl) !== 'null') {
                        this.CANVAS.clear().draw();
                    }
                }
            }.bind(this)
        }));
    },

    /**
     * watch the close button
     */

    watchClose: function () {
        var w = ('#' + this.windowopts.id);
        w.find('input[name=close-crop]').on('click', function (e) {
            this.storeActiveImageData();
            this.win.close();
        }.bind(this));
    },

    /**
     * Takes the current active image and creates cropped image data via a canvas element
     *
     * @param string filepath File path to image to crop. If blank use this.activeFilePath
     */
    storeActiveImageData: function (filepath) {
        filepath = filepath ? filepath : this.activeFilePath;
        if (typeOf(filepath) === 'null') {
            return;
        }
        var x = this.cropperCanvas.x;
        var y = this.cropperCanvas.y;
        var w = this.cropperCanvas.w - 2;
        var h = this.cropperCanvas.h - 2;
        x = x - (w / 2);
        y = y - (h / 2);

        var win = $('#' + this.windowopts.id);
        if (win.length === 0) {
            fconsole('storeActiveImageData no window found for ' + this.windowopts.id);
            return;
        }
        var canvas = win.find('canvas');

        var target = $(document.createElement('canvas')).attr({
            'width' : w + 'px',
            'height': h + 'px'
        }).inject(document.body);
        var ctx = target.getContext('2d');

        var file = filepath.split('\\').getLast();
        var f = document.find('input[name*=' + file + ']').filter(function (fld) {
            return fld.name.contains('cropdata');
        });

        ctx.drawImage(canvas, x, y, w, h, 0, 0, w, h);
        f.set('value', target.toDataURL({quality: this.windowopts.quality}));
        target.destroy();
    },

    /**
     * set up and wath the zoom slide and input field
     */

    watchZoom: function () {
        var w = $('#' + this.windowopts.id),
            self = this;
        if (!this.windowopts.crop) {
            return;
        }
        this.scaleField = w.find('input[name=zoom-val]');
        this.scaleSlide = new Slider(w.find('.fabrikslider-line'), w.find('.knob'), {
            range   : [20, 300],
            onChange: function (pos) {
                this.imgCanvas.scale = pos / 100;
                if (typeOf(this.img) !== 'null') {
                    try {
                        this.images[this.activeFilePath].scale = pos;
                    } catch (err) {
                        fconsole('didnt get active file path:' + this.activeFilePath);
                    }
                }
                this.scaleField.value = pos;
            }.bind(this)
        }).set(100);

        this.scaleField.on('keyup', function () {
            self.scaleSlide.set($(this).val());
        });
    },

    /**
     * set up and watch the rotate slide and input field
     */

    watchRotate: function () {
        var w = $('#' + this.windowopts.id),
            self = this;
        if (!this.windowopts.crop) {
            return;
        }
        var r = w.find('.rotate');
        this.rotateField = r.find('input[name=rotate-val]');
        this.rotateSlide = new Slider(r.find('.fabrikslider-line'), r.find('.knob'), {
            onChange: function (pos) {
                this.imgCanvas.rotation = pos;
                if (typeOf(this.img) !== 'null') {
                    try {
                        this.images[this.activeFilePath].rotation = pos;
                    } catch (err) {
                        fconsole('rorate err' + this.activeFilePath);
                    }
                }
                this.rotateField.value = pos;
            }.bind(this),
            steps   : 360
        }).set(0);
        this.rotateField.on('keyup', function (e) {
            self.rotateSlide.set($(this).val());
        });
    },

    showWin: function () {
        this.win = Fabrik.getWindow(this.windowopts);
        if (typeOf(this.CANVAS) === 'null') {
            return;
        }
        if (typeOf(this.CANVAS.ctxEl) !== 'null') {
            this.CANVAS.ctxPos = $('#' + this.CANVAS.ctxEl).getPosition();
        }

        if (typeOf(this.CANVAS.threads) !== 'null') {
            if (typeOf(this.CANVAS.threads.get('myThread')) !== 'null') {

                // Fixes issue where sometime canvas thread is not started/running so nothing is drawn
                this.CANVAS.threads.get('myThread').start();
            }
        }
        this.win.center();
    }
});
