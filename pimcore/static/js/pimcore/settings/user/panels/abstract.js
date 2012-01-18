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
        Ext.Ajax.request({
            url: "/admin/user/delete",
            params: {
                id: this.id
            }
        });

        this.remove();
    },

    add: function (type) {
        Ext.MessageBox.prompt(t('add'), t('please_enter_the_name'), function (button, value, object) {
            if(button=='ok' && value != ''){
                Ext.Ajax.request({
                    url: "/admin/user/add",
                    params: {
                        parentId: this.id,
                        type: type,
                        name: value,
                        active: 1
                    },
                    success: this.attributes.reference.addComplete.bind(this.attributes.reference, this.id)
                });
            }
        }.bind(this));
    },
});

