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

pimcore.registerNS("pimcore.report.custom.settings");
pimcore.report.custom.settings = Class.create({

    initialize: function (parent) {
        this.parent = parent;
    },

    getKey: function () {
        return "custom";
    },

    getLayout: function () {

        var editor = new pimcore.report.custom.panel();

        this.panel = new Ext.Panel({
            title: t("custom_reports"),
            bodyStyle: "padding: 10px;",
            autoScroll: true,
            layout: "fit",
            items: [editor.getTabPanel()]
        });

        return this.panel;
    },

    getValues: function () {
        /*var formData = this.panel.getForm().getFieldValues();
        return formData;*/

    }
});


pimcore.report.settings.broker.push("pimcore.report.custom.settings");
