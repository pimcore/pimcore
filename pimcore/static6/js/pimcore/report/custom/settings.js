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
