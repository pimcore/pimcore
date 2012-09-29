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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

pimcore.registerNS("pimcore.settings.fileexplorer.file");
pimcore.settings.fileexplorer.file = Class.create({

    initialize: function (path, explorer) {
        this.path = path;
        this.explorer = explorer;
        this.loadFileContents(path);
    },

    loadFileContents: function (path) {
        Ext.Ajax.request({
            url: "/admin/misc/fileexplorer-content",
            success: this.loadFileContentsComplete.bind(this),
            params: {
                path: path
            }
        });
    },

    loadFileContentsComplete: function (response) {
        var response = Ext.decode(response.responseText);
        if(response.success) {

            var toolbarItems = [];
            if(response.writeable) {
                toolbarItems.push({
                    text: t("save"),
                    handler: this.saveFile.bind(this, response.path),
                    iconCls: "pimcore_icon_save"
                });
            }

            this.editorId = "codeeditor_" + uniqid();
            this.editorMode = "text";
            if(response.path.match(/\.php$/)) {
                this.editorMode = "php";
            } else if (response.path.match(/\.js$/)) {
                this.editorMode = "javascript";
            } else if (response.path.match(/\.css$/)) {
                this.editorMode = "css";
            }  else if (response.path.match(/\.less$/)) {
                this.editorMode = "less";
            } else if (response.path.match(/\.scss$/)) {
                this.editorMode = "scss";
            } else if (response.path.match(/\.html$/)) {
                this.editorMode = "html";
            } else if (response.path.match(/\.coffee$/)) {
                this.editorMode = "coffee";
            } else if (response.path.match(/\.json$/)) {
                this.editorMode = "json";
            } else if (response.path.match(/\.sh$/)) {
                this.editorMode = "sh";
            } else if (response.path.match(/\.sql$/)) {
                this.editorMode = "sql";
            } else if (response.path.match(/\.svg$/)) {
                this.editorMode = "svg";
            } else if (response.path.match(/\.xml$/)) {
                this.editorMode = "xml";
            }


            this.editor = new Ext.Panel({
                title: response.path,
                closable: true,
                layout: "fit",
                tbar: toolbarItems,
                bodyStyle: "position:relative;",
                html: '<pre id="' + this.editorId + '"></pre>'
            });

            this.editor.on("beforedestroy", function () {
                delete this.explorer.openfiles[this.path];
            }.bind(this));

            this.editor.on("resize", function (el, width, height) {
                if(this.aceEditor) {
                    Ext.get(this.editorId).setWidth(width);
                    Ext.get(this.editorId).setHeight(height-12);
                    this.aceEditor.resize();
                }
            }.bind(this));

            this.editor.on("afterrender", function () {
                this.aceEditor = ace.edit(this.editorId);
                this.aceEditor.setTheme("ace/theme/chrome");
                this.aceEditor.getSession().setMode("ace/mode/" + this.editorMode);
                this.aceEditor.getSession().setValue(response.content);
            }.bind(this));

            this.explorer.editorPanel.add(this.editor);
            this.explorer.editorPanel.activate(this.editor);
            this.explorer.editorPanel.doLayout();
        }
    },

    saveFile: function (path) {
        var content = this.aceEditor.getSession().getValue();
        Ext.Ajax.request({
            method: "post",
            url: "/admin/misc/fileexplorer-content-save",
            params: {
                path: path,
                content: content
            },
            success: function (response) {
                try{
                    var rdata = Ext.decode(response.responseText);
                    if (rdata && rdata.success) {
                        pimcore.helpers.showNotification(t("success"), t("file_explorer_saved_file_success"), "success");
                    }
                    else {
                        pimcore.helpers.showNotification(t("error"), t("file_explorer_saved_file_error"), "error");
                    }
                } catch (e) {
                    pimcore.helpers.showNotification(t("error"), t("file_explorer_saved_file_error"), "error");
                }
            }.bind(this)
        });
    },

    activate: function () {
        this.explorer.editorPanel.activate(this.editor);
    }

});
