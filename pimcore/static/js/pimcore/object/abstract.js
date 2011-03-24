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

pimcore.registerNS("pimcore.object.abstract");
pimcore.object.abstract = Class.create(pimcore.element.abstract, {


    addLoadingPanel : function () {

        // DEPRECIATED loadingpanel not active
        return;

        window.setTimeout(this.checkLoadingStatus.bind(this), 5000);

        this.tabPanel = Ext.getCmp("pimcore_panel_tabs");

        this.loadingPanel = new Ext.Panel({
            title: t("loading"),
            closable:false,
            html: "",
            iconCls: "pimcore_icon_loading"
        });

        this.tabPanel.add(this.loadingPanel);
    },


    removeLoadingPanel: function () {

        pimcore.helpers.removeTreeNodeLoadingIndicator("object", this.id);

        // DEPRECIATED loadingpanel not active
        return;

        if (this.loadingPanel) {
            this.tabPanel.remove(this.loadingPanel);
        }
        this.loadingPanel = null;
    },


    checkLoadingStatus: function () {

        // DEPRECIATED loadingpanel not active
        return;

        if (this.loadingPanel) {
            // loadingpanel is active close the whole document
            pimcore.helpers.closeObject(this.id);
        }
    }

});