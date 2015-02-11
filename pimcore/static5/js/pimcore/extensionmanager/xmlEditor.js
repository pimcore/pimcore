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
        response = Ext.decode(response.responseText);
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
                value: response.content,
                style: "font-family:courier"
            });

            this.editor = new Ext.Panel({
                title: response.path,
                closable: true,
                layout: "fit",
                tbar: toolbarItems,
                bodyStyle: "position:relative;",
                items: [this.textarea]
            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.editor);
            tabPanel.activate(this.editor);
            tabPanel.doLayout();
        }
    },

    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.activate("pimcore_extension_" + this.id + "_" + type);
    }
});