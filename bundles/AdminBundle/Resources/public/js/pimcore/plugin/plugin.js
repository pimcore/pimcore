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

pimcore.registerNS("pimcore.plugin.admin");
pimcore.plugin.admin = Class.create({

    initialize: function() {
    },
    getClassName: function () {
    },

    /* is called after plugin is uninstalled - can be used to do deactivate plugin UI features. */
    uninstall: function() {
    },


    /* events */

    preOpenObject: function (object, type) {
    },
    postOpenObject: function (object, type) {
        if (type === 'object') {
            var uiStates = localStorage.getItem('uiState_'+object.id);
            var layoutContainer = object.tab;
            if(uiStates) {
                uiStates = JSON.parse(uiStates);
                this.setUiState(layoutContainer, uiStates);

                // prevent restoration of UI state on subsequent loading of given object
                localStorage.removeItem('uiState_'+object.id);
            }

            var reloadButton = object.toolbar.down('button[iconCls=pimcore_icon_reload]');
            if(reloadButton !== null) {
                var that = this;
                reloadButton.on('click', function() {
                    var uiStates = that.getUiState(layoutContainer);
                    localStorage.setItem('uiState_'+object.id, JSON.stringify(uiStates));
                });
            }
        }
    },


    preOpenAsset: function (asset, type) {
    },
    postOpenAsset: function (asset, type) {
    },

    preOpenDocument: function (document, type) {
    },
    postOpenDocument: function (document, type) {
    },

    pimcoreReady: function (viewport) {
    },

    getUiState: function(extJsObject) {
        var visible = extJsObject.isVisible();
        if(extJsObject.hasOwnProperty('collapsed')) {
            visible = !extJsObject.collapsed;
        }
        var states = {visible: visible, children: []};

        if(extJsObject.hasOwnProperty('items')) {
            var that = this;
            extJsObject.items.each(function(item, index) {
                states.children[index] = that.getUiState(item);
            });
        }

        return states;
    },

    setUiState: function(extJsObject, savedState) {
        if(savedState.visible) {
            if(!extJsObject.hasOwnProperty('collapsed')) {
                extJsObject.setVisible(savedState.visible);
            } else {
                // without timeout the accordion panel's state gets confused and thus panels are not toggleable
                setTimeout(function() {extJsObject.expand(false);}, 50);
            }
        }

        if(extJsObject.hasOwnProperty('items')) {
            var that = this;
            extJsObject.items.each(function(item, index) {
                that.setUiState(item, savedState.children[index]);
            });
        }
    }
});