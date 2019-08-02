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

pimcore.registerNS("pimcore.document.newsletters.plaintextPanel");
pimcore.document.newsletters.plaintextPanel = Class.create({
    
    initialize: function(document) {
        this.document = document;
    },

    getLayout: function () {

        if (this.layout == null) {
            this.layout = new Ext.FormPanel({
                title: t('plain_text'),
                border: false,
                autoScroll: true,
                bodyStyle:'padding:0 10px 0 10px;',
                iconCls: "pimcore_material_icon_text pimcore_material_icon",
                items: [{
                    xtype:'fieldset',
                    title: t('plain_text_email_part'),
                    itemId: "plaintextPanel",
                    collapsible: false,
                    items: [{
                        xtype: 'textarea',
                        maxLength: 4000,
                        height: 700,
                        width: 1400,
                        name: 'plaintext',
                        value: this.document.data.plaintext,
                        enableKeyEvents: true,
                        listeners: {
                            "keyup": function (el) {
                                this.layout.getComponent('plaintextPanel').titleCmp.update(t("plain_text_email_part") + " (" + el.getValue().length + "):");
                            }.bind(this)
                        }
                    }]
                }]
            });
        }

        return this.layout;
    },
    
    getValues: function () {

        if (!this.layout.rendered) {
            throw "plaintext not available";
        }

        // get values
        var plaintext = this.getLayout().getForm().getFieldValues();
        return plaintext;
    }
});
