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

pimcore.registerNS("pimcore.settings.translation.website");
pimcore.settings.translation.website = Class.create(pimcore.settings.translations,{

    translationType: 'website',
    dataUrl: '/admin/translation/translations',
    exportUrl: '/admin/translation/export',
    importUrl:'/admin/translation/import/',
    mergeUrl:'/admin/translation/import/?merge=1',
    cleanupUrl: "/admin/translation/cleanup/type/website",


    activate: function (filter) {
        if(filter){
            this.store.getProxy().setExtraParam("filter", filter);
            this.store.load();
            this.filterField.setValue(filter);
        }
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.setActiveItem("pimcore_translations_website");
    },

    getHint: function(){
        return "";
    },

    getAvailableLanguages: function () {
        Ext.Ajax.request({
            url: "/admin/translation/get-website-translation-languages",
            success: function (response) {
                try {
                    var container = Ext.decode(response.responseText);
                    this.languages = container.view;
                    this.editableLanguages = container.edit;

                    this.getTabPanel();
                }
                catch (e) {
                    console.log(e);
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
                items: [
                    this.getRowEditor()
                ]
            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.setActiveItem("pimcore_translations_website");

            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("translationwebsitemanager");
            }.bind(this));

            pimcore.layout.refresh();
        }

        return this.panel;
    }



});
