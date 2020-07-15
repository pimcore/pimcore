/**
 * @private
 * This class encapsulates a row of managed Widgets/Components when a WidgetColumn, or
 * RowWidget plugin is used in a grid.
 *
 * The instances are recycled, and this class holds instances which are derendered so that they can
 * be moved back into newly rendered rows.
 *
 * Developers should not use this class.
 *
 */
Ext.define('Ext.grid.RowContext', {
    constructor: function(config) {
        Ext.apply(this, config);
        this.widgets = {};
    },

    setRecord: function(record, recordIndex) {
        var viewModel = this.viewModel;

        this.record = record;
        this.recordIndex = recordIndex;

        if (viewModel) {
            viewModel.set('record', record);
            viewModel.set('recordIndex', recordIndex);
        }
    },

    free: function(view) {
        var me = this,
            widgets = me.widgets,
            widgetId,
            widget,
            focusEl,
            viewModel = me.viewModel;

        me.record = null;

        if (viewModel) {
            viewModel.set('record');
            viewModel.set('recordIndex');
        }

        // All the widgets this RowContext manages must be blurred
        // and moved into the detached body to save them from garbage collection.
        for (widgetId in widgets) {
            widget = widgets[widgetId];

            // if the view is being refresh only remove widgets owned by this view
            if (view && view.refreshing && !view.el.contains(widget.el)) {
                continue;
            }

            // Focusables in a grid must not be tabbable by default when they get put back in.
            focusEl = widget.getFocusEl();

            if (focusEl) {
                // Widgets are reused so we must reset their tabbable state
                // regardless of their visibility.
                // For example, when removing rows in IE8 we're attaching
                // the nodes to a document-fragment which itself is invisible,
                // so isTabbable() returns false. Next time when we're reusing
                // this widget it will be attached to the document with its
                // tabbable state unreset, which might lead to undesired results.
                if (focusEl.isTabbable(true)) {
                    focusEl.saveTabbableState({
                        includeHidden: true
                    });
                }

                // Some browsers do not deliver a focus change upon DOM removal.
                // Force the issue here.
                focusEl.blur();
            }

            widget.detachFromBody();
        }
    },

    getWidget: function(view, ownerId, widgetCfg) {
        var me = this,
            widgets = me.widgets || (me.widgets = {}),
            ownerGrid = me.ownerGrid,
            rowViewModel = ownerGrid.rowViewModel,
            rowVM = me.viewModel,
            result;

        // Only spin up an attached ViewModel when we instantiate our first managed Widget
        // which uses binding.
        if ((widgetCfg.bind || rowViewModel) && !rowVM) {
            if (typeof rowViewModel === 'string') {
                rowViewModel = {
                    type: rowViewModel
                };
            }

            me.viewModel = rowVM = Ext.Factory.viewModel(Ext.merge({
                parent: ownerGrid.getRowContextViewModelParent(),
                data: {
                    record: me.record,
                    recordIndex: me.recordIndex
                }
            }, rowViewModel));
        }

        if (!(result = widgets[ownerId])) {
            result = widgets[ownerId] = Ext.widget(Ext.apply({
                ownerCmp: view,
                _rowContext: me,
                // This will spin up a VM on the grid if we don't have one that
                // will be shared across all instances. If we don't have a rowVM
                // or a viewmodel on the created object, we don't  need it, but
                // we can't really tell until we create an instance.
                $vmParent: rowVM || ownerGrid.getRowContextViewModelParent(),
                initInheritedState: me.initInheritedStateHook,
                lookupViewModel: me.lookupViewModelHook
            }, widgetCfg));

            result.$fromLocked = !!view.isLockedView;

            // Components initialize binding on render.
            // Widgets in finishRender which will not be called in this case.
            // That is only called when rendered by a layout.
            if (result.isWidget) {
                result.initBindable();
            }
            else {
                // Components store an Element reference to their container element.
                // For ordinary components that is not a problem since usually
                // that element is also referenced by Container instance, and gets
                // cleaned up when Container is destroyed. However for row widgets
                // the container element is a cell; cells do not get Element instances
                // and View is not going to clean them up.
                // So we have to clean up explicitly when the widget is destroyed
                // to avoid orphan Element instances in Ext.cache.
                result.collectContainerElement = true;
            }
        }

        return result;
    },

    getWidgets: function() {
        var widgets = this.widgets,
            id,
            result = [];

        for (id in widgets) {
            result.push(widgets[id]);
        }

        return result;
    },

    handleWidgetViewChange: function(view, ownerId) {
        var widget = this.widgets[ownerId];

        if (widget) {
            // In this particular case poking the ownerCmp doesn't really have any significance here
            // since users will typically be interacting at the grid level. However, this is more
            // for the sake of correctness. We don't need to do anything other
            // than poke the reference.
            widget.ownerCmp = view;
            widget.$fromLocked = !!view.isLockedView;
        }
    },

    destroy: function() {
        var me = this,
            widgets = me.widgets,
            widgetId,
            widget;

        for (widgetId in widgets) {
            widget = widgets[widgetId];
            widget._rowContext = null;
            widget.destroy();
        }

        Ext.destroy(me.viewModel);

        me.callParent();
    },

    privates: {
        initInheritedStateHook: function(inheritedState, inheritedStateInner) {
            var vmParent = this.$vmParent;

            this.self.prototype.initInheritedState.call(this, inheritedState, inheritedStateInner);

            if (!inheritedState.hasOwnProperty('viewModel') && vmParent) {
                inheritedState.viewModel = vmParent;
            }
        },

        lookupViewModelHook: function(skipThis) {
            var ret = skipThis ? null : this.getViewModel();

            if (!ret) {
                ret = this.$vmParent || null;
            }

            return ret;
        }
    }
});
