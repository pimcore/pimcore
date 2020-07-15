/**
 * A wrapper around a DOM element that allows it to receive drops.
 *
 * ## Validity of drag operations
 *
 * There are certain conditions that govern whether a {@link Ext.drag.Source source}
 * and a target can interact. By default (without configuration), all
 * {@link Ext.drag.Source sources} and targets can interact with each other, the conditions
 * are evaluated in this order:
 *
 * ### {@link #isDisabled Disabled State}
 * If the target is disabled, the {@link Ext.drag.Source source} 
 * cannot interact with it.
 *
 * ### {@link #groups Groups}
 * Both the {@link Ext.drag.Source source} and target can belong to multiple groups. 
 * They may interact if:
 * - Neither has a group
 * - Both have one (or more) of the same group
 *
 * ### {@link #method!accepts Accept}
 * This method is called each time a {@link Ext.drag.Source source} enters this
 * target. If the method returns `false`, the drag is not considered valid.
 *
 * ## Asynchronous drop processing
 *
 *  When the drop completes, the {@link #drop} event will fire, however the underlying data
 * may not be ready to be consumed. By returning a {@link Ext.Promise Promise} from the data, 
 * it allows either:
 * - The data to be fetched (either from a remote source or generated if expensive).
 * - Any validation to take place before the drop is finalized.
 *
 * Once the promise is {@link Ext.Promise#resolve resolved} or {@link Ext.Promise#resolve rejected},
 * further processing can be completed.
 *
 * Validation example:
 *
 * 
 *      var confirmSource = new Ext.drag.Source({
 *          element: dragEl,
 *          describe: function(info) {
 *              // Provide the data up front
 *              info.setData('records', theRecords);
 *          }
 *      });  
 *
 *      var confirmTarget = new Ext.drag.Target({
 *          element: dropEl,
 *          listeners: {
 *              drop: function(target, info) {
 *                  Ext.MessageBox.confirm('Really', 'Are you sure?', function(btn) {
 *                      if (btn === 'yes') {
 *                          info.getData('records').then(function(data) {
 *                              // Process the data
 *                          });
 *                      }
 *                  });
 *              }
 *          }
 *      });
 *
 *
 * Remote data example:
 *
 *      var fetchSource = new Ext.drag.Source({
 *          element: dragEl,
 *          // The resulting drag data will be a binary blob
 *          // of image data, we don't want to fetch it up front, so
 *          // pass a callback to be executed when data is requested.
 *          describe: function(info) {
 *              info.setData('image', function() {
 *                  return Ext.Ajax.request({
 *                      url: 'data.json'
 *                      // some options
 *                  }).then(function(result) {
 *                      var imageData;
 *                      // Do some post-processing
 *                      return imageData;
 *                  }, function() {
 *                      return Ext.Promise.reject('Something went wrong!');
 *                  });
 *              });
 *          }
 *      });
 *
 *      var fetchTarget = new Ext.drag.Target({
 *          element: dropEl,
 *          accepts: function(info) {
 *              return info.types.indexOf('image') > -1;
 *          },
 *          listeners: {
 *              drop: function(target, info) {
 *                  info.getData('image').then(function() {
 *                      // All good, show the image
 *                  }, function() {
 *                      // Handle failure case
 *                  });
 *              }
 *          }
 *      });
 * 
 */
Ext.define('Ext.drag.Target', {
    extend: 'Ext.drag.Item',

    requires: ['Ext.drag.Manager'],

    defaultIdPrefix: 'target-',

    config: {
        /**
         * @cfg {String} invalidCls
         * A class to add to the {@link #element} when an
         * invalid drag is over this target.
         */
        invalidCls: '',

        /**
         * @cfg {String} validCls
         * A class to add to the {@link #element} when an
         * invalid drag is over this target.
         */
        validCls: ''
    },

    /**
     * @cfg {Function} accepts
     * See {@link #method-accepts}.
     */

    /**
     * @event beforedrop
     * Fires before a valid drop occurs. Return `false` to prevent the drop from
     * completing.
     *
     * @param {Ext.drag.Target} this This target.
     * @param {Ext.drag.Info} info The drag info.
     */

    /**
     * @event drop
     * Fires when a valid drop occurs.
     *
     * @param {Ext.drag.Target} this This target.
     * @param {Ext.drag.Info} info The drag info.
     */

    /**
     * @event dragenter
     * Fires when a drag enters this target.
     *
     * @param {Ext.drag.Target} this This target.
     * @param {Ext.drag.Info} info The drag info.
     */ 

    /**
     * @event dragleave
     * Fires when a source leaves this target.
     *
     * @param {Ext.drag.Target} this This target.
     * @param {Ext.drag.Info} info The drag info.
     */ 

    /**
     * @event dragmove
     * Fires when a drag moves while inside this target.
     *
     * @param {Ext.drag.Target} this This target.
     * @param {Ext.drag.Info} info The drag info.
     */ 

    constructor: function(config) {
        var me = this,
            accepts = config && config.accepts;

        if (accepts) {
            me.accepts = accepts;
            // Don't mutate the object the user passed. Need to do this
            // here otherwise initConfig will complain about writing over
            // the method.
            config = Ext.apply({}, config);
            delete config.accepts;
        }

        me.callParent([config]);

        Ext.drag.Manager.register(me);
    },

    /**
     * Called each time a {@link Ext.drag.Source source} enters this target.
     * Allows this target to indicate whether it will interact with
     * the given drag. Determined after {@link #isDisabled} and 
     * {@link #groups} checks. If either of the aforementioned conditions
     * means the target is not valid, this will not be called.
     *
     * Defaults to returning `true`.
     * 
     * @param {Ext.drag.Info} info The drag info.
     * @return {Boolean} `true` if the drag is valid for this target.
     *
     * @protected
     */
    accepts: function(info) {
        return true;
    },

    /**
     * @method disable
     * @inheritdoc
     */
    disable: function() {
        this.callParent();
        this.setupListeners(null);
    },

    /**
     * @method enable
     * @inheritdoc
     */
    enable: function() {
        this.callParent();
        this.setupListeners();
    },

    /**
     * @method
     * Called before a drag finishes on this target. Return `false` to veto
     * the drop.
     * @param {Ext.drag.Info} info The drag info.
     * @return {Boolean} `false` to veto the drop.
     *
     * @protected
     * @template
     */
    beforeDrop: Ext.emptyFn,

    /**
     * @method
     * Called when a drag is dropped on this target.
     * @param {Ext.drag.Info} info The drag info.
     *
     * @protected
     * @template
     */
    onDrop: Ext.emptyFn,

    /**
     * @method
     * Called when a drag enters this target.
     * @param {Ext.drag.Info} info The drag info.
     *
     * @protected
     * @template
     */
    onDragEnter: Ext.emptyFn,

    /**
     * @method
     * Called when a source leaves this target.
     * @param {Ext.drag.Info} info The drag info.
     *
     * @protected
     * @template
     */
    onDragLeave: Ext.emptyFn,

    /**
     * @method
     * Called when a drag is moved while inside this target.
     * @param {Ext.drag.Info} info The drag info.
     *
     * @protected
     * @template
     */
    onDragMove: Ext.emptyFn,

    updateInvalidCls: function(invalidCls, oldInvalidCls) {
        var info = this.info;

        this.doUpdateCls(info && !info.valid, invalidCls, oldInvalidCls);
    },

    updateValidCls: function(validCls, oldValidCls) {
        var info = this.info;

        this.doUpdateCls(info && info.valid, validCls, oldValidCls);
    },

    destroy: function() {
        Ext.drag.Manager.unregister(this);

        this.callParent();
    },

    privates: {
        /**
         * Removes a class and replaces it with a new one, if the old class
         * was already on the element.
         *
         * @param {Boolean} needsAdd `true` if the new class needs adding.
         * @param {String} cls The new class to add.
         * @param {String} oldCls The old class to remove.
         *
         * @private
         */
        doUpdateCls: function(needsAdd, cls, oldCls) {
            var el = this.getElement();

            if (oldCls) {
                el.removeCls(oldCls);
            }

            if (cls && needsAdd) {
                el.addCls(cls);
            }
        },

        /**
         * @method getElListeners
         * @inheritdoc
         */
        getElListeners: function() {
            return {
                dragenter: 'handleNativeDragEnter',
                dragleave: 'handleNativeDragLeave',
                dragover: 'handleNativeDragMove',
                drop: 'handleNativeDrop'
            };
        },

        /**
         * Called when a drag is dropped on this target.
         * @param {Ext.drag.Info} info The drag info.
         *
         * @private
         */
        handleDrop: function(info) {
            var me = this,
                hasListeners = me.hasListeners,
                valid = info.valid;

            me.getElement().removeCls([me.getInvalidCls(), me.getValidCls()]);

            if (valid && me.beforeDrop(info) !== false) {
                if (hasListeners.beforedrop && me.fireEvent('beforedrop', me, info) === false) {
                    return false;
                }

                me.onDrop(info);

                if (hasListeners.drop) {
                    me.fireEvent('drop', me, info);
                }
            }
            else {
                return false;
            }
        },

        /**
         * Called when a drag enters this target.
         * @param {Ext.drag.Info} info The drag info.
         *
         * @private
         */
        handleDragEnter: function(info) {
            var me = this,
                cls = info.valid ? me.getValidCls() : me.getInvalidCls();

            if (cls) {
                me.getElement().addCls(cls);
            }

            me.onDragEnter(info);

            if (me.hasListeners.dragenter) {
                me.fireEvent('dragenter', me, info);
            }
        },

        /**
         * Called when a source leaves this target.
         * @param {Ext.drag.Info} info The drag info.
         *
         * @private
         */
        handleDragLeave: function(info) {
            var me = this;

            me.getElement().removeCls([me.getInvalidCls(), me.getValidCls()]);
            me.onDragLeave(info);

            if (me.hasListeners.dragleave) {
                me.fireEvent('dragleave', me, info);
            }
        },

        /**
         * Called when a drag is moved while inside this target.
         * @param {Ext.drag.Info} info The drag info.
         *
         * @private
         */
        handleDragMove: function(info) {
            var me = this;

            me.onDragMove(info);

            if (me.hasListeners.dragmove) {
                me.fireEvent('dragmove', me, info);
            }
        },

        /**
         * Handle a native drag enter.
         * @param {Ext.event.Event} e The event.
         * 
         * @private
         */
        handleNativeDragEnter: function(e) {
            var me = this,
                info = Ext.drag.Manager.getNativeDragInfo(e);

            info.onNativeDragEnter(me, e);

            if (me.hasListeners.dragenter) {
                me.fireEvent('dragenter', me, info);
            }
        },

        /**
         * Handle a native drag leave.
         * @param {Ext.event.Event} e The event.
         * 
         * @private
         */
        handleNativeDragLeave: function(e) {
            var me = this,
                info = Ext.drag.Manager.getNativeDragInfo(e);

            info.onNativeDragLeave(me, e);

            if (me.hasListeners.dragleave) {
                me.fireEvent('dragleave', me, info);
            }
        },

        /**
         * Handle a native drag move.
         * @param {Ext.event.Event} e The event.
         * 
         * @private
         */
        handleNativeDragMove: function(e) {
            var me = this,
                info = Ext.drag.Manager.getNativeDragInfo(e);

            info.onNativeDragMove(me, e);

            if (me.hasListeners.dragmove) {
                me.fireEvent('dragmove', me, info);
            }
        },

        /**
         * Handle a native drop.
         * @param {Ext.event.Event} e The event.
         * 
         * @private
         */
        handleNativeDrop: function(e) {
            var me = this,
                hasListeners = me.hasListeners,
                info = Ext.drag.Manager.getNativeDragInfo(e),
                valid = info.valid;

            info.onNativeDrop(me, e);

            if (valid) {
                if (hasListeners.beforedrop && me.fireEvent('beforedrop', me, info) === false) {
                    return;
                }

                if (hasListeners.drop) {
                    me.fireEvent('drop', me, info);
                }
            }
        }
    }
});
