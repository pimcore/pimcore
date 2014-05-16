/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
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
        
        this.requiresPanel = new Ext.Panel({
            flex: 1,
            layout: "fit"
        });
        
        this.requiredByPanel = new Ext.Panel({
            flex: 1,
            layout: "fit"
        });
        
        if (this.layout == null) {
            this.layout = new Ext.Panel({
                title: t('dependencies'),
                border: false,
                iconCls: "pimcore_icon_tab_dependencies",
                autoScroll: true,
                bodyStyle: "padding: 10px",
                listeners:{
                    activate: this.getData.bind(this)
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
        
        this.layout.add(this.requiresNote);
        this.layout.add(this.requiresGrid);
        
        this.layout.add(this.requiredByNote);
        this.layout.add(this.requiredByGrid);
        
        this.layout.doLayout();
    },


    getData: function() {
        
        // only load it once
        if(this.requiresLoaded && this.requiredByLoaded) {
            return;
        }
        
        
        Ext.Ajax.request({
            url: '/admin/' + this.type + '/get-requires-dependencies/',
            params: {
                id: this.element.id
            },
            success: this.getRequiresLayout.bind(this)
        });
        
        Ext.Ajax.request({
            url: '/admin/' + this.type + '/get-required-by-dependencies/',
            params: {
                id: this.element.id
            },
            success: this.getRequiredByLayout.bind(this)
        });


        this.waitForLoaded();
    },
        
    getRequiresLayout: function(response) {
        if (response != null) {
            this.requiresData = Ext.decode(response.responseText);
        }
                
        
        this.requiresStore = new Ext.data.JsonStore({
            autoDestroy: true,
            data: this.requiresData,
            root: 'requires',
            fields: ['id', 'path', 'type', 'subtype']
        });                

        this.requiresGrid = new Ext.grid.GridPanel({
            store: this.requiresStore,
            columns: [
                {header: "ID", sortable: true, dataIndex: 'id'},
                {header: t("path"), id: "path", sortable: true, dataIndex: 'path'},
                {header: t("type"), sortable: true, dataIndex: 'type'},
                {header: t("subtype"), sortable: true, dataIndex: 'subtype'}
            ],
            columnLines: true,
            autoExpandColumn: "path",
            stripeRows: true,
            autoHeight: true,              
            title: t('requires')
        });
        this.requiresGrid.on("rowclick", this.click.bind(this));
        
        
        this.requiresNote = new Ext.Panel({
            html:t('hidden_dependencies'),
            cls:'dependency-warning',
            border:false,
            hidden: !this.requiresData.hasHidden                        
        });
        
        
        
        this.requiresLoaded = true;        
    },
    getRequiredByLayout: function(response) {
        if (response != null) {
            this.requiredByData = Ext.decode(response.responseText);
        }
                
        this.requiredByStore = new Ext.data.JsonStore({
            autoDestroy: true,
            data: this.requiredByData,
            root: 'requiredBy',
            fields: ['id', 'path', 'type', 'subtype']
        });
        
                        
                                
        this.requiredByGrid = new Ext.grid.GridPanel({
            store: this.requiredByStore,
            columns: [
                {header: "ID", sortable: true, dataIndex: 'id'},
                {header: t("path"), id: "path", sortable: true, dataIndex: 'path'},
                {header: t("type"), sortable: true, dataIndex: 'type'},
                {header: t("subtype"), sortable: true, dataIndex: 'subtype'}
            ],
            autoExpandColumn: "path",
            columnLines: true,
            stripeRows: true,
            autoHeight: true,
            title: t('required_by')
        });
        this.requiredByGrid.on("rowclick", this.click.bind(this));
        
        
        
        this.requiredByNote = new Ext.Panel({
            html:t('hidden_dependencies'),
            cls:'dependency-warning',
            border:false,
            hidden: !this.requiredByData.hasHidden                        
        });
        
    
        this.requiredByLoaded = true;        
    },

    click: function ( grid, index) {
        
        var d = grid.getStore().getAt(index).data;

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