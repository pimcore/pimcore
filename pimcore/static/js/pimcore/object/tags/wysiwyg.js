/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
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

    getGridColumnEditor: function(field) {
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
            return new Ext.form.HtmlEditor({
                width: 500,
                height: 300
            });
        }
    },

    getGridColumnFilter: function(field) {
        return {type: 'string', dataIndex: field.key};
    },

    getLayout: function () {

        var html = '<div class="pimcore_tag_wysiwyg" id="' + this.editableDivId + '" contenteditable="true">' + this.data + '</div>';
        var pConf = {
            iconCls: "pimcore_icon_droptarget",
            title: this.fieldConfig.title,
            html: html,
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
        }

        this.component = new Ext.Panel(pConf);
    },

    getLayoutShow: function () {
        this.getLayout();
        this.component.on("afterrender", function() {
            Ext.get(this.editableDivId).dom.setAttribute("contenteditable", "false");
        }.bind(this));
        return this.component;
    },

    getLayoutEdit: function () {
        this.getLayout();
        this.component.on("afterrender", this.initCkEditor.bind(this));
        this.component.on("beforedestroy", function() {
            if(this.ckeditor) {
                this.ckeditor.destroy();
                this.ckeditor = null;
            }
        }.bind(this));
        return this.component;
    },

    initCkEditor: function () {

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
            resize_enabled: false,
            language: pimcore.settings["language"]
        };


        eConfig.toolbarGroups = [
            { name: 'clipboard', groups: [ "sourcedialog", 'clipboard', 'undo', "find" ] },
            { name: 'basicstyles', groups: [ 'basicstyles', 'list'] },
            '/',
            { name: 'paragraph', groups: [ 'align', 'indent'] },
            { name: 'blocks' },
            { name: 'links' },
            { name: 'insert' },
            "/",
            { name: 'styles' },
            { name: 'tools', groups: ['colors', "tools", 'cleanup', 'mode', "others"] }
        ];

        if(this.fieldConfig.toolbarConfig) {
            eConfig.toolbarGroups = Ext.decode("[" + this.fieldConfig.toolbarConfig + "]")
        }

        eConfig.allowedContent = true; // disables CKEditor ACF (will remove pimcore_* attributes from links, etc.)
        eConfig.removePlugins = "tableresize";

        if(typeof(pimcore.object.tags.wysiwyg.defaultEditorConfig) == 'object'){
            eConfig = mergeObject(pimcore.object.tags.wysiwyg.defaultEditorConfig,eConfig);
        }

        if (intval(this.fieldConfig.width) > 1) {
            eConfig.width = this.fieldConfig.width;
        }
        if (intval(this.fieldConfig.height) > 1) {
            eConfig.height = this.fieldConfig.height;
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

            // HACK - clean all pasted html
            this.ckeditor.on('paste', function(evt) {
                evt.data.dataValue = '<!--class="Mso"-->' + evt.data.dataValue;
            }, null, null, 1);

        } catch (e) {
            console.log(e);
        }
    },

    onNodeDrop: function (target, dd, e, data) {

        if (!this.ckeditor) {
            return;
        }

        this.ckeditor.focus();

        var wrappedText = data.node.attributes.text;
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

        var id = data.node.attributes.id;
        var uri = data.node.attributes.path;
        var browserPossibleExtensions = ["jpg","jpeg","gif","png"];
        
        if (data.node.attributes.elementType == "asset") {
            if (data.node.attributes.type == "image" && textIsSelected == false) {
                // images bigger than 600px or formats which cannot be displayed by the browser directly will be
                // converted by the pimcore thumbnailing service so that they can be displayed in the editor
                var defaultWidth = 600;
                var additionalAttributes = "";
                uri = "/admin/asset/get-image-thumbnail/id/" + id + "/width/" + defaultWidth + "/aspectratio/true";

                if(typeof data.node.attributes.imageWidth != "undefined") {
                    if(data.node.attributes.imageWidth < defaultWidth
                                && in_arrayi(pimcore.helpers.getFileExtension(data.node.attributes.text),
                                                                        browserPossibleExtensions)) {
                        uri = data.node.attributes.path;
                        additionalAttributes += ' pimcore_disable_thumbnail="true"';
                    }

                    if(data.node.attributes.imageWidth < defaultWidth) {
                        defaultWidth = data.node.attributes.imageWidth;
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

        if (data.node.attributes.elementType == "document" && (data.node.attributes.type=="page"
                                || data.node.attributes.type=="hardlink" || data.node.attributes.type=="link")){
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
            return this.ckeditor.checkDirty();
        }
        return false;
    }
});

CKEDITOR.disableAutoInline = true;

// IE Hack see: http://dev.ckeditor.com/ticket/9958
// problem is that every button in a CKEDITOR window fires the onbeforeunload event
CKEDITOR.on('instanceReady', function (event) {
    event.editor.on('dialogShow', function (dialogShowEvent) {
        if (CKEDITOR.env.ie) {
            $(dialogShowEvent.data._.element.$).find('a[href*="void(0)"]').removeAttr('href');
        }
    });
});
