/**
 * @class Ext.view.AbstractView
 * This is an abstract superclass and should not be used directly. Please see {@link Ext.view.View}.
 * @private
 */
Ext.define('Ext.view.AbstractView', {
    extend: 'Ext.Component',

    requires: [
        'Ext.LoadMask',
        'Ext.CompositeElementLite',
        'Ext.selection.DataViewModel',
        'Ext.view.NavigationModel',
        'Ext.util.CSS'
    ],

    mixins: [
        'Ext.util.StoreHolder'
    ],

    isDataView: true,

    inheritableStatics: {
        /**
         * @private
         * @static
         * @inheritable
         */
        getRecord: function(node) {
            return this.getBoundView(node).getRecord(node);
        },

        /**
         * @private
         * @static
         * @inheritable
         */
        getBoundView: function(node) {
            return Ext.getCmp(node.getAttribute('data-boundView'));
        }
    },

    /**
     * @property defaultBindProperty
     * @inheritdoc
     */
    defaultBindProperty: 'store',

    /**
     * @private
     * Used for buffered rendering.
     */
    renderBuffer: new Ext.dom.Fly(document.createElement('div')),

    statics: {
        /**
         * @prop {Number} [updateDelay=200] Global config for use when using
         * {@link #throttledUpdate throttled view updating} if the data in the backing
         * {@link Ext.data.Store store} is being changed rapidly, for example receiving changes
         * from the server through a WebSocket connection.
         *
         * To avoid too-frequent view updates overloading the browser with style recalculation,
         * layout and paint requests, updates can be {@link #throttledUpdate throttled} to 
         * coalesced, and applied at the interval specified in milliseconds.
         *
         * Note that on lower powered devices, updating is throttled to once every second.
         */
        updateDelay: Ext.platformTags.desktop ? 200 : 1000,

        queueRecordChange: function(view, store, record, operation, modifiedFieldNames) {
            var me = this,
                changeQueue = me.changeQueue || (me.changeQueue = {}),
                recId = record.internalId,
                recChange,
                updated,
                len, i, fieldName, value,
                checkForReversion;

            recChange = changeQueue[recId] || (changeQueue[recId] = {
                operation: operation,
                record: record,
                data: {},
                views: []
            });

            // Hash of original values
            updated = recChange.data;

            // Make sure this view is among those updated when record changes are flushed
            Ext.Array.include(recChange.views, view);

            // Note the following condition tests the result of an assignment statement.
            // If we have been informed that specific fields have changed.
            if (modifiedFieldNames && (len = modifiedFieldNames.length)) {
                for (i = 0; i < len; i++) {
                    fieldName = modifiedFieldNames[i];
                    value = record.data[fieldName];

                    // More than one update is being performed...
                    if (updated.hasOwnProperty(fieldName)) {

                        // If the update is back to the original value,
                        // this may have reverted the record to original state
                        if (record.isEqual(updated[fieldName], value)) {
                            delete updated[fieldName];
                            checkForReversion = true;
                        }
                    }

                    // On first update, cache the original value
                    else {
                        updated[fieldName] = value;
                    }
                }

                // If the record has been returned to its original state, delete the queue entry.
                // checkForReversion flag saves the expensive (on legacy browsers)
                // call to Ext.Object.getKeys
                if (checkForReversion && !Ext.Object.getKeys(updated).length) {
                    delete changeQueue[recId];
                }
            }

            // Unspecified fields have changed. We have to collect the whole data object.
            else {
                Ext.apply(updated, record.data);
            }

            // Create a task which will call flushChangeQueue in updateDelay milliseconds
            // from the time it's invoked.
            if (!me.flushQueueTask) {
                me.flushQueueTask = new Ext.util.DelayedTask(
                    Ext.global.requestAnimationFrame
                        ? Ext.Function.createAnimationFrame(me.flushChangeQueue, me)
                        : me.flushChangeQueue.bind(me), me, null, false
                );
            }

            if (!me.flushTimer) {
                me.flushTimer = me.flushQueueTask.delay(Ext.view.AbstractView.updateDelay);
            }
        },

        /**
        * @private
        * Flushes all queued field updates to the UI.
        *
        * Called in the context of the AbstractView class.
        *
        * The queue is shared across all Views so that there is only one global flush operation.
        */
        flushChangeQueue: function() {
            // Maintainer: Note that "me" references AbstractView class
            var me = this,
                dirtyViews, len, changeQueue, recChange, recId, i, view;

            // If there is scrolling going on anywhere, requeue the flush operation ASAP.
            if (Ext.isScrolling) {
                return me.flushTimer = me.flushQueueTask.delay(1);
            }

            me.flushTimer = null;

            changeQueue = me.changeQueue;

            // Empty the view's changeQueue
            me.changeQueue = {};

            for (recId in changeQueue) {
                recChange = changeQueue[recId];
                dirtyViews = recChange.views;
                len = dirtyViews.length;

                // Loop through all the views which have outstanding changes.
                for (i = 0; i < len; i++) {
                    view = dirtyViews[i];

                    // View may have been destroyed during the buffered phase.
                    if (!view.destroyed) {
                        view.handleUpdate(
                            view.dataSource, recChange.record, recChange.operation,
                            Ext.Object.getKeys(recChange.data)
                        );
                    }
                }
            }
        }
    },

    config: {
        /**
         * @cfg {Ext.data.Store} store
         * The {@link Ext.data.Store} to bind this DataView to.
         * @since 2.3.0
         */
        store: 'ext-empty-store',

        // @cmd-auto-dependency { aliasPrefix: 'view.navigation.' }
        /**
         * @private
         * The {@link Ext.view.NavigationModel} [default] alias to use.
         * @since 5.0.1
         */
        navigationModel: {
            type: 'default'
        },

        // @cmd-auto-dependency { aliasPrefix: 'selection.' }
        /**
         * @cfg {Object/Ext.selection.DataViewModel} selectionModel
         * The {@link Ext.selection.Model selection model} [dataviewmodel] config or alias to use.
         * @since 5.1.0
         */
        selectionModel: {
            type: 'dataviewmodel'
        }
    },

    /**
     * @cfg publishes
     * @inheritdoc
     */
    publishes: ['selection'],

    /**
     * @cfg twoWayBindable
     * @inheritdoc
     */
    twoWayBindable: ['selection'],

    /**
     * @cfg {Ext.data.Model} selection
     * The selected model. Typically used with {@link #bind binding}.
     */
    selection: null,

    /**
     * @cfg {Boolean} throttledUpdate
     * Configure as `true` to have this view participate in the global throttled update queue
     * which flushes store changes to the UI at a maximum rate determined by the
     * {@link #updateDelay} setting.
     */
    throttledUpdate: false,

    /**
     * @cfg {String/String[]/Ext.XTemplate} tpl (required)
     * The HTML fragment or an array of fragments that will make up the template
     * used by this DataView.  This should be specified in the same format expected by the
     * constructor of {@link Ext.XTemplate}. When a `tpl` is specified, this class assumes that
     * records are rendered in the order they appear in the `{@link #store}`. If a custom `tpl`
     * does not conform to this assumption, index values will be incorrect which may cause the view
     * to misbehave.
     * @since 2.3.0
     */

    /**
     * @cfg {Boolean} deferInitialRefresh
     * Configure as 'true` to defer the initial refresh of the view.
     *
     * This allows the View to execute its render and initial layout more quickly because
     * the process will not be encumbered by the update of the view structure.
     */
    deferInitialRefresh: false,

    /**
     * @cfg {String} itemSelector (required)
     * <b>This is a required setting</b>. A simple CSS selector (e.g. `div.some-class` or
     * `span:first-child`) that will be used to determine what nodes this DataView will be
     * working with. The itemSelector is used to map DOM nodes to records. As such, there should
     * only be one root level element that matches the selector for each record. The itemSelector
     * will be automatically configured if the {@link #itemTpl} config is used.
     * 
     *     new Ext.view.View({
     *         renderTo: Ext.getBody(),
     *         store: {
     *             fields: ['name'],
     *             data: [
     *                 {name: 'Item 1'},
     *                 {name: 'Item 2'}
     *             ]
     *         },
     *         tpl: [
     *             '<ul>',
     *             '<tpl for=".">',
     *                 '<li>{name}</li>',
     *             '</tpl>',
     *             '</ul>'
     *         ],
     *         // Match the li, since each one maps to a record
     *         itemSelector: 'li'
     *     });
     * 
     * @since 2.3.0
     */

    /**
     * @cfg {String} itemCls
     * Specifies the class to be assigned to each element in the view when used in conjunction
     * with the {@link #itemTpl} configuration.
     * @since 2.3.0
     */
    itemCls: Ext.baseCSSPrefix + 'dataview-item',

    /**
     * @cfg {String/String[]/Ext.XTemplate} itemTpl
     * The inner portion of the item template to be rendered. Follows an XTemplate
     * structure and will be placed inside of a tpl.
     */

    /**
     * @cfg {String} overItemCls
     * A CSS class to apply to each item in the view on mouseover.
     * Setting this will automatically set {@link #trackOver} to `true`.
     */

    /**
     * @cfg {String} loadingText
     * A string to display during data load operations.  If specified, this text will be
     * displayed in a loading div and the view's contents will be cleared while loading, otherwise
     * the view's contents will continue to display normally until the new data is loaded
     * and the contents are replaced.
     * @since 2.3.0
     * @locale
     */
    loadingText: 'Loading...',

    /**
     * @cfg {Boolean/Object} loadMask
     * False to disable a load mask from displaying while the view is loading. This can also be a
     * {@link Ext.LoadMask} configuration object.
     */
    loadMask: true,

    /**
     * @cfg {String} loadingCls
     * The CSS class to apply to the loading message element. Defaults to
     * Ext.LoadMask.prototype.msgCls "x-mask-loading".
     */

    /**
     * @cfg {Boolean} loadingUseMsg
     * Whether or not to use the loading message.
     * @private
     */
    loadingUseMsg: true,

    /**
     * @cfg {Number} loadingHeight
     * If specified, gives an explicit height for the data view when it is showing the
     * {@link #loadingText}, if that is specified. This is useful to prevent the view's height
     * from collapsing to zero when the loading mask is applied and there are no other contents
     * in the data view.
     */

    /**
     * @cfg {String} selectedItemCls
     * A CSS class to apply to each selected item in the view.
     */
    selectedItemCls: Ext.baseCSSPrefix + 'item-selected',

    /**
     * @cfg {String} emptyText
     * The text to display in the view when there is no data to display.
     * Note that when using local data the emptyText will not be displayed unless you set
     * the {@link #deferEmptyText} option to false.
     * @since 2.3.0
     * @accessor
     * @locale
     */
    emptyText: "",

    /**
     * @cfg {Boolean} deferEmptyText
     * True to defer emptyText being applied until the store's first load.
     * @since 2.3.0
     */
    deferEmptyText: true,

    /**
     * @cfg {Boolean} trackOver
     * When `true` the {@link #overItemCls} will be applied to items when hovered over.
     * This in return will also cause {@link Ext.view.View#highlightitem highlightitem} and
     * {@link Ext.view.View#unhighlightitem unhighlightitem} events to be fired.
     *
     * Enabled automatically when the {@link #overItemCls} config is set.
     *
     * @since 2.3.0
     */
    trackOver: false,

    /**
     * @cfg {Boolean} blockRefresh
     * Set this to true to ignore refresh events on the bound store. This is useful if
     * you wish to provide custom transition animations via a plugin
     * @since 3.4.0
     */
    blockRefresh: false,

    /**
     * @cfg {Boolean} [disableSelection=false]
     * True to disable selection within the DataView. This configuration will lock
     * the selection model that the DataView uses.
     */

    /**
     * @cfg {Boolean} preserveScrollOnRefresh
     * True to preserve scroll position across refresh operations.
     */
    preserveScrollOnRefresh: false,

    /**
     * @cfg {Boolean} preserveScrollOnReload
     * True to preserve scroll position when the store is reloaded.
     *
     * You may want to configure this as `true` if you are using a
     * {@link Ext.data.BufferedStore buffered store} and you require refreshes of the client side
     * data state not to disturb the state of the UI.
     *
     * @since 5.1.1
     */
    preserveScrollOnReload: false,

    /**
     * @property autoDestroyBoundStore
     * @inheritdoc
     */
    autoDestroyBoundStore: true,

    /**
     * @property ariaRole
     * @inheritdoc
     */
    ariaRole: 'listbox',
    itemAriaRole: 'option',

    /**
     * @private
     */
    last: false,

    /**
     * @property focusable
     * @inheritdoc
     */
    focusable: true,

    /**
     * @cfg tabIndex
     * @inheritdoc
     */
    tabIndex: 0,

    triggerEvent: 'itemclick',
    triggerCtEvent: 'containerclick',

    // Starts as true by default so that pn the leading edge of the first layout a refresh
    // will be triggered. A refresh opereration sets this flag to false.
    // When a refresh is requested using refreshView, the request may be deferred because of hidden
    // or collapsed state. This is done by setting the refreshNeeded flag to true, and the the next
    // layout will trigger refresh.
    refreshNeeded: true,

    updateSuspendCounter: 0,

    addCmpEvents: Ext.emptyFn,

    /**
     * @event beforerefresh
     * Fires before the view is refreshed
     * @param {Ext.view.View} this The DataView object
     */

    /**
     * @event refresh
     * Fires when the view is refreshed
     * @param {Ext.view.View} this The DataView object
     */

    /**
     * @event viewready
     * Fires when the View's item elements representing Store items has been rendered.
     * No items will be available for selection until this event fires.
     * @param {Ext.view.View} this
     */

    /**
     * @event itemupdate
     * Fires when the node associated with an individual record is updated
     * @param {Ext.data.Model} record The model instance
     * @param {Number} index The index of the record
     * @param {HTMLElement} node The node that has just been updated
     * @param {Ext.view.View} view The view containing the item
     */

    /**
     * @event itemadd
     * Fires when the nodes associated with an recordset have been added to the underlying store
     * @param {Ext.data.Model[]} records The model instance
     * @param {Number} index The index at which the set of records was inserted
     * @param {HTMLElement[]} node The node that has just been updated
     * @param {Ext.view.View} view The view adding the item
     */

    /**
     * @event itemremove
     * Fires when the node associated with an individual record is removed
     * @param {Ext.data.Model[]} records The model instances removed
     * @param {Number} index The index from which the records wer removed
     * @param {HTMLElement[]} item The view items removed
     * @param {Ext.view.View} view The view removing the item
     */

    constructor: function(config) {
        if (config && config.selModel) {
            config.selectionModel = config.selModel;
        }

        this.callParent([config]);
    },

    initComponent: function() {
        var me = this,
            isDef = Ext.isDefined,
            itemTpl = me.itemTpl,
            memberFn = {},
            selection = me.selection,
            store;

        if (selection) {
            me.selection = null;
            me.setSelection(selection);
        }

        if (itemTpl) {
            if (Ext.isArray(itemTpl)) {
                // string array
                if (typeof itemTpl[itemTpl.length - 1] !== 'string') {
                    itemTpl = itemTpl.slice(0);
                    memberFn = itemTpl.pop();
                }

                itemTpl = itemTpl.join('');
            }
            else if (Ext.isObject(itemTpl)) {
                // tpl instance
                memberFn = Ext.apply(memberFn, itemTpl.initialConfig);
                itemTpl = itemTpl.html;
            }

            if (!me.itemSelector) {
                me.itemSelector = '.' + me.itemCls;
            }

            if (memberFn.fn) {
                memberFn.baseFn = memberFn.fn;
                delete memberFn.fn;
                itemTpl = "{%this.baseFn(out, values, parent, xindex, xcount, xkey)%}";
            }

            itemTpl = Ext.String.format(
                '<tpl for="."><div class="{0}" role="{2}">{1}</div></tpl>', me.itemCls, itemTpl,
                me.itemAriaRole
            );

            me.tpl = new Ext.XTemplate(itemTpl, memberFn);
        }

        //<debug>
        if (!isDef(me.tpl) || !isDef(me.itemSelector)) {
            Ext.raise({
                sourceClass: 'Ext.view.View',
                tpl: me.tpl,
                itemSelector: me.itemSelector,
                msg: "DataView requires both tpl and itemSelector configurations to be defined."
            });
        }
        //</debug>

        me.callParent();
        me.tpl = me.lookupTpl('tpl');

        //<debug>
        // backwards compat alias for overClass/selectedClass
        // TODO: Consider support for overCls generation Ext.Component config
        if (isDef(me.overCls) || isDef(me.overClass)) {
            if (Ext.isDefined(Ext.global.console)) {
                Ext.global.console.warn(
                    'Ext.view.View: Using the deprecated overCls or overClass configuration. ' +
                    'Use overItemCls instead.'
                );
            }

            me.overItemCls = me.overCls || me.overClass;

            delete me.overCls;
            delete me.overClass;
        }

        if (isDef(me.selectedCls) || isDef(me.selectedClass)) {
            if (Ext.isDefined(Ext.global.console)) {
                Ext.global.console.warn(
                    'Ext.view.View: Using the deprecated selectedCls or selectedClass ' +
                    'configuration. Use selectedItemCls instead.');
            }

            me.selectedItemCls = me.selectedCls || me.selectedClass;

            delete me.selectedCls;
            delete me.selectedClass;
        }
        //</debug>

        if (me.overItemCls) {
            me.trackOver = true;
        }

        me.addCmpEvents();

        // Look up the configured Store. If none configured, use the fieldless,
        // empty Store defined in Ext.data.Store.
        store = me.store = Ext.data.StoreManager.lookup(me.store || 'ext-empty-store');

        // Use the provided store as the data source unless a Feature or plugin
        // has injected a special one
        if (!me.dataSource) {
            me.dataSource = store;
        }

        me.bindStore(store, true);

        // Must exist before the selection model.
        // Selection model listens to this for navigation events.
        me.getNavigationModel().bindComponent(this);

        if (!me.all) {
            me.all = new Ext.CompositeElementLite();
        }

        // We track the scroll position
        me.scrollState = {
            top: 0,
            left: 0
        };

        me.savedTabIndexAttribute = 'data-savedtabindex-' + me.id;
    },

    getElConfig: function() {
        var result = this.mixins.renderable.getElConfig.call(this);

        // Subclasses may set focusable to false (BoundList is not focusable)
        if (this.focusable) {
            result.tabIndex = 0;
        }

        return result;
    },

    onRender: function(parentNode, containerIdx) {
        var mask = this.loadMask;

        this.callParent([parentNode, containerIdx]);

        if (mask) {
            this.createMask(mask);
        }
    },

    beforeLayout: function() {
        var me = this;

        me.callParent();

        // If there is a deferred refresh timer running, allow that to do the refresh.
        if (me.refreshNeeded && !me.pendingRefresh) {
            // If we have refreshed before, just call a refresh now.
            if (me.refreshCounter) {
                me.refreshView();
            }
            else {
                me.doFirstRefresh(me.dataSource);
            }
        }
    },

    onMaskBeforeShow: function() {
        var me = this,
            loadingHeight = me.loadingHeight;

        if (loadingHeight && loadingHeight > me.getHeight()) {
            me.hasLoadingHeight = true;
            me.oldMinHeight = me.minHeight;
            me.minHeight = loadingHeight;
            me.updateLayout();
        }
    },

    onMaskHide: function() {
        var me = this;

        if (!me.destroying && me.hasLoadingHeight) {
            me.minHeight = me.oldMinHeight;
            me.updateLayout();
            delete me.hasLoadingHeight;
        }
    },

    beforeRender: function() {
        this.callParent();
        this.getSelectionModel().beforeViewRender(this);
    },

    afterRender: function() {
        this.callParent();

        // Subclasses may set focusable to false.
        // BoundList is not focusable.
        // BoundList processes key events from its boundField.
        if (this.focusable) {
            this.focusEl = this.el;
        }
    },

    getRefItems: function() {
        var mask = this.loadMask,
            result = [];

        if (mask && mask.isComponent) {
            result.push(mask);
        }

        return result;
    },

    getSelection: function() {
        return this.getSelectionModel().getSelection();
    },

    /**
     * Sets the value of the selection.
     * @param {Ext.data.Model} selection
     */
    setSelection: function(selection) {
        // This is purposefully written not as a config. Because getSelection
        // is an existing API that doesn't mirror the value for setSelection, we
        // don't want the publish system to call the getter, but rather just the
        // raw property.
        var current = this.selection;

        if (selection !== current) {
            this.selection = selection;
            this.updateSelection(selection, current);
        }
    },

    updateSelection: function(selection) {
        var me = this,
            sm;

        if (!me.ignoreNextSelection) {
            me.ignoreNextSelection = true;
            sm = me.getSelectionModel();

            if (selection) {
                sm.select(selection);
            }
            else {
                sm.deselectAll();
            }

            me.ignoreNextSelection = false;
        }

        me.publishState('selection', selection);
    },

    updateBindSelection: function(selModel, selection) {
        var me = this,
            selected = null;

        if (!me.ignoreNextSelection) {
            me.ignoreNextSelection = true;

            if (selection.length) {
                selected = selModel.getLastSelected();
                me.hasHadSelection = true;
            }

            if (me.hasHadSelection) {
                me.setSelection(selected);
            }

            me.ignoreNextSelection = false;
        }
    },

    applySelectionModel: function(selModel, oldSelModel) {
        var me = this,
            grid = me.grid,
            mode, ariaAttr, ariaDom;

        if (oldSelModel) {
            // Could be already destroyed, and listeners cleared
            if (!oldSelModel.destroyed) {
                oldSelModel.un({
                    scope: me,
                    selectionchange: me.updateBindSelection,
                    lastselectedchanged: me.updateBindSelection
                });
            }

            Ext.destroy(me.selModelRelayer);
            selModel = Ext.Factory.selection(selModel);
        }
        // If this is the initial configuration, pull overriding configs in from this view
        else {
            if (selModel && selModel.isSelectionModel) {
                selModel.locked = me.disableSelection;
            }
            else {
                if (me.simpleSelect) {
                    mode = 'SIMPLE';
                }
                else if (me.multiSelect) {
                    mode = 'MULTI';
                }
                else {
                    mode = 'SINGLE';
                }

                if (typeof selModel === 'string') {
                    selModel = {
                        type: selModel
                    };
                }

                selModel = Ext.Factory.selection(Ext.apply({
                    allowDeselect: me.allowDeselect || me.multiSelect,
                    mode: mode,
                    locked: me.disableSelection
                }, selModel));
            }
        }

        // Grids should have aria-multiselectable on their ariaEl instead
        if (selModel.mode !== 'SINGLE') {
            ariaDom = (grid || me).ariaEl.dom;

            if (ariaDom) {
                ariaDom.setAttribute('aria-multiselectable', true);
            }
            else if (!grid) {
                ariaAttr = me.ariaRenderAttributes || (me.ariaRenderAttributes = {});
                ariaAttr['aria-multiselectable'] = true;
            }
        }

        me.selModelRelayer = me.relayEvents(selModel, [
            'selectionchange', 'beforeselect', 'beforedeselect', 'select', 'deselect', 'focuschange'
        ]);

        selModel.on({
            scope: me,
            lastselectedchanged: me.updateBindSelection,
            selectionchange: me.updateBindSelection
        });

        return selModel;
    },

    updateSelectionModel: function(selectionModel) {
        // Keep the legacy property correct
        this.selModel = selectionModel;
    },

    applyNavigationModel: function(navigationModel) {
        return Ext.Factory.viewNavigation(navigationModel);
    },

    onFocusEnter: function(e) {
        var me = this,
            navigationModel = me.getNavigationModel(),
            focusPosition = me.lastFocused;

        // This is set on mousedown on the scrollbar in IE/Edge.
        // Those browsers focus the element on mousedown on its scrollbar
        // which is not what we want, so throw focus back in this
        // situation.
        // See Ext.view.navigationModel for this being set.
        me.lastFocused = null;

        if (focusPosition === 'scrollbar') {
            e.relatedTarget.focus();

            return;
        }

        // Disable tabbability of elements within this view.
        me.toggleChildrenTabbability(false);

        if (!me.itemFocused && me.all.getCount()) {

            // SHIFT+TAB hit the tab guard - focus last item.
            if (e.event.getTarget() === me.tabGuardEl) {
                focusPosition = me.all.getCount() - 1;
            }
            else {
                focusPosition = navigationModel.getLastFocused();
            }

            navigationModel.setPosition(focusPosition || 0, e.event, null, !focusPosition);

            // We now contain focus is that was successful
            me.itemFocused = navigationModel.getPosition() != null;
        }

        // View's main el should be kept untabbable, otherwise pressing
        // Shift-Tab key in the view would move the focus to the main el
        // which will then bounce it back to the last focused item.
        // That would effectively make Shift-Tab unusable.
        if (me.itemFocused) {
            me.el.dom.setAttribute('tabIndex', -1);

            if (me.tabGuardEl) {
                me.tabGuardEl.setAttribute('tabIndex', -1);
            }
        }

        me.callParent([e]);
    },

    onFocusLeave: function(e) {
        var me = this;

        // Ignore this event if we do not actually contain focus,
        // or if the reason for focus exiting was that we are refreshing.
        if (me.itemFocused && !me.refreshing) {

            // Blur the focused cell
            me.getNavigationModel().setPosition(null, e.event, null, true);

            me.itemFocused = false;
            me.el.dom.setAttribute('tabIndex', 0);

            if (me.tabGuardEl) {
                me.tabGuardEl.setAttribute('tabIndex', 0);
            }
        }

        me.callParent([e]);
    },

    /**
     * @private
     * Cancel a pending focus task, if any.
     * This is a separate method to allow simple abstraction for locked views.
     */
    cancelFocusTask: function() {
        var task = this.getFocusTask();

        if (task) {
            task.cancel();
        }
    },

    onRemoved: function(isDestroying) {
        this.callParent([isDestroying]);

        // IE does not fire focusleave on removal from DOM
        if (!isDestroying) {
            this.onFocusLeave({});
        }
    },

    /**
     * Refreshes the view by reloading the data from the store and re-rendering the template.
     *
     * @since 2.3.0
     */
    refresh: function() {
        var me = this,
            items = me.all,
            prevItemCount = items.getCount(),
            refreshCounter = me.refreshCounter,
            targetEl,
            records,
            selModel = me.getSelectionModel(),
            restoreFocus,
            // If there are items in the view, then honour preserveScrollOnRefresh
            scroller = refreshCounter && items.getCount() && me.preserveScrollOnRefresh &&
                       me.getScrollable(),
            bufferedRenderer = me.bufferedRenderer,
            scrollPos;

        if (!me.rendered || me.destroyed) {
            return;
        }

        if (!me.hasListeners.beforerefresh || me.fireEvent('beforerefresh', me) !== false) {
            // So that listeners to itemremove events know that its because of a refresh
            me.refreshing = true;

            // If focus was in this view, this will restore it
            restoreFocus = me.saveFocusState();

            targetEl = me.getTargetEl();
            records = me.getViewRange();

            if (scroller) {
                scrollPos = scroller.getPosition();

                if (!(scrollPos.x || scrollPos.y)) {
                    scrollPos = null;
                }
            }

            if (refreshCounter || me.emptyEl) {
                me.clearViewEl();
            }

            if (refreshCounter) {
                me.refreshCounter++;
            }
            else {
                me.refreshCounter = 1;
            }

            // Usually, for an empty record set, this would be blank, but when the Template
            // Creates markup outside of the record loop, this must still be honoured
            // even if there are no records.
            me.tpl.append(targetEl, me.collectData(records, items.startIndex || 0));

            // The emptyText is now appended to the View's element
            // after nodes outside the tpl block.
            if (records.length < 1) {
                // Process empty text unless the store is being cleared.
                me.addEmptyText();
                items.clear();
            }
            else {
                me.collectNodes(targetEl.dom);
                me.updateIndexes(0);
            }

            // If focus was in any way in this view, this will restore it
            restoreFocus();

            // Some subclasses do not need to do this. TableView does not need to do this -
            // it renders selected class using its tenmplate.
            if (me.refreshSelmodelOnRefresh !== false) {
                selModel.refresh();
            }

            me.refreshNeeded = false;

            // Ensure layout system knows about new content size.
            // If number of items have changed, force a layout.
            me.refreshSize(items.getCount() !== prevItemCount);

            me.fireItemMutationEvent('refresh', me, records);

            if (scroller) {
                scroller.scrollTo(scrollPos);
            }

            // Upon first refresh, fire the viewready event.
            // Reconfiguring the grid "renews" this event.
            if (!me.viewReady) {
                // Fire an event when deferred content becomes available.
                me.viewReady = true;
                me.fireEvent('viewready', me);
            }

            me.refreshing = false;

            if (bufferedRenderer) {
                bufferedRenderer.refreshSize();
            }

            me.cleanupData();
        }

        // The tabGuardEl is only needed before focus has entered the view to prevent
        // naturally tabbable interior elements from attracting focus when using SHIFT+TAB
        // from after the view. Once focus has entered, we disable tabbability on interior
        // elements so it will not be needed.
        // Subsequent *full* refreshes will destroy it.
        // Buffer rendered refreshes are more granular, and do not destroy extraneous
        // DOM, but this does not matter because tha tabGuardEl will be tabIndex="-1"
        // so out of the tab order.
        if (!me.tabGuardEl) {
            // We only need an "after" tab guard.
            // The View el is tabIndex="0", so captures forward TAB.
            // It's only SHIFT+TAB that we have to guard against.
            me.tabGuardEl = me.el.createChild({
                cls: Ext.baseCSSPrefix + 'tab-guard ' + Ext.baseCSSPrefix + 'tab-guard-after',
                tabIndex: "0"
            }, null, true);
        }
    },

    addEmptyText: function() {
        var me = this,
            store = me.getStore();

        if (me.emptyText && !store.isLoading() &&
            (!me.deferEmptyText || me.refreshCounter > 1 || store.isLoaded())) {
            if (!me.emptyEl) {
                me.emptyEl =
                    Ext.core.DomHelper.insertHtml('beforeEnd', me.getTargetEl().dom, me.emptyText);
            }
            else {
                Ext.fly(me.emptyEl).setHtml(me.emptyText);
            }
        }
    },

    getEmptyText: function() {
        return this.emptyText;
    },

    setEmptyText: function(emptyText) {
        var me = this;

        if (me.emptyText !== emptyText) {
            me.emptyText = emptyText;
            me.refresh();
        }

        return me;
    },

    getViewRange: function() {
        return this.dataSource.getRange();
    },

    /**
     * @private
     * Called by the framework when the view is refreshed, or when rows are added or deleted.
     *
     * These operations may cause the view's dimensions to change, and if the owning container
     * is shrinkwrapping this view, then the layout must be updated to accommodate these new
     * dimensions.
     */
    refreshSize: function(forceLayout) {
        var me = this,
            sizeModel = me.getSizeModel();

        if (sizeModel.height.shrinkWrap || sizeModel.width.shrinkWrap || forceLayout) {
            me.updateLayout();
        }
    },

    afterFirstLayout: function(width, height) {
        var me = this,
            scroller = me.getScrollable();

        if (scroller) {
            me.viewScrollListeners = scroller.on({
                scroll: me.onViewScroll,
                scrollend: me.onViewScrollEnd,
                scope: me,
                onFrame: !!Ext.global.requestAnimationFrame,
                destroyable: true
            });
        }

        me.callParent([width, height]);
    },

    clearViewEl: function() {
        var me = this,
            targetEl = me.getTargetEl(),
            all = me.all,
            store = me.getStore(),
            nodeContainerIsTarget = me.getNodeContainer() === targetEl,
            i, removedItems, removedRecs;

        // We must ensure that the itemremove event is fired EVERY time an item is removed from the
        // view. This is so that widgets rendered into a view by a WidgetColumn can be recycled.
        removedItems = all.slice();
        removedRecs = [];

        for (i = all.startIndex; i <= all.endIndex; i++) {
            removedRecs.push(
                store.getByInternalId(all.item(i, true).getAttribute('data-recordId'))
            );
        }

        me.fireItemMutationEvent('itemremove', removedRecs, all.startIndex || 0, removedItems, me);

        me.clearEmptyEl();

        // If nodeContainer is the el, just clear the innerHTML. Otherwise, we need
        // to manually remove each node we know about.
        me.all.clear(!nodeContainerIsTarget);

        targetEl = nodeContainerIsTarget ? targetEl.dom : me.getNodeContainer();

        if (targetEl) {
            targetEl.innerHTML = '';
        }
    },

    clearEmptyEl: function() {
        var emptyEl = this.emptyEl;

        // emptyEl is likely to be a TextNode if emptyText is not HTML code.
        // Use native DOM to remove it.
        if (emptyEl) {
            Ext.removeNode(emptyEl);
        }

        this.emptyEl = null;
    },

    onViewScroll: function(scroller, x, y) {
        if (!this.destroyed) {
            this.fireEvent('scroll', this, x, y);
        }
    },

    onViewScrollEnd: function(scroller, x, y) {
        if (!this.destroyed) {
            this.fireEvent('scrollend', this, x, y);
        }
    },

    /**
     * Saves the scrollState in a private variable.
     * Must be used in conjunction with restoreScrollState.
     * @private
     */
    saveScrollState: function() {
        var me = this,
            state = me.scrollState;

        if (me.rendered) {
            state.left = me.getScrollX();
            state.top = me.getScrollY();
        }
    },

    /**
     * Restores the scrollState.
     * Must be used in conjunction with saveScrollState
     * @private
     */
    restoreScrollState: function() {
        var me = this,
            state = me.scrollState;

        if (me.rendered) {
            me.setScrollX(state.left);
            me.setScrollY(state.top);
        }
    },

    /**
     * Function which can be overridden to provide custom formatting for each Record that is used by
     * this DataView's {@link #tpl template} to render each node.
     * @param {Object/Object[]} data The raw data object that was used to create the Record.
     * @param {Number} recordIndex the index number of the Record being prepared for rendering.
     * @param {Ext.data.Model} record The Record being prepared for rendering.
     * @return {Array/Object} The formatted data in a format expected by the internal
     * {@link #tpl template}'s overwrite() method. (either an array if your params are numeric
     * (i.e. {0}) or an object (i.e. { foo: 'bar' }))
     * @since 2.3.0
     */
    prepareData: function(data, recordIndex, record) {
        var associatedData, attr, hasCopied;

        if (record) {
            associatedData = record.getAssociatedData();

            for (attr in associatedData) {
                if (associatedData.hasOwnProperty(attr)) {
                    // This would be better done in collectData, however
                    // we only need to copy the data object if we have any associations,
                    // so we optimize it by only copying if we must.
                    // We do this so we don't mutate the underlying record.data
                    if (!hasCopied) {
                        data = Ext.Object.chain(data);
                        hasCopied = true;
                    }

                    data[attr] = associatedData[attr];
                }
            }
        }

        return data;
    },

    /**
     * Function which can be overridden which returns the data object passed to this
     * DataView's {@link #cfg-tpl template} to render the whole DataView.
     *
     * This is usually an Array of data objects, each element of which is processed by an
     * {@link Ext.XTemplate XTemplate} which uses `'&lt;tpl for="."&gt;'` to iterate over
     * its supplied data object as an Array. However, <i>named</i> properties may be placed
     * into the data object to provide non-repeating data such as headings, totals etc.
     *
     * @param {Ext.data.Model[]} records An Array of {@link Ext.data.Model}s to be rendered into
     * the DataView.
     * @param {Number} startIndex the index number of the Record being prepared for rendering.
     * @return {Object[]} An Array of data objects to be processed by a repeating XTemplate.
     * May also contain <i>named</i> properties.
     * @since 2.3.0
     */
    collectData: function(records, startIndex) {
        var data = [],
            i = 0,
            len = records.length,
            record;

        for (; i < len; i++) {
            record = records[i];
            data[i] = this.prepareData(record.data, startIndex + i, record);
        }

        return data;
    },

    cleanupData: Ext.emptyFn,

    bufferRender: function(records, index) {
        var me = this,
            div = me.renderBuffer,
            result = document.createDocumentFragment(),
            nodes, len, i;

        me.tpl.overwrite(div, me.collectData(records, index));
        nodes = div.query(me.getItemSelector());

        for (i = 0, len = nodes.length; i < len; i++) {
            result.appendChild(nodes[i]);
        }

        return {
            fragment: result,
            children: nodes
        };
    },

    // Element which contains rows
    nodeContainerSelector: null,

    /**
     * For use by the {@link Ext.view.DragZone} plugin on platforms which use the
     * [Pointer Events standard](https://www.w3.org/TR/pointerevents/).
     *
     * If using touch scrolling, the `pointerdown` event is reserved for starting the scroll
     * gesture. To enable dragging of items using the ExtJS drag/drop system, items
     * must be set draggable. This means that `pointerdown` on view items initiate an ExtJS drag
     * and *not* a scroll gesture.
     *
     * When items are set draggable: true, pointer events platforms can still scroll using two
     * finger drag, or by dragging empty parts of the view.
     *
     * For normal dataviews, havig the backgrounc-color of items and the view be different will
     * indicate where to touch to initiate a scroll.
     *
     * For grids, if rows need to be dragged, there must be some blank space after rows
     * to touch to initiate the scroll gesture.
     *
     * @param {type} draggable
     * @private
     */
    setItemsDraggable: function(draggable) {
        var me = this,
            selector = '#' + me.id + ' ' + me.getItemSelector(),
            styleSheet = me.viewStyleSheet;

        if (draggable) {
            if (!styleSheet) {
                styleSheet = Ext.view.AbstractView.prototype.viewStyleSheet =
                    Ext.util.CSS.createStyleSheet('', 'AbstractView');
            }

            // Pointer Events platforms implement the touch-action or -ms-touch-action properties
            // which deicate how an element responds to touches.
            // Non Pointer Events platforms such as iOS show a selection rectangle
            // on longpress+drag, and that is disabled by -webkit-user-drag: none;
            Ext.util.CSS.createRule(
                styleSheet, selector,
                'touch-action: pinch-zoom double-tap-zoom;' +
                '-ms-touch-action: pinch-zoom double-tap-zoom;-webkit-user-drag: none;'
            );
        }
        else if (styleSheet) {
            Ext.util.CSS.deleteRule(selector);
        }
    },

    /**
     * Returns a CSS selector which selects the element which contains record nodes.
     */
    getNodeContainerSelector: function() {
        return this.nodeContainerSelector;
    },

    onUpdate: function(store, record, operation, modifiedFieldNames, details) {
        var me = this,
            isFiltered = details && details.filtered;

        // If, due to filtering or buffered rendering, or node collapse, the updated record is not
        // represented in the rendered structure, this is a no-op.
        // The correct, new values will be rendered the next time the record becomes visible
        // and is rendered.
        if (!isFiltered && me.getNode(record)) {

            // If we are throttling UI updates (See the updateDelay global config),
            // ensure there's a change entry queued for the record in the global queue.
            if (me.throttledUpdate) {
                me.statics().queueRecordChange(me, store, record, operation, modifiedFieldNames);
            }
            else {
                // Cannot use arguments array.
                // TableView's signature acceses these arguments plus one more of its own.
                // Event firing passes the addListener options object as rge final parameter
                // and we must not pass that.
                me.handleUpdate(store, record, operation, modifiedFieldNames, details);
            }
        }
    },

    handleUpdate: function(store, record) {
        var me = this,
            selModel = me.getSelectionModel(),
            index, node;

        if (me.viewReady && !me.refreshNeeded) {
            index = me.dataSource.indexOf(record);

            // If the record has been removed from the data source since the changes were made,
            // do nothing
            if (index > -1) {
                // ensure the node actually exists in the DOM
                if (me.getNode(record)) {
                    node = me.bufferRender([record], index).children[0];
                    me.all.replaceElement(index, node, true);
                    me.updateIndexes(index, index);

                    // Maintain selection after update
                    selModel.onUpdate(record);
                    me.refreshSizePending = true;

                    if (selModel.isSelected(record)) {
                        me.onItemSelect(record);
                    }

                    if (me.hasListeners.itemupdate) {
                        me.fireEvent('itemupdate', record, index, node, me);
                    }

                    return node;
                }
            }
        }
    },

    /**
     * @private
     * Respond to store replace event which is fired by GroupStore group expand/collapse operations.
     * This saves a layout because a remove and add operation are coalesced in this operation.
     */
    onReplace: function(store, startIndex, oldRecords, newRecords) {
        var me = this,
            all = me.all,
            scroller = me.getScrollable(),
            yPos = scroller && scroller.getPosition().y,
            selModel = me.getSelectionModel(),
            origStart = startIndex,
            result, item, fragment, children, oldItems, endIndex, restoreFocus;

        if (me.rendered) {
            // Insert the new items before the remove block
            result = me.bufferRender(newRecords, startIndex, true);
            fragment = result.fragment;
            children = result.children;
            item = all.item(startIndex);

            if (item) {
                all.item(startIndex).insertSibling(fragment, 'before', true);
            }
            else {
                me.appendNodes(fragment);
            }

            all.insert(startIndex, children);

            if (oldRecords.length) {
                // If focus was in the view, this will return
                // a function which will restore that state.
                // If not, a function which does nothing.
                restoreFocus = me.saveFocusState();
            }

            startIndex += newRecords.length;
            endIndex = startIndex + oldRecords.length - 1;

            // Remove the items which correspond to old records
            oldItems = all.removeRange(startIndex, endIndex, true);

            // Restore scroll position
            if (scroller) {
                scroller.scrollTo(null, yPos);
            }

            // Some subclasses do not need to do this. TableView does not need to do this.
            if (me.refreshSelmodelOnRefresh !== false) {
                selModel.refresh();
            }

            // Update the row indices (TableView) doesn't do this.
            me.updateIndexes(startIndex);

            me.fireItemMutationEvent('itemremove', oldRecords, origStart, oldItems, me);
            me.fireItemMutationEvent('itemadd', newRecords, origStart, children, me);

            // If focus was in this view, this will restore it
            restoreFocus();

            me.refreshSize();
        }
    },

    onAdd: function(store, records, index) {
        var me = this,
            nodes,
            selModel = me.getSelectionModel();

        if (me.rendered && !me.refreshNeeded) {
            // If we are adding into an empty view, we must refresh in order that
            // the *full tpl* is applied which might create boilerplate content *around*
            // the record nodes.
            if (me.all.getCount() === 0) {
                me.refresh();
                nodes = me.all.slice();
            }
            else {
                nodes = me.doAdd(records, index);

                // Some subclasses do not need to do this. TableView does not need to do this.
                if (me.refreshSelmodelOnRefresh !== false) {
                    selModel.refresh();
                }

                me.updateIndexes(index);

                // Ensure layout system knows about new content size
                me.refreshSizePending = true;
            }

            me.fireItemMutationEvent('itemadd', records, index, nodes, me);
        }

    },

    appendNodes: function(nodes) {
        var all = this.all,
            count = all.getCount();

        if (this.nodeContainerSelector) {
            this.getNodeContainer().appendChild(nodes);
        }
        else {
            // If we don't have a nodeContainerSelector, we may have our
            // itemSelector nodes wrapped in some other container, so we
            // can't just append them to the node container, it may be the wrong element
            all.item(count - 1).insertSibling(nodes, 'after');
        }
    },

    doAdd: function(records, index) {
        var me = this,
            result = me.bufferRender(records, index, true),
            fragment = result.fragment,
            children = result.children,
            all = me.all,
            count = all.getCount(),
            firstRowIndex = all.startIndex || 0,

            lastRowIndex = all.endIndex || count - 1;

        if (count === 0 || index > lastRowIndex) {
            me.appendNodes(fragment);
        }
        else if (index <= firstRowIndex) {
            all.item(firstRowIndex).insertSibling(fragment, 'before', true);
        }
        else {
            all.item(index).insertSibling(children, 'before', true);
        }

        all.insert(index, children);

        return children;
    },

    onRemove: function(store, records, index) {
        var me = this,
            rows = me.all,
            currIdx, i, record, nodes, node, restoreFocus;

        if (me.rendered && !me.refreshNeeded && rows.getCount()) {
            if (me.dataSource.getCount() === 0) {
                me.refresh();
            }
            else {
                // If this view contains focus, this will return
                // a function which will restore that state.
                restoreFocus = me.saveFocusState();

                // Just remove the elements which corresponds to the removed records
                // The tpl's full HTML will still be in place.
                nodes = [];

                for (i = records.length - 1; i >= 0; --i) {
                    record = records[i];
                    currIdx = index + i;

                    if (nodes) {
                        node = rows.item(currIdx);
                        nodes[i] = node ? node.dom : undefined;
                    }

                    if (rows.item(currIdx)) {
                        me.doRemove(record, currIdx);
                    }
                }

                me.fireItemMutationEvent('itemremove', records, index, nodes, me);

                // If focus was in this view, this will restore it
                restoreFocus();
                me.updateIndexes(index);
            }

            // Ensure layout system knows about new content size
            me.refreshSizePending = true;
        }
    },

    doRemove: function(record, index) {
        this.all.removeElement(index, true);
    },

    eventLifecycleMap: {
        refresh: 'onViewRefresh',
        itemremove: 'onItemRemove',
        itemadd: 'onItemAdd'
    },

    fireItemMutationEvent: function(eventName) {
        var me = this,
            ownerGrid = me.ownerGrid,
            vm;

        Ext.suspendLayouts();

        // Inform the ownerGrid.
        if (ownerGrid) {
            if (eventName !== 'refresh') {
                vm = me.lookupViewModel();
            }

            ownerGrid[me.eventLifecycleMap[eventName]].apply(
                ownerGrid, Ext.Array.slice(arguments, 1)
            );
        }

        me.fireEvent.apply(me, arguments);

        // The content height MUST be measurable by the caller (the buffered renderer),
        // so data must be flushed to it immediately.
        if (vm) {
            vm.notify();
        }

        Ext.resumeLayouts(true);
    },

    /**
     * @private
     * Called prior to an operation which mey remove focus from this view by some kind
     * of DOM operation.
     *
     * If this view contains focus, this method returns a function which, when called after
     * the disruptive DOM operation will restore focus to the same record, or, if the record has
     * been removed to the same item index..
     *
     * @returns {Function} A function that will restore focus if focus was within this view,
     * or a function which does nothing is focus is not in this view.
     */
    saveFocusState: function() {
        var me = this,
            store = me.dataSource || me.store,
            navModel = me.getNavigationModel(),
            lastFocusedIndex = navModel.recordIndex,
            lastFocusedRec = navModel.record,
            containsFocus = me.el.contains(Ext.Element.getActiveElement());

        // If there is a position to restore...
        if (lastFocusedRec) {
            // Check if we really have focus.
            // Some NavigationModels record position with focus outside of the view.
            // This happens in BoundLists when focus stays in the bound field.
            // Blur the focused descendant, but do not trigger focusLeave.
            if (containsFocus) {
                me.el.dom.focus();
            }

            // The following function will attempt to refocus back to the same record
            // if it is still there, or the same item index.
            return function() {
                // If we still have data, attempt to refocus at the same record,
                // or the same item index..
                if (store.getCount()) {
                    // Adjust expectations of where we are able to refocus according to
                    // what kind of destruction might have been wrought on this view's DOM
                    // during focus save.
                    lastFocusedIndex = Math.min(lastFocusedIndex, me.all.getCount() - 1);

                    navModel.setPosition(
                        store.contains(lastFocusedRec)
                            ? lastFocusedRec
                            : lastFocusedIndex,
                        null, null, true, !containsFocus
                    );
                }
            };
        }

        return Ext.emptyFn;
    },

    /**
     * Refreshes an individual node's data from the store.
     * @param {Ext.data.Model/Number} record The record or index of the record to update.
     * @since 2.3.0
     */
    refreshNode: function(record) {
        if (Ext.isNumber(record)) {
            record = this.store.getAt(record);
        }

        this.onUpdate(this.dataSource, record);
    },

    updateIndexes: function(startIndex, endIndex) {
        var me = this,
            nodes = me.all.elements,
            records = me.getViewRange(),
            selModel = me.getSelectionModel(),
            myId = me.id,
            node, record, i;

        startIndex = startIndex || 0;
        endIndex = endIndex || ((endIndex === 0) ? 0 : (nodes.length - 1));

        for (i = startIndex; i <= endIndex; i++) {
            node = nodes[i];
            record = records[i];

            node.setAttribute('data-recordIndex', i);
            node.setAttribute('data-recordId', record.internalId);
            node.setAttribute('data-boundView', myId);

            if (selModel.getLastSelected()) {
                me[selModel.isSelected(record) ? 'onItemSelect' : 'onItemDeselect'](record);
            }
        }
    },

    /**
     * Changes the data store bound to this view and refreshes it.
     * @param {Ext.data.Store} store The store to bind to this view
     * @param {Object} initial
     * @since 3.4.0
     */
    bindStore: function(store, initial) {
        var me = this,
            selModel = me.getSelectionModel(),
            navModel = me.getNavigationModel();

        // Can be already destroyed if we're called from doDestroy()
        if (selModel && !selModel.destroyed) {
            selModel.bindStore(store, initial);
            selModel.bindComponent(store ? me : null);
        }

        me.mixins.storeholder.bindStore.apply(me, arguments);

        // Navigation model must bind to new store
        if (navModel && !navModel.destroyed) {
            navModel.setStore(store);
        }

        // If we have already achieved our first layout, refresh immediately.
        // If we bind to the Store before the first layout, then beforeLayout will
        // call doFirstRefresh
        if (store && me.componentLayoutCounter && !me.blockRefresh) {
            // If not the initial bind, we enforce noDefer.
            me.doFirstRefresh(store, !initial);
        }
    },

    /**
     * @private
     * Perform the first refresh of the View from a newly bound store.
     *
     * This is called when this View has been sized for the first time.
     */
    doFirstRefresh: function(store, noDefer) {
        var me = this;

        // If we are configured to defer, and *NOT* called from the defer call below
        if (me.deferInitialRefresh && !noDefer) {
            Ext.defer(me.doFirstRefresh, 1, me, [store, true]);
        }

        else {
            // 4.1.0: If we have a store, and the Store is *NOT* already loading
            // (a refresh is on the way), then on first layout, refresh regardless of record count.
            // Template may contain boilerplate HTML outside of record iteration loop.
            // Also, emptyText is appended by the refresh method.
            if (store && !me.deferRefreshForLoad(store)) {
                me.refresh();
            }
        }
    },

    onUnbindStore: function(store) {
        this.setMaskBind(null);

        if (this.dataSource === store) {
            this.dataSource = null;
        }
    },

    onBindStore: function(store, oldStore) {
        var me = this;

        // A BufferedStore has to know to reload the most recent visible zone if its View
        // is preserveScrollOnReload
        if (me.store.isBufferedStore) {
            me.store.preserveScrollOnReload = me.preserveScrollOnReload;
        }

        if (oldStore && oldStore.isBufferedStore) {
            delete oldStore.preserveScrollOnReload;
        }

        me.setMaskBind(store);

        // When unbinding the data store, the dataSource will be nulled out
        // if it's the same as the data store. Restore it here.
        if (!me.dataSource) {
            me.dataSource = store;
        }
    },

    setMaskBind: function(store) {
        var mask = this.loadMask;

        if (this.rendered && mask && store && !mask.bindStore) {
            mask = this.createMask();
        }

        if (mask && mask.bindStore) {
            mask.bindStore(store);
        }
    },

    getStoreListeners: function() {
        var me = this;

        return {
            refresh: me.onDataRefresh,
            replace: me.onReplace,
            add: me.onAdd,
            remove: me.onRemove,
            update: me.onUpdate,
            clear: me.onDataRefresh,
            beginupdate: me.onBeginUpdate,
            endupdate: me.onEndUpdate
        };
    },

    onBeginUpdate: function() {
        ++this.updateSuspendCounter;

        Ext.suspendLayouts();
    },

    onEndUpdate: function() {
        var me = this;

        if (me.updateSuspendCounter) {
            --me.updateSuspendCounter;
        }

        Ext.resumeLayouts(true);

        if (me.refreshSizePending) {
            me.refreshSize(true);
            me.refreshSizePending = false;
        }
    },

    /**
     * @private
     * Calls this.refresh if this.blockRefresh is not true
     * @since 3.4.0
     */
    onDataRefresh: function(store) {
        var me = this,
            preserveScrollOnRefresh = me.preserveScrollOnRefresh;

        // If this refresh event is fire from a store load, then use the 
        // preserveScrollOnReload setting to decide whether to preserve scroll position
        if (store.loadCount >= (me.lastRefreshLoadCount || 0)) {
            me.preserveScrollOnRefresh = me.preserveScrollOnReload;
        }

        me.refreshView();
        me.preserveScrollOnRefresh = preserveScrollOnRefresh;
        me.lastRefreshLoadCount = store.loadCount;
    },

    refreshView: function(startIndex) {
        var me = this,
            // If we have an ancestor in a non-boxready state (collapsed or about to collapse,
            // or hidden), then block the refresh because the next layout will trigger the refresh
            blocked = me.blockRefresh || !me.rendered ||
                      me.up('[collapsed],[isCollapsingOrExpanding=1],[hidden]'),
            bufferedRenderer = me.bufferedRenderer;

        // If we are blocked in any way due to either a setting, or hidden or collapsed,
        // or animating ancestor, then the next refresh attempt at the upcoming layout
        // must not defer.
        if (blocked) {
            me.refreshNeeded = true;
        }
        else {
            if (bufferedRenderer) {
                bufferedRenderer.refreshView(startIndex);
            }
            else {
                me.refresh();
            }
        }
    },

    /**
     * Returns the template node the passed child belongs to, or null if it doesn't belong to one.
     * @param {HTMLElement} node
     * @return {HTMLElement} The template node
     */
    findItemByChild: function(node) {
        return Ext.fly(node).findParent(this.getItemSelector(), this.getTargetEl());
    },

    /**
     * Returns the template node by the Ext.event.Event or null if it is not found.
     * @param {Ext.event.Event} e
     */
    findTargetByEvent: function(e) {
        return e.getTarget(this.getItemSelector(), this.getTargetEl());
    },

    /**
     * Gets the currently selected nodes.
     * @return {HTMLElement[]} An array of HTMLElements
     * @since 2.3.0
     */
    getSelectedNodes: function() {
        var nodes = [],
            records = this.getSelectionModel().getSelection(),
            ln = records.length,
            i = 0;

        for (; i < ln; i++) {
            nodes.push(this.getNode(records[i]));
        }

        return nodes;
    },

    /**
     * Gets an array of the records from an array of nodes
     * @param {HTMLElement[]} nodes The nodes to evaluate
     * @return {Ext.data.Model[]} records The {@link Ext.data.Model} objects
     * @since 2.3.0
     */
    getRecords: function(nodes) {
        var me = this,
            records = [],
            i;

        for (i = 0; i < nodes.length; i++) {
            records.push(me.getRecord(nodes[i]));
        }

        return records;
    },

    /**
     * Gets a record from a node
     * @param {Ext.dom.Element/HTMLElement} node The node to evaluate
     *
     * @return {Ext.data.Model} record The {@link Ext.data.Model} object
     * @since 2.3.0
     */
    getRecord: function(node) {
        var dom = Ext.getDom(node),
            id = dom.getAttribute('data-recordId');

        return this.dataSource.getByInternalId(id);
    },

    /**
     * Returns true if the passed node is selected, else false.
     * @param {HTMLElement/Number/Ext.data.Model} node The node, node index or record to check
     * @return {Boolean} True if selected, else false
     * @since 2.3.0
     */
    isSelected: function(node) {
        var r = this.getRecord(node);

        return this.getSelectionModel().isSelected(r);
    },

    /**
     * Selects a record instance by record instance or index.
     * @param {Ext.data.Model[]/Number} records An array of records or an index
     * @param {Boolean} keepExisting
     * @param {Boolean} suppressEvent Set to false to not fire a select event
     * @deprecated 4.0 Use {@link Ext.selection.Model#select} instead.
     * @since 2.3.0
     */
    select: function(records, keepExisting, suppressEvent) {
        this.getSelectionModel().select(records, keepExisting, suppressEvent);
    },

    /**
     * Deselects a record instance by record instance or index.
     * @param {Ext.data.Model[]/Number} records An array of records or an index
     * @param {Boolean} suppressEvent Set to false to not fire a deselect event
     * @since 2.3.0
     */
    deselect: function(records, suppressEvent) {
        this.getSelectionModel().deselect(records, suppressEvent);
    },

    /**
     * Gets a template node.
     * @param {HTMLElement/String/Number/Ext.data.Model} nodeInfo An HTMLElement template node,
     * index of a template node, the id of a template node or the record associated with the node.
     * @return {HTMLElement} The node or null if it wasn't found
     * @since 2.3.0
     */
    getNode: function(nodeInfo) {
        var me = this,
            out;

        if (me.rendered && (nodeInfo || nodeInfo === 0)) {
            if (Ext.isString(nodeInfo)) {
                // Id
                out = document.getElementById(nodeInfo);
            }
            else if (nodeInfo.isModel) {
                // Record
                out = me.getNodeByRecord(nodeInfo);
            }
            else if (Ext.isNumber(nodeInfo)) {
                // Index
                out = me.all.elements[nodeInfo];
            }
            else {
                if (nodeInfo.target && nodeInfo.target.nodeType) {
                    // An event. Check that target is a node: <a target="_blank">
                    // must pass unchanged
                    nodeInfo = nodeInfo.target;
                }

                // already an HTMLElement
                out = Ext.fly(nodeInfo).findParent(me.itemSelector, me.getTargetEl());
            }
        }

        return out || null;
    },

    /**
     * @private
     */
    getNodeByRecord: function(record) {
        var index = this.store.indexOf(record);

        return this.all.elements[index] || null;
    },

    /**
     * Gets a range nodes.
     * @param {Number} start (optional) The index of the first node in the range
     * @param {Number} end (optional) The index of the last node in the range
     * @return {HTMLElement[]} An array of nodes
     * @since 2.3.0
     */
    getNodes: function(start, end) {
        var all = this.all;

        if (end !== undefined) {
            end++;
        }

        return all.slice(start, end);
    },

    /**
     * Finds the index of the passed node.
     * @param {HTMLElement/String/Number/Ext.data.Model} node An HTMLElement template node,
     * index of a template node, the id of a template node or a record associated with a node.
     * @return {Number} The index of the node or -1
     * @since 2.3.0
     */
    indexOf: function(node) {
        node = this.getNode(node);

        if (!node && node !== 0) {
            return -1;
        }

        if (node.getAttribute('data-recordIndex')) {
            return Number(node.getAttribute('data-recordIndex'));
        }

        return this.all.indexOf(node);
    },

    doDestroy: function() {
        var me = this,
            count = me.updateSuspendCounter,
            tabGuardEl = me.tabGuardEl;

        if (me.viewScrollListeners) {
            me.viewScrollListeners.destroy();
        }

        // Can be already destroyed in Table view
        if (me.all && !me.all.destroyed) {
            me.all.clear();
        }

        if (tabGuardEl) {
            if (tabGuardEl.parentNode) {
                tabGuardEl.parentNode.removeChild(tabGuardEl);
            }
        }

        me.emptyEl = null;
        me.setItemsDraggable(false);

        me.bindStore(null);

        if (me.selModelRelayer) {
            me.selModelRelayer.destroy();
        }

        Ext.destroy(me.navigationModel, me.selectionModel, me.loadMask);

        // We have been destroyed during a begin/end update, which means we're
        // suspending layouts, must forcibly do it here.
        while (count--) {
            Ext.resumeLayouts(true);
        }

        me.callParent();
    },

    // invoked by the selection model to maintain visual UI cues
    onItemSelect: function(record) {
        var node = this.getNode(record);

        if (node) {
            Ext.fly(node).addCls(this.selectedItemCls);
            node.setAttribute('aria-selected', 'true');
        }

        return node;
    },

    // invoked by the selection model to maintain visual UI cues
    onItemDeselect: function(record) {
        var node = this.getNode(record);

        if (node) {
            Ext.fly(node).removeCls(this.selectedItemCls);
            node.setAttribute('aria-selected', 'false');
        }

        return node;
    },

    getItemSelector: function() {
        return this.itemSelector;
    },

    /**
     * Adds a CSS Class to a specific item.
     * @param {HTMLElement/String/Number/Ext.data.Model} itemInfo An HTMLElement,
     * index or instance of a model representing this item
     * @param {String} cls
     */
    addItemCls: function(itemInfo, cls) {
        var item = this.getNode(itemInfo);

        if (item) {
            Ext.fly(item).addCls(cls);
        }
    },

    /**
     * Removes a CSS Class from a specific item.
     * @param {HTMLElement/String/Number/Ext.data.Model} itemInfo An HTMLElement,
     * index or instance of a model representing this item
     * @param {String} cls
     */
    removeItemCls: function(itemInfo, cls) {
        var item = this.getNode(itemInfo);

        if (item) {
            Ext.fly(item).removeCls(cls);
        }
    },

    setStore: function(newStore) {
        // Here we want to override the config system setter because setting the store
        // is a special case that the config system wasn't able to handle.
        //
        // For instance, because `bindStore` is the only API for both binding and unbinding a store,
        // we couldn't unbind the old store using the config system because it would simply unbind
        // the new store that the setter had just poked onto the instance:
        //
        //      setStore    -> intance.store = newStore
        //      updateStore -> view.unbind(null) (unbinds the newStore)
        //
        var me = this;

        if (me.store !== newStore) {
            if (me.isConfiguring) {
                me.store = newStore;
            }
            else {
                me.bindStore(newStore, /* initial */ false);
            }
        }
    },

    privates: {
        deferRefreshForLoad: function(store) {
            return store.isLoading();
        },

        toggleChildrenTabbability: function(enableTabbing) {
            var focusEl = this.getTargetEl();

            if (enableTabbing) {
                focusEl.restoreTabbableState({ skipSelf: true });
            }
            else {
                // Do NOT includeSaved
                // Once an item has had tabbability saved, do not increment its save level
                focusEl.saveTabbableState({
                    skipSelf: true,
                    includeSaved: false
                });
            }
        },

        /**
         * @private
         * Called by refresh to collect the view item nodes.
         */
        collectNodes: function(targetEl) {
            var all = this.all,
                options = {
                    role: this.itemAriaRole
                };

            all.fill(Ext.fly(targetEl).query(this.getItemSelector()), all.startIndex || 0);

            // Subclasses may set focusable to false (BoundList is not focusable)
            if (this.focusable) {
                options.tabindex = '-1';
            }

            all.set(options);
        },

        createMask: function(mask) {
            var me = this,
                maskStore = me.getStore(),
                cfg;

            if (maskStore && !maskStore.isEmptyStore && !maskStore.loadsSynchronously()) {
                cfg = {
                    target: me,
                    msg: me.loadingText,
                    useMsg: me.loadingUseMsg,
                    // The store gets bound in initComponent, so while
                    // rendering let's push on the store
                    store: maskStore
                };

                // Do not overwrite default msgCls if we do not have a loadingCls
                if (me.loadingCls) {
                    cfg.msgCls = me.loadingCls;
                }

                // either a config object 
                if (Ext.isObject(mask)) {
                    cfg = Ext.apply(cfg, mask);
                }

                // Attach the LoadMask to a *Component* so that it can be sensitive to resizing
                // during long loads. If this DataView is floating, then mask this DataView.
                // Otherwise, mask its owning Container (or this, if there *is* no owning Container)
                // LoadMask captures the element upon render.
                me.loadMask = new Ext.LoadMask(cfg);

                me.loadMask.on({
                    scope: me,
                    beforeshow: me.onMaskBeforeShow,
                    hide: me.onMaskHide
                });
            }

            return me.loadMask;
        },

        /**
         * @private
         * This method returns the inner node containing element. This is useful
         * for the bufferedRenderer or for when the view contains extra elements
         * and we need to point the exact element that will contain the view nodes.
         */
        getNodeContainer: function() {
            var target = this.getTargetEl(),
                selector = this.nodeContainerSelector;

            return selector ? target.down(selector, true) : target;
        },

        getOverflowEl: function() {
            // The desired behavior here is just to inherit from the superclass.  However,
            // the superclass method calls this.getTargetEl, which sends us into an infinte
            // loop because our getTargetEl may call getScrollerEl(), which calls getOverflowEl()
            return Ext.Component.prototype.getTargetEl.call(this);
        }
    }
}, function() {
    // all of this information is available directly
    // from the SelectionModel itself, the only added methods
    // to DataView regarding selection will perform some transformation/lookup
    // between HTMLElement/Nodes to records and vice versa.
    Ext.deprecate('extjs', '4.0', function() {
        Ext.view.AbstractView.override({
            /**
             * @cfg {Boolean} [multiSelect=false]
             * True to allow selection of more than one item at a time, false to allow selection
             * of only a single item at a time or no selection at all, depending on the value of
             * {@link #singleSelect}.
             * @deprecated 4.0 Use {@link Ext.selection.Model#cfg-mode} 'MULTI' instead.
             * @since 2.3.0
             */

            /**
             * @cfg {Boolean} [singleSelect]
             * Allows selection of exactly one item at a time. As this is the default selection mode
             * anyway, this config is completely ignored.
             * @removed 4.0 Use {@link Ext.selection.Model#cfg-mode} 'SINGLE' instead.
             * @since 2.3.0
             */

            /**
             * @cfg {Boolean} [simpleSelect=false]
             * True to enable multiselection by clicking on multiple items without requiring
             * the user to hold Shift or Ctrl, false to force the user to hold Ctrl or Shift
             * to select more than on item.
             * @deprecated 4.0 Use {@link Ext.selection.Model#cfg-mode} 'SIMPLE' instead.
             * @since 2.3.0
             */

            /**
             * Gets the number of selected nodes.
             * @return {Number} The node count
             * @deprecated 4.0 Use {@link Ext.selection.Model#getCount} instead.
             * @since 2.3.0
             */
            getSelectionCount: function() {
                if (Ext.global.console) {
                    Ext.global.console.warn(
                        "DataView: getSelectionCount will be removed, please interact " +
                        "with the Ext.selection.DataViewModel"
                    );
                }

                return this.selModel.getSelection().length;
            },

            /**
             * Gets an array of the selected records
             * @return {Ext.data.Model[]} An array of {@link Ext.data.Model} objects
             * @deprecated 4.0 Use {@link Ext.selection.Model#getSelection} instead.
             * @since 2.3.0
             */
            getSelectedRecords: function() {
                if (Ext.global.console) {
                    Ext.global.console.warn(
                        "DataView: getSelectedRecords will be removed, please interact " +
                        "with the Ext.selection.DataViewModel"
                    );
                }

                return this.selModel.getSelection();
            },

            // documented above
            // @ignore
            select: function(records, keepExisting, supressEvents) {
                if (Ext.global.console) {
                    Ext.global.console.warn(
                        "DataView: select will be removed, please access select through " +
                        "a DataView's SelectionModel, ie: view.getSelectionModel().select()"
                    );
                }

                // eslint-disable-next-line vars-on-top
                var sm = this.getSelectionModel();

                return sm.select.apply(sm, arguments);
            },

            /**
             * Deselects all selected records.
             * @deprecated 4.0 Use {@link Ext.selection.Model#deselectAll} instead.
             * @since 2.3.0
             */
            clearSelections: function() {
                if (Ext.global.console) {
                    Ext.global.console.warn(
                        "DataView: clearSelections will be removed, please access " +
                        "deselectAll through DataView's SelectionModel, ie: " +
                        "view.getSelectionModel().deselectAll()"
                    );
                }

                // eslint-disable-next-line vars-on-top
                var sm = this.getSelectionModel();

                return sm.deselectAll();
            }
        });
    });
});
