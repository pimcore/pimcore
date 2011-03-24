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

            this.textarea = new Ext.form.TextArea({
                value: response.content
            })

            this.editor = new Ext.Panel({
                title: response.path,
                closable: true,
                layout: "fit",
                tbar: toolbarItems,
                items: [this.textarea]
            });

            this.explorer.editorPanel.add(this.editor);
            this.explorer.editorPanel.activate(this.editor);
            this.explorer.editorPanel.doLayout();

            this.codeMirror = CodeMirror.fromTextArea(this.textarea.getEl().dom, {
                parserfile: [
                    "parsexml.js",
                    "parsecss.js",
                    "tokenizejavascript.js",
                    "parsejavascript.js",
                    "parsehtmlmixed.js",
                    "../contrib/php/js/tokenizephp.js",
                    "../contrib/php/js/parsephp.js",
                    "../contrib/php/js/parsephphtmlmixed.js"
                ],
                stylesheet: [
                    "/pimcore/static/js/lib/codemirror/css/xmlcolors.css",
                    "/pimcore/static/js/lib/codemirror/css/jscolors.css",
                    "/pimcore/static/js/lib/codemirror/css/csscolors.css",
                    "/pimcore/static/js/lib/codemirror//contrib/php/css/phpcolors.css"
                ],
                path: "/pimcore/static/js/lib/codemirror/js/",
                height: (this.editor.getInnerHeight() - 30) + "px",
                width: (this.editor.getInnerWidth() - 40) + "px",
                lineNumbers: true,
                tabMode: "spaces"
            });

        }
    },

    saveFile: function (path) {
        this.codeMirror.save();

        var content = this.textarea.getValue();
        Ext.Ajax.request({
            method: "post",
            url: "/admin/misc/fileexplorer-content-save",
            params: {
                path: path,
                content: content
            }
        });
    }

});
