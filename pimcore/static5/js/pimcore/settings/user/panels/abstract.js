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


pimcore.registerNS("pimcore.settings.user.panels.abstract");
pimcore.settings.user.panels.abstract = Class.create({

    initialize: function () {
        this.panels = {};
        this.getTabPanel();
    },

    getEditPanel: function () {
        if (!this.editPanel) {
            this.editPanel = new Ext.TabPanel({
                activeTab: 0,
                items: [],
                region: 'center'
            });
        }

        return this.editPanel;
    },

    getTreeNodeListeners: function () {
        var treeNodeListeners = {
            'itemclick' : this.onTreeNodeClick.bind(this),
            'itemcontextmenu': this.onTreeNodeContextmenu.bind(this)
        };

        return treeNodeListeners;
    },


    remove: function (tree, record) {

        Ext.MessageBox.show({
            title:t('delete'),
            msg: record.hasChildNodes() ? t("are_you_sure_recursive") : t("are_you_sure"),
            buttons: Ext.Msg.OKCANCEL ,
            icon: record.hasChildNodes() ? Ext.MessageBox.WARNING : Ext.MessageBox.QUESTION,
            fn: function (button) {
                if (button == "ok") {
                    Ext.Ajax.request({
                        url: "/admin/user/delete",
                        params: {
                            id: record.data.id
                        },
                        success: function() {
                            record.remove();
                        }.bind(this, tree, record)
                    });
                }
            }.bind(this)
        });
    },


    add: function (type, cloneRecord, selectedRecord) {
        if (cloneRecord) {
            rid = cloneRecord.data.id;
            parentNode = cloneRecord.parentNode;
        } else {
            rid = 0;
            parentNode = selectedRecord;
        }
        var pid = parentNode.data.id;
        Ext.MessageBox.prompt(t('add'), t('please_enter_the_name'), function (button, value, object) {
            if(button=='ok' && value != ''){
                Ext.Ajax.request({
                    url: "/admin/user/add",
                    params: {
                        parentId: pid,
                        type: type,
                        name: value,
                        active: 1,
                        rid: rid
                    },
                    success: this.addComplete.bind(this, parentNode)
                });
            }
        }.bind(this));
    }
});

