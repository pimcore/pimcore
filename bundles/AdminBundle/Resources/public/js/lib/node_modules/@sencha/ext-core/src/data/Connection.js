/**
 * The Connection class encapsulates a connection to the page's originating domain, allowing
 * requests to be made either to a configured URL, or to a URL specified at request time.
 *
 * Requests made by this class are asynchronous, and will return immediately. No data from the
 * server will be available to the statement immediately following the {@link #request} call.
 * To process returned data, use a success callback in the request options object, or an
 * {@link #requestcomplete event listener}.
 *
 * # File Uploads
 *
 * File uploads are not performed using normal "Ajax" techniques, that is they are not performed
 * using XMLHttpRequests. Instead the form is submitted in the standard manner with the DOM
 * &lt;form&gt; element temporarily modified to have its target set to refer to a dynamically
 * generated, hidden &lt;iframe&gt; which is inserted into the document but removed after the
 * return data has been gathered.
 *
 * The server response is parsed by the browser to create the document for the IFRAME. If the
 * server is using JSON to send the return object, then the Content-Type header must be set to
 * "text/html" in order to tell the browser to insert the text unchanged into the document body.
 *
 * Characters which are significant to an HTML parser must be sent as HTML entities, so encode
 * `<` as `&lt;`, `&` as `&amp;` etc.
 *
 * The response text is retrieved from the document, and a fake XMLHttpRequest object is created
 * containing a responseText property in order to conform to the requirements of event handlers and
 * callbacks.
 *
 * Be aware that file upload packets are sent with the content type multipart/form and some server
 * technologies (notably JEE) may require some custom processing in order to retrieve parameter
 * names and parameter values from the packet content.
 *
 * Also note that it's not possible to check the response code of the hidden iframe, so the success
 * handler will ALWAYS fire.
 *
 * # Binary Posts
 *
 * The class supports posting binary data to the server by using native browser capabilities,
 * or a flash polyfill plugin in browsers that do not support native binary posting (e.g.
 * Internet Explorer version 9 or less). A number of limitations exist when the polyfill is used:
 *
 * - Only asynchronous connections are supported.
 * - Only the POST method can be used.
 * - The return data can only be binary for now. Set the {@link Ext.data.Connection#binary binary}
 * parameter to `true`.
 * - Only the 0, 1 and 4 (complete) readyState values will be reported to listeners.
 * - The flash object will be injected at the bottom of the document and should be invisible.
 * - Important: See note about packaing the flash plugin with the app in the documenetation of
 * {@link Ext.data.flash.BinaryXhr BinaryXhr}.
 *
 */
Ext.define('Ext.data.Connection', {
    mixins: {
        observable: 'Ext.mixin.Observable'
    },

    requires: [
        'Ext.data.request.Ajax',
        'Ext.data.request.Form',
        'Ext.data.flash.BinaryXhr',
        'Ext.Deferred'
    ],

    statics: {
        requestId: 0
    },

    enctypeRe: /multipart\/form-data/i,

    config: {
        /**
         * @cfg {String} url
         * The URL for this connection.
         */
        url: null,

        /**
         * @cfg {Boolean} async
         * `true` if this request should run asynchronously. Setting this to `false` should
         * generally be avoided, since it will cause the UI to be blocked, the user won't be able
         * to interact with the browser until the request completes.
         */
        async: true, // eslint-disable-line id-blacklist

        /**
         * @cfg {String} username
         * The username to pass when using {@link #withCredentials}.
         */
        username: '',

        /**
         * @cfg {String} password
         * The password to pass when using {@link #withCredentials}.
         */
        password: '',

        /**
         * @cfg {Boolean} disableCaching
         * True to add a unique cache-buster param to GET requests.
         */
        disableCaching: true,

        /**
         * @cfg {Boolean} withCredentials
         * True to set `withCredentials = true` on the XHR object
         */
        withCredentials: false,

        /**
         * @cfg {Boolean} binary
         * True if the response should be treated as binary data.  If true, the binary
         * data will be accessible as a "responseBytes" property on the response object.
         */
        binary: false,

        /**
         * @cfg {Boolean} cors
         * True to enable CORS support on the XHR object. Currently the only effect of this option
         * is to use the XDomainRequest object instead of XMLHttpRequest if the browser is IE8
         * or above.
         */
        cors: false,

        isXdr: false,

        defaultXdrContentType: 'text/plain',

        /**
         * @cfg {String} disableCachingParam
         * Change the parameter which is sent went disabling caching through a cache buster.
         */
        disableCachingParam: '_dc',

        /**
         * @cfg {Number} [timeout=30000] The timeout in milliseconds to be used for
         * requests.
         * Defaults to 30000 milliseconds (30 seconds).
         *
         * When a request fails due to timeout the XMLHttpRequest response object will
         * contain:
         *
         *     timedout: true
         */
        timeout: 30000,

        /**
         * @cfg {Object} [extraParams] Any parameters to be appended to the request.
         */
        extraParams: null,

        /**
         * @cfg {Boolean} [autoAbort=false]
         * Whether this request should abort any pending requests.
         */
        autoAbort: false,

        /**
         * @cfg {String} method
         * The default HTTP method to be used for requests.
         *
         * If not set, but {@link #request} params are present, POST will be used;
         * otherwise, GET will be used.
         */
        method: null,

        /**
         * @cfg {Object} defaultHeaders
         * An object containing request headers which are added to each request made by this object.
         */
        defaultHeaders: null,

        /**
         * @cfg {String} defaultPostHeader
         * The default header to be sent out with any post request.
         */
        defaultPostHeader: 'application/x-www-form-urlencoded; charset=UTF-8',

        /**
         * @cfg {Boolean} useDefaultXhrHeader
         * `true` to send the {@link #defaultXhrHeader} along with any request.
         */
        useDefaultXhrHeader: true,

        /**
         * @cfg {String}
         * The header to send with Ajax requests. Also see {@link #useDefaultXhrHeader}.
         */
        defaultXhrHeader: 'XMLHttpRequest'
    },

    /**
     * @event beforerequest
     * @preventable
     * Fires before a network request is made to retrieve a data object.
     * @param {Ext.data.Connection} conn This Connection object.
     * @param {Object} options The options config object passed to the {@link #request} method.
     */

    /**
     * @event requestcomplete
     * Fires if the request was successfully completed.
     * @param {Ext.data.Connection} conn This Connection object.
     * @param {Object} response The XHR object containing the response data.
     * See [The XMLHttpRequest Object](http://www.w3.org/TR/XMLHttpRequest/) for details.
     * @param {Object} options The options config object passed to the {@link #request} method.
     */

    /**
     * @event requestexception
     * Fires if an error HTTP status was returned from the server. This event may also
     * be listened to in the event that a request has timed out or has been aborted.
     * See [HTTP Status Code Definitions](http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html)
     * for details of HTTP status codes.
     * @param {Ext.data.Connection} conn This Connection object.
     * @param {Object} response The XHR object containing the response data.
     * See [The XMLHttpRequest Object](http://www.w3.org/TR/XMLHttpRequest/) for details.
     * @param {Object} options The options config object passed to the {@link #request} method.
     */

    constructor: function(config) {
        // Will call initConfig
        this.mixins.observable.constructor.call(this, config);

        this.requests = {};
    },

    /**
     * @method request
     * Sends an HTTP (Ajax) request to a remote server.
     *
     * **Important:** Ajax server requests are asynchronous, and this call will
     * return before the response has been received.
     *
     * Instead, process any returned data using a promise:
     *
     *      Ext.Ajax.request({
     *          url: 'ajax_demo/sample.json'
     *      }).then(function(response, opts) {
     *          var obj = Ext.decode(response.responseText);
     *          console.dir(obj);
     *      },
     *      function(response, opts) {
     *          console.log('server-side failure with status code ' + response.status);
     *      });
     *
     * Or in callback functions:
     *
     *      Ext.Ajax.request({
     *          url: 'ajax_demo/sample.json',
     *
     *          success: function(response, opts) {
     *              var obj = Ext.decode(response.responseText);
     *              console.dir(obj);
     *          },
     *
     *          failure: function(response, opts) {
     *              console.log('server-side failure with status code ' + response.status);
     *          }
     *      });
     *
     * To execute a callback function in the correct scope, use the `scope` option.
     *
     * @param {Object} options An object which may contain the following properties:
     *
     * (The options object may also contain any other property which might be needed to perform
     * postprocessing in a callback because it is passed to callback functions.)
     *
     * @param {String/Function} options.url The URL to which to send the request, or a function
     * to call which returns a URL string. The scope of the function is specified by the `scope`
     * option. Defaults to the configured `url`.
     *
     * @param {Boolean} options.async `true` if this request should run asynchronously.
     * Setting this to `false` should generally be avoided, since it will cause the UI to be
     * blocked, the user won't be able to interact with the browser until the request completes.
     * Defaults to `true`.
     *
     * @param {Object/String/Function} options.params An object containing properties which are
     * used as parameters to the request, a url encoded string or a function to call to get either.
     * The scope of the function is specified by the `scope` option.
     *
     * @param {String} options.method The HTTP method to use
     * for the request. Defaults to the configured method, or if no method was configured,
     * "GET" if no parameters are being sent, and "POST" if parameters are being sent.  Note that
     * the method name is case-sensitive and should be all caps.
     *
     * @param {Function} options.callback The function to be called upon receipt of the HTTP
     * response. The callback is called regardless of success or failure and is passed the
     * following parameters:
     * @param {Object} options.callback.options The parameter to the request call.
     * @param {Boolean} options.callback.success True if the request succeeded.
     * @param {Object} options.callback.response The XMLHttpRequest object containing the response
     * data. See [www.w3.org/TR/XMLHttpRequest/](http://www.w3.org/TR/XMLHttpRequest/) for details
     * about accessing elements of the response.
     *
     * @param {Function} options.success The function to be called upon success of the request.
     * The callback is passed the following parameters:
     * @param {Object} options.success.response The XMLHttpRequest object containing the response
     * data.
     * @param {Object} options.success.options The parameter to the request call.
     *
     * @param {Function} options.failure The function to be called upon failure of the request.
     * The callback is passed the following parameters:
     * @param {Object} options.failure.response The XMLHttpRequest object containing the response
     * data.
     * @param {Object} options.failure.options The parameter to the request call.
     *
     * @param {Object} options.scope The scope in which to execute the callbacks: The "this" object
     * for the callback function. If the `url`, or `params` options were specified as functions
     * from which to draw values, then this also serves as the scope for those function calls.
     * Defaults to the browser window.
     *
     * @param {Number} options.timeout The timeout in milliseconds to be used for this
     * request.
     * Defaults to 30000 milliseconds (30 seconds).
     *
     * When a request fails due to timeout the XMLHttpRequest response object will
     * contain:
     *
     *     timedout: true
     *
     * @param {Ext.Element/HTMLElement/String} options.form The `<form>` Element or the id of the
     * `<form>` to pull parameters from.
     *
     * @param {Boolean} options.isUpload **Only meaningful when used with the `form` option.**
     *
     * True if the form object is a file upload (will be set automatically if the form was
     * configured with **`enctype`** `"multipart/form-data"`).
     *
     * File uploads are not performed using normal "Ajax" techniques, that is they are **not**
     * performed using XMLHttpRequests. Instead the form is submitted in the standard manner with
     * the DOM `&lt;form&gt;` element temporarily modified to have its
     * [target](https://developer.mozilla.org/en-US/docs/Web/API/HTMLFormElement/target)
     * set to refer to a dynamically generated, hidden `&lt;iframe&gt;` which is inserted
     * into the document but removed after the return data has been gathered.
     *
     * The server response is parsed by the browser to create the document for the IFRAME. If the
     * server is using JSON to send the return object, then the
     * [Content-Type](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Type)
     * header must be set to "text/html" in order to tell the browser to insert the text
     * unchanged into the document body.
     *
     * The response text is retrieved from the document, and a fake XMLHttpRequest object is created
     * containing a `responseText` property in order to conform to the requirements of event
     * handlers and callbacks.
     *
     * Be aware that file upload packets are sent with the content type
     * [multipart/form](https://tools.ietf.org/html/rfc7233#section-4.1) and some server
     * technologies (notably JEE) may require some custom processing in order to retrieve parameter
     * names and parameter values from the packet content.
     *
     *  - [target](http://www.w3.org/TR/REC-html40/present/frames.html#adef-target)
     *  - [Content-Type](http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.17)
     *  - [multipart/form](http://www.faqs.org/rfcs/rfc2388.html)
     *
     * @param {Object} options.headers Request headers to set for the request.
     * The XHR will attempt to set an appropriate Content-Type based on the params/data passed
     * to the request. To prevent this, setting the Content-Type header to `null` or `undefined`
     * will not attempt to set any Content-Type and it will be left to the browser.
     *
     * @param {Object} options.xmlData XML document to use for the post. Note: This will be used
     * instead of params for the post data. Any params will be appended to the URL.
     *
     * @param {Object/String} options.jsonData JSON data to use as the post. Note: This will be used
     * instead of params for the post data. Any params will be appended to the URL.
     *
     * @param {String} options.rawData A raw string to use as the post. Note: This will be used
     * instead of params for the post data. Any params will be appended to the URL.
     *
     * @param {Array} options.binaryData An array of bytes to submit in binary form. Any params
     * will be appended to the URL. If binaryData is present, you must set
     * {@link Ext.data.Connection#binary binary} to `true` and options.method to `POST`.
     *
     * @param {Boolean} options.disableCaching True to add a unique cache-buster param to GET
     * requests.
     *
     * @param {Boolean} options.withCredentials True to add the withCredentials property to the
     * XHR object
     *
     * @param {String} options.username The username to pass when using `withCredentials`.
     *
     * @param {String} options.password The password to pass when using `withCredentials`.
     *
     * @param {Boolean} options.binary True if the response should be treated as binary data.
     * If true, the binary data will be accessible as a "responseBytes" property on the response
     * object.
     *
     * @return {Ext.data.request.Base} The request object. This may be used to abort the
     * request.
     */
    request: function(options) {
        var me = this,
            requestOptions, request;

        options = options || {};

        if (me.fireEvent('beforerequest', me, options) !== false) {
            requestOptions = me.setOptions(options, options.scope || Ext.global);

            request = me.createRequest(options, requestOptions);

            return request.start(requestOptions.data);
        }

        // Reusing for response
        request = {
            status: -1,
            statusText: 'Request cancelled in beforerequest event handler'
        };

        Ext.callback(options.callback, options.scope, [options, false, request]);

        return Ext.Deferred.rejected([options, false, request]);
    },

    createRequest: function(options, requestOptions) {
        var me = this,
            type = options.type || requestOptions.type,
            request;

        // If request type is not specified we have to deduce it
        if (!type) {
            type = me.isFormUpload(options) ? 'form' : 'ajax';
        }

        // if autoabort is set, cancel the current transactions
        if (options.autoAbort || me.getAutoAbort()) {
            me.abort();
        }

        // It is possible for the original options object to be mutated if somebody
        // had overridden Connection.setOptions method; it is also possible that such
        // override would do a sensible thing and mutate outgoing requestOptions instead.
        // So we have to pass *both* to the Request constructor, along with the set
        // of defaults potentially set on the Connection instance.
        // If it looks ridiculous, that's because it is; things we have to do for
        // backward compatibility...
        request = Ext.Factory.request({
            type: type,
            owner: me,
            options: options,
            requestOptions: requestOptions,
            ownerConfig: me.getConfig()
        });

        me.requests[request.id] = request;
        me.latestId = request.id;

        return request;
    },

    /**
     * Detects whether the form is intended to be used for an upload.
     * @private
     */
    isFormUpload: function(options) {
        var form = this.getForm(options);

        if (form) {
            return options.isUpload || this.enctypeRe.test(form.getAttribute('enctype'));
        }

        return false;
    },

    /**
     * Gets the form object from options.
     * @private
     * @param {Object} options The request options
     * @return {HTMLElement} The form, null if not passed
     */
    getForm: function(options) {
        return Ext.getDom(options.form);
    },

    /**
     * Sets various options such as the url, params for the request
     * @param {Object} options The initial options
     * @param {Object} scope The scope to execute in
     * @return {Object} The params for the request
     */
    setOptions: function(options, scope) {
        var me = this,
            params = options.params || {},
            extraParams = me.getExtraParams(),
            urlParams = options.urlParams,
            url = options.url || me.getUrl(),
            cors = options.cors,
            jsonData = options.jsonData,
            method, disableCache, data;

        if (cors !== undefined) {
            me.setCors(cors);
        }

        // allow params to be a method that returns the params object
        if (Ext.isFunction(params)) {
            params = params.call(scope, options);
        }

        // allow url to be a method that returns the actual url
        if (Ext.isFunction(url)) {
            url = url.call(scope, options);
        }

        url = this.setupUrl(options, url);

        //<debug>
        if (!url) {
            Ext.raise({
                options: options,
                msg: 'No URL specified'
            });
        }
        //</debug>

        // check for xml or json data, and make sure json data is encoded
        data = options.rawData || options.binaryData || options.xmlData || jsonData || null;

        if (jsonData && !Ext.isPrimitive(jsonData)) {
            data = Ext.encode(data);
        }

        // Check for binary data. Transform if needed
        if (options.binaryData) {
            //<debug>
            if (!Ext.isArray(options.binaryData)) {
                Ext.log.warn("Binary submission data must be an array of byte values! " +
                             "Instead got " + typeof(options.binaryData));
            }
            //</debug>

            if (me.nativeBinaryPostSupport()) {
                data = (new Uint8Array(options.binaryData)); // eslint-disable-line no-undef

                if ((Ext.isChrome && Ext.chromeVersion < 22) || Ext.isSafari || Ext.isGecko) {
                    // send the underlying buffer, not the view, since that's not supported
                    // on versions of chrome older than 22
                    data = data.buffer;
                }
            }
        }

        // make sure params are a url encoded string and include any extraParams if specified
        if (Ext.isObject(params)) {
            params = Ext.Object.toQueryString(params);
        }

        if (Ext.isObject(extraParams)) {
            extraParams = Ext.Object.toQueryString(extraParams);
        }

        params = params + ((extraParams) ? ((params) ? '&' : '') + extraParams : '');

        urlParams = Ext.isObject(urlParams) ? Ext.Object.toQueryString(urlParams) : urlParams;

        params = this.setupParams(options, params);

        // decide the proper method for this request
        method = (options.method || me.getMethod() ||
                  ((params || data) ? 'POST' : 'GET')).toUpperCase();

        this.setupMethod(options, method);

        disableCache = options.disableCaching !== false
            ? (options.disableCaching || me.getDisableCaching())
            : false;

        // if the method is get append date to prevent caching
        if (method === 'GET' && disableCache) {
            url = Ext.urlAppend(url, (options.disableCachingParam || me.getDisableCachingParam()) +
                                '=' + (new Date().getTime()));
        }

        // if the method is get or there is json/xml data append the params to the url
        if ((method === 'GET' || data) && params) {
            url = Ext.urlAppend(url, params);
            params = null;
        }

        // allow params to be forced into the url
        if (urlParams) {
            url = Ext.urlAppend(url, urlParams);
        }

        return {
            url: url,
            method: method,
            data: data || params || null
        };
    },

    /**
     * Template method for overriding url
     * @private
     * @param {Object} options
     * @param {String} url
     * @return {String} The modified url
     */
    setupUrl: function(options, url) {
        var form = this.getForm(options);

        if (form) {
            url = url || form.action;
        }

        return url;
    },

    /**
     * Template method for overriding params
     * @private
     * @param {Object} options
     * @param {String} params
     * @return {String} The modified params
     */
    setupParams: function(options, params) {
        var form = this.getForm(options),
            serializedForm;

        if (form && !this.isFormUpload(options)) {
            serializedForm = Ext.Element.serializeForm(form);
            params = params ? (params + '&' + serializedForm) : serializedForm;
        }

        return params;
    },

    /**
     * Template method for overriding method
     * @private
     * @param {Object} options
     * @param {String} method
     * @return {String} The modified method
     */
    setupMethod: function(options, method) {
        if (this.isFormUpload(options)) {
            return 'POST';
        }

        return method;
    },

    /**
     * Determines whether this object has a request outstanding.
     *
     * @param {Object} [request] Defaults to the last transaction
     *
     * @return {Boolean} True if there is an outstanding request.
     */
    isLoading: function(request) {
        if (!request) {
            request = this.getLatest();
        }

        return request ? request.isLoading() : false;
    },

    /**
     * Aborts an active request.
     * @param {Ext.ajax.Request} [request] Defaults to the last request
     */
    abort: function(request) {
        if (!request) {
            request = this.getLatest();
        }

        if (request && request.isLoading()) {
            request.abort();
        }
    },

    /**
     * Aborts all active requests
     */
    abortAll: function() {
        var requests = this.requests,
            id;

        for (id in requests) {
            this.abort(requests[id]);
        }
    },

    /**
     * Gets the most recent request
     * @return {Object} The request. Null if there is no recent request
     * @private
     */
    getLatest: function() {
        var id = this.latestId,
            request;

        if (id) {
            request = this.requests[id];
        }

        return request || null;
    },

    /**
     * Clears the timeout on the request
     * @param {Object} request The request
     * @private
     */
    clearTimeout: function(request) {
        if (!request) {
            request = this.getLatest();
        }

        if (request) {
            request.clearTimer();
        }
    },

    onRequestComplete: function(request) {
        delete this.requests[request.id];
    },

    /**
     * @return {Boolean} `true` if the browser can natively post binary data.
     * @private
     */
    nativeBinaryPostSupport: function() {
        return Ext.isChrome ||
            (Ext.isSafari && Ext.isDefined(window.Uint8Array)) ||
            (Ext.isGecko && Ext.isDefined(window.Uint8Array));
    }
});
