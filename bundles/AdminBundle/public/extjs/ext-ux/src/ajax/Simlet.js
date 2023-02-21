/**
 * This is a base class for more advanced "simlets" (simulated servers). A simlet is asked
 * to provide a response given a {@link Ext.ux.ajax.SimXhr} instance.
 */
Ext.define('Ext.ux.ajax.Simlet', function() {
    var urlRegex = /([^?#]*)(#.*)?$/,
        dateRegex = /^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2}(?:\.\d*)?)Z$/,
        intRegex = /^[+-]?\d+$/,
        floatRegex = /^[+-]?\d+\.\d+$/;

    function parseParamValue(value) {
        var m;

        if (Ext.isDefined(value)) {
            value = decodeURIComponent(value);

            if (intRegex.test(value)) {
                value = parseInt(value, 10);
            }
            else if (floatRegex.test(value)) {
                value = parseFloat(value);
            }
            else if (!!(m = dateRegex.exec(value))) {
                value = new Date(Date.UTC(+m[1], +m[2] - 1, +m[3], +m[4], +m[5], +m[6]));
            }
        }

        return value;
    }

    return {
        alias: 'simlet.basic',

        isSimlet: true,

        responseProps: ['responseText', 'responseXML', 'status', 'statusText', 'responseHeaders'],

        /**
         * @cfg {String/Function} responseText
         */

        /**
         * @cfg {String/Function} responseXML
         */

        /**
         * @cfg {Object/Function} responseHeaders
         */

        /**
         * @cfg {Number/Function} status
         */
        status: 200,

        /**
         * @cfg {String/Function} statusText
         */
        statusText: 'OK',

        constructor: function(config) {
            Ext.apply(this, config);
        },

        doGet: function(ctx) {
            return this.handleRequest(ctx);
        },

        doPost: function(ctx) {
            return this.handleRequest(ctx);
        },

        doRedirect: function(ctx) {
            return false;
        },

        doDelete: function(ctx) {
            var me = this,
                xhr = ctx.xhr,
                records = xhr.options.records;

            me.removeFromData(ctx, records);
        },

        /**
         * Performs the action requested by the given XHR and returns an object to be applied
         * on to the XHR (containing `status`, `responseText`, etc.). For the most part,
         * this is delegated to `doMethod` methods on this class, such as `doGet`.
         *
         * @param {Ext.ux.ajax.SimXhr} xhr The simulated XMLHttpRequest instance.
         * @return {Object} The response properties to add to the XMLHttpRequest.
         */
        exec: function(xhr) {
            var me = this,
                ret = {},
                method = 'do' + Ext.String.capitalize(xhr.method.toLowerCase()), // doGet
                fn = me[method];

            if (fn) {
                ret = fn.call(me, me.getCtx(xhr.method, xhr.url, xhr));
            }
            else {
                ret = { status: 405, statusText: 'Method Not Allowed' };
            }

            return ret;
        },

        getCtx: function(method, url, xhr) {
            return {
                method: method,
                params: this.parseQueryString(url),
                url: url,
                xhr: xhr
            };
        },

        handleRequest: function(ctx) {
            var me = this,
                ret = {},
                val;

            Ext.Array.forEach(me.responseProps, function(prop) {
                if (prop in me) {
                    val = me[prop];

                    if (Ext.isFunction(val)) {
                        val = val.call(me, ctx);
                    }

                    ret[prop] = val;
                }
            });

            return ret;
        },

        openRequest: function(method, url, options, async) {
            var ctx = this.getCtx(method, url),
                redirect = this.doRedirect(ctx),
                xhr;

            if (options.action === 'destroy') {
                method = 'delete';
            }

            if (redirect) {
                xhr = redirect;
            }
            else {
                xhr = new Ext.ux.ajax.SimXhr({
                    mgr: this.manager,
                    simlet: this,
                    options: options
                });
                xhr.open(method, url, async);
            }

            return xhr;
        },

        parseQueryString: function(str) {
            var m = urlRegex.exec(str),
                ret = {},
                key, value, pair, parts, i, n;

            if (m && m[1]) {
                parts = m[1].split('&');

                for (i = 0, n = parts.length; i < n; ++i) {
                    if ((pair = parts[i].split('='))[0]) {
                        key = decodeURIComponent(pair.shift());
                        value = parseParamValue((pair.length > 1) ? pair.join('=') : pair[0]);

                        if (!(key in ret)) {
                            ret[key] = value;
                        }
                        else if (Ext.isArray(ret[key])) {
                            ret[key].push(value);
                        }
                        else {
                            ret[key] = [ret[key], value];
                        }
                    }
                }
            }

            return ret;
        },

        redirect: function(method, url, params) {
            switch (arguments.length) {
                case 2:
                    if (typeof url === 'string') {
                        break;
                    }

                    params = url;
                    // fall...

                // eslint-disable-next-line no-fallthrough
                case 1:
                    url = method;
                    method = 'GET';
                    break;
            }

            if (params) {
                url = Ext.urlAppend(url, Ext.Object.toQueryString(params));
            }

            return this.manager.openRequest(method, url);
        },

        removeFromData: function(ctx, records) {
            var me = this,
                data = me.getData(ctx),
                model = (ctx.xhr.options.proxy && ctx.xhr.options.proxy.getModel()) || {},
                idProperty = model.idProperty || 'id',
                i;

            Ext.each(records, function(record) {
                var id = record.get(idProperty);

                for (i = data.length; i-- > 0;) {
                    if (data[i][idProperty] === id) {
                        me.deleteRecord(i);
                        break;
                    }
                }
            });
        }
    };
}());
