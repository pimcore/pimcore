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

pimcore.registerNS("pimcore.element.dependencies");
pimcore.element.dependencies = Class.create({

    initialize: function(element, type) {
        this.element = element;
        this.type = type;
        this.requiresLoaded = false;
        this.requiredByLoaded = false;
    },

    getLayout: function() {
        
        if (this.layout == null) {
            this.layout = new Ext.Panel({
                tabConfig: {
                    tooltip: t('dependencies')
                },
                layout: {
                    type: 'hbox',
                    pack: 'start',
                    align: 'stretch',
                },
                iconCls: "pimcore_icon_dependencies",
                listeners:{
                    activate: this.getGridLayouts.bind(this)
                }
            });
        }
        return this.layout;
    },

    waitForLoaded: function() {
        if (this.requiredByLoaded && this.requiresLoaded) {
            this.completeLoad();
        } else {
            window.setTimeout(this.waitForLoaded.bind(this), 1000);
        }
    },

    completeLoad: function() {
        this.layout.add(this.requiresPanel);
        this.layout.add(this.requiredByPanel);
        
        this.layout.updateLayout();
    },


    getGridLayouts: function() {
        
        // only load it once
        if(this.requiresLoaded && this.requiredByLoaded) {
            return;
        }

        this.getRequiresLayout();
        this.getRequiredByLayout();

        this.waitForLoaded();
    },
        
    getRequiresLayout: function() {

        var itemsPerPage = pimcore.helpers.grid.getDefaultPageSize(-1);

        this.requiresStore = new Ext.data.Store({
            pageSize: itemsPerPage,
            proxy : {
                type: 'ajax',
                url: '/admin/element/get-requires-dependencies',
                reader: {
                    type: 'json',
                    rootProperty: 'requires'
                },
                extraParams: {
                    id: this.element.id,
                    elementType: this.type
                }
            },
            autoLoad: false,
            fields: ['id', 'path', 'type', 'subtype']
        });

        this.requiresGrid = new Ext.grid.GridPanel({
            store: this.requiresStore,
            columns: [
                {text: "ID", sortable: true, dataIndex: 'id', hidden:true},
                {text: t("type"), sortable: true, dataIndex: 'type', hidden: true},
                {text: t("subtype"), sortable: true, dataIndex: 'subtype', width: 50,
                  renderer:
                    function (value, metaData, record, rowIndex, colIndex, store) {
                        return '<div style="height: 16px;" class="pimcore_icon_asset pimcore_icon_' + value
                            + '" name="' + t(record.data.subtype) + '">&nbsp;</div>';
                    }
                },
                {text: t("path"), sortable: true, dataIndex: 'path', flex: 1, renderer: Ext.util.Format.htmlEncode}
            ],
            flex: 1,
            columnLines: true,
            stripeRows: true,
            bbar: pimcore.helpers.grid.buildDefaultPagingToolbar(this.requiresStore, {pageSize: itemsPerPage})
        });
        this.requiresGrid.on("rowclick", this.click.bind(this));
        
        this.requiresStore.load({
            callback : function(records, operation, success) {
                if (success) {
                    var response = operation.getResponse();
                    this.requiresData = Ext.decode(response.responseText);

                    if (this.requiresData.hasHidden) {
                        this.requiresNote.show();
                    }
                }
            }.bind(this)
        });

        this.requiresNote = new Ext.Panel({
            html:t('hidden_dependencies'),
            cls:'dependency-warning',
            border: false,
            hidden: true,
            height: 50,
            style: {
                marginLeft: '10px'
            },
        });

        this.requiresPanel = new Ext.Panel({
            title: t('requires'),
            flex: .5,
            layout: {
                  type: 'vbox',
                  align: 'stretch'
            },
            resizable: true,
            split: true,
            collapsible: true,
            collapseDirection: 'left',
            items: [this.requiresNote, this.requiresGrid]
        });

        this.requiresLoaded = true;        
    },

    getRequiredByLayout: function() {

        var itemsPerPage = pimcore.helpers.grid.getDefaultPageSize(-1);
        
        this.requiredByStore = new Ext.data.Store({
            pageSize: itemsPerPage,
            proxy : {
                type: 'ajax',
                url: '/admin/element/get-required-by-dependencies',
                reader: {
                    type: 'json',
                    rootProperty: 'requiredBy'
                },
                extraParams: {
                    id: this.element.id,
                    elementType: this.type
                }
            },
            autoLoad: false,
            fields: ['id', 'path', 'type', 'subtype']
        });

        this.requiredByGrid = Ext.create('Ext.grid.Panel', {
            store: this.requiredByStore,
            columns: [
                {text: "ID", sortable: true, dataIndex: 'id', hidden:true},
                {text: t("type"), sortable: true, dataIndex: 'type', hidden: true},
                {text: t("subtype"), sortable: true, dataIndex: 'subtype', width: 50,
                 renderer:
                    function (value, metaData, record, rowIndex, colIndex, store) {
                        return '<div style="height: 16px;" class="pimcore_icon_asset pimcore_icon_' + value
                            + '" name="' + t(record.data.subtype) + '">&nbsp;</div>';
                    }
                },
                {text: t("path"), sortable: true, dataIndex: 'path', flex: 1, renderer: Ext.util.Format.htmlEncode}
            ],
            columnLines: true,
            stripeRows: true,
            flex: 1,
            bbar: pimcore.helpers.grid.buildDefaultPagingToolbar(this.requiredByStore,{pageSize: itemsPerPage})
        });
        this.requiredByGrid.on("rowclick", this.click.bind(this));

        this.requiredByStore.load({
            callback : function(records, operation, success) {
                if (success) {
                    var response = operation.getResponse();
                    this.requiredByData = Ext.decode(response.responseText);

                    if (this.requiredByData.hasHidden) {
                        this.requiredByNote.show();
                    }
                }
            }.bind(this)
        });

        this.requiredByNote = new Ext.Panel({
            html:t('hidden_dependencies'),
            cls:'dependency-warning',
            border:false,
            hidden: true,
            height: 50,
            style: {
                marginLeft: '10px'
            },
        });

        this.requiredByPanel = new Ext.Panel({
            title: t('required_by'),
            flex: .5,
            layout: {
                  type: 'vbox',
                  align: 'stretch'
            },
            resizable: true,
            split: true,
            collapsible: true,
            collapseDirection: 'right',
            autoExpandColumn: "path",
            items: [this.requiredByNote, this.requiredByGrid]
        });
    
        this.requiredByLoaded = true;        
    },

    click: function ( grid, record, tr, rowIndex, e, eOpts ) {
        
        var d = record.data;

        if (d.type == "object") {
            pimcore.helpers.openObject(d.id, d.subtype);
        }
        else if (d.type == "asset") {
            pimcore.helpers.openAsset(d.id, d.subtype);
        }
        else if (d.type == "document") {
            pimcore.helpers.openDocument(d.id, d.subtype);
        }
    }

});