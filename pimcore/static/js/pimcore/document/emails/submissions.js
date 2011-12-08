

pimcore.registerNS("pimcore.plugin.formbuilder.tabs.submissions");
pimcore.plugin.formbuilder.tabs.submissions = Class.create({

    initialize: function (form) {

        this.form = form;
    },

    getLayout: function () {


        this.panel = new Ext.Panel({
            title: "Submissions",
            layout: "fit",
            items: [this.getGrid()]
        });

        return this.panel;
    },

    getGrid: function () {

        var itemsPerPage = 120;

        var plugins = [];
        var gridColumns = [{
            header: "ID",
            dataIndex: "id",
            width: 40,
            hidden: true
        },{
            header: "Date",
            dataIndex: "date",
            width: 130,
            hidden: false,
            renderer: function (d) {
                var date = new Date(intval(d) * 1000);
                return date.format("Y-m-d H:i:s");
            }
        }];

        var storeFields = ["id","formId","date"];
 
        var columnKeys = Object.keys(this.form.config.fields);
		var columnConfig = null;
        if(this.form.config.fields){
		for (var i=0; i<columnKeys.length; i++) {
            
			columnConfig = {
				header: this.form.config.fields[columnKeys[i]],
				dataIndex: columnKeys[i]
			};
       //     console.log(columnConfig);
			if(this.form.config.fieldtypes[columnKeys[i]] == "renderlet") {

				columnConfig.renderer = function (value, metaData, record, rowIndex, colIndex, store) {
					
					var links = [];
					if(value != null && value.length > 0) {
						for(var i=0; i<value.length; i++) {
							links.push('<span onclick="pimcore.helpers.openObject(' + value[i].id + ', \'object\');" style="cursor:pointer;">' + value[i].path + '</span>');
						}
					}
					
					return links.join("<br />");
				}
			}

			gridColumns.push(columnConfig);
            storeFields.push(columnKeys[i]);
        }
        }

        var store = new Ext.data.JsonStore({
            restful: false,
            idProperty: 'id',
            remoteSort: true,
            root: "data",
            url: "/plugin/Formbuilder/index/grid",
            baseParams: {
                limit: itemsPerPage,
                formId: this.form.document.id
            },
            fields: storeFields
        });
        store.load();


        this.pagingtoolbar = new Ext.PagingToolbar({
            pageSize: itemsPerPage,
            store: store,
            displayInfo: true,
            displayMsg: '{0} - {1} / {2}',
            emptyMsg: t("no_objects_found")
        });


        this.grid = new Ext.grid.GridPanel({
            frame: false,
            store: store,
            columns : gridColumns,
            columnLines: true,
            stripeRows: true,
            plugins: plugins,
            border: true,
            trackMouseOver: true,
            loadMask: true,
            viewConfig: {
                forceFit: false
            },
            tbar: ["->", {
                xtype: "button",
                text: "Download as CSV",
                iconCls: "pimcore_icon_export",
                handler: this.downloadCsv.bind(this)
            }],
            bbar: [this.pagingtoolbar]
        });
        this.grid.on("rowcontextmenu", this.onRowContextmenu);

        return this.grid;
    },

    downloadCsv: function () {
        pimcore.helpers.download("/plugin/Formbuilder/index/download-csv?formId=" + this.form.document.id);
    }

});
