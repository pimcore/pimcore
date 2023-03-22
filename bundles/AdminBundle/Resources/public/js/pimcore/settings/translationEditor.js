/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

pimcore.registerNS("pimcore.settings.translation.editor");
pimcore.settings.translation.editor = Class.create({
    
    initialize: function (context, field, translationType, editorType) {

        Ext.WindowManager.each(function(window, idx, length) {
            window.destroy();
        });

        this.field = field;
        this.context = context;
        let value = field.getValue();

        let bbar = [];

        if (editorType === 'wysiwyg') {
            this.editableDivId = "translationeditor_" + uniqid();

            var html = '<div class="pimcore_editable_wysiwyg" id="' + this.editableDivId + '" contenteditable="true"></div>';
            var pConf = {
                html: html,
                border: true,
                style: "margin-bottom: 10px",
                height: '100%',
                autoScroll: true
            };

            this.component = new Ext.Panel(pConf);

            this.component.on("beforedestroy", function () {
                    if (this.ckeditor) {
                        this.ckeditor.destroy();
                        this.ckeditor = null;
                    }
                }
            );

            this.component.on("afterlayout", this.initCkEditor.bind(this));
        } else {
            this.component = new Ext.form.TextArea({
                width: '100%',
                height: '100%',
                value: value,
            });

            if(translationType === 'custom') {
                bbar.push({
                    xtype: "displayfield",
                    value: t('symfony_translation_link')
                });
            }
        }

        bbar.push({
            text: t("save"),
            iconCls: 'pimcore_icon_save',
            handler: function () {
                let newValue = '';
                if (editorType == "wysiwyg") {
                    try {
                        if (this.ckeditor) {
                            newValue = this.ckeditor.getData();
                        }
                    }
                    catch (e) {
                    }
                } else {
                    newValue = this.component.getValue();
                }

                this.field.setValue(newValue);
                this.context.setValueStatus(this.field, newValue);

                this.editWin.close();
            }.bind(this)
        });

        bbar.push({
            text: t("cancel"),
            iconCls: 'pimcore_icon_cancel',
            handler: function () {
                this.editWin.close();
            }.bind(this)
        });

        this.editWin = new Ext.Window({
            modal: false,
            items: [this.component],
            bodyStyle: "background: #fff; padding: 10px",
            width: 700,
            height: 400,
            layout: 'fit',
            closeAction: 'method-destroy',
            autoScroll: true,
            preventRefocus: true,      // nasty hack because this is an internal property
                                       // for html grid cell values with hrefs this prevents that the cell
                                       // gets refocused which would then trigger another editor window
                                       // upon close of this instance
            bbar: bbar
        });


        this.editWin.show();
        this.editWin.updateLayout();
    },

    destroy: function () {
        if (this.editWin) {
            this.editWin.destroy();
        }
    },

    initCkEditor: function () {

        if (this.ckeditor) {
            return;
        }

        // add drop zone, use the parent panel here (container), otherwise this can cause problems when specifying a fixed height on the wysiwyg
        var dd = new Ext.dd.DropZone(Ext.get(this.editableDivId).parent(), {
            ddGroup: "element",

            getTargetFromEvent: function(e) {
                return this.getEl();
            },

            onNodeOver : function(target, dd, e, data) {
                if (data.records.length == 1) {
                    var record = data.records[0];
                    data = record.data;
                    if (this.dndAllowed(data)) {
                        return Ext.dd.DropZone.prototype.dropAllowed;
                    }
                }
                return Ext.dd.DropZone.prototype.dropNotAllowed;

            }.bind(this),

            onNodeDrop : this.onNodeDrop.bind(this)
        });


        var eConfig = {};

        eConfig.toolbarGroups = [
            {name: 'basicstyles', groups: ['undo', 'find', 'basicstyles', 'list']},
            '/',
            {name: 'paragraph', groups: ['align', 'indent']},
            {name: 'blocks'},
            {name: 'links'},
            {name: 'insert'},
            '/',
            {name: 'styles'},
            {name: 'tools', groups: ['colors', 'tools', 'cleanup', 'mode', 'others']}
        ];

        //prevent override important settings!
        eConfig.resize_enabled = false;
        eConfig.enterMode = CKEDITOR.ENTER_BR;
        eConfig.entities = false;
        eConfig.entities_greek = false;
        eConfig.entities_latin = false;
        eConfig.extraAllowedContent = "*[pimcore_type,pimcore_id]";
        eConfig.baseFloatZIndex = 40000;   // prevent that the editor gets displayed behind the grid cell editor window

        if (eConfig.hasOwnProperty('removePlugins')) {
            eConfig.removePlugins += ",tableresize";
        }
        else {
            eConfig.removePlugins = "tableresize";
        }


        try {
            this.ckeditor = CKEDITOR.inline(this.editableDivId, eConfig);
            this.ckeditor.setData(this.field.getValue());

            // disable URL field in image dialog
            this.ckeditor.on("dialogShow", function (e) {
                var urlField = e.data.getElement().findOne("input");
                if (urlField && urlField.getValue()) {
                    if (urlField.getValue().indexOf("/image-thumbnails/") > 1) {
                        urlField.getParent().getParent().getParent().hide();
                    }
                } else if (urlField) {
                    urlField.getParent().getParent().getParent().show();
                }
            });
        } catch (e) {
            console.log(e);
        }
    },

    onNodeDrop: function (target, dd, e, data) {
        if (!pimcore.helpers.dragAndDropValidateSingleItem(data)) {
            return;
        }

        if (!this.ckeditor) {
            return;
        }

        this.ckeditor.focus();

        var node = data.records[0];

        if (!this.ckeditor ||!this.dndAllowed(node.data)) {
            return;
        }

        var wrappedText = node.data.text;
        var textIsSelected = false;

        try {
            var selection = this.ckeditor.getSelection();
            var bookmarks = selection.createBookmarks();
            var range = selection.getRanges()[ 0 ];
            var fragment = range.clone().cloneContents();

            selection.selectBookmarks(bookmarks);
            var retval = "";
            var childList = fragment.getChildren();
            var childCount = childList.count();

            for (var i = 0; i < childCount; i++) {
                var child = childList.getItem(i);
                retval += ( child.getOuterHtml ?
                    child.getOuterHtml() : child.getText() );
            }

            if (retval.length > 0) {
                wrappedText = retval;
                textIsSelected = true;
            }
        }
        catch (e2) {
        }


        // remove existing links out of the wrapped text
        wrappedText = wrappedText.replace(/<\/?([a-z][a-z0-9]*)\b[^>]*>/gi, function ($0, $1) {
            if($1.toLowerCase() == "a") {
                return "";
            }
            return $0;
        });

        var id = node.data.id;
        var uri = node.data.path;
        var browserPossibleExtensions = ["jpg","jpeg","gif","png"];

        if (node.data.elementType == "asset") {
            if (node.data.type == "image" && textIsSelected == false) {
                // images bigger than 600px or formats which cannot be displayed by the browser directly will be
                // converted by the pimcore thumbnailing service so that they can be displayed in the editor
                var defaultWidth = 600;
                var additionalAttributes = "";
                uri = Routing.generate('pimcore_admin_asset_getimagethumbnail') + "?id=" + id + "&width=" + defaultWidth + "&aspectratio=true";

                if(typeof node.data.imageWidth != "undefined") {
                    if(node.data.imageWidth < defaultWidth
                        && in_arrayi(pimcore.helpers.getFileExtension(node.data.text),
                            browserPossibleExtensions)) {
                        uri = node.data.path;
                        additionalAttributes += ' pimcore_disable_thumbnail="true"';
                    }

                    if(node.data.imageWidth < defaultWidth) {
                        defaultWidth = node.data.imageWidth;
                    }
                }

                this.ckeditor.insertHtml('<img src="' + uri + '" pimcore_type="asset" pimcore_id="' + id
                    + '" style="width:' + defaultWidth + 'px;"' + additionalAttributes + ' />');
                return true;
            }
            else {
                this.ckeditor.insertHtml('<a href="' + uri + '" pimcore_type="asset" pimcore_id="'
                    + id + '">' + wrappedText + '</a>');
                return true;
            }
        }

        if (node.data.elementType == "document" && (node.data.type=="page"
                || node.data.type=="hardlink" || node.data.type=="link")){
            this.ckeditor.insertHtml('<a href="' + uri + '" pimcore_type="document" pimcore_id="'
                + id + '">' + wrappedText + '</a>');
            return true;
        }

        if (node.data.elementType == "object"){
            this.ckeditor.insertHtml('<a href="' + uri + '" pimcore_type="object" pimcore_id="'
                + id + '">' + wrappedText + '</a>');
            return true;
        }

    },

    dndAllowed: function(data) {

        if (data.elementType == "document" && (data.type=="page"
                || data.type=="hardlink" || data.type=="link")){
            return true;
        } else if (data.elementType=="asset" && data.type != "folder"){
            return true;
        } else if (data.elementType=="object" && data.type != "folder"){
            return true;
        }

        return false;
    }
});
