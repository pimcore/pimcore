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
            'click' : this.onTreeNodeClick.bind(this),
            "contextmenu": this.onTreeNodeContextmenu,
            "move": this.onTreeNodeMove
        };

        return treeNodeListeners;
    },

    onTreeNodeMove: function (tree, element, oldParent, newParent, index) {
        this.attributes.reference.update(this.id, {
            parentId: newParent.id
        });
    },

    remove: function () {

        Ext.MessageBox.show({
            title:t('delete'),
            msg: this.hasChildNodes() ? t("are_you_sure_recursive") : t("are_you_sure"),
            buttons: Ext.Msg.OKCANCEL ,
            icon: this.hasChildNodes() ? Ext.MessageBox.WARNING : Ext.MessageBox.QUESTION,
            fn: function (button) {
                if (button == "ok") {
                    Ext.Ajax.request({
                        url: "/admin/user/delete",
                        params: {
                            id: this.id
                        },
                        success: function() {
                            this.remove();
                        }.bind(this)
                    });
                }
            }.bind(this)
        });
    },


    add: function (type, rid) {
        var pid = this.id;
        if (rid) {
            pid = this.parentNode.id;
        }
        Ext.MessageBox.prompt(t('add'), t('please_enter_the_name'), function (button, value, object) {
            if(button=='ok' && value != ''){
                Ext.Ajax.request({
                    url: "/admin/user/add",
                    params: {
                        parentId: this.id,
                        type: type,
                        name: value,
                        active: 1,
                        rid: rid
                    },
                    success: this.attributes.reference.addComplete.bind(this.attributes.reference, pid)
                });
            }
        }.bind(this));
    }
});

