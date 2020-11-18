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

if(typeof window['t'] !== 'function') {
    // for compatibility reasons
    window.t = function(v) { return v; };
}


Ext.form.field.Date.prototype.startDay = 1;

Ext.override(Ext.dd.DragDropMgr, {
        startDrag: function (x, y) {

            // always hide tree-previews on drag start
            pimcore.helpers.treeNodeThumbnailPreviewHide();

            this.callParent(arguments);
        }
    }
);

/**
 * Undesired behaviour: submenu is hidden on clicking owner menu item
 * fix see https://www.sencha.com/forum/showthread.php?305492-Undesired-behaviour-submenu-is-hidden-on-clicking-owner-menu-item
 * @param e
 */
Ext.menu.Manager.checkActiveMenus = function(e) {
    var allMenus = this.visible,
        len = allMenus.length,
        i, menu,
        mousedownCmp = Ext.Component.fromElement(e.target);
    if (len) {
        // Clone here, we may modify this collection while the loop is active
        allMenus = allMenus.slice();
        for (i = 0; i < len; ++i) {
            menu = allMenus[i];
            // Hide the menu if:
            //      The menu does not own the clicked upon element AND
            //      The menu is not the child menu of a clicked upon MenuItem
            if (!(menu.owns(e) || (mousedownCmp && mousedownCmp.isMenuItem && mousedownCmp.menu === menu))) {
                menu.hide();
            }
        }
    }
};


Ext.define('pimcore.FieldSetTools', {
    extend: 'Ext.form.FieldSet',

    createLegendCt: function () {
        var me = this;
        var result = this.callSuper(arguments);

        if (me.config.tools && me.config.tools.length > 0) {
            for (var i = 0; i < me.config.tools.length; i++) {
                var tool = me.config.tools[i];
                this.createToolCmp(tool, result);
            }
        }
        return result;

    },


    createToolCmp: function(tool, result) {
        var me = this;
        var cls = me.baseCls + '-header-tool-default ' + me.baseCls + '-header-tool-right';
        if (tool['cls']) {
            cls = cls + ' ' + tool['cls'];
        }
        var cfg = {
            type: tool['type'],
            html: me.title,
            ui: me.ui,
            tooltip: tool.qtip,
            handler: tool.handler,
            hidden: tool.hidden,
            cls: cls,
            ariaRole: 'checkbox',
            ariaRenderAttributes: {
                'aria-checked': !me.collapsed
            }
        };

        if (tool['id']) {
            cfg['id'] = tool['id'];
        }

        var cmp = new Ext.panel.Tool(cfg);
        result.add(cmp);
        return result;
    },
});



Ext.define('pimcore.filters', {
    extend: 'Ext.grid.filters.Filters',
    alias: 'plugin.pimcore.gridfilters',

    createColumnFilter: function(column) {
        this.callSuper(arguments);
        var type = column.filter.type;
        var theFilter = column.filter.filter;

        if (column.filter instanceof Ext.grid.filters.filter.TriFilter) {
            theFilter.lt.config.type = type;
            theFilter.gt.config.type = type;
            theFilter.eq.config.type = type;

            if (column.decimalPrecision) {
                column.filter.fields.lt.decimalPrecision = column.decimalPrecision;
                column.filter.fields.gt.decimalPrecision = column.decimalPrecision;
                column.filter.fields.eq.decimalPrecision = column.decimalPrecision;
            }
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
        if (!Ext.getVersion().match('6.0.0.640')) {
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
        },
        beforeitemupdate: function(record) {
            if(record.ptb) {
                record.ptb.destroy();
                delete record.ptb;
            }
        },

        itemupdate: function(record) {
            if (record.needsPaging && typeof record.ptb == "undefined") {
                this.doUpdatePaging(record);
            }
        }
    },

    queue: {},

    renderRow: function(record, rowIdx, out) {
        var me = this;
        if (record.needsPaging) {
            me.queue[record.id] = record;
        }

        me.superclass.renderRow.call(this, record, rowIdx, out);

        if (record.needsPaging && typeof record.ptb == "undefined") {
            this.doUpdatePaging(record);
        }

        this.fireEvent("itemafterrender", record, rowIdx, out);
    },

    doUpdatePaging: function(node) {

        if (node.data.expanded && node.needsPaging) {

            node.ptb = ptb = Ext.create('pimcore.toolbar.Paging', {
                    node: node,
                    width: 260
                }
            );

            node.ptb.node = node;
            node.ptb.store = this.store;


            var tree = node.getOwnerTree();
            var view = tree.getView();
            var nodeEl = Ext.fly(view.getNodeByRecord(node));
            if (!nodeEl) {
                //console.log("Could not resolve node " + node.id);
                return;
            }
            nodeEl = nodeEl.getFirstChild();
            nodeEl = nodeEl.query(".x-tree-node-text");
            nodeEl = nodeEl[0];
            var el = nodeEl;

            //el.addCls('x-grid-header-inner');
            el = Ext.DomHelper.insertAfter(el, {
                tag: 'span',
                "class": "pimcore_pagingtoolbar_container"
            }, true);

            el.addListener("click", function(e) {
                e.stopPropagation();
            });


            el.addListener("mousedown", function(e) {
                e.stopPropagation();
            });

            ptb.render(el);
            tree.updateLayout();

            if (node.filter) {
                node.ptb.filterField.focus([node.filter.length, node.filter.length]);
            } else if (node.fromPaging) {
                node.ptb.numberItem.focus();
            }
        }

    },

    updatePaging: function() {
        var me = this;
        var queue = me.queue;

        var names = Object.getOwnPropertyNames(queue);

        for (i = 0; i < names.length; i++) {
            var node = queue[names[i]];
            this.doUpdatePaging(node);
        }

        me.queue = {}
    }
});

Ext.define('pimcore.data.PagingTreeStore', {

    extend: 'Ext.data.TreeStore',

    ptb: false,

    onProxyLoad: function(operation) {
        try {
            var me = this;
            var options = operation.initialConfig
            var node = options.node;
            var proxy = me.getProxy();
            var extraParams = proxy.getExtraParams();


            var response = operation.getResponse();
            var data = Ext.decode(response.responseText);

            node.fromPaging = data.fromPaging;
            node.filter = data.filter;
            node.inSearch = data.inSearch;
            node.overflow = data.overflow;

            proxy.setExtraParam("fromPaging", 0);

            var total = data.total;

            var text = node.data.text;
            if (typeof total == "undefined") {
                total = 0;
            }

            node.addListener("expand", function (node) {
                var tree = node.getOwnerTree();
                if (tree) {
                    var view = tree.getView();
                    view.updatePaging();
                }
            }.bind(this));

            //to hide or show the expanding icon depending if childs are available or not
            node.addListener('remove', function (node, removedNode, isMove) {
                if (!node.hasChildNodes()) {
                    node.set('expandable', false);
                }
            });
            node.addListener('append', function (node) {
                node.set('expandable', true);
            });

            if (me.pageSize < total || node.inSearch) {
                node.needsPaging = true;
                node.pagingData = {
                    total: data.total,
                    offset: data.offset,
                    limit: data.limit
                }
            } else {
                node.needsPaging = false;
            }

            me.superclass.onProxyLoad.call(this, operation);
            var proxy = this.getProxy();
            proxy.setExtraParam("start", 0);
        } catch (e) {
            console.log(e);
        }
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

    width: 280,

    height: 20,

    border: false,

    emptyPageData: {
        total: 0,
        currentPage: 0,
        pageCount: 0,
        toRecord: 0,
        fromRecord: 0
    },

    doCancelSearch: function (node) {
        this.inSearch = 0;
        this.cancelFilterButton.hide();
        this.filterButton.show();
        this.filterField.setValue("");
        this.filterField.hide();

        var store = this.store;
        store.load({
                node: node,
                params: {
                    "inSearch": 0
                }
            }
        );


        this.first.show();
        this.prev.show();
        this.numberItem.show();
        this.spacer.show();
        this.afterItem.show();
        this.next.show();
        this.last.show();
    },

    getPagingItems: function () {
        var me = this,
            inputListeners = {
                scope: me,
                blur: me.onPagingBlur
            };

        var node = me.node;
        var pagingData = me.node.pagingData;

        var currPage = pagingData.offset / pagingData.limit + 1;

        this.inSearch = node.inSearch;
        var hidden = this.inSearch
        pimcore.isTreeFiltering = false;

        inputListeners[Ext.supports.SpecialKeyDownRepeat ? 'keydown' : 'keypress'] = me.onPagingKeyDown;

        this.filterField = new Ext.form.field.Text({
            name: 'filter',
            width: 160,
            border: true,
            cls: "pimcore_pagingtoolbar_container_filter",
            fieldStyle: "padding: 0 10px 0 10px;",
            height: 18,
            value: node.filter ? node.filter : "",
            enableKeyEvents: true,
            hidden: !hidden,
            listeners: {
                "keydown": function (node, inputField, event) {
                    if (event.keyCode == 13) {
                        var store = this.store;
                        var proxy = store.getProxy();
                        this.currentFilter = this.filterField.getValue();


                        try {
                            store.load({
                                    node: node,
                                    params: {
                                        "filter": this.filterField.getValue(),
                                        "inSearch": this.inSearch
                                    }
                                }
                            );
                        } catch (e) {

                        }


                    }
                }.bind(this, node)
            }

        })
        ;

        var result = [this.filterField];

        this.overflow = new Ext.button.Button(
            {
                tooltip: t("there_are_more_items"),
                overflowText: t("there_are_more_items"),
                iconCls: "pimcore_icon_warning",
                disabled: false,
                scope: me,
                border: false,
                hidden: !node.overflow
            });


        this.filterButton = new Ext.button.Button(
            {
                itemId: 'filterButton',
                tooltip: t("filter"),
                overflowText: t("filter"),
                iconCls: Ext.baseCSSPrefix + 'tbar-page-filter',
                margin: '-1 2 3 2',
                handler: function () {
                    this.inSearch = 1;
                    this.cancelFilterButton.show();
                    this.filterButton.hide();
                    this.filterField.setValue("");
                    this.filterField.show();

                    this.filterField.focus();

                    this.first.hide();
                    this.prev.hide();
                    this.numberItem.hide();
                    this.spacer.hide();
                    this.afterItem.hide();
                    this.next.hide();
                    this.last.hide();
                }.bind(this),
                scope: me,
                hidden: this.inSearch
            });

        this.cancelFilterButton = new Ext.button.Button(
            {
                itemId: 'cancelFlterButton',
                tooltip: t("clear"),
                overflowText: t("clear"),
                margin: '-1 2 3 2',
                iconCls: Ext.baseCSSPrefix + 'tbar-page-cancel-filter',
                handler: function () {
                    this.doCancelSearch(node);

                }.bind(this),
                scope: me,
                hidden: !this.inSearch
            });

        this.afterItem = Ext.create('Ext.form.NumberField', {

            cls: Ext.baseCSSPrefix + 'tbar-page-number',
            value: Math.ceil(pagingData.total / pagingData.limit),
            hideTrigger: true,
            heightLabel: true,
            height: 18,
            width: 38,
            disabled: true,
            margin: '-1 2 3 2',
            hidden: hidden
        });


        this.numberItem = new Ext.form.field.Number({
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
            listeners: inputListeners,
            hidden: hidden
        });


        this.first = new Ext.button.Button(
            {
                itemId: 'first',
                tooltip: me.firstText,
                overflowText: me.firstText,
                iconCls: Ext.baseCSSPrefix + 'tbar-page-first',
                disabled: me.node.pagingData.offset == 0,
                handler: me.moveFirst,
                scope: me,
                border: false,
                hidden: hidden

            });


        this.prev = new Ext.button.Button({
            itemId: 'prev',
            tooltip: me.prevText,
            overflowText: me.prevText,
            iconCls: Ext.baseCSSPrefix + 'tbar-page-prev',
            disabled: me.node.pagingData.offset == 0,
            handler: me.movePrevious,
            scope: me,
            border: false,
            hidden: hidden
        });


        this.spacer = new Ext.toolbar.Spacer({
            xtype: "tbspacer",
            hidden: hidden
        });


        this.next = new Ext.button.Button({
            itemId: 'next',
            tooltip: me.nextText,
            overflowText: me.nextText,
            iconCls: Ext.baseCSSPrefix + 'tbar-page-next',
            disabled: (Math.ceil(me.node.pagingData.total / me.node.pagingData.limit) - 1) * me.node.pagingData.limit == me.node.pagingData.offset,
            handler: me.moveNext,
            scope: me,
            hidden: hidden
        });


        this.last = new Ext.button.Button({
            itemId: 'last',
            tooltip: me.lastText,
            overflowText: me.lastText,
            iconCls: Ext.baseCSSPrefix + 'tbar-page-last',
            disabled: (Math.ceil(me.node.pagingData.total / me.node.pagingData.limit) - 1) * me.node.pagingData.limit == me.node.pagingData.offset,
            handler: me.moveLast,
            scope: me,
            hidden: hidden
        });


        result.push(this.overflow);
        result.push(this.filterButton);
        result.push(this.cancelFilterButton);

        result.push(this.filterField);
        result.push(this.first);
        result.push(this.prev);
        result.push(this.numberItem);
        result.push(this.spacer);
        result.push(this.afterItem);
        result.push(this.next);
        result.push(this.last);


        return result;
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
        var inputItem = this.getInputItem(),
            curPage;
        if (inputItem) {
            //curPage = this.getPageData().currentPage;
            //inputItem.setValue(curPage);
        }
    },

    onPagingKeyDown: function(field, e) {
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
        proxy.setExtraParam("fromPaging", 1);
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


/**
 * Already fixed in 6.0.1
 * Inspired from https://www.sencha.com/forum/showthread.php?302760
 */
Ext.define('EXTJS-16385.event.publisher.Dom', {
    override: 'Ext.event.publisher.Dom',

    isEventBlocked: function(e) {
        var me = this,
            type = e.type,
            self = Ext.event.publisher.Dom,
            now = Ext.now();

        if (Ext.isGecko && e.type === 'click' && e.button === 2) {
            return true;
        }
    }
});



/**
 * Addresses FF 52 issues on touch devices (desktop + touch)
 * https://www.sencha.com/forum/showthread.php?336762-Examples-don-t-work-in-Firefox-52-touchscreen&p=1174857&viewfull=1#post1174857
 */
Ext.define('EXTJS_23846.Element', {
    override: 'Ext.dom.Element'
}, function(Element) {
    var supports = Ext.supports,
        proto = Element.prototype,
        eventMap = proto.eventMap,
        additiveEvents = proto.additiveEvents;

    if (Ext.os.is.Desktop && supports.TouchEvents && !supports.PointerEvents) {
        eventMap.touchstart = 'mousedown';
        eventMap.touchmove = 'mousemove';
        eventMap.touchend = 'mouseup';
        eventMap.touchcancel = 'mouseup';

        additiveEvents.mousedown = 'mousedown';
        additiveEvents.mousemove = 'mousemove';
        additiveEvents.mouseup = 'mouseup';
        additiveEvents.touchstart = 'touchstart';
        additiveEvents.touchmove = 'touchmove';
        additiveEvents.touchend = 'touchend';
        additiveEvents.touchcancel = 'touchcancel';

        additiveEvents.pointerdown = 'mousedown';
        additiveEvents.pointermove = 'mousemove';
        additiveEvents.pointerup = 'mouseup';
        additiveEvents.pointercancel = 'mouseup';
    }
});

Ext.define('EXTJS_23846.Gesture', {
    override: 'Ext.event.publisher.Gesture'
}, function(Gesture) {
    var me = Gesture.instance;

    if (Ext.supports.TouchEvents && !Ext.isWebKit && Ext.os.is.Desktop) {
        me.handledDomEvents.push('mousedown', 'mousemove', 'mouseup');
        me.registerEvents();
    }
});

/**
 * Fixes ID validation to include more characters as we need the colon for nested editable names
 *
 * See:
 *
 * - http://www.sencha.com/forum/showthread.php?296173-validIdRe-throwing-Invalid-Element-quot-id-quot-for-valid-ids-containing-colons
 * - https://github.com/JarvusInnovations/sencha-hotfixes/blob/ext/5/0/1/1255/overrides/dom/Element/ValidId.js
 */
Ext.define('EXTJS-17231.ext.dom.Element.validIdRe', {
    override: 'Ext.dom.Element',

    validIdRe: /^[a-z][a-z0-9\-_:.]*$/i,

    getObservableId: function () {
        return (this.observableId = this.callParent().replace(/([.:])/g, "\\$1"));
    }
});

// use only native scroll bar, the touch-scroller causes issues on hybrid touch devices when using with a mouse
// this ist fixed in ExtJS 6.2.0 since there's no TouchScroller anymore, see:
// http://docs.sencha.com/extjs/6.2.0/guides/whats_new/extjs_upgrade_guide.html
Ext.define('Ext.scroll.TouchScroller', {
    extend: 'Ext.scroll.DomScroller',
    alias: 'scroller.touch'
});
Ext.supports.touchScroll = 0;

/**
 * Fieldtype date is not able to save the correct value (before 1951) #1329
 *
 * When saving a date before the year 1951 (e.g. 01/01/1950) with the fieldtype "date" inside a object ...
 *
 * Expected behavior
 *
 * ... the timestamp saved into the database should contain the date 01/01/1950.
 *
 * Actual behavior
 *
 * ... but it actually contains the value of 01/01/2050.
 *
 *
 */
Ext.define('pimcore.Ext.form.field.Date', {
    override: 'Ext.form.field.Date',

    initValue: function() {
        var value = this.value;

        if (Ext.isString(value)) {
            this.value = this.rawToValue(value);
            this.rawDate = this.value;
            this.rawDateText = this.parseDate(this.value);
        }
        else {
            this.value = value || null;
            this.rawDate = this.value;
            this.rawDateText = this.value ? this.parseDate(this.value) : '';
        }

        this.callParent();
    },

    rawToValue: function(rawValue) {
        if (rawValue === this.rawDateText) {
            return this.rawDate;
        }
        return this.parseDate(rawValue) || rawValue || null;
    },

    setValue: function(v) {
        var utilDate = Ext.Date,
            rawDate;

        this.lastValue = this.rawDateText;
        this.lastDate = this.rawDate;
        if (Ext.isDate(v)) {
            rawDate = this.rawDate  = v;
            this.rawDateText = this.formatDate(v);
        }
        else {
            rawDate = this.rawDate = this.rawToValue(v);
            this.rawDateText = this.formatDate(v);
            if (rawDate === v) {
                rawDate = this.rawDate = null;
                this.rawDateText = '';
            }
        }
        if (rawDate && !utilDate.formatContainsHourInfo(this.format)) {
            this.rawDate = utilDate.clearTime(rawDate, true);
        }
        this.callParent(arguments);
    },

    checkChange: function() {
        var  newVal, oldVal, lastDate;

        if (!this.suspendCheckChange) {
            newVal = this.getRawValue();
            oldVal = this.lastValue;
            lastDate = this.lastDate;

            if (!this.destroyed && this.didValueChange(newVal, oldVal)) {
                this.rawDate = this.rawToValue(newVal);
                this.rawDateText = this.formatDate(newVal);
                this.lastValue = newVal;
                this.lastDate = this.rawDate;
                this.fireEvent('change', this, this.getValue(), lastDate);
                this.onChange(newVal, oldVal);
            }
        }
    },

    getSubmitValue: function() {
        var format = this.submitFormat || this.format,
            value = this.rawDate;

        return value ? Ext.Date.format(value, format) : '';
    },

    getValue: function() {
        return this.rawDate || null;
    },

    setRawValue: function(value) {
        this.callParent([value]);
        this.rawDate = Ext.isDate(value) ? value : this.rawToValue(value);
        this.rawDateText = this.formatDate(value);
    },

    onSelect: function(m, d) {
        this.setValue(d);
        this.rawDate = d;
        this.fireEvent('select', this, d);
        this.onTabOut(m);
    },

    onTabOut: function(picker) {
        this.inputEl.focus();
        this.collapse();
    },

    onExpand: function() {
        var value = this.rawDate;
        this.picker.setValue(Ext.isDate(value) ? value : null);
    }
});

//Fix - Date picker does not align to component in scrollable container and breaks view layout randomly.
Ext.override(Ext.picker.Date, {
        afterComponentLayout: function (width, height, oldWidth, oldHeight) {
        var field = this.pickerField;
        this.callParent([
            width,
            height,
            oldWidth,
            oldHeight
        ]);
        // Bound list may change size, so realign on layout
        // **if the field is an Ext.form.field.Picker which has alignPicker!**
        if (field && field.alignPicker) {
            field.alignPicker();
        }
    }
});


/**
 * A specialized {@link Ext.view.BoundListKeyNav} implementation for navigating in the quicksearch.
 * This is needed because in the default implementation the Crtl+A combination is disabled, but this is needed
 * for the purpose of the quicksearch
 */
Ext.define('Pimcore.view.BoundListKeyNav', {
    extend: 'Ext.view.BoundListKeyNav',

    alias: 'view.navigation.quicksearch.boundlist',

    initKeyNav: function(view) {
        var me = this,
            field = view.pickerField;

        // Add the regular KeyNav to the view.
        // Unless it's already been done (we may have to defer a call until the field is rendered.
        if (!me.keyNav) {
            me.callParent([view]);

            // Add ESC handling to the View's KeyMap to collapse the field
            me.keyNav.map.addBinding({
                key: Ext.event.Event.ESC,
                fn: me.onKeyEsc,
                scope: me
            });
        }

        // BoundLists must be able to function standalone with no bound field
        if (!field) {
            return;
        }

        if (!field.rendered) {
            field.on('render', Ext.Function.bind(me.initKeyNav, me, [view], 0), me, {single: true});
            return;
        }

        // BoundListKeyNav also listens for key events from the field to which it is bound.
        me.fieldKeyNav = new Ext.util.KeyNav({
            disabled: true,
            target: field.inputEl,
            forceKeyDown: true,
            up: me.onKeyUp,
            down: me.onKeyDown,
            right: me.onKeyRight,
            left: me.onKeyLeft,
            pageDown: me.onKeyPageDown,
            pageUp: me.onKeyPageUp,
            home: me.onKeyHome,
            end: me.onKeyEnd,
            tab: me.onKeyTab,
            space: me.onKeySpace,
            enter: me.onKeyEnter,
            // This object has to get its key processing in first.
            // Specifically, before any Editor's key hyandling.
            priority: 1001,
            scope: me
        });
    }
});


/**
 * EXTJS-17945
 * Ext.menu.Item changes the hash to # when clicking on Windows 10 Touch Screens
 * https://www.sencha.com/forum/showthread.php?309916
 */
Ext.define(null, {
    override: 'Ext.menu.Menu',

    onBoxReady: function () {
        var me = this,
            iconSeparatorCls = me._iconSeparatorCls,
            keyNav = me.focusableKeyNav;

        // Keyboard handling can be disabled, e.g. by the DatePicker menu
        // or the Date filter menu constructed by the Grid
        if (keyNav) {
            keyNav.map.processEventScope = me;
            keyNav.map.processEvent = function (e) {
                // ESC may be from input fields, and FocusableContainers ignore keys from
                // input fields. We do not want to ignore ESC. ESC hide menus.
                if (e.keyCode === e.ESC) {
                    e.target = this.el.dom;
                }

                return e;
            };

            // Handle ESC key
            keyNav.map.addBinding([{
                key: Ext.event.Event.ESC,
                handler: me.onEscapeKey,
                scope: me
            },
                // Handle character shortcuts
                {
                    key: /[\w]/,
                    handler: me.onShortcutKey,
                    scope: me,
                    shift: false,
                    ctrl: false,
                    alt: false
                }
            ]);
        } else {
            // Even when FocusableContainer key event processing is disabled,
            // we still need to handle the Escape key!
            me.escapeKeyNav = new Ext.util.KeyNav(me.el, {
                eventName: 'keydown',
                scope: me,
                esc: me.onEscapeKey
            });
        }

        me.callSuper(arguments);

        // TODO: Move this to a subTemplate When we support them in the future
        if (me.showSeparator) {
            me.iconSepEl = me.body.insertFirst({
                role: 'presentation',
                cls: iconSeparatorCls + ' ' + iconSeparatorCls + '-' + me.ui,
                html: ' '
            });
        }

        // Modern IE browsers have click events translated to PointerEvents, and b/c of this the
        // event isn't being canceled like it needs to be. So, we need to add an extra listener.
        // For devices that have touch support, the default click event may be a gesture that
        // runs asynchronously, so by the time we try and prevent it, it's already happened

        // we use Ext.supports.TouchEvents here, because we're overriding Ext.supports.Touch in edit/startup.js (Editmode)
        if (Ext.supports.TouchEvents || Ext.supports.MSPointerEvents || Ext.supports.PointerEvents) {
            me.el.on({
                scope: me,
                click: me.preventClick,
                translate: false
            });
        }

        me.mouseMonitor = me.el.monitorMouseLeave(100, me.onMouseLeave, me);
    }
});
