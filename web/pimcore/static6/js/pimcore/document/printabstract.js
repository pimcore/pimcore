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

pimcore.registerNS("pimcore.document.printabstract");
pimcore.document.printabstract = Class.create(pimcore.document.page_snippet, {

    urlprefix: "/admin/",
    type: "printabstract",

    initialize: function(id) {

        pimcore.plugin.broker.fireEvent("preOpenDocument", this, this.getType());

        this.addLoadingPanel();
        this.id = intval(id);
        this.getData();
    },


    addTab: function($super) {
        $super();
        if (this.isAllowed("publish")) {

            //necessary to hide save scheduled tasks
            this.toolbar.remove(this.toolbarButtons.publish);
            this.toolbarButtons.publish = new Ext.SplitButton({
                text: t('save_and_publish'),
                iconCls: "pimcore_icon_publish",
                scale: "medium",
                handler: this.publish.bind(this),
                menu: [
                    {
                        text: t('save_pubish_close'),
                        iconCls: "pimcore_icon_save",
                        handler: this.publishClose.bind(this)
                    },{
                        text: t('save_only_new_version'),
                        iconCls: "pimcore_icon_save",
                        handler: this.save.bind(this)
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
    }
});

