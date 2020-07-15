/**
 * This mixin provides a `dirty` config that tracks the modified state of an object. If
 * the class using this mixin is {@link Ext.mixin.Observable observable}, changes to the
 * `dirty` config will fire the `dirtychange` event.
 * @protected
 * @since 6.2.0
 */
Ext.define('Ext.mixin.Dirty', {
    mixinId: 'dirty',

    /**
     * @event dirtychange
     * Fires when a change in the object's {@link #cfg-dirty} state is detected.
     *
     * **Note:** In order for this event to fire, the class that mixes in this mixin
     * must be `{@link Ext.mixin.Observable Observable}`.
     *
     * @param {Ext.Base} this
     * @param {Boolean} dirty Whether or not the object is now dirty.
     */

    config: {
        /**
         * @cfg {Boolean} dirty
         * This config property describes the modified state of this object. In most
         * cases this config's value is maintained by the object and should be considered
         * readonly. The class implementor should be the only one to call the setter.
         */
        dirty: {
            $value: false,
            lazy: true
        }
    },

    dirty: false,  // on the prototype as false (not undefined)

    /**
     * @property {Number} _dirtyRecordCount
     * The number of newly created, modified or dropped records.
     * @private
     * @readonly
     */
    _dirtyRecordCount: 0,

    /**
     * @cfg {Boolean} ignoreDirty
     * This config property indicates that the `dirty` state of this object should be
     * ignored. Because this capability is mixed in at a class level, this config can
     * be helpful when some instances do not participate in dirty state tracking.
     *
     * This option should be set at construction time. When set to `true`, the object
     * will always have `dirty` value of `false`.
     */
    ignoreDirty: false,

    /**
     * @cfg {Boolean} recordStateIsDirtyState
     * Set this config at construction time (or on the class body) to automatically set
     * the `dirty` state based on the records passed to `trackRecordState`.
     *
     * This config defaults to `true` but only has an effect when the record tracking
     * methods are called (`trackRecordState`, `untrackRecordState` and `clearRecordStates`).
     * @protected
     */
    recordStateIsDirtyState: true,

    /**
     * Returns `true` if this object is `dirty`.
     */
    isDirty: function() {
        // This alias matches the Ext.form.field.* family.
        return this.getDirty();
    },

    applyDirty: function(dirty) {
        return this.ignoreDirty ? false : dirty;
    },

    updateDirty: function(dirty) {
        var me = this;

        // Store the property directly in case we are used in an "_dirty" world.
        me.dirty = dirty;

        if (me.fireEvent && !me.isDirtyInitializing) {
            me.fireDirtyChange();
        }
    },

    /**
     * Clears all record state tracking. This state is maintained by `trackRecordState`
     * and `untrackRecordState`.
     * @protected
     */
    clearRecordStates: function() {
        var me = this,
            counters = me._crudCounters;

        if (counters) {
            counters.C = counters.U = counters.D = 0;
        }

        me._dirtyRecordCount = 0;

        if (me.recordStateIsDirtyState) {
            me.setDirty(false);
        }
    },

    fireDirtyChange: function() {
        var me = this;

        if (!me.ignoreDirty && me.hasListeners.dirtychange) {
            me.fireEvent('dirtychange', me, me.dirty);
        }
    },

    /**
     * This method is called to track a given record in the total number of dirty records
     * (modified, created or dropped). See `untrackRecordState` and `clearRecordStates`.
     *
     * @param {Ext.data.Model} record The record to track.
     * @param {Boolean} initial Pass `true` the first time a record is introduced.
     * @return {Boolean} Returns `true` if the state of dirty records has changed.
     * @protected
     */
    trackRecordState: function(record, initial) {
        var me = this,
            counters = me._crudCounters || (me._crudCounters = { C: 0, R: 0, U: 0, D: 0 }),
            dirtyRecordCountWas = me._dirtyRecordCount,
            state = record.crudState,
            stateWas = record.crudStateWas,
            changed, dirtyRecordCount;

        if (initial || state !== stateWas) {
            if (!initial && stateWas) {
                --counters[stateWas];
            }

            if (!(record.phantom && state === 'D')) {
                ++counters[state];
            }

            //<debug>
            me.checkCounters();
            //</debug>

            me._dirtyRecordCount = dirtyRecordCount = counters.C + counters.U + counters.D;

            changed = !dirtyRecordCount !== !dirtyRecordCountWas;

            if (changed && me.recordStateIsDirtyState) {
                me.setDirty(dirtyRecordCount > 0);
            }
        }

        return changed;
    },

    /**
     * This method is called to remove the tracking of a given record from the total number
     * of dirty records (modified, created or dropped). The record passed to this method
     * must have been previously passed to `trackRecordState`.
     *
     * @param {Ext.data.Model} record The record to stop tracking.
     * @return {Boolean} Returns `true` if the state of dirty records has changed.
     * @protected
     */
    untrackRecordState: function(record) {
        var me = this,
            counters = me._crudCounters,
            dirtyRecordCountWas = me._dirtyRecordCount,
            state = record.crudState,
            changed, dirtyRecordCount;

        // If it's erased and dropped, it will have already been tracked
        if (counters && state !== 'D' && !record.erased) {
            --counters[state];

            //<debug>
            me.checkCounters();
            //</debug>

            me._dirtyRecordCount = dirtyRecordCount = counters.C + counters.U + counters.D;

            changed = !dirtyRecordCount !== !dirtyRecordCountWas;

            if (changed && me.recordStateIsDirtyState) {
                me.setDirty(dirtyRecordCount > 0);
            }
        }

        return changed;
    }

    //<debug>
    , checkCounters: function() { // eslint-disable-line comma-style
        var counters = this._crudCounters,
            key;

        for (key in counters) {
            if (counters[key] < 0) {
                Ext.raise('Invalid state for ' + key);
            }
        }
    }
    //</debug>
});
