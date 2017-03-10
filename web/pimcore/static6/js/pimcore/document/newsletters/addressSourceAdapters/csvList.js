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

    /**
     * returns name of corresponding php implementation class
     *
     * @returns {string}
     */
    getName: function() {
        return "csvList";
    },

    /**
     * returns layout for sending panel
     *
     * @returns {Ext.form.Panel|*}
     */
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
                        fieldLabel: t("newsletter_csvList"),
                        width: 600,
                        height: 300
                    }
                ]
            });
        }

        return this.layout;
    },

    /**
     * returns values for sending process
     *
     * @returns {*|Object}
     */
    getValues: function () {

        if (!this.layout.rendered) {
            throw "settings not available";
        }

        return this.getLayout().getForm().getFieldValues();
    }

});