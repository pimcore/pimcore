/**
 * Plugin (ptype = 'rowwidget') that adds the ability to second row body in a grid
 * which expands/contracts.
 *
 * The expand/contract behavior is configurable to react on clicking of the column,
 * double click of the row, and/or hitting enter while a row is selected.
 *
 * The expansion row may contain a {@link #cfg-widget} which is primed with the record
 * of the corresponding grid row. The widget's
 * {@link Ext.Component#cfg-defaultBindProperty defaultBindProperty} property is set to the record.
 */
Ext.define('Ext.grid.plugin.RowWidget', {
    extend: 'Ext.grid.plugin.RowExpander',
    alias: 'plugin.rowwidget',

    mixins: [
        'Ext.mixin.Identifiable',
        'Ext.mixin.StyleCacher'
    ],

    lockableScope: 'top',

    config: {
        /**
         * @cfg {Object} defaultWidgetUI
         * A map of xtype to {@link Ext.Component#ui} names to use when using Components
         * in the expansion row.
         */
        defaultWidgetUI: {}
    },

    /**
     * @cfg {Object} widget
     * A config object containing an {@link Ext.Component#cfg-xtype xtype}.
     *
     * This is used to create the widgets or components which are rendered into the expansion row.
     *
     * The associated grid row's record is used to update the widget/component's
     * {@link Ext.Component#defaultBindProperty defaultBindProperty}.
     *
     * Note that if this plugin is applied to a lockable grid, the widget applies to the normal
     * (unlocked) side.
     * See {@link #lockedWidget}
     *
     */
    widget: null,

    /**
     * @cfg {Object} [lockedWidget]
     * A config object containing an {@link Ext.Component#cfg-xtype xtype}.
     *
     * This is used to create the widgets or components which are rendered into the expansion row
     * *on the locked side of a lockable grid*.
     */
    lockedWidget: null,

    addCollapsedCls: {
        fn: function(out, values, parent) {
            var me = this.rowExpander;

            if (!me.recordsExpanded[values.record.internalId]) {
                values.itemClasses.push(me.rowCollapsedCls);
            }

            this.nextTpl.applyOut(values, out, parent);
        },

        // We need a high priority to get in ahead of the outerRowTpl
        // so we can setup row data
        priority: 20000
    },

    setCmp: function(grid) {
        var me = this,
            features,
            widget;

        // Generate a unique class name so we can identify our row element.
        me.rowIdCls = Ext.id(null, Ext.baseCSSPrefix + 'rowwidget-');

        // Keep track of which record internalIds are expanded.
        me.recordsExpanded = {};

        Ext.plugin.Abstract.prototype.setCmp.apply(me, arguments);

        widget = me.widget;

        //<debug>
        if (!widget || widget.isComponent) {
            Ext.raise('RowWidget requires a widget configuration.');
        }

        //</debug>
        me.widget = widget = Ext.apply({}, widget);

        // Apply the default UI for the xtype which is going to feature
        // in the normal side's expansion row.
        if (!widget.ui) {
            widget.ui = me.getDefaultWidgetUI()[widget.xtype] || 'default';
        }

        // If the grid is a lockable assembly, we have to track locked widgets.
        if (grid.enableLocking && me.lockedWidget) {
            me.lockedWidget = widget = Ext.apply({}, me.lockedWidget);

            // Apply the default UI for the xtype which is going to feature
            // in the locked side's expansion row.
            if (!widget.ui) {
                widget.ui = me.getDefaultWidgetUI()[widget.xtype] || 'default';
            }
        }

        features = me.getFeatureConfig(grid);

        if (grid.features) {
            grid.features = Ext.Array.push(features, grid.features);
        }
        else {
            grid.features = features;
        }
        // NOTE: features have to be added before init (before Table.initComponent)
    },

    /**
     * @protected
     * @return {Array} And array of Features or Feature config objects.
     * Returns the array of Feature configurations needed to make the RowWidget work.
     * May be overridden in a subclass to modify the returned array.
     */
    getFeatureConfig: function(grid) {
        var me = this,
            features = [],
            featuresCfg = {
                ftype: 'rowbody',
                rowExpander: me,
                doSync: false,
                rowIdCls: me.rowIdCls,
                bodyBefore: me.bodyBefore,
                recordsExpanded: me.recordsExpanded,
                rowBodyHiddenCls: me.rowBodyHiddenCls,
                rowCollapsedCls: me.rowCollapsedCls,
                setupRowData: me.setupRowData,
                setup: me.setup,

                // Do not relay click events into the client grid's row
                onClick: Ext.emptyFn
            };

        features.push(Ext.apply({
            lockableScope: 'normal'
        }, featuresCfg));

        // Locked side will need a copy to keep the two DOM structures symmetrical.
        // A lockedWidget config is available to create content in locked side.
        // The enableLocking flag is set early in Ext.panel.Table#initComponent
        // if any columns are locked.
        if (grid.enableLocking) {
            features.push(Ext.apply({
                lockableScope: 'locked'
            }, featuresCfg));
        }

        return features;
    },

    setupRowData: function(record, rowIndex, rowValues) {
        var me = this.rowExpander;

        me.rowBodyFeature = this;
        rowValues.rowBodyCls = me.recordsExpanded[record.internalId] ? '' : me.rowBodyHiddenCls;
    },

    bindView: function(view) {
        var me = this;

        me.viewListeners = view.on({
            refresh: me.onViewRefresh,
            itemadd: me.onItemAdd,
            scope: me,
            destroyable: true
        });
        Ext.override(view, me.viewOverrides);
    },

    destroy: function() {
        var me = this,
            id = me.getId();

        me.viewListeners.destroy();

        if (me.grid.lockable) {
            me.grid.destroyManagedWidgets(id + '-' + me.lockedView.getId());
            me.grid.destroyManagedWidgets(id + '-' + me.normalView.getId());
        }
        else {
            me.grid.destroyManagedWidgets(id + '-' + me.view.getId());
        }

        me.callParent();
    },

    privates: {
        viewOverrides: {
            handleEvent: function(e) {
                // An override applied to the client view so that it ignores events
                // from within the expander row
                // Ignore all events from within our rowwidget
                if (e.getTarget('.' + this.rowExpander.rowIdCls, this.body)) {
                    return;
                }

                this.callParent([e]);
            },

            onFocusEnter: function(e) {
                // An override applied to the client view so that it ignores
                // focus moving into the expander row
                if (e.event.getTarget('.' + this.rowExpander.rowIdCls, this.body)) {
                    return;
                }

                this.callParent([e]);
            },

            toggleChildrenTabbability: function(enableTabbing) {
                // An override applied to the client view so that it does not interfere
                // with tabbability of elements within the expander rows.
                var focusEl = this.getTargetEl(),
                    rows = this.all,
                    restoreOptions = { skipSelf: true },
                    saveOptions = { skipSelf: true, includeSaved: false },
                    i;

                for (i = rows.startIndex; i <= rows.endIndex; i++) {
                    // Extract the data row from each row.
                    // We do not interfere with tabbing in the the expander row.
                    focusEl = Ext.fly(this.getRow(rows.item(i)));

                    if (!focusEl) {
                        continue;
                    }

                    if (enableTabbing) {
                        focusEl.restoreTabbableState(restoreOptions);
                    }
                    else {
                        // Do NOT includeSaved
                        // Once an item has had tabbability saved, do not increment its save level
                        focusEl.saveTabbableState(saveOptions);
                    }
                }
            }
        },

        destroyLiveWidget: function(recId, widget) {
            widget.destroy();
        },

        destroyFreeWidget: function(widget) {
            widget.destroy();
        },

        onItemAdd: function(newRecords, startIndex, newItems, view) {
            var me = this,
                len = newItems.length,
                i,
                record,
                ownerLockable = me.grid.lockable;

            // May be multiple widgets being layed out here
            Ext.suspendLayouts();

            for (i = 0; i < len; i++) {
                record = newRecords[i];

                if (!record.isNonData && me.recordsExpanded[record.internalId]) {
                    // If any added items are expanded, we will need a syncRowHeights
                    // call on next layout
                    if (ownerLockable) {
                        me.grid.syncRowHeightOnNextLayout = true;
                    }

                    me.addWidget(view, record);
                }
            }

            Ext.resumeLayouts(true);
        },

        onViewRefresh: function(view, records) {
            var me = this,
                rows = view.all,
                itemIndex, recordIndex;

            Ext.suspendLayouts();

            // eslint-disable-next-line max-len
            for (itemIndex = rows.startIndex, recordIndex = 0; itemIndex <= rows.endIndex; itemIndex++, recordIndex++) {
                if (me.recordsExpanded[records[recordIndex].internalId]) {
                    me.addWidget(view, records[recordIndex]);
                }
            }

            Ext.resumeLayouts(true);
        },

        returnFalse: function() {
            return false;
        },

        /**
         * Returns if possible the widget currently associated with the passed record
         * within the passed view.
         *
         * Note that if the record is not currently in the rendered block, *or*,
         * it has never been expanded then there will not be a widget associated
         * with that `record/view` context.
         * @param {type} view The view for which to return the widget
         * @param {type} record The record for which to return the widget
         * @return {me.lockedLiveWidgets/me.liveWidgets}
         */
        getWidget: function(view, record) {
            var me = this,
                result,
                widget;

            if (record) {
                widget = me.grid.lockable && view === me.lockedView ? me.lockedWidget : me.widget;

                if (widget) {
                    result = me.grid.createManagedWidget(
                        view, me.getId() + '-' + view.getId(), widget, record
                    );

                    result.measurer = me;
                    result.ownerLayout = view.componentLayout;
                }
            }

            return result;
        },

        addWidget: function(view, record) {
            var me = this,
                target,
                width,
                widget,
                hasAttach = !!me.onWidgetAttach,
                isFixedSize = me.isFixedSize,
                el;

            // If the record is non data (placeholder), or not expanded, return
            if (record.isNonData || !me.recordsExpanded[record.internalId]) {
                return;
            }

            target = Ext.fly(view.getNode(record).querySelector(me.rowBodyFeature.innerSelector));
            width = target.getWidth(true) - target.getPadding('lr');
            widget = me.getWidget(view, record);

            // Might be no widget if we are handling a lockable grid
            // and only one side has a widget definition.
            if (widget) {
                if (hasAttach) {
                    Ext.callback(me.onWidgetAttach, me.scope, [me, widget, record], 0, me);
                }

                el = widget.el || widget.element;

                if (el) {
                    target.dom.appendChild(el.dom);

                    if (!isFixedSize && widget.width !== width) {
                        widget.setWidth(width);
                    }
                    else {
                        widget.updateLayout();
                    }

                    widget.reattachToBody();
                }
                else {
                    if (!isFixedSize) {
                        widget.width = width;
                    }

                    widget.render(target);
                }

                widget.updateLayout();
            }

            return widget;
        },

        toggleRow: function(rowIdx, record) {
            var me = this,
                // If we are handling a lockable assembly,
                // handle the normal view first
                view = me.normalView || me.view,
                rowNode = view.getNode(rowIdx),
                normalRow = Ext.fly(rowNode),
                lockedRow,
                nextBd = normalRow.down(me.rowBodyTrSelector, true),
                wasCollapsed = normalRow.hasCls(me.rowCollapsedCls),
                addOrRemoveCls = wasCollapsed ? 'removeCls' : 'addCls',
                ownerLockable = me.grid.lockable && me.grid,
                widget, vm;

            normalRow[addOrRemoveCls](me.rowCollapsedCls);
            Ext.fly(nextBd)[addOrRemoveCls](me.rowBodyHiddenCls);

            // All layouts must be coalesced.
            // Particularly important for locking assemblies which need
            // to sync row height on the next layout.
            Ext.suspendLayouts();

            // We're expanding
            if (wasCollapsed) {
                me.recordsExpanded[record.internalId] = true;
                widget = me.addWidget(view, record);
                vm = widget.lookupViewModel();
            }
            else {
                delete me.recordsExpanded[record.internalId];
                widget = me.getWidget(view, record);
            }

            // Sync the collapsed/hidden classes on the locked side
            if (ownerLockable) {

                // Only attempt to toggle lockable side if it is visible.
                if (ownerLockable.lockedGrid.isVisible()) {

                    view = me.lockedView;

                    // Process the locked side.
                    lockedRow = Ext.fly(view.getNode(rowIdx));

                    // Just because the grid is locked, doesn't mean we'll necessarily
                    // have a locked row.
                    if (lockedRow) {
                        lockedRow[addOrRemoveCls](me.rowCollapsedCls);

                        // If there is a template for expander content in the locked side,
                        // toggle that side too
                        nextBd = lockedRow.down(me.rowBodyTrSelector, true);
                        Ext.fly(nextBd)[addOrRemoveCls](me.rowBodyHiddenCls);

                        // Pass an array if we're in a lockable assembly.
                        if (wasCollapsed && me.lockedWidget) {
                            widget = [widget, me.addWidget(view, record)];
                        }
                        else {
                            widget = [widget, me.getWidget(view, record)];
                        }

                    }

                    // We're going to need a layout run to synchronize row heights
                    ownerLockable.syncRowHeightOnNextLayout = true;
                }
            }

            me.view.fireEvent(wasCollapsed ? 'expandbody' : 'collapsebody', rowNode, record,
                              nextBd, widget);

            view.updateLayout();

            // Before layouts are resumed, if we have *expanded* the widget row,
            // then ensure bound data is flushed into the widget so that it assumes its final size.
            if (vm) {
                vm.notify();
            }

            Ext.resumeLayouts(true);

            if (me.scrollIntoViewOnExpand && wasCollapsed) {
                me.grid.ensureVisible(rowIdx);
            }
        }
    }
});
