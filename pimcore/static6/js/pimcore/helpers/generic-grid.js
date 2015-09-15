
pimcore.helpers.grid = {};

pimcore.helpers.grid.buildDefaultStore = function(url, fields, itemsPerPage) {

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



    var store = Ext.create('Ext.data.Store', {
        proxy: proxy,
        autoLoad: true,
        autoSync: true,
        pageSize: itemsPerPage,
        fields: fields,
        remoteSort: true,
        remoteFilter: true
    });

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
