/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

pimcore.registerNS("pimcore.object.tags.wysiwyg");
pimcore.object.tags.wysiwyg = Class.create(pimcore.object.tags.abstract, {

    type: "wysiwyg",

    initialize: function (data, fieldConfig) {
        this.data = "";
        if (data) {
            this.data = data;
        }
        this.fieldConfig = fieldConfig;
        this.editableDivId = "object_wysiwyg_" + uniqid();
    },

    /**
     * @extjs since HTMLEditor seems not working properly in grid, this feature is deactivated for now
     */
    /*getGridColumnEditor: function(field) {
        var editorConfig = {};

        if (field.config) {
            if (field.config.width) {
                if (intval(field.config.width) > 10) {
                    editorConfig.width = field.config.width;
                }
            }
        }

        if(field.layout.noteditable) {
            return null;
        }
        // WYSIWYG
        if (field.type == "wysiwyg") {
            return Ext.create('Ext.form.HtmlEditor', {
                width: 500,
                height: 300
            });
        }
    },*/


    getGridColumnConfig: function (field) {
        var renderer = function (key, value, metaData, record) {
            this.applyPermissionStyle(key, value, metaData, record);

            try {
                if (record.data.inheritedFields && record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited == true) {
                    metaData.tdCls += " grid_value_inherited";
                }
            } catch (e) {
                console.log(e);
            }
            return value;

        }.bind(this, field.key);

        return {
            text: t(field.label), sortable: true, dataIndex: field.key, renderer: renderer,
            getEditor: this.getWindowCellEditor.bind(this, field)
        };
    },


    getGridColumnFilter: function (field) {
        return {type: 'string', dataIndex: field.key};
    },

    getLayout: function () {

        var iconCls = null;
        if(this.fieldConfig.noteditable == false) {
            iconCls = "pimcore_icon_droptarget";
        }

        var html = '<div class="pimcore_tag_wysiwyg" id="' + this.editableDivId + '" contenteditable="true">' + this.data + '</div>';
        var pConf = {
            iconCls: iconCls,
            title: this.fieldConfig.title,
            html: html,
            border: true,
            bodyStyle: 'background: #fff',
            style: "margin-bottom: 10px",
            manageHeight: false,
            cls: "object_field object_field_type_" + this.type
        };

        if(this.fieldConfig.width) {
            pConf["width"] = this.fieldConfig.width;
        }

        if(this.fieldConfig.height) {
            pConf["height"] = this.fieldConfig.height;
            pConf["autoScroll"] = true;
        } else {
            pConf["autoHeight"] = true;
            pConf["autoScroll"] = true;
        }

        this.component = new Ext.Panel(pConf);
    },

    getLayoutShow: function () {
        this.getLayout();
        this.component.on("afterrender", function() {
            Ext.get(this.editableDivId).dom.setAttribute("contenteditable", "false");
        }.bind(this));
        this.component.disable();
        return this.component;
    },

    getLayoutEdit: function () {
        this.getLayout();
        this.component.on("afterlayout", this.initCkEditor.bind(this));
        this.component.on("beforedestroy", function() {
            if(this.ckeditor) {
                this.ckeditor.destroy();
                this.ckeditor = null;
            }
        }.bind(this));
        return this.component;
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
                if (data.records.length === 1 && this.dndAllowed(data.records[0].data)) {
                    return Ext.dd.DropZone.prototype.dropAllowed;
                }
            }.bind(this),

            onNodeDrop : this.onNodeDrop.bind(this)
        });

        var eConfig = {
            width: this.fieldConfig.width,
            height: this.fieldConfig.height,
            language: pimcore.settings["language"],
            resize_enabled: false,
            entities: false,
            entities_greek: false,
            entities_latin: false,
            extraAllowedContent: "*[pimcore_type,pimcore_id,pimcore_disable_thumbnail]",
            baseFloatZIndex: 40000 // prevent that the editor gets displayed behind the grid cell editor window
        };

        eConfig.toolbarGroups = [
            { name: 'basicstyles', groups: [ 'undo', 'find', 'basicstyles', 'list'] },
            '/',
            { name: 'paragraph', groups: [ 'align', 'indent'] },
            { name: 'blocks' },
            { name: 'links' },
            { name: 'insert' },
            '/',
            { name: 'styles' },
            { name: 'tools', groups: ['colors', 'tools', 'cleanup', 'mode', 'others'] }
        ];

        if(eConfig.hasOwnProperty('removePlugins'))
            eConfig.removePlugins += ",tableresize";
        else
            eConfig.removePlugins = "tableresize";

        if (intval(this.fieldConfig.width) > 1) {
            eConfig.width = this.fieldConfig.width;
        }
        if (intval(this.fieldConfig.height) > 1) {
            eConfig.height = this.fieldConfig.height;
        }

        if(typeof(pimcore.object.tags.wysiwyg.defaultEditorConfig) == 'object'){
            eConfig = mergeObject(eConfig, pimcore.object.tags.wysiwyg.defaultEditorConfig);
        }

        if(this.fieldConfig.toolbarConfig) {
            var elementCustomConfig = Ext.decode(this.fieldConfig.toolbarConfig);
            eConfig = mergeObject(eConfig, elementCustomConfig);
        }

        try {
            this.ckeditor = CKEDITOR.inline(this.editableDivId, eConfig);

            // disable URL field in image dialog
            this.ckeditor.on("dialogShow", function (e) {
                var urlField = e.data.getElement().findOne("input");
                if(urlField && urlField.getValue()) {
                    if(urlField.getValue().indexOf("/image-thumbnails/") > 1) {
                        urlField.getParent().getParent().getParent().hide();
                    }
                } else if (urlField) {
                    urlField.getParent().getParent().getParent().show();
                }
            });

            // force paste dialog to prevent security message on various browsers
            this.ckeditor.on('beforeCommandExec', function(event) {
                if (event.data.name === 'paste') {
                    event.editor._.forcePasteDialog = true;
                }

                if (event.data.name === 'pastetext' && event.data.commandData.from === 'keystrokeHandler') {
                    event.cancel();
                }
            });

        } catch (e) {
            console.log(e);
        }
    },

    onNodeDrop: function (target, dd, e, data) {

        if(!pimcore.helpers.dragAndDropValidateSingleItem(data)) {
            return false;
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
                uri = Routing.generate('pimcore_admin_asset_getimagethumbnail', {id: id, width: defaultWidth, aspectration: true});

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
    },

    getValue: function () {

        var data = this.data;
        try {
            if (this.ckeditor) {
                data = this.ckeditor.getData();
            }
        }
        catch (e) {
        }

        this.data = data;

        return this.data;
    },

    getName: function () {
        return this.fieldConfig.name;
    },

    isDirty: function() {
        if(!this.isRendered()) {
            return false;
        }

        if(this.dirty) {
            return this.dirty;
        }

        if(this.ckeditor) {
            var ckeditorDirty = this.ckeditor.checkDirty();
            if(ckeditorDirty) {
                // once dirty, always dirty
                // this is due the way checkDirty() is implemented in CKEditor, because it relies on the initial content
                this.dirty = ckeditorDirty;
            }
            return ckeditorDirty;
        }

        return false;
    },

    getWindowCellEditor: function (field, record) {
        return new pimcore.element.helpers.gridCellEditor({
                fieldInfo: field,
                elementType: "object"
            }
        );
    },

    getCellEditValue: function () {
        return this.getValue();
    }
});

CKEDITOR.disableAutoInline = true;
