pimcore.registerNS("pimcore.settings.translation.translationmerger");
pimcore.settings.translation.translationmerger = Class.create({


    initialize: function (translationType, mergeResult, callback) {
        this.translationType = translationType;
        var delta = mergeResult.delta;
        delta = base64_decode(delta);
        delta = Ext.decode(delta);

        if (delta.length == 0) {
            pimcore.helpers.showNotification(t("info"), t("nothing_to_merge"), "info");
            return;
        }

        this.delta = delta;
        this.languages = mergeResult.languages;
        this.callback = callback;

        this.store = new Ext.data.JsonStore({
            autoDestroy: true,
            sortInfo: {
                field    : 'key',
                direction: 'ASC'

            } ,
            data: this.delta,
            fields: ['lg', 'lgname', 'icon', 'key','text' , 'current', 'csv', 'dirty']
        });

        this.getTabPanel();
    },


    getTabPanel: function () {

        if (!this.panel) {


            this.gridfilters = this.getGridFilters();

            this.layout = new Ext.grid.GridPanel({
            store: this.store,
            plugins: [this.gridfilters],
            columns: [
                {header: t("language"), sortable: true, dataIndex: 'lgname', editable: false},
                {header: "", sortable: true, dataIndex: 'icon', editable: false, width: 20,
                                renderer: function(data){
                                    return '<img src="'+data+'" alt="" />';
                                }
                },
                {header: t("key"), sortable: true, dataIndex: 'key', editable: false, width: 150},
                {header: t("translation_merger_csv"), sortable: true, dataIndex: 'csv', editable: false, width: 200},
                {
                    header: t("action"),
                    xtype: 'actioncolumn',
                    width: 30,
                    tooltip: t('action'),
                    items: [
                        {
                            getClass: function(v, meta, rec) {
                                switch (rec.get('dirty')) {
                                    case 1: return 'pimcore_icon_revert pimcore_action_column';
                                    case -1: return 'pimcore_icon_hourglass pimcore_action_column';
                                    default: return 'pimcore_icon_arrow_right pimcore_action_column';

                                }
                            },

                            handler: function(grid, rowIndex, colIndex) {
                                // this.download(rowIndex);
                                //this.store.removeAt(rowIndex);

                                var rec = this.store.getAt(rowIndex);
                                var state =  rec.get("dirty");
                                var current = rec.get("current");

                                if (state == 1) {
                                    newState = 0;
                                } else {
                                    newState = 1;
                                }
                                if (state == 1) {
                                    rec.set("dirty", -1);
                                    rec.set("current", rec.get("text"));
                                } else if (state != -1) {
                                    rec.set("dirty", -1);
                                    rec.set("current", rec.get("csv"));
                                }


                                if (rec.get("dirty") == -1) {
                                    Ext.Ajax.request({
                                        url: "/admin/translation/merge-item/",
                                        method: "post",
                                        params: {
                                            data: Ext.encode(rec.data),
                                            translationType: this.translationType
                                        },
                                        success: function (response) {
                                            var result = Ext.decode(response.responseText);
                                            if (result.success) {
                                                rec.set("dirty", newState);
                                            } else {
                                                rec.set("dirty" , state);
                                                rec.set("current", current);
                                            }
                                        }.bind(this)
                                    });
                                }



                            }.bind(this)
                        }
                    ]
                },

                {header: t("translation_merger_current"), sortable: true, dataIndex: 'current', editable: false, width: 200}
                ],
                viewConfig: {
                    forceFit: true,
                    markDirty:false
                },
                cls: "translationmerger"
            });

            this.panel = new Ext.Panel({
                id: uniqid(),
                title: t("translation_merger_" + this.translationType),
                iconCls: "pimcore_icon_translations",
                border: false,
                layout: "fit",
                closable:true

            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);

            tabPanel.activate(this.panel.getId());

            this.panel.add(this.layout);
            pimcore.layout.refresh();

            this.layout.doLayout();

        }

        return this.panel;
    },

    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.activate(this.panel.getId());
    },

    getGridFilters: function() {
        var configuredFilters = [{
            type: "string",
            dataIndex: "csv"
        },{
            type: "string",
            dataIndex: "current"
        },{
            type: "string",
            dataIndex: "key"
        }];

        // filters
        var gridfilters = new Ext.ux.grid.GridFilters({
            local: true,
            filters: configuredFilters
        });

        return gridfilters;
    }
});