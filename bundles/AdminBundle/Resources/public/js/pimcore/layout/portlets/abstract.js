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

pimcore.registerNS("pimcore.layout.portlets.abstract");
pimcore.layout.portlets.abstract = Class.create({
    /**
     * Determines if the portlet is available for the current context. This
     * can be used to hide certain portlets depending on user permissions.
     *
     * @returns {boolean}
     */
    isAvailable: function() {
        return true;
    },

    getDefaultConfig: function () {

        var tools = [
            {
                type:'close',
                handler: this.remove.bind(this)
            }
        ];

        return {
            closable: false,
            tools: tools,
            widgetType: this.getType()
        };
    },

    remove: function (event, tool, header, owner) {
        var portlet = header.ownerCt;
        var column = portlet.ownerCt;
        column.remove(portlet, true);

        Ext.Ajax.request({
            url: Routing.generate('pimcore_admin_portal_removewidget'),
            method: 'DELETE',
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
    }

});
