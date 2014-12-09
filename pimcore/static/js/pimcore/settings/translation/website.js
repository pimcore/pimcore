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

pimcore.registerNS("pimcore.settings.translation.website");
pimcore.settings.translation.website = Class.create(pimcore.settings.translations,{

    translationType: 'website',
    dataUrl: '/admin/translation/translations',
    exportUrl: '/admin/translation/export',
    importUrl:'/admin/translation/import/?pimcore_admin_sid=' + pimcore.settings.sessionId,
    mergeUrl:'/admin/translation/import/?merge=1&pimcore_admin_sid=' + pimcore.settings.sessionId,
    cleanupUrl: "/admin/translation/cleanup/type/website",


    activate: function (filter) {
        if(filter){
            this.store.baseParams.filter = filter;
            this.store.load();
            this.filterField.setValue(filter);
        }
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.activate("pimcore_translations_website");
    },

    getHint: function(){
        return "";
    },

    getAvailableLanguages: function () {
        Ext.Ajax.request({
            url: "/admin/settings/get-available-languages",
            success: function (response) {
                try {
                    this.languages = Ext.decode(response.responseText);
                    this.getTabPanel();
                }
                catch (e) {
                    Ext.MessageBox.alert(t('error'), t('translations_are_not_configured')
                    + '<br /><br /><a href="http://www.pimcore.org/documentation/" target="_blank">'
                    + t("read_more_here") + '</a>');
                }
            }.bind(this)
        });
    },


    getTabPanel: function () {

        if (!this.panel) {
            this.panel = new Ext.Panel({
                id: "pimcore_translations_website",
                iconCls: "pimcore_icon_translations",
                title: t("translations"),
                border: false,
                layout: "fit",
                closable:true,
                items: [this.getRowEditor()]
            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.activate("pimcore_translations_website");

            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("translationwebsitemanager");
            }.bind(this));

            pimcore.layout.refresh();
        }

        return this.panel;
    }



});
