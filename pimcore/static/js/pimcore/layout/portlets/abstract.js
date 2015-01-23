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

pimcore.registerNS("pimcore.layout.portlets.abstract");
pimcore.layout.portlets.abstract = Class.create({

    getDefaultConfig: function () {

        var tools = [
            {
                id:'close',
                handler: this.remove.bind(this)
            }
        ];

        return {
            tools: tools,
            widgetType: this.getType()
        };
    },

    remove: function (e, target, panel) {
        panel.ownerCt.remove(panel, true);

        Ext.Ajax.request({
            url: "/admin/portal/remove-widget",
            params: {
                key: this.portal.key,
                id: this.layout.portletId
            }
        });

        // remove from portal        
        for (var i = 0; i < this.portal.activePortlets.length; i++) {
            if (this.portal.activePortlets[i] == this.layout.portletId) {
                delete this.portal.activePortlets[i];
                break;
            }
        }

        delete this;
    },

    setPortal: function (portal) {
        this.portal = portal;
    },

    setConfig: function (config) {
        this.config = config;
    },

    /**
     * checks, if portlet is available for current user. default is to true.
     *
     * @param user
     * @returns {boolean}
     */
    isAllowed: function(user) {
        return true;
    }

});
