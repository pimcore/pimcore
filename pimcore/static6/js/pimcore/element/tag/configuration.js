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

pimcore.registerNS("pimcore.element.tag.configuration");
pimcore.element.tag.configuration = Class.create({

    initialize: function() {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.add(this.getLayout());
        tabPanel.setActiveItem("tag_configuration");

        this.getLayout().on("destroy", function () {
            pimcore.globalmanager.remove("element_tag_configuration");
        });

        pimcore.layout.refresh();
    },

    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.setActiveItem("tag_configuration");
    },

    getLayout: function () {

        if (this.layout == null) {

            var tree = new pimcore.element.tag.tree();

            this.layout = new Ext.Panel({
                id: "tag_configuration",
                title: t('element_tag_configuration'),
                iconCls: "pimcore_icon_element_tags",
                items: [tree.getLayout()],
                layout: "border",
                closable: true
            });
        }

        return this.layout;
    }
});
