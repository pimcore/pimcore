/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
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