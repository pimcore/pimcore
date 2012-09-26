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


pimcore.registerNS("pimcore.settings.user.workspaces");
pimcore.settings.user.workspaces = Class.create({

    initialize: function (userPanel) {
        this.userPanel = userPanel;
        this.data = this.userPanel.data;
    },

    getPanel: function () {


        this.asset = new pimcore.settings.user.workspace.asset(this);
        this.document = new pimcore.settings.user.workspace.document(this);
        this.object = new pimcore.settings.user.workspace.object(this);

        this.panel = new Ext.Panel({
            title: t("workspaces"),
            bodyStyle: "padding:10px;",
            autoScroll: true,
            items: [this.document.getPanel(), this.asset.getPanel(), this.object.getPanel()]
        });

        return this.panel;
    },

    disable: function () {
        this.panel.disable();
    },

    enable: function () {
        this.panel.enable();
    },

    getValues: function () {
        return {
            asset: this.asset.getValues(),
            object: this.object.getValues(),
            document: this.document.getValues()
        };
    }

});