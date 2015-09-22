/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
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
        var layout = this.editor.getLayout();

        this.window = new Ext.Window({
            modal: true,
            width: 800,
            height: 600,
            //layout: "fit",
            resizable: true,
            //bodyStyle: "padding: 20px;",
            autoScroll: true,
            title: t("classificationstore_detailed_config"),
            items: [layout],
            bbar: [
            "->",{
                xtype: "button",
                text: t("cancel"),
                icon: "/pimcore/static6/img/icon/cancel.png",
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