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

pimcore.registerNS("pimcore.document.newsletters.addressSourceAdapters.csvList");
pimcore.document.newsletters.addressSourceAdapters.csvList = Class.create({

    initialize: function(document, data) {
        this.document = document;
    },

    getName: function() {
        return "csvList";
    },


    getLayout: function () {

        if (this.layout == null) {

            this.layout = Ext.create('Ext.form.Panel', {
                border: false,
                autoScroll: true,
                defaults: {labelWidth: 200},
                items: [
                    {
                        xtype: "textarea",
                        name: "csvList",
                        fieldLabel: t("newsletter_csvlist"),
                        width: 600,
                        height: 300
                    }
                ]
            });
        }

        return this.layout;
    },

    getValues: function () {

        if (!this.layout.rendered) {
            throw "settings not available";
        }

        // get values
        var settings = this.getLayout().getForm().getFieldValues();

        return settings;
    }

});