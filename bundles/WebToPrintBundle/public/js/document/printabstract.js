/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

pimcore.registerNS("pimcore.document.printabstract");
/**
 * @private
 */
pimcore.document.printabstract = Class.create(pimcore.document.page_snippet, {
    type: "printabstract",

    initialize: function(id, options) {
        this.id = intval(id);
        this.options = options;
        this.addLoadingPanel();

        const preOpenDocument = new CustomEvent(pimcore.events.preOpenDocument, {
            detail: {
                document: this,
                type: this.getType()
            },
            cancelable: true
        });

        const isAllowed = document.dispatchEvent(preOpenDocument);
        if (!isAllowed) {
            this.removeLoadingPanel();
            return;
        }

        this.getData();
    },


    addTab: function($super) {
        $super();
        if (this.isAllowed("publish")) {

            //necessary to hide save scheduled tasks
            this.toolbar.remove(this.toolbarButtons.publish);
            this.toolbarButtons.publish = new Ext.SplitButton({
                text: t('save_and_publish'),
                iconCls: "pimcore_icon_save_white",
                cls: "pimcore_save_button",
                scale: "medium",
                handler: this.publish.bind(this),
                menu: [
                    {
                        text: t('save_pubish_close'),
                        iconCls: "pimcore_icon_save",
                        handler: this.publishClose.bind(this)
                    },{
                        text: t('save_draft'),
                        iconCls: "pimcore_icon_save",
                        handler: this.save.bind(this, 'version')
                    }
                ]
            });
            this.toolbar.insert(1, this.toolbarButtons.publish);
        }
    },

    getSaveData : function (only) {

        var parameters = {};
        parameters.id = this.id;


        // save all data allowed
        if (this.isAllowed("properties")) {
            // properties
            try {
                parameters.properties = Ext.encode(this.properties.getValues());
            }
            catch (e) {
                //console.log(e);
            }
        }

        var settings = null;
        if (this.isAllowed("settings")) {
            // settings
            try {
                settings = Ext.encode(this.settings.getValues());
                settings = this.settings.getValues();
                settings.published = this.data.published;
            }
            catch (e) {
                //console.log(e);
            }

        }

        // data
        try {
            parameters.data = Ext.encode(this.edit.getValues());
        }
        catch (e) {
            //console.log(e);
        }
        // styles
        try {
            var styles = this.preview.getValues();
            if(!settings) {
                settings = {};
            }
            settings.css = styles.css;
        }
        catch (e) {
            //console.log(e);
        }

        if(settings) {
            parameters.settings = Ext.encode(settings);
        }


        return parameters;
    },


    unpublish: function ($super) {
        $super();
        if(this.pdfpreview) {
            this.pdfpreview.enableGenerateButton(false);
        }
    },

    publish: function($super) {
        $super();
        if(this.pdfpreview) {
            this.pdfpreview.enableGenerateButton(true);
        }
    },

    getData: function () {
        var options = this.options || {};
        Ext.Ajax.request({
            url: Routing.generate(this.getDataRoute()),
            params: {id: this.id},
            ignoreErrors: options.ignoreNotFoundError,
            success: this.getDataComplete.bind(this),
            failure: function () {
                pimcore.helpers.forgetOpenTab("document_" + this.id + "_" + this.type);
                pimcore.helpers.closeDocument(this.id);
            }.bind(this)
        });
    },

    getDataRoute: function() {
        return "pimcore_bundle_web2print_document_" + this.type + '_getdatabyid';
    },

    getSaveRoute: function() {
        return "pimcore_bundle_web2print_document_" + this.type + '_save';
    },

    getAddRoute: function() {
        return "pimcore_bundle_web2print_document_" + this.type + '_add';
    },

    getSaveToSessionRoute: function() {
        return "pimcore_bundle_web2print_document_" + this.type + '_savetosession';
    },

    getRemoveFromSessionRoute: function() {
        return "pimcore_bundle_web2print_document_" + this.type + '_removefromsession';
    },

});

