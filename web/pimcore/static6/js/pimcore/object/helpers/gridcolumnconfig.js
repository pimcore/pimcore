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


/**
 * NOTE: This helper-methods are added to the classes pimcore.object.edit, pimcore.object.fieldcollection,
 * pimcore.object.tags.localizedfields
 */

pimcore.registerNS("pimcore.object.helpers.gridcolumnconfig");
pimcore.object.helpers.gridcolumnconfig = {

    getSaveAsDialog: function () {
        var defaultName = new Date();

        var nameField = new Ext.form.TextField({
            fieldLabel: t('name'),
            length: 50,
            allowBlank: false,
            value: this.settings.gridConfigName ? this.settings.gridConfigName : defaultName
        });

        var descriptionField = new Ext.form.TextArea({
            fieldLabel: t('description'),
            height: 400,
            value: this.settings.gridConfigDescription
        });

        var configPanel = new Ext.Panel({
            layout: "form",
            bodyStyle: "padding: 10px;",
            items: [nameField, descriptionField],
            buttons: [{
                text: t("save"),
                iconCls: "pimcore_icon_apply",
                handler: function () {
                    this.settings.gridConfigId = null;
                    this.settings.gridConfigName = nameField.getValue();
                    this.settings.gridConfigDescription = descriptionField.getValue();

                    pimcore.helpers.saveColumnConfig(this.object.id, this.classId, this.getGridConfig(), this.searchType, this.saveColumnConfigButton,
                        this.columnConfigurationSavedHandler.bind(this), this.settings);
                    this.saveWindow.close();
                }.bind(this)
            }]
        });

        this.saveWindow = new Ext.Window({
            width: 600,
            height: 300,
            modal: true,
            title: t('save_as'),
            layout: "fit",
            items: [configPanel]
        });

        this.saveWindow.show();
        nameField.focus();
        nameField.selectText();
        return this.window;
    },

    deleteGridConfig: function () {

        Ext.MessageBox.show({
            title: t('delete'),
            msg: t('delete_gridconfig_dblcheck'),
            buttons: Ext.Msg.OKCANCEL,
            icon: Ext.MessageBox.INFO,
            fn: this.deleteGridConfigConfirmed.bind(this)
        });
    },

    deleteGridConfigConfirmed: function (btn) {
        if (btn == 'ok') {
            Ext.Ajax.request({
                url: "/admin/object-helper/grid-delete-column-config",
                params: {
                    id: this.classId,
                    objectId:
                    this.object.id,
                    gridtype: "grid",
                    gridConfigId: this.settings.gridConfigId,
                    searchType: this.searchType
                },
                success: function (response) {

                    decodedResponse = Ext.decode(response.responseText);
                    if (decodedResponse.deleteSuccess) {
                        pimcore.helpers.showNotification(t("success"), t("gridconfig_removed"), "success");
                    } else {
                        pimcore.helpers.showNotification(t("error"), t("gridconfig_not_removed"), "error");
                    }
                    this.createGrid(false, response);
                }.bind(this)
            });
        }
    },

    switchToGridConfig: function (menuItem) {
        var gridConfig = menuItem.gridConfig;
        this.settings.gridConfigId = gridConfig.id;
        this.getTableDescription();
    },

    columnConfigurationSavedHandler: function (rdata) {
        this.settings = rdata.settings;
        this.availableConfigs = rdata.availableConfigs;
        this.buildColumnConfigMenu();
    },

    addGridConfigMenuItems: function(menu, list) {
        for (var i = 0; i < list.length; i++) {
            var disabled = false;
            var config = list[i];
            var text = config["name"];
            if (config.id == this.settings.gridConfigId) {
                text = this.settings.gridConfigName,
                    text = "<b>" + text + "</b>";
                disabled = true;
            }
            var menuConfig = {
                text: text,
                disabled: disabled,
                iconCls: 'pimcore_icon_gridcolumnconfig',
                gridConfig: config,
                handler: this.switchToGridConfig.bind(this)
            }
            menu.add(menuConfig);
        }
    },

    buildColumnConfigMenu: function () {
        var menu = this.columnConfigButton.getMenu();
        menu.removeAll();

        menu.add({
            text: t('save_as'),
            iconCls: "pimcore_icon_save",
            handler: this.saveConfig.bind(this, true)
        });

        menu.add({
            text: t('set_as_favourite'),
            iconCls: "pimcore_icon_favourite",
            handler: function () {
                pimcore.helpers.markColumnConfigAsFavourite(this.object.id, this.classId, this.settings.gridConfigId, this.searchType, true);
            }.bind(this)
        });

        menu.add({
            text: t('remove_config'),
            iconCls: "pimcore_icon_delete",
            disabled: !this.settings.gridConfigId,
            handler: this.deleteGridConfig.bind(this)
        });

        menu.add('-');

        var disabled = false;
        var text = t('predefined');
        if (!this.settings.gridConfigId) {
            text = "<b>" + text + "</b>";
            disabled = true;

        }

        menu.add({
            text: text,
            iconCls: "pimcore_icon_gridcolumnconfig",
            disabled: disabled,
            gridConfig: {
                id: 0
            },
            handler: this.switchToGridConfig.bind(this)
        });

        if (this.availableConfigs && this.availableConfigs.length > 0) {
            this.addGridConfigMenuItems(menu, this.availableConfigs);
        }

        if (this.sharedConfigs && this.sharedConfigs.length > 0) {
            menu.add('-');
            this.addGridConfigMenuItems(menu, this.sharedConfigs);
        }
    },

    saveConfig: function (asCopy) {
        if (asCopy) {
            this.getSaveAsDialog();
        } else {
            pimcore.helpers.saveColumnConfig(this.object.id, this.classId, this.getGridConfig(), this.searchType, this.saveColumnConfigButton,
                this.columnConfigurationSavedHandler.bind(this), this.settings);
        }
    },


    columnConfigurationSavedHandler: function (rdata) {
        this.settings = rdata.settings;
        this.availableConfigs = rdata.availableConfigs;
        this.buildColumnConfigMenu();
    }


};
