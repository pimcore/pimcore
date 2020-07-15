/**
 * Tracks what records are currently selected in a databound widget. This class is mixed in to
 * {@link Ext.view.View dataview} and all subclasses.
 * @private
 */
Ext.define('Ext.mixin.Selectable', {
    extend: 'Ext.Mixin',

    mixinConfig: {
        id: 'selectable',
        after: {
            updateStore: 'updateStore'
        }
    },

    /**
     * @event selectionchange
     * Fires when a selection changes.
     * @param {Ext.mixin.Selectable} this
     * @param {Ext.data.Model[]} records The records whose selection has changed.
     */

    config: {
        /**
         * @cfg {Boolean} disableSelection
         * Set to `true` to disable selection.
         * This configuration will lock the selection model that the DataView uses.
         * @accessor
         */
        disableSelection: null,

        /**
         * @cfg {'SINGLE'/'SIMPLE'/'MULTI'} mode
         * Modes of selection.
         * @accessor
         */
        mode: 'SINGLE',

        /**
         * @cfg {Boolean} allowDeselect
         * Allow users to deselect a record in a DataView, List or Grid. Only applicable when
         * the Selectable's `mode` is `'SINGLE'`.
         * @accessor
         */
        allowDeselect: false,

        /**
         * @cfg {Ext.data.Model} lastSelected
         * @private
         * @accessor
         */
        lastSelected: null,

        /**
         * @cfg {Ext.data.Model} lastFocused
         * @private
         * @accessor
         */
        lastFocused: null,

        /**
         * @cfg {Boolean} deselectOnContainerClick
         * Set to `true` to deselect current selection when the container body is clicked.
         * @accessor
         */
        deselectOnContainerClick: true,

        /**
         * @cfg {Ext.util.Collection} selected
         * A {@link Ext.util.Collection} instance, or configuration object used to create
         * the collection of selected records.
         * @readonly
         */
        selected: true,

        /**
         * @cfg {Boolean} pruneRemoved
         * Remove records from the selection when they are removed from the store.
         *
         * **Important:** When using {@link Ext.toolbar.Paging paging} or a
         * {@link Ext.data.BufferedStore}, records which are cached in the Store's
         * {@link Ext.data.Store#property-data data collection} may be removed from the Store
         * when pages change, or when rows are scrolled out of view. For this reason `pruneRemoved`
         * should be set to `false` when using a buffered Store.
         *
         * Also, when previously pruned pages are returned to the cache, the records objects
         * in the page will be *new instances*, and will not match the instances in the selection
         * model's collection. For this reason, you MUST ensure that the Model definition's
         * {@link Ext.data.Model#idProperty idProperty} references a unique key because in this
         * situation, records in the Store have their **IDs** compared to records in the
         * SelectionModel in order to re-select a record which is scrolled back into view.
         */
        pruneRemoved: true,

        /**
         * @cfg {Ext.data.Model} selection
         * The selected record.
         */
        selection: null,

        /**
         * @cfg twoWayBindable
         * @inheritdoc Ext.mixin.Bindable#cfg-twoWayBindable
         */
        twoWayBindable: {
            selection: 1
        },

        /**
         * @cfg publishes
         * @inheritdoc Ext.mixin.Bindable#cfg-publishes
         */
        publishes: {
            selection: 1
        }
    },

    modes: {
        SINGLE: true,
        SIMPLE: true,
        MULTI: true
    },

    onNavigate: function(event) {

    },

    selectableEventHooks: {
        add: 'onSelectionStoreAdd',
        remove: 'onSelectionStoreRemove',
        update: 'onSelectionStoreUpdate',
        clear: {
            fn: 'onSelectionStoreClear',
            priority: 1000
        },
        load: 'refreshSelection',
        refresh: 'refreshSelection'
    },

    initSelectable: function() {
        this.publishState('selection', this.getSelection());
    },

    applySelected: function(selected) {
        if (!selected.isCollection) {
            selected = new Ext.util.Collection(selected);
        }

        // Add this Selectable as an observer immediately so that we are informed of any
        // mutations which occur in this event run.
        selected.addObserver(this);

        return selected;
    },

    /**
     * @private
     */
    applyMode: function(mode) {
        mode = mode ? mode.toUpperCase() : 'SINGLE';

        // set to mode specified unless it doesnt exist, in that case
        // use single.
        return this.modes[mode] ? mode : 'SINGLE';
    },

    /**
     * @private
     */
    updateStore: function(newStore, oldStore) {
        var me = this,
            bindEvents = Ext.apply({}, me.selectableEventHooks, { scope: me });

        if (oldStore && Ext.isObject(oldStore) && oldStore.isStore) {
            if (oldStore.autoDestroy) {
                oldStore.destroy();
            }
            else {
                oldStore.un(bindEvents);
            }
        }

        if (newStore) {
            newStore.on(bindEvents);
            me.refreshSelection();
        }
    },

    /**
     * Selects all records.
     * @param {Boolean} silent `true` to suppress all select events.
     */
    selectAll: function(silent) {
        var me = this,
            selections = me.getStore().getRange();

        me.select(selections, true, silent);
    },

    /**
     * Deselects all records.
     */
    deselectAll: function(supress) {
        var me = this;

        me.deselect(me.getSelected().getRange(), supress);
        me.setLastSelected(null);
        me.setLastFocused(null);
    },

    updateSelection: function(selection) {
        if (this.changingSelection) {
            return;
        }

        if (selection) {
            this.select(selection);
        }
        else {
            this.deselectAll();
        }
    },

    // Provides differentiation of logic between MULTI, SIMPLE and SINGLE
    // selection modes.
    selectWithEvent: function(record) {
        var me = this,
            isSelected = me.isSelected(record);

        switch (me.getMode()) {
            case 'MULTI':
            case 'SIMPLE':
                if (isSelected) {
                    me.deselect(record);
                }
                else {
                    me.select(record, true);
                }

                break;

            case 'SINGLE':
                if (me.getAllowDeselect() && isSelected) {
                    // if allowDeselect is on and this record isSelected, deselect it
                    me.deselect(record);
                }
                else {
                    // select the record and do NOT maintain existing selections
                    me.select(record, false);
                }

                break;
        }
    },

    /**
     * Selects a range of rows if the selection model
     * {@link Ext.mixin.Selectable#getDisableSelection} is not locked.
     * All rows in between `startRecord` and `endRecord` are also selected.
     * @param {Number} startRecord The index of the first row in the range.
     * @param {Number} endRecord The index of the last row in the range.
     * @param {Boolean} [keepExisting] `true` to retain existing selections.
     */
    selectRange: function(startRecord, endRecord, keepExisting) {
        var me = this,
            store = me.getStore(),
            records = [],
            tmp, i;

        if (me.getDisableSelection()) {
            return;
        }

        // swap values
        if (startRecord > endRecord) {
            tmp = endRecord;
            endRecord = startRecord;
            startRecord = tmp;
        }

        for (i = startRecord; i <= endRecord; i++) {
            records.push(store.getAt(i));
        }

        this.doMultiSelect(records, keepExisting);
    },

    /**
     * Adds the given records to the currently selected set.
     * @param {Ext.data.Model/Array/Number} records The records to select.
     * @param {Boolean} keepExisting If `true`, the existing selection will be added to
     * (if not, the old selection is replaced).
     * @param {Boolean} suppressEvent If `true`, the `select` event will not be fired.
     */
    select: function(records, keepExisting, suppressEvent) {
        var me = this,
            record;

        if (me.getDisableSelection()) {
            return;
        }

        if (typeof records === "number") {
            records = [me.getStore().getAt(records)];
        }

        if (!records) {
            return;
        }

        if (me.getMode() === "SINGLE" && records) {
            record = records.length ? records[0] : records;
            me.doSingleSelect(record, suppressEvent);
        }
        else {
            me.doMultiSelect(records, keepExisting, suppressEvent);
        }
    },

    /**
     * Selects a single record.
     * @private
     */
    doSingleSelect: function(record, suppressEvent) {
        this.doMultiSelect([record], false, suppressEvent);
    },

    /**
     * Selects a set of multiple records.
     * @private
     */
    doMultiSelect: function(records, keepExisting, suppressEvent) {
        if (records === null || this.getDisableSelection()) {
            return;
        }

        records = !Ext.isArray(records) ? [records] : records;

        // eslint-disable-next-line vars-on-top
        var me = this,
            selected = me.getSelected(),
            selectionCount = selected.getCount(),
            store = me.getStore(),
            toRemove = [],
            record, i, len;

        if (!keepExisting && selectionCount) {
            toRemove = selected.getRange();
        }

        // Ensure they are all records
        for (i = 0, len = records.length; i < len; i++) {
            record = records[i];

            if (typeof record === 'number') {
                records[i] = store.getAt(record);
            }
        }

        // Potentially remove from, then add the selected Collection.
        // We will react to successful removal as an observer.
        // We will need to know at that time whether the event is suppressed.
        selected.suppressEvent = suppressEvent;
        selected.splice(selectionCount, toRemove, records);
        selected.suppressEvent = false;
    },

    /**
     * Deselects the given record(s). If many records are currently selected, it will only deselect
     * those you pass in.
     * @param {Number/Array/Ext.data.Model} records The record(s) to deselect. Can also be a number
     * to reference by index.
     * @param {Boolean} suppressEvent If `true` the `deselect` event will not be fired.
     */
    deselect: function(records, suppressEvent) {
        var me = this,
            selected, store, record, i, len;

        if (me.getDisableSelection()) {
            return;
        }

        records = Ext.isArray(records) ? records : [records];
        selected = me.getSelected();
        store = me.getStore();

        // Ensure they are all records
        for (i = 0, len = records.length; i < len; i++) {
            record = records[i];

            if (typeof record === 'number') {
                records[i] = store.getAt(record);
            }
        }

        // Remove the records from the selected Collection.
        // We will react to successful removal as an observer.
        // We will need to know at that time whether the event is suppressed.
        selected.suppressEvent = suppressEvent;
        selected.remove(records);
        selected.suppressEvent = false;
    },

    /**
     * @private
     * Respond to deselection. Call the onItemDeselect template method
     */
    onCollectionRemove: function(selectedCollection, chunk) {
        var me = this,
            lastSelected = me.getLastSelected(),
            records = chunk.items;

        // Keep lastSelected up to date
        if (lastSelected && !selectedCollection.contains(lastSelected)) {
            me.setLastSelected(selectedCollection.last());
        }

        me.onItemDeselect(records, selectedCollection.suppressEvent);

        if (!selectedCollection.suppressEvent) {
            me.fireSelectionChange(records);
        }
    },

    /**
     * @private
     * Respond to selection. Call the onItemSelect template method
     */
    onCollectionAdd: function(selectedCollection, adds) {
        var me = this,
            records = adds.items;

        // Keep lastSelected up to date
        me.setLastSelected(selectedCollection.last());

        me.onItemSelect(records, selectedCollection.suppressEvent);

        if (!selectedCollection.suppressEvent) {
            me.fireSelectionChange(records);
        }
    },

    // TODO: This is the job of a NavigationModel
    /**
     * Sets a record as the last focused record. This does NOT mean
     * that the record has been selected.
     * @param {Ext.data.Record} newRecord
     * @param {Ext.data.Record} oldRecord
     */
    updateLastFocused: function(newRecord, oldRecord) {
        this.onLastFocusChanged(oldRecord, newRecord);
    },

    fireSelectionChange: function(records) {
        var me = this;

        me.changingSelection = true;
        me.setSelection(me.getLastSelected() || null);
        me.changingSelection = false;
        me.fireAction('selectionchange', [me, records], 'getSelections');
    },

    /**
     * Returns the currently selected records.
     * @return {Ext.data.Model[]} The selected records.
     */
    getSelections: function() {
        return this.getSelected().getRange();
    },

    /**
     * Returns `true` if the specified row is selected.
     * @param {Ext.data.Model/Number} record The record or index of the record to check.
     * @return {Boolean}
     */
    isSelected: function(record) {
        record = Ext.isNumber(record) ? this.getStore().getAt(record) : record;

        return this.getSelected().indexOf(record) !== -1;
    },

    /**
     * Returns `true` if there is a selected record.
     * @return {Boolean}
     */
    hasSelection: function() {
        return this.getSelected().getCount() > 0;
    },

    /**
     * @private
     */
    refreshSelection: function() {
        var me = this,
            selected = me.getSelected(),
            selections = selected.getRange(),
            selectionLength = selections.length,
            storeCollection = me.getStore().getData(),
            toDeselect = [],
            toReselect = [],
            i, rec, matchingSelection;

        // Build the toDeselect list
        if (me.getPruneRemoved()) {
            // Uncover the unfiltered selection if it's there.
            // We only want to prune from the selection records whhich are
            // *really* no longer in the store.
            storeCollection = storeCollection.getSource() || storeCollection;

            for (i = 0; i < selectionLength; i++) {
                rec = selections[i];
                matchingSelection = storeCollection.get(storeCollection.getKey(rec));

                if (matchingSelection) {
                    if (matchingSelection !== rec) {
                        toDeselect.push(rec);
                        toReselect.push(matchingSelection);
                    }
                }
                else {
                    toDeselect.push(rec);
                }
            }
        }

        // Update the selected Collection.
        // Records which are no longer present will be in the toDeselect list
        // Records which have the same id which have returned will be in the toSelect list.
        // We will react to successful removal as an observer.
        // We will need to know at that time whether the event is suppressed.
        selected.suppressEvent = true;
        selected.splice(selected.getCount(), toDeselect, toReselect);
        selected.suppressEvent = false;
    },

    // prune records from the SelectionModel if
    // they were selected at the time they were
    // removed.
    onSelectionStoreRemove: function(store, records) {
        var me = this,
            selected = me.getSelected(),
            ln = records.length,
            removed, record, i;

        if (me.getDisableSelection()) {
            return;
        }

        for (i = 0; i < ln; i++) {
            record = records[i];

            if (selected.remove(record)) {
                if (me.getLastSelected() == record) { // eslint-disable-line eqeqeq
                    me.setLastSelected(null);
                }

                if (me.getLastFocused() == record) { // eslint-disable-line eqeqeq
                    me.setLastFocused(null);
                }

                removed = removed || [];
                removed.push(record);
            }
        }

        if (removed) {
            me.fireSelectionChange([removed]);
        }
    },

    onSelectionStoreClear: function(store) {
        var records = store.getData().items;

        this.onSelectionStoreRemove(store, records);
    },

    /**
     * Returns the number of selections.
     * @return {Number}
     */
    getSelectionCount: function() {
        return this.getSelected().getCount();
    },

    onSelectionStoreAdd: Ext.emptyFn,
    onSelectionStoreUpdate: Ext.emptyFn,
    onItemSelect: Ext.emptyFn,
    onItemDeselect: Ext.emptyFn,
    onLastFocusChanged: Ext.emptyFn,
    onEditorKey: Ext.emptyFn
}, function() {
    /**
     * Selects a record instance by record instance or index.
     * @member Ext.mixin.Selectable
     * @method doSelect
     * @param {Ext.data.Model/Number} records An array of records or an index.
     * @param {Boolean} keepExisting
     * @param {Boolean} suppressEvent Set to `false` to not fire a select event.
     * @deprecated 2.0.0 Please use {@link #select} instead.
     */

    /**
     * Deselects a record instance by record instance or index.
     * @member Ext.mixin.Selectable
     * @method doDeselect
     * @param {Ext.data.Model/Number} records An array of records or an index.
     * @param {Boolean} suppressEvent Set to `false` to not fire a deselect event.
     * @deprecated 2.0.0 Please use {@link #deselect} instead.
     */

    /**
     * Returns the selection mode currently used by this Selectable.
     * @member Ext.mixin.Selectable
     * @method getSelectionMode
     * @return {String} The current mode.
     * @deprecated 2.0.0 Please use {@link #getMode} instead.
     */

    /**
     * Returns the array of previously selected items.
     * @member Ext.mixin.Selectable
     * @method getLastSelected
     * @return {Array} The previous selection.
     * @deprecated 2.0.0 This method is deprecated.
     */

    /**
     * Returns `true` if the Selectable is currently locked.
     * @member Ext.mixin.Selectable
     * @method isLocked
     * @return {Boolean} True if currently locked
     * @deprecated 2.0.0 Please use {@link #getDisableSelection} instead.
     */

    /**
     * This was an internal function accidentally exposed in 1.x and now deprecated. Calling it
     * has no effect
     * @member Ext.mixin.Selectable
     * @method setLastFocused
     * @deprecated 2.0.0 This method is deprecated.
     */

    /**
     * Deselects any currently selected records and clears all stored selections.
     * @member Ext.mixin.Selectable
     * @method clearSelections
     * @deprecated 2.0.0 Please use {@link #deselectAll} instead.
     */

    /**
     * Returns the number of selections.
     * @member Ext.mixin.Selectable
     * @method getCount
     * @return {Number}
     * @deprecated 2.0.0 Please use {@link #getSelectionCount} instead.
     */

    /**
     * @cfg locked
     * @inheritdoc Ext.mixin.Selectable#cfg-disableSelection
     * @deprecated 2.0.0 Please use {@link #disableSelection} instead.
     */
});
