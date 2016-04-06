/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in 
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
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
