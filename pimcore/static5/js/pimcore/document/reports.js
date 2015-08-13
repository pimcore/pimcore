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

pimcore.registerNS("pimcore.document.reports");
pimcore.document.reports = Class.create({


    initialize: function(document) {
        this.document = document;
    },

    getLayout: function () {

        if (this.layout == null) {

            this.tree = new Ext.tree.TreePanel({
                xtype: "treepanel",
                region: "west",
                width: 200,
                enableDD: false,
                autoScroll: true,
                rootVisible: false,
                root: {
                    id: "0",
                    root: true,
                    reference: this,
                    listeners: this.getTreeNodeListeners()
                }
            });

            this.layout = new Ext.Panel({
                title: t('reports'),
                border: false,
                layout: "border",
                items: [this.tree, {
                    region: "center",
                    html: "das ist ein test"
                }],
                iconCls: "pimcore_icon_reports"
            });
        }

        return this.layout;
    },

    getTreeNodeListeners: function () {

    }
});