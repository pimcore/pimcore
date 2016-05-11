/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

pimcore.helpers.grid = {};

pimcore.helpers.grid.buildDefaultStore = function(url, fields, itemsPerPage, customConfig) {

    if(url.indexOf('?') === -1) {
        url = url + "?";
    } else {
        url = url + "&";
    }

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
        }/*,
        listeners: {
            exception: function(proxy, response, operation){
                Ext.MessageBox.show({
                    title: 'REMOTE EXCEPTION',
                    msg: operation.getError(),
                    icon: Ext.MessageBox.ERROR,
                    buttons: Ext.Msg.OK
                });
            }
        }*/
    });

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


pimcore.helpers.grid.getDefaultPageSize = function(scale) {
    if (scale < 0) {
        return 25;
    }
    return 50;
};

pimcore.helpers.grid.buildDefaultPagingToolbar = function(store, options) {
    var config = {
        pageSize: pimcore.helpers.grid.getDefaultPageSize(),
        store: store,
        displayInfo: true,
        displayMsg: '{0} - {1} / {2}',
        emptyMsg: t("no_items_found")
    };
    if (typeof options !== "undefined") {
        config = Ext.applyIf(options, config);
    }
    var pagingtoolbar = Ext.create('Ext.PagingToolbar', config);

    if (!config.hideSelection) {
        // add per-page selection
        pagingtoolbar.add("-");

        pagingtoolbar.add(Ext.create('Ext.Toolbar.TextItem', {
            text: t("items_per_page")
        }));
        pagingtoolbar.add(Ext.create('Ext.form.ComboBox', {
            store: [
                [25, "25"],
                [50, "50"],
                [100, "100"],
                [200, "200"],
                [999999, t("all")]
            ],
            mode: "local",
            width: 80,
            value: config.pageSize,
            triggerAction: "all",
            editable: true,
            listeners: {
                change: function (box, newValue, oldValue) {
                    var store = this.getStore();
                    newValue = intval(newValue);
                    if (!newValue) {
                        newValue = options.pageSize;
                    }
                    store.setPageSize(newValue);
                    this.moveFirst();
                }.bind(pagingtoolbar)
            }
        }));
    }

    return pagingtoolbar;
};
