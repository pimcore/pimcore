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

pimcore.registerNS("pimcore.object.classificationstore.keyDefinitionWindow");
pimcore.object.classificationstore.keyDefinitionWindow = Class.create({

    initialize: function (data, keyid, parentPanel) {
        if (data) {
            this.data = data;
        } else {
            this.data = {};
        }

        this.parentPanel = parentPanel;
        this.keyid = keyid;
    },


    show: function() {

        var fieldtype = this.data.fieldtype;
        this.editor = new pimcore.object.classes.data[fieldtype](null, this.data);
        this.editor.setInClassificationStoreEditor(true);
        var layout = this.editor.getLayout();

        var invisibleFields = ["invisible","visibleGridView","visibleSearch","index"];
        var invisibleField;
        for(var f=0; f<invisibleFields.length; f++) {
            invisibleField = layout.getComponent("standardSettings").getComponent(invisibleFields[f]);
            if(invisibleField) {
                invisibleField.hide();
            }
        }

        this.window = new Ext.Window({
            modal: true,
            width: 800,
            height: 600,
            resizable: true,
            scrollable: "y",
            title: t("classificationstore_detailed_config"),
            items: [layout],
            bbar: [
            "->",{
                xtype: "button",
                text: t("cancel"),
                iconCls: "pimcore_icon_cancel",
                handler: function () {
                    this.window.close();
                }.bind(this)
            },{
                xtype: "button",
                text: t("apply"),
                iconCls: "pimcore_icon_apply",
                handler: function () {
                    this.applyData();
                }.bind(this)
            }],
            plain: true
        });

        this.window.show();
    },

    applyData: function() {

        this.editor.applyData();
        var definition = this.editor.getData();
        this.parentPanel.applyDetailedConfig(this.keyid, definition);
        this.window.close();
    }

});