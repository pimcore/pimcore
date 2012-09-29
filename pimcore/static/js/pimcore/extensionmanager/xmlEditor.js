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

pimcore.registerNS("pimcore.extensionmanager.xmlEditor");
pimcore.extensionmanager.xmlEditor = Class.create(pimcore.settings.fileexplorer.file, {
    id: null,
    type: null,

    initialize: function (id, type, xmlFile) {
        this.id = id;
        this.type = type;
        this.loadFileContents(xmlFile);
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

            this.panelId = "pimcore_extension_" + this.id + "_" + this.type;
            this.editorId = this.panelId + "_editor";
            this.editorMode = "xml";

            this.editor = new Ext.Panel({
                title: t('settings') + ' - ' + this.id,
                id : this.panelId,
                closable: true,
                layout: "fit",
                tbar: toolbarItems,
                bodyStyle: "position:relative;",
                html: '<pre id="' + this.editorId + '"></pre>'
            });


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

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.editor);
            tabPanel.activate(this.editor);
            tabPanel.doLayout();
        }
    },

    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.activate("pimcore_extension_" + id + "_" + type);
    }
});