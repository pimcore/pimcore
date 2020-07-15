/**
 * @singleton
 * @private
 */
Ext.define('Ext.perf.Monitor', {
    singleton: true,
    alternateClassName: 'Ext.Perf',

    requires: [
        'Ext.perf.Accumulator'
    ],

    constructor: function() {
        this.accumulators = [];
        this.accumulatorsByName = {};
    },

    calibrate: function() {
        var accum = new Ext.perf.Accumulator('$'),
            total = accum.total,
            getTimestamp = Ext.perf.Accumulator.getTimestamp,
            count = 0,
            frame,
            endTime,
            startTime;

        startTime = getTimestamp();

        do {
            frame = accum.enter();
            frame.leave();
            ++count;
        } while (total.sum < 100);

        endTime = getTimestamp();

        return (endTime - startTime) / count;
    },

    get: function(name) {
        var me = this,
            accum = me.accumulatorsByName[name];

        if (!accum) {
            me.accumulatorsByName[name] = accum = new Ext.perf.Accumulator(name);
            me.accumulators.push(accum);
        }

        return accum;
    },

    enter: function(name) {
        return this.get(name).enter();
    },

    monitor: function(name, fn, scope) {
        this.get(name).monitor(fn, scope);
    },

    report: function() {
        var me = this,
            accumulators = me.accumulators,
            calibration = me.calibrate();

        accumulators.sort(function(a, b) {
            return (a.name < b.name) ? -1 : ((b.name < a.name) ? 1 : 0);
        });

        me.updateGC();

        Ext.log('Calibration: ' + Math.round(calibration * 100) / 100 + ' msec/sample');
        Ext.each(accumulators, function(accum) {
            Ext.log(accum.format(calibration));
        });
    },

    getData: function(all) {
        var ret = {},
            accumulators = this.accumulators;

        Ext.each(accumulators, function(accum) {
            if (all || accum.count) {
                ret[accum.name] = accum.getData();
            }
        });

        return ret;
    },

    reset: function() {
        Ext.each(this.accumulators, function(accum) {
            var me = accum;

            me.count = me.childCount = me.depth = me.maxDepth = 0;

            me.pure = {
                min: Number.MAX_VALUE,
                max: 0,
                sum: 0
            };

            me.total = {
                min: Number.MAX_VALUE,
                max: 0,
                sum: 0
            };
        });
    },

    updateGC: function() {
        var accumGC = this.accumulatorsByName.GC,
            toolbox = Ext.senchaToolbox,
            bucket;

        if (accumGC) {
            accumGC.count = toolbox.garbageCollectionCounter || 0;

            if (accumGC.count) {
                bucket = accumGC.pure;
                accumGC.total.sum = bucket.sum = toolbox.garbageCollectionMilliseconds;
                bucket.min = bucket.max = bucket.sum / accumGC.count;
                bucket = accumGC.total;
                bucket.min = bucket.max = bucket.sum / accumGC.count;
            }
        }
    },

    watchGC: function() {
        var toolbox = Ext.senchaToolbox;

        Ext.perf.getTimestamp(); // initializes SenchaToolbox (if available)

        if (toolbox) {
            this.get("GC");
            toolbox.watchGarbageCollector(false); // no logging, just totals
        }
    },

    setup: function(config) {
        var key, prop,
            accum, className, methods;

        if (!config) {
            config = {
                /* insertHtml: {
                    'Ext.dom.Helper': 'insertHtml'
                }, */
                /* xtplCompile: {
                    'Ext.XTemplateCompiler': 'compile'
                }, */
                //                doInsert: {
                //                    'Ext.Template': 'doInsert'
                //                },
                //                applyOut: {
                //                    'Ext.XTemplate': 'applyOut'
                //                },
                render: {
                    'Ext.Component': 'render'
                },
                //                fnishRender: {
                //                    'Ext.Component': 'finishRender'
                //                },
                //                renderSelectors: {
                //                    'Ext.Component': 'applyRenderSelectors'
                //                },
                //                compAddCls: {
                //                    'Ext.Component': 'addCls'
                //                },
                //                compRemoveCls: {
                //                    'Ext.Component': 'removeCls'
                //                },
                //                getStyle: {
                //                    'Ext.core.Element': 'getStyle'
                //                },
                //                setStyle: {
                //                    'Ext.core.Element': 'setStyle'
                //                },
                //                addCls: {
                //                    'Ext.core.Element': 'addCls'
                //                },
                //                removeCls: {
                //                    'Ext.core.Element': 'removeCls'
                //                },
                //                measure: {
                //                    'Ext.layout.component.Component': 'measureAutoDimensions'
                //                },
                //                moveItem: {
                //                    'Ext.layout.Layout': 'moveItem'
                //                },
                //                layoutFlush: {
                //                    'Ext.layout.Context': 'flush'
                //                },
                layout: {
                    'Ext.layout.Context': 'run'
                }
            };
        }

        this.currentConfig = config;

        for (key in config) {
            if (config.hasOwnProperty(key)) {
                prop = config[key];
                accum = Ext.Perf.get(key);

                for (className in prop) {
                    if (prop.hasOwnProperty(className)) {
                        methods = prop[className];
                        accum.tap(className, methods);
                    }
                }
            }
        }

        this.watchGC();
    },

    // This is a quick hack for now
    setupLog: function(config) {
        var className, cls, methods, method, override;

        for (className in config) {
            if (config.hasOwnProperty(className)) {
                cls = Ext.ClassManager.get(className);

                if (cls) {
                    methods = config[className];

                    override = {};

                    for (method in methods) {
                        override[method] = (function(methodName, idProp) {
                            return function() {
                                var before, diff, id, idHolder, ret;

                                before = +Date.now();
                                ret = this.callParent(arguments);
                                diff = +Date.now() - before;

                                if (window.console && diff > 0) {
                                    /* eslint-disable multiline-ternary, no-multi-spaces, indent */
                                    idHolder = idProp === 'this'          ? this
                                             : typeof idProp === 'string' ? this[idProp]
                                             : typeof idProp === 'number' ? arguments[idProp]
                                             :                              null
                                             ;
                                    /* eslint-enable */

                                    if (idHolder) {
                                        id = idHolder.id;
                                    }

                                    if (id != null) {
                                        console.log(methodName + ' for ' + id + ': ' + diff + 'ms');
                                    }
                                    else {
                                        console.log(methodName + ' for unknown: ' + diff + 'ms');
                                    }

                                    if (console.trace) {
                                        console.trace();
                                    }
                                }

                                return ret;
                            };
                        })(method, methods[method]);
                    }

                    Ext.override(cls, override);
                }
            }
        }
    }
});
