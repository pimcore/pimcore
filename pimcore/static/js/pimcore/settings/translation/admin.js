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

pimcore.registerNS("pimcore.settings.translation.admin");
pimcore.settings.translation.admin = Class.create(pimcore.settings.translations,{

    dataUrl: '/admin/settings/translations?admin=1',
    exportUrl: '/admin/settings/translations-export/?admin=1',
    importUrl:'/admin/settings/translations-import/?admin=1&pimcore_admin_sid=' + pimcore.settings.sessionId,
    cleanupUrl: "/admin/settings/translations-cleanup/type/admin",

    activate: function (filter) {
        if(filter){
            this.store.baseParams.filter = filter;
            this.store.load();
            this.filterField.setValue(filter);
        }
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.activate("pimcore_translations_admin");
    },

    getHint: function(){
        return t('translations_admin_hint');
    },

    getAvailableLanguages: function () {
        Ext.Ajax.request({
            url: "/admin/settings/get-available-admin-languages",
            success: function (response) {
                try {
                    var languages = Ext.decode(response.responseText);
                    this.languages = [];
                    for(i=0;i<languages.length;i++){
                        this.languages.push(languages[i]["language"]);    
                    }
                    this.getTabPanel();
                }
                catch (e) {
                    Ext.MessageBox.alert(t('error'), t('translations_are_not_configured') + '<br /><br /><a href="http://www.pimcore.org/documentation/" target="_blank">' + t("read_more_here") + '</a>');
                }
            }.bind(this)
        });
    },


    getTabPanel: function () {

        if (!this.panel) {
            this.panel = new Ext.Panel({
                id: "pimcore_translations_admin",
                iconCls: "pimcore_icon_translations",
                title: t("translations_admin"),
                border: false,
                layout: "fit",
                closable:true,
                items: [this.getRowEditor()]
            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.activate("pimcore_translations_admin");

            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("translationadminmanager");
            }.bind(this));

            pimcore.layout.refresh();
        }

        return this.panel;
    }



});

