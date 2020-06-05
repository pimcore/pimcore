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

pimcore.registerNS("pimcore.analytics.piwik.WidgetStoreProvider");

(function() {
    'use strict';

    var stores = {};

    pimcore.analytics.piwik.WidgetStoreProvider = {
        /**
         * @returns {Ext.data.Store}
         */
        getConfiguredSitesStore: function() {
            if ('undefined' !== typeof stores.configuredSites) {
                return stores.configuredSites;
            }

            stores.configuredSites = new Ext.data.Store({
                autoDestroy: false,
                autoLoad: true,
                proxy: {
                    type: 'ajax',
                    url: Routing.generate('pimcore_admin_reports_piwik_sites'),
                    reader: {
                        type: 'json',
                        rootProperty: 'data'
                    }
                }
            });

            return stores.configuredSites;
        },

        /**
         * @param {String} siteConfigKey
         * @returns {Ext.data.Store}
         */
        getPortalWidgetsStore: function(siteConfigKey) {
            if ('undefined' === typeof stores.portalWidgets) {
                stores.portalWidgets = {};
            }

            if ('undefined' !== typeof stores.portalWidgets[siteConfigKey]) {
                return stores.portalWidgets[siteConfigKey];
            }

            stores.portalWidgets[siteConfigKey] = new Ext.data.Store({
                autoDestroy: false,
                autoLoad: true,
                proxy: {
                    type: 'ajax',
                    url: Routing.generate('pimcore_admin_reports_piwik_portalwidgets', {configKey: siteConfigKey}),
                    reader: {
                        type: 'json',
                        rootProperty: 'data'
                    }
                }
            });

            return stores.portalWidgets[siteConfigKey];
        }
    };
}());
