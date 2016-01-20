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

pimcore.helpers.grid = {};

pimcore.helpers.grid.buildDefaultStore = function(url, fields, itemsPerPage, customConfig) {

    var proxy = new Ext.data.proxy.Ajax({
        type: 'ajax',

        reader: {
            type: 'json',
            rootProperty: 'data'
        },
        writer: {
            type: 'json',
            writeAllFields: true,
            rootProperty: 'data',
            encode: 'true'
        },
        api: {
            create  : url + "xaction=create",
            read    : url + "xaction=read",
            update  : url + "xaction=update",
            destroy : url + "xaction=destroy"
        },
        actionMethods: {
            create : 'POST',
            read   : 'POST',
            update : 'POST',
            destroy: 'POST'
        }
    });
    //
    //Ext.override(proxy, {
    //
    //    encodeFilters: function(filters) {
    //        console.log("gaga");
    //        var out = [],
    //            length = filters.length,
    //            i, op;
    //        for (i = 0; i < length; i++) {
    //            out[i] = filters[i].serialize();
    //        }
    //        return this.applyEncoding(out);
    //    }.bind(proxy)
    //});
    //


    var config =  {
        proxy: proxy,
        autoLoad: true,
        autoSync: true,
        pageSize: itemsPerPage,
        fields: fields,
        remoteSort: true,
        remoteFilter: true
    };

    if(customConfig) {
        Ext.apply(config, customConfig);
    }

    var store = Ext.create('Ext.data.Store', config);

    return store;
};


pimcore.helpers.grid.buildDefaultPagingToolbar = function(store, itemsPerPage) {
    var pagingtoolbar = Ext.create('Ext.PagingToolbar', {
        pageSize: itemsPerPage,
        store: store,
        displayInfo: true,
        displayMsg: '{0} - {1} / {2}',
        emptyMsg: t("no_objects_found")
    });

    // add per-page selection
    pagingtoolbar.add("-");

    pagingtoolbar.add(Ext.create('Ext.Toolbar.TextItem', {
        text: t("items_per_page")
    }));
    pagingtoolbar.add(Ext.create('Ext.form.ComboBox', {
        store: [
            [10, "10"],
            [20, "20"],
            [40, "40"],
            [60, "60"],
            [80, "80"],
            [100, "100"]
        ],
        mode: "local",
        width: 80,
        value: itemsPerPage,
        triggerAction: "all",
        listeners: {
            select: function (box, rec) {
                var store = this.getStore();
                store.setPageSize(intval(rec.data.field1));
                this.moveFirst();
            }.bind(pagingtoolbar)
        }
    }));

    return pagingtoolbar;
};
