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

pimcore.registerNS("pimcore.object.permissions");
pimcore.object.permissions = Class.create({

    initialize: function(object) {
        this.object = object;
    },

    load: function () {


    },

    getLayout: function () {

        if (this.layout == null) {

            this.grid = this.getGrid();

            this.layout = new Ext.Panel({
                title: t('permissions'),
                border: false,
                layout: "fit",
                iconCls: "pimcore_icon_tab_permissions",
                items: [this.grid],
                listeners: {
                    activate: function() {
                        this.store.load();
                        this.grid.getView().refresh();
                    }.bind(this)
                }
            });
        }

        return this.layout;
    },

    getGrid: function () {

        this.store = new Ext.data.JsonStore({
            autoDestroy: true,
            root: 'permissions',
            url: "/admin/object/get-user-permissions",
            autoLoad: false,
            idProperty: "username",
            baseParams:{
                object: this.object.id
            },
            fields: ['username', 'list', 'view', 'save', 'publish', 'unpublish', 'delete', 'rename', 'create', "permissions", "versions", "properties", "settings"]
        });


        var typesColumns = [
            {header: t("username"), sortable: true, dataIndex: 'username'},
            {header: t("list"), width: 20, sortable: false, dataIndex: 'list', renderer:this.renderPermission},
            {header: t("view"), width: 20, sortable: false, dataIndex: 'view', renderer:this.renderPermission},
            {header: t("save"), width: 20, sortable: false, dataIndex: 'save', renderer:this.renderPermission},
            {header: t("publish"), width: 20, sortable: false, dataIndex: 'publish', renderer:this.renderPermission},
            {header: t("unpublish"), width: 20, sortable: false, dataIndex: 'unpublish', renderer:this.renderPermission},
            {header: t("delete"), width: 20, sortable: false, dataIndex: 'delete', renderer:this.renderPermission},
            {header: t("rename"), width: 20, sortable: false, dataIndex: 'rename', renderer:this.renderPermission},
            {header: t("create_childs"), width: 20, sortable: false, dataIndex: 'create', renderer:this.renderPermission},
            {header: t("settings"), width: 20, sortable: false, dataIndex: 'settings', renderer:this.renderPermission},
            {header: t("properties"), width: 20, sortable: false, dataIndex: 'properties', renderer:this.renderPermission},
            {header: t("permissions"), width: 20, sortable: false, dataIndex: 'permissions', renderer:this.renderPermission},
            {header: t("versions"), width: 20, sortable: false, dataIndex: 'versions', renderer:this.renderPermission}
        ];

        this.grid = new Ext.grid.GridPanel({
            frame: false,
            autoScroll: true,
            store: this.store,
            columns : typesColumns,
            tbar: [
                {
                    text: t("edit_user_permissions"),
                    iconCls: "pimcore_icon_menu_users",
                    handler: this.editUsers
                }
            ],
            viewConfig: {
                forceFit: true
            }
        });

        return this.grid;

    },

    renderPermission: function(v) {
        if (v) {
            return'<div class="permission_checked">&nbsp;</div>';
        } else {
            return '<div class="permission_not_available permission_small">-</div>';
        }
    },

    editUsers: function () {

        try {
            pimcore.globalmanager.get("users").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("users", new pimcore.settings.user.panel());
        }
    }



});