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

pimcore.registerNS("pimcore.element.history");
pimcore.element.history = Class.create({


    initialize:function () {
        this.getTabPanel();
    },

    activate:function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.setActiveItem("element_history");
    },

    getTabPanel:function () {
        if (!this.panel) {
            this.panel = new Ext.Panel({
                id:"element_history",
                title:t("element_history"),
                border:false,
                layout:"fit",
                iconCls:"pimcore_icon_schedule",
                closable:true
            });

            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("element_history");
            }.bind(this));

            var history = pimcore.helpers.getHistory();
            var storeValues = [];
            for(var i=0; i < history.length; i++) {
                var item = history[i];
                var time = new Date(item.time);
                var name = "";
                if (item.name) {
                    name = item.name;
                }

                storeValues.push([name, item.type, item.id, time]);
            }

            this.store =  new Ext.data.ArrayStore({
                fields: [ "name", "type", "id", "time"],
                data: storeValues
            });


            this.resultpanel = Ext.create('Ext.grid.Panel', {
                store:this.store,
                trackMouseOver:true,
                disableSelection:true,
                autoScroll:true,

                columns:[
                        {
                            hideable: false,
                            xtype: 'actioncolumn',
                            width: 30,
                            items: [
                                {
                                    tooltip: t('open'),
                                    icon: "/pimcore/static6/img/icon/pencil_go.png",
                                    handler: function (grid, rowIndex) {
                                        var data = grid.getStore().getAt(rowIndex).data;
                                        pimcore.helpers.openElement(data.id, data.type);

                                    }.bind(this)
                                    ,
                                    getClass: function(value,metadata,record) {

                                        return 'x-grid-center-icon';

                                    }
                                }
                            ]
                        },
                        {
                            header:t("name"),
                            dataIndex:'name',
                            flex:500,
                            align:'left',
                            sortable:true
                        }

                        ,
                        {
                            header:t("type"),
                            dataIndex:'type',
                            flex:80,
                            align:'left',
                            sortable:true
                        }
                        ,
                        {
                            header:t("id"),
                            dataIndex:'id',
                            flex:80,
                            align:'left',
                            sortable:true
                        }
                        ,
                        {
                            header:t("time"),
                            dataIndex:'time',
                            flex:220,
                            align:'left',
                            sortable:true
                        }
                    ]
                ,

                listeners: {
                    rowclick : function(table, record, tr, rowIndex, e, eOpts ) {
                        var data = record.data;
                        pimcore.helpers.openElement(data.id, data.type);
                    }.bind(this)
                },
                viewConfig: {
                    forceFit: true
                }
            });


            this.panel.add(this.resultpanel);
            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.setActiveItem("element_history");

            pimcore.layout.refresh();
        }
        return this.panel;
    }
});
