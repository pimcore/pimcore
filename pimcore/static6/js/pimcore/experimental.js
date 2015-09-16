Ext.define('overrides.Component', {
    override: 'Ext.Component'

    //initComponent: function() {
    //    this.callParent(arguments);
    //    this.on('enable', function(cmp) {
    //        // workaround for http://www.sencha.com/forum/showthread.php?295910
    //        // [5.1.0.107] setDisabled(true) on formpanel doesn't enable buttons
    //        // ...
    //        // Success! Looks like we've fixed this one. According to our records the fix was applied for EXTJS-16180 in 5.1.1.
    //        //..
    //        try {
    //            if (typeof cmp.isMasked == "function") {
    //                if (cmp.isMasked()) {
    //                    cmp.unmask();
    //                }
    //            }
    //        } catch (e) {
    //
    //        }
    //    })
    //}
});

Ext.define('pimcore.FieldSetTools', {
    extend: 'Ext.form.FieldSet',

    createLegendCt: function () {
        var me = this;
        var result = this.callSuper(arguments);

        if (me.config.tools && me.config.tools.length > 0) {
            this.createCloseCmp(result);
        }
        return result;

    },


    createCloseCmp: function(result) {
        //TODO do this in a generic way
        var me = this;
        var tool = me.config.tools[0];

        var cfg = {
                type: 'close',
                html: me.title,
                ui: me.ui,
                tooltip: tool.qtip,
                handler: tool.handler,
                cls: me.baseCls + '-header-tool-default ' + me.baseCls + '-header-tool-right',
                id: me.id + '-legendTitle2',
                    ariaRole: 'checkbox',
                    ariaLabel: "gaga",
                    ariaRenderAttributes: {
                        'aria-checked': !me.collapsed
                    }
            };

        me.titleCmp2 = new Ext.panel.Tool(cfg);
        result.add(me.titleCmp2);
        return me.titleCmp2;
        result.add(closeCmp);
    return closeCmp;
},

});



Ext.define('pimcore.filters', {
    extend: 'Ext.grid.filters.Filters',
    alias: 'plugin.pimcore.gridfilters',

    createColumnFilter: function(column) {
        this.callSuper(arguments);
        var type = column.filter.type;
        var theFilter = column.filter.filter;

        if (type == "date" || type == "number") {
            theFilter.lt.config.type = type;
            theFilter.gt.config.type = type;
            theFilter.eq.config.type = type;
        } else {
            theFilter.config.type = type;
        }
    }
});

// See https://www.sencha.com/forum/showthread.php?288385
Ext.define('Ext.overrides.grid.View', {
        extend: 'Ext.grid.View',

        alias: 'widget.patchedgridview'
        ,

        handleUpdate: function(store, record, operation, changedFieldNames) {
            var me = this,
                rowTpl = me.rowTpl,
                oldItem, oldItemDom, oldDataRow,
                newItemDom,
                newAttrs, attLen, attName, attrIndex,
                overItemCls,
                focusedItemCls,
                selectedItemCls,
                columns,
                column,
                columnsToUpdate = [],
                len, i,
                hasVariableRowHeight = me.variableRowHeight,
                cellUpdateFlag,
                updateTypeFlags = 0,
                cell,
                fieldName,
                value,
                defaultRenderer,
                scope,
                ownerCt = me.ownerCt;


            if (me.viewReady) {
                oldItemDom = me.getNodeByRecord(record);

                if (oldItemDom) {
                    overItemCls = me.overItemCls;
                    focusedItemCls = me.focusedItemCls;
                    selectedItemCls = me.selectedItemCls;
                    columns = me.ownerCt.getVisibleColumnManager().getColumns();

                    if (!me.getRowFromItem(oldItemDom) || (updateTypeFlags & 1) || (oldItemDom.tBodies[0].childNodes.length > 1)) {
                        oldItem = Ext.fly(oldItemDom, '_internal');
                        newItemDom = me.createRowElement(record, me.dataSource.indexOf(record), columnsToUpdate);
                        if (oldItem.hasCls(overItemCls)) {
                            Ext.fly(newItemDom).addCls(overItemCls);
                        }
                        if (oldItem.hasCls(focusedItemCls)) {
                            Ext.fly(newItemDom).addCls(focusedItemCls);
                        }
                        if (oldItem.hasCls(selectedItemCls)) {
                            Ext.fly(newItemDom).addCls(selectedItemCls);
                        }

                        if (Ext.isIE9m && oldItemDom.mergeAttributes) {
                            oldItemDom.mergeAttributes(newItemDom, true);
                        } else {
                            newAttrs = newItemDom.attributes;
                            attLen = newAttrs.length;
                            for (attrIndex = 0; attrIndex < attLen; attrIndex++) {
                                attName = newAttrs[attrIndex].name;
                                if (attName !== 'id') {
                                    oldItemDom.setAttribute(attName, newAttrs[attrIndex].value);
                                }
                            }
                        }


                        if (columns.length && (oldDataRow = me.getRow(oldItemDom))) {
                            me.updateColumns(oldDataRow, Ext.fly(newItemDom).down(me.rowSelector, true), columnsToUpdate);
                        }

                        while (rowTpl) {
                            if (rowTpl.syncContent) {
                                if (rowTpl.syncContent(oldItemDom, newItemDom, changedFieldNames ? columnsToUpdate : null) === false) {
                                    break;
                                }
                            }
                            rowTpl = rowTpl.nextTpl;
                        }
                    }
                    else {
                        this.refresh();
                    }

                    if (hasVariableRowHeight) {
                        Ext.suspendLayouts();
                    }


                    me.fireEvent('itemupdate', record, me.store.indexOf(record), oldItemDom);

                    if (hasVariableRowHeight) {
                        me.refreshSize();

                        Ext.resumeLayouts(true);
                    }
                }
            }
        }
    }, function() {
        if (!Ext.getVersion().match('5.1.0.107')) {
            console.warn('This patch has not been tested with this version of ExtJS');
        }

    }

    );

    Ext.define('pimcore.tree.Panel', {
        extend: 'Ext.tree.Panel'
    });

    Ext.define('pimcore.tree.View', {
        extend: 'Ext.tree.View',
        alias: 'widget.pimcoretreeview',
        listeners: {
            refresh: function() {
                this.updatePaging();
            }
        },

        queue: {},

        renderRow: function(record, rowIdx, out) {
            var me = this;
            if (record.needsPaging) {
                me.queue[record.id] = record;
            }

            me.superclass.renderRow.call(this, record, rowIdx, out);
        },

        updatePaging: function() {
            var me = this;
            var queue = me.queue;

            var names = Object.getOwnPropertyNames(queue);

            for (i = 0; i < names.length; i++) {
                var node = queue[names[i]];
                //console.log("create toolbar for " + node.id + " " + node.data.expanded);

                if (node.data.expanded) {
                    node.ptb = ptb = Ext.create('pimcore.toolbar.Paging', {
                            node: node
                        }
                    );

                     node.ptb.node = node;

                    var tree = node.getOwnerTree();
                    var view = tree.getView();
                    var nodeEl = Ext.fly(view.getNodeByRecord(node));
                    nodeEl = nodeEl.getFirstChild();
                    nodeEl = nodeEl.query(".x-tree-node-text");
                    nodeEl = nodeEl[0];
                    var el = nodeEl;

                    //el.addCls('x-grid-header-inner');
                    el = Ext.DomHelper.insertAfter(el, {
                        tag: 'span',
                        style: 'display: inline-flex;white-space:nowrap;',
                        "class": "pimcore_pagingtoolbar_container"
                    }, true);
                    el.addListener("click", function(e) {
                        e.stopEvent();
                    });


                    ptb.render(el);
                    tree.updateLayout();
                }
            }

            me.queue = {}
        }
    });

    Ext.define('pimcore.data.PagingTreeStore', {

        extend: 'Ext.data.TreeStore',

        ptb: false,

        onProxyLoad: function(operation) {
            var me = this;
            var options = operation.initialConfig
            var node = options.node;

            var response = operation.getResponse();
            var data = Ext.decode(response.responseText);
            var total = data.total;
            // console.log("total nodes for  " + node.data.text + " (" + total + ")");

            var text = node.data.text;
            if (typeof total == "undefined") {
                total = 0;
            }

            //if (!node.decorated) {
            //    node.decorated = true;
            //    if (node.data && node.data.text) {
            //        node.data.text = node.data.text + " (" + total + ")" ;
            //    }
            //}

            node.addListener("expand", function(node) {
                var tree = node.getOwnerTree();
                if (tree) {
                    var view = tree.getView();
                    view.updatePaging();
                }
            }.bind(this));

            if (me.pageSize < total) {
                node.needsPaging = true;
                node.pagingData = {
                    total: data.total,
                    offset: data.offset,
                    limit: data.limit
                }
            }

            me.superclass.onProxyLoad.call(this, operation);

            //var store = node.getTreeStore();
            var proxy = this.getProxy();
            proxy.setExtraParam("start", 0);
        }
    });


    Ext.define('pimcore.toolbar.Paging', {
        extend: 'Ext.toolbar.Toolbar',
        requires: [
            'Ext.toolbar.TextItem',
            'Ext.form.field.Number'
        ],

        displayInfo: false,

        prependButtons: false,

        displayMsg: t('Displaying {0} - {1} of {2}'),

        emptyMsg: t('no_data_to_display'),

        beforePageText: t('page'),

        afterPageText: '/ {0}',

        firstText: t('first_page'),

        prevText: t('previous_page'),

        nextText: t('next_page'),

        lastText: t('last_page'),

        refreshText: t('refresh'),

        width: 180,

        height: 20,

        border: false,

        emptyPageData: {
            total: 0,
            currentPage: 0,
            pageCount: 0,
            toRecord: 0,
            fromRecord: 0
        },

        getPagingItems: function() {
            var me = this,
                inputListeners = {
                    scope: me,
                    blur: me.onPagingBlur
                };
            var pagingData = me.node.pagingData;

            var currPage = pagingData.offset / pagingData.limit + 1;
            //

            this.afterItem = Ext.create('Ext.form.NumberField', {

                cls: Ext.baseCSSPrefix + 'tbar-page-number',
                value: Math.ceil(pagingData.total / pagingData.limit),
                hideTrigger: true,
                heightLabel: true,
                height: 18,
                width: 40,
                disabled: true,
                margin: '-1 2 3 2'
            });


            inputListeners[Ext.supports.SpecialKeyDownRepeat ? 'keydown' : 'keypress'] = me.onPagingKeyDown;
            return [
                {
                    itemId: 'first',
                    tooltip: me.firstText,
                    overflowText: me.firstText,
                    iconCls: Ext.baseCSSPrefix + 'tbar-page-first',
                    disabled: me.node.pagingData.offset == 0,
                    handler: me.moveFirst,
                    scope: me,
                    border: false

                },
                {
                    itemId: 'prev',
                    tooltip: me.prevText,
                    overflowText: me.prevText,
                    iconCls: Ext.baseCSSPrefix + 'tbar-page-prev',
                    disabled: me.node.pagingData.offset == 0,
                    handler: me.movePrevious,
                    scope: me,
                    border: false
                }
                ,
                {
                    xtype: 'numberfield',
                    itemId: 'inputItem',
                    name: 'inputItem',
                    heightLabel: true,
                    cls: Ext.baseCSSPrefix + 'tbar-page-number',
                    allowDecimals: false,
                    minValue: 1,
                    maxValue: this.getMaxPageNum(),
                    value: currPage,
                    hideTrigger: true,
                    enableKeyEvents: true,
                    keyNavEnabled: false,
                    selectOnFocus: true,
                    submitValue: false,
                    height: 18,
                    width: 40,
                    isFormField: false,
                    margin: '-1 2 3 2',
                    listeners: inputListeners
                },
                 new Ext.Toolbar.TextItem({
                    text: "/",
                    style: ""
                })
                ,
                this.afterItem,
                ,
                {
                    itemId: 'next',
                    tooltip: me.nextText,
                    overflowText: me.nextText,
                    iconCls: Ext.baseCSSPrefix + 'tbar-page-next',
                    disabled: (Math.ceil(me.node.pagingData.total / me.node.pagingData.limit) - 1) * me.node.pagingData.limit == me.node.pagingData.offset,
                    handler: me.moveNext,
                    scope: me
                },
                {
                    itemId: 'last',
                    tooltip: me.lastText,
                    overflowText: me.lastText,
                    iconCls: Ext.baseCSSPrefix + 'tbar-page-last',
                    disabled: (Math.ceil(me.node.pagingData.total / me.node.pagingData.limit) - 1) * me.node.pagingData.limit == me.node.pagingData.offset,
                    handler: me.moveLast,
                    scope: me
                }
                //,
                //'-',
                //{
                //    itemId: 'refresh',
                //    tooltip: me.refreshText,
                //    overflowText: me.refreshText,
                //    iconCls: Ext.baseCSSPrefix + 'tbar-loading',
                //    disabled: false,
                //    handler: me.doRefresh,
                //    scope: me
                //}
            ];
        },

        getMaxPageNum: function() {
            var me = this;
            return Math.ceil(me.node.pagingData.total / me.node.pagingData.limit)
        },

        initComponent: function(config) {
            var me = this,
                userItems = me.items || me.buttons || [],
                pagingItems;

            pagingItems = me.getPagingItems();
            if (me.prependButtons) {
                me.items = userItems.concat(pagingItems);
            } else {
                me.items = pagingItems.concat(userItems);
            }
            delete me.buttons;
            if (me.displayInfo) {
                me.items.push('->');
                me.items.push({
                    xtype: 'tbtext',
                    itemId: 'displayItem'
                });
            }
            me.callParent();
        },


        getInputItem: function() {
            return this.child('#inputItem');
        },


        onPagingBlur: function(e) {
            console.log("onPagingBlur");
            var inputItem = this.getInputItem(),
                curPage;
            if (inputItem) {
                //curPage = this.getPageData().currentPage;
                //inputItem.setValue(curPage);
            }
        },

        onPagingKeyDown: function(field, e) {
            console.log("onPagingKeyDown");
            this.processKeyEvent(field, e);
        },

        readPageFromInput: function() {
            var inputItem = this.getInputItem(),
                pageNum = false,
                v;
            if (inputItem) {
                v = inputItem.getValue();
                pageNum = parseInt(v, 10);
            }
            return pageNum;
        },


        processKeyEvent: function(field, e) {
            var me = this,
                k = e.getKey(),
                //pageData = me.getPageData(),
                increment = e.shiftKey ? 10 : 1,
                pageNum;
            if (k == e.RETURN) {
                e.stopEvent();
                pageNum = me.readPageFromInput();
                if (pageNum !== false) {
                    pageNum = Math.min(Math.max(1, pageNum), this.getMaxPageNum());
                    this.moveToPage(pageNum);
                }


            } else if (k == e.HOME) {
                e.stopEvent();
                this.moveFirst();
            } else if (k == e.END) {
                e.stopEvent();
                this.moveLast();
            } else if (k == e.UP || k == e.PAGE_UP || k == e.DOWN || k == e.PAGE_DOWN) {
                e.stopEvent();
                pageNum = me.readPageFromInput();
                if (pageNum) {
                    if (k == e.DOWN || k == e.PAGE_DOWN) {
                        increment *= -1;
                    }
                    pageNum += increment;
                    if (pageNum >= 1 && pageNum <= this.getMaxPageNum()) {
                        this.moveToPage(pageNum);
                    }
                }
            }
        },

        moveToPage: function(page) {
            var me = this;
            var node = me.node;
            var pagingData = node.pagingData;
            var store = node.getTreeStore();


            var proxy = store.getProxy();
            proxy.setExtraParam("start",  pagingData.limit * (page - 1));
            store.load({
                node: node
            });
        },



        moveFirst: function() {
            var me = this;
            var node = me.node;
            var pagingData = node.pagingData;
            var store = node.getTreeStore();
            var page = pagingData.offset / pagingData.total;

            var proxy = store.getProxy();
            proxy.setExtraParam("start", 0);
            store.load({
                node: node
            });
        },

        movePrevious: function() {
            var me = this;
            var node = me.node;
            var pagingData = node.pagingData;
            var store = node.getTreeStore();
            var page = pagingData.offset / pagingData.total;

            var proxy = store.getProxy();
            proxy.setExtraParam("start", pagingData.offset - pagingData.limit);
            store.load({
                node: node
            });
        },

        moveNext: function() {
            var me = this;
            var node = me.node;
            var pagingData = node.pagingData;
            var store = node.getTreeStore();
            var page = pagingData.offset / pagingData.total;

            var proxy = store.getProxy();
            proxy.setExtraParam("start", pagingData.offset + pagingData.limit);
            store.load({
                node: node
            });

        },

        moveLast: function() {
            var me = this;
            var node = me.node;
            var pagingData = node.pagingData;
            var store = node.getTreeStore();
            var offset = (Math.ceil(pagingData.total / pagingData.limit) - 1) * pagingData.limit;

            var proxy = store.getProxy();
            proxy.setExtraParam("start", offset);
            store.load({
                node: node
            });
        },

        doRefresh: function() {
            var me = this;
            var node = me.node;
            var pagingData = node.pagingData;
            var store = node.getTreeStore();
            var page = pagingData.offset / pagingData.total;

            var proxy = store.getProxy();
            proxy.setExtraParam("start", pagingData.offset);
            store.load({
                node: node
            });
        },

        onDestroy: function() {
            //this.bindStore(null);
            this.callParent();
        }
    });


