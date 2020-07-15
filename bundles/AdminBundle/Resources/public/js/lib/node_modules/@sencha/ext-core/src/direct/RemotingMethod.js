/**
 * @private
 * Small utility class used internally to represent a Direct method.
 */
Ext.define('Ext.direct.RemotingMethod', {

    constructor: function(config) {
        var me = this,
            params = config.params,
            len = config.len,
            metadataCfg = config.metadata,
            metadata = {},
            name, pLen, p, param;

        me.name = config.name;
        me.disableBatching = config.batched != null ? !config.batched : false;

        if (config.formHandler) {
            me.formHandler = config.formHandler;
        }
        else if (Ext.isNumeric(len)) {
            // given only the number of parameters
            me.len = len;
            me.ordered = true;
        }
        else {
            /*
             * Given an array of either
             * a) String
             * b) Objects with a name property. We may want to encode extra info in here later
             * c) Empty array signifies no mandatory parameters
             */
            me.named = true;
            me.strict = config.strict !== undefined ? config.strict : true;
            me.params = {};

            // params may not be defined for a formHandler, or named method
            // with no strict checking
            pLen = params && params.length;

            for (p = 0; p < pLen; p++) {
                param = params[p];
                name = Ext.isObject(param) ? param.name : param;
                me.params[name] = true;
            }
        }

        if (metadataCfg) {
            params = metadataCfg.params;
            len = metadataCfg.len;

            if (Ext.isNumeric(len)) {
                //<debug>
                if (len === 0) {
                    Ext.raise('metadata.len cannot be 0 ' +
                                    'for Ext Direct method ' + me.name);
                }
                //</debug>

                metadata.ordered = true;
                metadata.len = len;
            }
            else if (Ext.isArray(params)) {
                metadata.named = true;
                metadata.params = {};

                for (p = 0, pLen = params.length; p < pLen; p++) {
                    param = params[p];
                    metadata.params[param] = true;
                }

                metadata.strict = metadataCfg.strict !== undefined ? metadataCfg.strict : true;
            }
            //<debug>
            else {
                Ext.raise('metadata is neither named nor ordered ' +
                                'for Ext Direct method ' + me.name);
            }
            //</debug>

            me.metadata = metadata;
        }
    },

    /**
     * Prepare Direct function arguments that can be used with getCallData().
     */
    getArgs: function(config) {
        var me = this,
            params = config.params,
            paramOrder = config.paramOrder,
            metadata = config.metadata,
            options = config.options,
            args = [],
            flatten, i, len;

        if (me.ordered) {
            if (me.len > 0) {
                // If a paramOrder was specified, add the params into the argument list
                // in that order.
                if (paramOrder) {
                    // Direct proxy uses this configuration for its CRUD operations.
                    // We only do this kind of thing for ordered Methods that accept 1 argument,
                    // if there's more or less we fall back to default processing.
                    flatten = config.paramsAsArray && me.len === 1 &&
                              (paramOrder.length > 1 || Ext.isArray(params));

                    if (flatten) {
                        if (Ext.isArray(params)) {
                            for (i = 0, len = params.length; i < len; i++) {
                                args.push(me.convertParams(params[i], paramOrder,
                                                           paramOrder.length, true));
                            }
                        }
                        else {
                            args = me.convertParams(params, paramOrder, paramOrder.length, true);
                        }

                        if (!params.allowSingle || args.length > 1) {
                            args = [args];
                        }
                    }
                    else {
                        // The number of arguments expected by the Method has priority
                        // over the number of parameters in paramOrder.
                        args = me.convertParams(params, paramOrder, me.len, false);
                    }
                }
                else {
                    args.push(params);
                }
            }
        }
        else {
            args.push(params);
        }

        args.push(config.callback, config.scope || window);

        if (options || metadata) {
            options = Ext.apply({}, options);

            if (metadata) {
                // Could be either an object of named arguments,
                // or an array of ordered arguments
                options.metadata = metadata;
            }

            args.push(options);
        }

        return args;
    },

    convertParams: function(params, paramOrder, count, flatten) {
        var ret = [],
            paramName, i, len;

        for (i = 0, len = count; i < len; i++) {
            paramName = paramOrder[i];
            ret.push(params[paramName]);
        }

        if (flatten) {
            return ret.length === 0 ? undefined : ret.length === 1 ? ret[0] : ret;
        }
        else {
            return ret;
        }
    },

    /**
     * Takes the arguments for a Direct function and splits the arguments
     * from the scope and the callback.
     *
     * @param {Array} args The arguments passed to the direct call
     *
     * @return {Object} An object with 4 properties: args, callback, scope, and options object.
     */
    getCallData: function(args) {
        var me = this,
            data = null,
            len = me.len,
            params = me.params,
            strict = me.strict,
            form, callback, scope, name, options, metadata;

        // Historically, the presence of required arguments was not checked;
        // another idiosyncrasy is that null is sent to the server side
        // instead of empty array when len === 0
        if (me.ordered) {
            callback = args[len];
            scope = args[len + 1];
            options = args[len + 2];

            if (len !== 0) {
                data = args.slice(0, len);
            }
        }
        else if (me.formHandler) {
            form = args[0];
            callback = args[1];
            scope = args[2];
            options = args[3];
        }
        else {
            data = Ext.apply({}, args[0]);
            callback = args[1];
            scope = args[2];
            options = args[3];

            // filter out any non-existent properties unless !strict
            if (strict) {
                for (name in data) {
                    if (data.hasOwnProperty(name) && !params[name]) {
                        delete data[name];
                    }
                }
            }
        }

        if (me.metadata && options && options.metadata) {
            if (me.metadata.ordered) {
                //<debug>
                if (!Ext.isArray(options.metadata)) {
                    Ext.raise('options.metadata is not an Array ' +
                                    'for Ext Direct method ' + me.name);
                }
                else if (options.metadata.length < me.metadata.len) {
                    Ext.raise('Not enough parameters in options.metadata ' +
                                    'for Ext Direct method ' + me.name);
                }
                //</debug>

                metadata = options.metadata.slice(0, me.metadata.len);
            }
            else {
                //<debug>
                if (!Ext.isObject(options.metadata)) {
                    Ext.raise('options.metadata is not an Object ' +
                                    'for Ext Direct method ' + me.name);
                }
                //</debug>

                metadata = Ext.apply({}, options.metadata);

                if (me.metadata.strict) {
                    for (name in metadata) {
                        if (metadata.hasOwnProperty(name) && !me.metadata.params[name]) {
                            delete metadata[name];
                        }
                    }
                }

                //<debug>
                for (name in me.metadata.params) {
                    if (!metadata.hasOwnProperty(name)) {
                        Ext.raise('Named parameter ' + name + ' is missing ' +
                                        'in options.metadata for Ext Direct method ' +
                                        me.name);
                    }
                }
                //</debug>
            }

            delete options.metadata;
        }

        return {
            form: form,
            data: data,
            metadata: metadata,
            callback: callback,
            scope: scope,
            options: options
        };
    }
});
