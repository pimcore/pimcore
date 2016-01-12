/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

// some global helper functions
pimcore.registerNS("pimcore.helpers.quantityValue.x");

pimcore.helpers.quantityValue.storeLoaded = false;
pimcore.helpers.quantityValue.store = null;

pimcore.helpers.quantityValue.initUnitStore = function(callback, filters) {
    if (!pimcore.helpers.quantityValue.storeLoaded) {
        var newListener = function () {
            pimcore.helpers.quantityValue.storeLoaded = true;
            pimcore.helpers.quantityValue.storeLoading = false;
            pimcore.helpers.quantityValue.getData(callback, filters);
        }.bind(this);

        if (!pimcore.helpers.quantityValue.store) {
            pimcore.helpers.quantityValue.store = new Ext.data.JsonStore({
                autoLoad: true,
                proxy: {
                    type: 'ajax',
                    url: '/admin/quantity-value/unit-list',
                    reader: {
                        type: 'json',
                        rootProperty: 'data'
                    },
                    writer: {
                        type: 'json'
                    }
                },
                fields: ['id', 'abbreviation'],
                listeners: {
                    load: newListener
                }
            });
        } else {
            pimcore.helpers.quantityValue.store.addListener("load", newListener);
        }

    } else {
        pimcore.helpers.quantityValue.getData(callback, filters);
    }

}

pimcore.helpers.quantityValue.getData = function(callback, filterArray) {
    if(callback) {
        pimcore.helpers.quantityValue.store.clearFilter();
        //var filterArray = filters.split(',');

        var data = [];
        if (filterArray) {
            for (var i = 0; i < filterArray.length; i++) {
                var rec = pimcore.helpers.quantityValue.store.getById(filterArray[i]);
                if (rec) {
                    data.push(rec.data);
                }
            }
        }
        callback({data: data});
    }
}

pimcore.helpers.quantityValue.classDefinitionStore = null;
pimcore.helpers.quantityValue.getClassDefinitionStore = function() {
    if(!pimcore.helpers.quantityValue.classDefinitionStore) {
        pimcore.helpers.quantityValue.classDefinitionStore = new Ext.data.JsonStore({
            //autoDestroy: true,
            autoLoad: true,
            proxy: {
                type: 'ajax',
                url: '/admin/quantity-value/unit-list',
                reader: {
                    type: 'json',
                    rootProperty: 'data'
                }
            },
            fields: ['id', 'abbreviation']
        });
    }
    return pimcore.helpers.quantityValue.classDefinitionStore;
}
