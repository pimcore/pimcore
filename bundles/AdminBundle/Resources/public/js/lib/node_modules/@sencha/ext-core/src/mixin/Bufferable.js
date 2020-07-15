/**
 * This class makes buffered methods simple and also handles cleanup on `destroy`.
 *
 *      Ext.define('Foo', {
 *          mixins: [
 *              'Ext.mixin.Bufferable'
 *          ],
 *
 *          bufferableMethods: {
 *              // Provides a "foobar" method that calls "doFoobar" with the
 *              // most recent arguments but delayed by 50ms from the last
 *              // call. Calls to "foobar" made during the 50ms wait restart
 *              // the timer and replace the arguments.
 *
 *              foobar: 50
 *          },
 *
 *          method: function() {
 *              this.foobar(42);  // call doFoobar in 50ms
 *
 *              if (this.isFoobarPending) {
 *                  // test if "foobar" is pending
 *              }
 *
 *              this.flushFoobar();  // actually, call it now
 *
 *              this.cancelFoobar(); // or never mind
 *          },
 *
 *          doFoobar: function() {
 *              // time to do the "foobar" thing
 *          }
 *      });
 *
 * @since 6.5.0
 * @private
 */
Ext.define('Ext.mixin.Bufferable', function(Bufferable) { return { // eslint-disable-line brace-style, max-len
    extend: 'Ext.Mixin',

    mixinConfig: {
        id: 'bufferable',

        after: {
            destroy: 'cancelAllCalls'
        },

        before: {
            // The bufferables need to be destroyed before they get nulled
            $reap: 'cancelAllCalls'
        },

        extended: function(baseClass, derivedClass, classBody) {
            var bufferableMethods = classBody.bufferableMethods;

            if (bufferableMethods) {
                delete classBody.bufferableMethods;

                Bufferable.processClass(derivedClass, bufferableMethods);
            }
        }
    },

    afterClassMixedIn: function(targetClass) {
        Bufferable.processClass(targetClass);
    },

    privates: {
        /**
         * Cancel all pending `bufferableMethod` calls on this object.
         * @since 6.5.0
         * @private
         */
        cancelAllCalls: function() {
            var bufferables = this.bufferables,
                name;

            if (bufferables) {
                for (name in bufferables) {
                    bufferables[name].cancel();
                    delete bufferables[name];
                }
            }
        },

        /**
         * Cancel a specific pending `bufferableMethod` call on this object.
         * @param {String} name The name of the buffered method to cancel.
         * @param {Boolean} invoke (private)
         * @return {Boolean} Returns `true` if a cancellation occurred.
         * @since 6.5.0
         * @private
         */
        cancelBufferedCall: function(name, invoke) {
            var bufferables = this.bufferables,
                timer = bufferables && bufferables[name];

            if (timer) {
                timer[invoke ? 'invoke' : 'cancel']();
            }

            return !!timer;
        },

        /**
         * Flushes a specific pending `bufferableMethod` call on this object if one is
         * pending.
         * @param {String} name The name of the buffered method to cancel.
         * @return {Boolean} Returns `true` if a flush occurred.
         * @since 6.5.0
         * @private
         */
        flushBufferedCall: function(name) {
            return this.cancelBufferedCall(name, true);
        },

        /**
         * This method initializes an instance when the first bufferable method is called.
         * It merges an instance-level `bufferableMethods` config if present. This allows
         * an instance to change the buffer timeouts, even to 0 to disable buffering.
         *
         *      Ext.create({
         *          ...
         *          bufferableMethods: {
         *              foobar: 0
         *          }
         *      });
         *
         * Note, this method cannot effect unbuffered methods. The `bufferableMethods`
         * config only instruments buffered methods when used on a class declaration.
         *
         * @return {Object}
         * @since 6.5.0
         * @private
         */
        initBufferables: function() {
            var me = this,
                methods = me.hasOwnProperty('bufferableMethods') && me.bufferableMethods,
                classMethods;

            if (methods) {
                Bufferable._canonicalize(methods);

                classMethods = me.self.prototype.bufferableMethods;

                me.bufferableMethods = Ext.merge(Ext.clone(classMethods), methods);
            }

            return (me.bufferables = {});
        },

        /**
         * Returns `true` if a specific `bufferableMethod` is pending.
         * @param {String} name The name of the buffered method to cancel.
         * @return {Boolean}
         * @since 6.5.0
         * @private
         */
        isCallPending: function(name) {
            var bufferables = this.bufferables,
                timer = bufferables && bufferables[name];

            return !!timer;
        },

        statics: {
            SINGLE: { single: true },

            _canonicalize: function(methods) {
                var t, def, s, name;

                for (name in methods) {
                    s = Ext.String.capitalize(name);
                    def = methods[name];
                    t = typeof def;

                    if (t === 'number' || t === 'string') {
                        // method: 50
                        // method: 'asap'
                        // method: 'idle'
                        // method: 'raf'
                        methods[name] = def = {
                            delay: def
                        };
                    }

                    if (typeof(t = def.delay) === 'string') {
                        // method: {
                        //     delay: 'asap'
                        // }
                        def[t] = true;
                        delete def.delay;
                    }

                    def.capitalized = s;
                    def.name = name;

                    if (!def.fn) {
                        def.fn = 'do' + s;
                    }

                    if (!def.flag) {
                        def.flag = 'is' + s + 'Pending';
                    }
                }
            },

            _canceller: function() {
                var timer = this, // this fn is "cancel()" on timer instances
                    id = timer.id;

                if (id) {
                    if (timer.delay) {
                        Ext.undefer(id);
                    }
                    else if (timer.asap) {
                        Ext.unasap(id);
                    }
                    else if (timer.idle) {
                        Ext.un('idle', id, null, Bufferable.SINGLE);
                    }
                    else if (timer.raf) {
                        Ext.unraf(id);
                    }

                    timer.id = null;
                }

                timer.args = null;
                timer.target[timer.flag] = false;
            },

            _invoker: function() {
                var timer = this, // this fn is "invoke()" on timer instances
                    args = timer.args || Ext.emptyArray,
                    target = timer.target;

                //<debug>
                ++timer.invokes;
                //</debug>

                timer.cancel();
                target[timer.fn].apply(target, args);
            },

            delayCall: function(target, def, args) {
                if (target.destroying) {
                    return;
                }

                // eslint-disable-next-line vars-on-top
                var bufferables = target.bufferables || target.initBufferables(),
                    name = def.name,
                    timer = bufferables[name] || (bufferables[name] = Ext.apply({
                        //<debug>
                        calls: 0,
                        invokes: 0,
                        //</debug>
                        args: null,
                        cancel: Bufferable._canceller,
                        id: null,
                        target: target,
                        invoke: Bufferable._invoker
                    }, def)),
                    delay = def.delay,
                    exec = function() {
                        if (timer.id) {
                            timer.id = null;
                            timer.invoke();
                        }
                    };

                if (timer.id) {
                    timer.cancel();
                }

                timer.args = args;
                //<debug>
                ++timer.calls;
                //</debug>

                target[timer.flag] = true;

                if (delay) {
                    timer.id = Ext.defer(exec, delay);
                }
                else if (def.asap) {
                    timer.id = Ext.asap(exec);
                }
                else if (def.idle) {
                    timer.id = exec;
                    Ext.on('idle', exec, null, Bufferable.SINGLE);
                }
                else if (def.raf) {
                    timer.id = Ext.raf(exec);
                }
                else {
                    // allow bufferableMethods: { foo: 0 } to force immediate call
                    timer.invoke();
                }
            },

            processClass: function(cls, bufferableMethods) {
                var proto = cls.prototype,
                    inherited = proto.bufferableMethods,
                    def, name;

                if (bufferableMethods) { // if (derived class)
                    Bufferable._canonicalize(bufferableMethods);

                    if (inherited) {
                        // If we have a derived class, it could be just adjusting the
                        // configuration, not introducing new properties, so clone the
                        // inherited config and merge on the one from the classBody.
                        inherited = Ext.merge(Ext.clone(inherited), bufferableMethods);
                    }

                    proto.bufferableMethods = inherited || bufferableMethods;
                }
                else {
                    // else we are being mixed in, so the bufferableMethods on the
                    // prototype almost certainly belong to the immediate user class
                    // that is mixing us in... (leave the config on the prototype)
                    bufferableMethods = inherited;
                    Bufferable._canonicalize(bufferableMethods);

                    // prevent shape change
                    proto.bufferables = null;
                }

                if (bufferableMethods) {
                    for (name in bufferableMethods) {
                        if (!proto[name]) {
                            def = bufferableMethods[name];
                            Bufferable.processMethod(proto, def, Array.prototype.slice);
                        }
                    }
                }
            },

            processMethod: function(proto, def, slice) {
                var name = def.name,
                    cap = def.capitalized;

                proto[name] = function() {
                    return Bufferable.delayCall(this, def, slice.call(arguments));
                };

                proto['cancel' + cap] = function() {
                    return this.cancelBufferedCall(name);
                };

                proto['flush' + cap] = function() {
                    return this.flushBufferedCall(name);
                };
            }
        } // statics
    } // privates
};
});
