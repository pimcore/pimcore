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

pimcore.registerNS("pimcore.report.sql.settings");
pimcore.report.sql.settings = Class.create({

    initialize: function (parent) {
        this.parent = parent;
    },

    getKey: function () {
        return "sql";
    },

    getLayout: function () {

        var editor = new pimcore.report.sql.panel();

        this.panel = new Ext.Panel({
            title: t("sql_reports"),
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


pimcore.report.settings.broker.push("pimcore.report.sql.settings");
