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
