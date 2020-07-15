/**
 * A widget column is configured with a {@link #widget} config object which specifies an
 * {@link Ext.Component#cfg-xtype xtype} to indicate which type of Widget or Component belongs
 * in the cells of this column.
 *
 * When a widget cell is rendered, a {@link Ext.Widget Widget} or {@link Ext.Component Component}
 * of the specified type is rendered into that cell.
 * 
 * There are two ways of setting values in a cell widget.
 * 
 * The simplest way is to use data binding. To use column widget data binding, the widget must
 * either contain  a top-level bind statement, which will cause a
 * {@link Ext.app.ViewModel ViewModel} to be automatically injected into the widget. This ViewModel
 * will inherit data from any ViewModel that the grid is using, and it will also contain
 * two extra properties:
 *
 * - `record`: {@link Ext.data.Model Model}<br>The record which backs the grid row.
 * - `recordIndex`: {@link Number}<br>The index in the dataset of the record which backs
 *    the grid row.
 *
 * For complex widgets, where the widget may be a container that does not directly use any
 * data binding, but has items which do, the specification of a
 * {@link Ext.panel.Table#rowViewModel rowViewModel} type or configuration  is required on the grid.
 * This can simply be an empty object if grid widgets only require binding to the row record.
 * 
 * The deprecated way is to configure the column with a {@link #dataIndex}. The widget's
 * {@link Ext.Component#defaultBindProperty defaultBindProperty} will be set using the
 * specified field from the associated record.
 *
 * In the example below we are monitoring the throughput of electricity substations. The capacity
 * being used as a proportion of the maximum rated capacity is displayed as a progress bar.
 * As new data arrives and the instantaneous usage value is updated, the `capacityUsed`
 * field updates itself, and the record's change is broadcast to all bindings.
 * The {@link Ext.Progress Progress Bar Widget}'s
 * {@link Ext.ProgressBarWidget#defaultBindProperty defaultBindProperty} (which is
 * "value") is set to the calculated `capacityUsed`.
 *
 *     @example
 *     var grid = new Ext.grid.Panel({
 *         title: 'Substation power monitor',
 *         width: 600,
 *         viewConfig: {
 *             enableTextSelection: false,
 *             markDirty: false
 *         },
 *         columns: [{
 *             text: 'Id',
 *             dataIndex: 'id',
 *             width: 120
 *         }, {
 *             text: 'Rating',
 *             dataIndex: 'maxCapacity',
 *             width: 80
 *         }, {
 *             text: 'Avg.',
 *             dataIndex: 'avg',
 *             width: 85,
 *             formatter: 'number("0.00")'
 *         }, {
 *             text: 'Max',
 *             dataIndex: 'max',
 *             width: 80
 *         }, {
 *             text: 'Instant',
 *             dataIndex: 'instant',
 *             width: 80
 *         }, {
 *             text: '%Capacity',
 *             width: 150,
 *
 *             // This is our Widget column
 *             xtype: 'widgetcolumn',
 *
 *             // This is the widget definition for each cell.
 *             // The Progress widget class's defaultBindProperty is 'value'
 *             // so its "value" setting is taken from the ViewModel's record "capacityUsed" field
 *             // Note that a row ViewModel will automatically be injected due to the existence of 
 *             // the bind property in the widget configuration.
 *             widget: {
 *                 xtype: 'progressbarwidget',
 *                 bind: '{record.capacityUsed}',
 *                 textTpl: [
 *                     '{percent:number("0")}% capacity'
 *                 ]
 *             }
 *         }],
 *         renderTo: document.body,
 *         disableSelection: true,
 *         store: {
 *            fields: [{
 *                name: 'id',
 *                type: 'string'
 *            }, {
 *                name: 'maxCapacity',
 *                type: 'int'
 *            }, {
 *                name: 'avg',
 *                type: 'int',
 *                calculate: function(data) {
 *                    // Make this depend upon the instant field being set which sets
 *                    // the sampleCount and total.
 *                    // Use subscript format to access the other pseudo fields
 *                    //  which are set by the instant field's converter
 *                    return data.instant && data['total'] / data['sampleCount'];
 *                }
 *            }, {
 *                name: 'max',
 *                type: 'int',
 *                calculate: function(data) {
 *                    // This will be seen to depend on the "instant" field.
 *                    // Use subscript format to access this field's current value
 *                    //  to avoid circular dependency error.
 *                    return (data['max'] || 0) < data.instant ? data.instant : data['max'];
 *                }
 *            }, {
 *                name: 'instant',
 *                type: 'int',
 *
 *                // Upon every update of instantaneous power throughput,
 *                // update the sample count and total so that the max field can calculate itself
 *                convert: function(value, rec) {
 *                    rec.data.sampleCount = (rec.data.sampleCount || 0) + 1;
 *                    rec.data.total = (rec.data.total || 0) + value;
 *                    return value;
 *                },
 *               depends: []
 *            }, {
 *                name: 'capacityUsed',
 *                calculate: function(data) {
 *                    return data.instant / data.maxCapacity;
 *                }
 *            }],
 *            data: [{
 *                id: 'Substation A',
 *                maxCapacity: 1000,
 *                avg: 770,
 *                max: 950,
 *                instant: 685
 *            }, {
 *                id: 'Substation B',
 *                maxCapacity: 1000,
 *                avg: 819,
 *                max: 992,
 *                instant: 749
 *            }, {
 *                id: 'Substation C',
 *                maxCapacity: 1000,
 *                avg: 588,
 *                  max: 936,
 *                instant: 833
 *            }, {
 *                id: 'Substation D',
 *                maxCapacity: 1000,
 *                avg: 639,
 *                max: 917,
 *                instant: 825
 *            }]
 *        }
 *     });
 *
 *     // Fake data updating...
 *     // Change one record per second to a random power value
 *     Ext.interval(function() {
 *         var recIdx = Ext.Number.randomInt(0, 3),
 *             newPowerReading = Ext.Number.randomInt(500, 1000);
 *
 *         grid.store.getAt(recIdx).set('instant', newPowerReading);
 *     }, 1000);
 *
 * @since 5.0.0
 */
Ext.define('Ext.grid.column.Widget', {
    extend: 'Ext.grid.column.Column',
    xtype: 'widgetcolumn',

    mixins: ['Ext.mixin.StyleCacher'],

    config: {
        /**
         * @cfg {Object} defaultWidgetUI
         * A map of xtype to {@link Ext.Component#ui} names to use when using Components
         * in this column.
         *
         * Currently {@link Ext.Button Button} and all subclasses of
         * {@link Ext.form.field.Text TextField} default to using `ui: "default"`
         * when in a WidgetColumn except for in the "classic" theme, when they use ui "grid-cell".
         */
        defaultWidgetUI: {}
    },

    /**
     * @cfg ignoreExport
     * @inheritdoc
     */
    ignoreExport: true,

    /**
     * @cfg sortable
     * @inheritdoc
     */
    sortable: false,

    /**
     * @cfg {Object} renderer
     * @hide
     */

    /**
     * @cfg {Object} scope
     * @hide
     */

    /**
     * @cfg {Object} widget
     * A config object containing an {@link Ext.Component#cfg-xtype xtype}.
     *
     * This is used to create the widgets or components which are rendered into the cells
     * of this column.
     *
     * The rendered component has a {@link Ext.app.ViewModel ViewModel} injected which inherits
     * from any ViewModel that the grid is using, and contains two extra properties:
     *
     * - `record`: {@link Ext.data.Model Model}<br>The record which backs the grid row.
     * - `recordIndex`: {@link Number}<br>The index in the dataset of the record which backs
     * the grid row.
     *
     * The widget configuration may contain a {@link #cfg-bind} config which uses
     * the ViewModel's data.
     *
     * The derecated way of obtaining data from the record is still supported if the widget
     * does *not* use a {@link #cfg-bind} config.
     *
     * This column's {@link #dataIndex} is used to update the widget/component's
     * {@link Ext.Component#defaultBindProperty defaultBindProperty}.
     *
     * The widget will be decorated with 2 methods:
     * {@link #method-getWidgetRecord} - Returns the {@link Ext.data.Model record} the widget
     * is associated with.
     * {@link #method-getWidgetColumn} - Returns the {@link Ext.grid.column.Widget column}
     * the widget was associated with.
     */

    /**
     * @cfg {Function/String} onWidgetAttach
     * A function that will be called when a widget is attached to a record. This may be useful for
     * doing any post-processing.
     * 
     *     Ext.create({
     *         xtype: 'grid',
     *         title: 'Student progress report',
     *         width: 250,
     *         renderTo: Ext.getBody(),
     *         disableSelection: true,
     *         store: {
     *             fields: ['name', 'isHonorStudent'],
     *             data: [{
     *                 name: 'Finn',
     *                 isHonorStudent: true
     *             }, {
     *                 name: 'Jake',
     *                 isHonorStudent: false
     *             }]
     *         },
     *         columns: [{
     *             text: 'Name',
     *             dataIndex: 'name',
     *             flex: 1
     *         }, {
     *             xtype: 'widgetcolumn',
     *             text: 'Honor Roll',
     *             dataIndex: 'isHonorStudent',
     *             width: 150,
     *             widget: {
     *                 xtype: 'button',
     *                 handler: function() {
     *                     // print certificate handler
     *                 }
     *             },
     *             // called when the widget is initially instantiated
     *             // on the widget column
     *             onWidgetAttach: function(col, widget, rec) {
     *                 widget.setText('Print Certificate');
     *                 widget.setDisabled(!rec.get('isHonorStudent'));
     *             }
     *         }]
     *     });
     * 
     * @param {Ext.grid.column.Column} column The column.
     * @param {Ext.Component/Ext.Widget} widget The {@link #widget} rendered to each cell.
     * @param {Ext.data.Model} record The record used with the current widget (cell).
     * @controllable
     */
    onWidgetAttach: null,

    preventUpdate: true,

    innerCls: Ext.baseCSSPrefix + 'grid-widgetcolumn-cell-inner',

    /**
     * @cfg {Boolean} [stopSelection=true]
     * Prevent grid selection upon click on the widget.
     */
    stopSelection: true,

    initComponent: function() {
        var me = this,
            widget;

        me.callParent(arguments);

        widget = me.widget;

        //<debug>
        if (!widget || widget.isComponent) {
            Ext.raise('column.Widget requires a widget configuration.');
        }
        //</debug>

        me.widget = widget = Ext.apply({}, widget);

        // Apply the default UI for the xtype which is going to feature in this column.
        if (!widget.ui) {
            widget.ui = me.getDefaultWidgetUI()[widget.xtype] || 'default';
        }

        me.isFixedSize = Ext.isNumber(widget.width);
    },

    /**
     * @method getWidgetRecord
     * getWidgetRecord is a method that decorates every widget.
     * Returns the {@link Ext.data.Model record} the widget is associated with.
     * @return {Ext.data.Model}
     */

    /**
     * @method getWidgetColumn
     * getWidgetColumn is a method that decorates every widget.
     * Returns the {@link Ext.grid.column.Widget column} the widget was associated with.
     * @return {Ext.grid.column.Widget}
     */

    processEvent: function(type, view, cell, recordIndex, cellIndex, e, record, row) {
        var target;

        if (this.stopSelection && type === 'click') {
            // Grab the target that matches the cell inner selector. If we have a target, then,
            // that means we either clicked on the inner part or the widget inside us. If 
            // target === e.target, then it was on the cell, so it's ok. Otherwise, inside so
            // prevent the selection from happening
            target = e.getTarget(view.innerSelector);

            if (target && target !== e.target) {
                e.stopSelection = true;
            }
        }
    },

    beforeRender: function() {
        var me = this,
            tdCls = me.tdCls,
            widget;

        // Need an instantiated example to retrieve the tdCls that it needs
        widget = Ext.widget(me.widget);

        // If the widget is not using binding, but we have a dataIndex, and there's
        // a defaultBindProperty to push it into, set flag to indicate to do that.
        me.bindDataIndex = me.dataIndex && widget.defaultBindProperty && !widget.bind;

        tdCls = tdCls ? tdCls + ' ' : '';
        me.tdCls = tdCls + widget.getTdCls();
        me.setupViewListeners(me.getView());
        me.callParent();

        widget.destroy();
    },

    afterRender: function() {
        var view = this.getView();

        this.callParent();

        // View already ready, means we were added later so go and set up our widgets,
        // but if the grid is reconfiguring, then the column will be rendered & the view
        // will be ready, so wait until the reconfigure forces a refresh
        if (view && view.viewReady && !view.ownerGrid.reconfiguring) {
            this.onViewRefresh(view, view.getViewRange());
        }
    },

    // Cell must be left blank
    /**
     * @method defaultRenderer
     * @inheritdoc
     * @localdoc **Important:** Cell must be left blank
     */
    defaultRenderer: Ext.emptyFn,

    updater: function(cell, value, record) {
        this.updateWidget(record);
    },

    onCellsResized: function(newWidth) {
        var me = this,
            liveWidgets = me.ownerGrid.getManagedWidgets(me.getId()),
            len = liveWidgets.length,
            view = me.getView(),
            i, cell;

        if (!me.isFixedSize && me.rendered && view && view.viewReady) {
            cell = view.getEl().down(me.getCellInnerSelector());

            if (cell) {
                // Subtract innerCell padding width
                newWidth -= parseInt(me.getCachedStyle(cell, 'padding-left'), 10) +
                            parseInt(me.getCachedStyle(cell, 'padding-right'), 10);

                for (i = 0; i < len; ++i) {
                    // Ensure these are treated as the top of the modified tree.
                    // If not within a layout run, this will work fine.
                    // If within a layout run, Component#updateLayout will
                    // just ask its runningLayoutContext to invalidate it.
                    liveWidgets[i].ownerLayout = null;
                    liveWidgets[i].setWidth(newWidth);
                    liveWidgets[i].ownerLayout = view.componentLayout;
                }
            }
        }
    },

    onAdded: function() {
        var me = this,
            view;

        me.callParent(arguments);

        me.ownerGrid = me.up('tablepanel').ownerGrid;

        // If the grid is lockable we should mark this column with variableRowHeight,
        // as widgets can cause rows to be taller and this config will force them
        // to be synced on every layout cycle.
        if (me.ownerGrid.lockable) {
            me.variableRowHeight = true;
        }

        view = me.getView();

        // If we are being added to a rendered HeaderContainer
        if (view) {
            me.setupViewListeners(view);
        }
    },

    onRemoved: function(isDestroying) {
        var viewListeners = this.viewListeners;

        if (viewListeners) {
            Ext.destroy(viewListeners);
        }

        if (isDestroying) {
            this.ownerGrid.destroyManagedWidgets(this.getId());
        }

        this.callParent(arguments);
    },

    doDestroy: function() {
        this.ownerGrid.destroyManagedWidgets(this.getId());
        this.callParent();
    },

    privates: {
        getWidget: function(record) {
            var me = this,
                result = null;

            if (record) {
                result =
                    me.ownerGrid.createManagedWidget(me.getView(), me.getId(), me.widget, record);

                result.getWidgetRecord = me.widgetRecordDecorator;
                result.getWidgetColumn = me.widgetColumnDecorator;
                result.measurer = me;

                // The ownerCmp of the widget is the encapsulating view, which means
                // it will be considered as a layout child, but it isn't really, we always need
                // the layout on the component to run if asked.
                result.isLayoutChild = me.returnFalse;
            }

            return result;
        },

        onItemAdd: function(records) {
            var me = this,
                view = me.getView(),
                hasAttach = !!me.onWidgetAttach,
                dataIndex = me.dataIndex,
                isFixedSize = me.isFixedSize,
                len = records.length,
                i, record, cell, widget, el, focusEl, width;

            // Loop through all records added, ensuring that our corresponding cell in each item
            // has a Widget of the correct type in it, and is updated with the correct value
            // from the record.
            if (me.isVisible(true)) {
                for (i = 0; i < len; i++) {
                    record = records[i];

                    if (record.isNonData) {
                        continue;
                    }

                    cell = view.getCell(record, me);

                    // May be a placeholder with no data row
                    if (cell) {
                        cell = cell.firstChild;

                        if (!isFixedSize && !width && me.lastBox) {
                            width = me.lastBox.width -
                                    parseInt(me.getCachedStyle(cell, 'padding-left'), 10) -
                                    parseInt(me.getCachedStyle(cell, 'padding-right'), 10);
                        }

                        widget = me.getWidget(record);
                        widget.$widgetColumn = me;
                        widget.$widgetRecord = record;

                        // Render/move a widget into the new row
                        Ext.fly(cell).empty();

                        // Call the appropriate setter with this column's data field
                        if (widget.defaultBindProperty && dataIndex) {
                            widget.setConfig(widget.defaultBindProperty, record.get(dataIndex));
                        }

                        el = widget.el || widget.element;

                        if (el) {
                            cell.appendChild(el.dom);

                            if (!isFixedSize) {
                                widget.setWidth(width);
                            }

                            widget.reattachToBody();
                        }
                        else {
                            if (!isFixedSize) {
                                // Must have a width so that the initial layout works
                                widget.width = width || 100;
                            }

                            widget.render(cell);
                        }

                        // We have to run the callback *after* reattaching the Widget
                        // back to the document body. Otherwise widget's layout may fail
                        // because there are no dimensions to measure when the callback is fired!
                        if (hasAttach) {
                            Ext.callback(me.onWidgetAttach, me.scope, [me, widget, record], 0, me);
                        }

                        // If the widget has a focusEl, ensure that its tabbability status
                        // is synched with the view's navigable/actionable state.
                        focusEl = widget.getFocusEl();

                        if (focusEl) {
                            if (view.actionableMode) {
                                if (!focusEl.isTabbable()) {
                                    focusEl.restoreTabbableState();
                                }
                            }
                            else {
                                if (focusEl.isTabbable()) {
                                    focusEl.saveTabbableState();
                                }
                            }
                        }
                    }
                }
            }
            else {
                view.refreshNeeded = true;
            }
        },

        onItemUpdate: function(record, recordIndex, oldItemDom) {
            this.updateWidget(record);
        },

        onLock: function(header) {
            this.callParent([header]);
            this.resetView();
        },

        onUnlock: function(header) {
            this.callParent([header]);
            this.resetView();
        },

        onViewRefresh: function(view, records) {
            Ext.suspendLayouts();
            this.onItemAdd(records);
            Ext.resumeLayouts(true);
        },

        resetView: function() {
            var me = this,
                viewListeners = me.viewListeners;

            if (viewListeners) {
                Ext.destroy(viewListeners);
            }

            me.setupViewListeners(me.getView());

            me.ownerGrid.handleWidgetViewChange(me.getView(), me.getId());
        },

        returnFalse: function() {
            return false;
        },

        setupViewListeners: function(view) {
            var me = this,
                listeners = {
                    refresh: me.onViewRefresh,
                    itemadd: me.onItemAdd,
                    scope: me,
                    destroyable: true
                };

            // If we are set up to push a dataIndex property into the widget's defaultBindProperty
            // then we must react to itemupdate events to keep the widget fresh.
            if (me.bindDataIndex) {
                listeners.itemUpdate = me.onItemUpdate;
            }

            me.viewListeners = view.on(listeners);
        },

        updateWidget: function(record) {
            var dataIndex = this.dataIndex,
                widget;

            if (this.rendered && this.bindDataIndex) {
                widget = this.getWidget(record);

                // Call the appropriate setter with this column's data field
                // unless it's using binding
                if (widget) {
                    widget.setConfig(widget.defaultBindProperty, record.get(dataIndex));
                }
            }
        },

        widgetRecordDecorator: function() {
            return this.$widgetRecord;
        },

        widgetColumnDecorator: function() {
            return this.$widgetColumn;
        }
    }
});
