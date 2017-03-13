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

    getGridColumnFilter: function(field) {
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
            style: "margin-bottom: 10px",
            manageHeight: false,
            cls: "object_field"
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
                return Ext.dd.DropZone.prototype.dropAllowed;
            },

            onNodeDrop : this.onNodeDrop.bind(this)
        });

        var eConfig = {
            width: this.fieldConfig.width,
            height: this.fieldConfig.height,
            language: pimcore.settings["language"]
        };

        eConfig.toolbarGroups = [
            { name: 'clipboard', groups: [ 'sourcedialog', 'clipboard', 'undo', 'find' ] },
            { name: 'basicstyles', groups: [ 'basicstyles', 'list'] },
            '/',
            { name: 'paragraph', groups: [ 'align', 'indent'] },
            { name: 'blocks' },
            { name: 'links' },
            { name: 'insert' },
            '/',
            { name: 'styles' },
            { name: 'tools', groups: ['colors', 'tools', 'cleanup', 'mode', 'others'] }
        ];

        //prevent override important settings!
        eConfig.resize_enabled = false;
        eConfig.entities = false;
        eConfig.entities_greek = false;
        eConfig.entities_latin = false;
        eConfig.extraAllowedContent = "*[pimcore_type,pimcore_id]";

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

        if(this.fieldConfig.toolbarConfig) {
            var elementCustomConfig = Ext.decode(this.fieldConfig.toolbarConfig);
            eConfig = mergeObject(eConfig, elementCustomConfig);
        }

        if(typeof(pimcore.object.tags.wysiwyg.defaultEditorConfig) == 'object'){
            eConfig = mergeObject(pimcore.object.tags.wysiwyg.defaultEditorConfig,eConfig);
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
        } catch (e) {
            console.log(e);
        }
    },

    onNodeDrop: function (target, dd, e, data) {

        if (!this.ckeditor) {
            return;
        }

        this.ckeditor.focus();

        var node = data.records[0];
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
                uri = "/admin/asset/get-image-thumbnail?id=" + id + "&width=" + defaultWidth + "&aspectratio=true";

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
    }
});

CKEDITOR.disableAutoInline = true;