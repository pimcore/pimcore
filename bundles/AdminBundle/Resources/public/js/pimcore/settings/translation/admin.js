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

pimcore.registerNS("pimcore.settings.translation.admin");
pimcore.settings.translation.admin = Class.create(pimcore.settings.translations,{

    translationType: 'admin',

    initialize: function ($super, filter) {
        $super(filter);

        this.dataUrl = Routing.generate('pimcore_admin_translation_translations', {admin: 1});
        this.exportUrl = Routing.generate('pimcore_admin_translation_export', {admin: 1});
        this.uploadImportUrl = Routing.generate('pimcore_admin_translation_uploadimportfile', {admin: 1});
        this.importUrl = Routing.generate('pimcore_admin_translation_import', {admin: 1});
        this.mergeUrl = Routing.generate('pimcore_admin_translation_import', {admin: 1, merge: 1});
        this.cleanupUrl = Routing.generate('pimcore_admin_translation_cleanup', {admin: 1});
    },

    activate: function (filter) {
        if(filter){
            this.store.getProxy().setExtraParam("searchString", filter);
            this.store.load();
            this.filterField.setValue(filter);
        }
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.setActiveItem("pimcore_translations_admin");
    },

    getHint: function(){
        return t('translations_admin_hint');
    },

    getAvailableLanguages: function () {
        Ext.Ajax.request({
            url: Routing.generate('pimcore_admin_settings_getavailableadminlanguages'),
            success: function (response) {
                try {
                    var languages = Ext.decode(response.responseText);
                    this.languages = [];
                    for(var i=0; i<languages.length; i++){
                        this.languages.push(languages[i]["language"]);
                    }
                    this.editableLanguages = this.languages;

                    this.getTabPanel();
                }
                catch (e) {
                    Ext.MessageBox.alert(t('error'), t('translations_are_not_configured')
                    + '<br /><br /><a href="http://www.pimcore.org/docs/" target="_blank">'
                    + t("read_more_here") + '</a>');
                }
            }.bind(this)
        });
    },


    getTabPanel: function () {

        if (!this.panel) {
            this.panel = new Ext.Panel({
                id: "pimcore_translations_admin",
                iconCls: "pimcore_icon_translations",
                title: t("admin_translations"),
                border: false,
                layout: "fit",
                closable:true,
                items: [this.getRowEditor()]
            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.setActiveItem("pimcore_translations_admin");

            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("translationadminmanager");
            }.bind(this));

            pimcore.layout.refresh();
        }

        return this.panel;
    }
});
