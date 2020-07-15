/**
 * This class manages a pending Ajax request. Instances of this type are created by the
 * `{@link Ext.data.Connection#request}` method.
 * @since 6.0.0
 */
Ext.define('Ext.data.request.Ajax', {
    extend: 'Ext.data.request.Base',
    alias: 'request.ajax',

    requires: [
        'Ext.data.flash.BinaryXhr'
    ],

    statics: {
        /**
         * Checks if the response status was successful
         * @param {Number} status The status code
         * @param {Object} response The Response object
         * @return {Object} An object containing success/status state
         * @private
         */
        parseStatus: function(status, response) {
            var type, len, success, isException;

            if (response) {
                // We have to account for binary and other response types
                type = response.responseType;

                if (type === 'arraybuffer') {
                    len = response.byteLength;
                }
                else if (type === 'blob') {
                    len = response.response.size;
                }
                else if ((type === 'json' || type === 'document') && response.response) {
                    len = 0;
                }
                else if ((type === 'text' || type === '' || !type) && response.responseText) {
                    len = response.responseText.length;
                }
            }

            // see: https://prototype.lighthouseapp.com/projects/8886/tickets/129-ie-mangles-http-response-status-code-204-to-1223
            status = status === 1223 ? 204 : status;

            isException = false;

            // Status can be 0 for file:/// requests
            success = (status >= 200 && status < 300) || status === 304 ||
                      (status === 0 && Ext.isNumber(len));

            if (!success) {
                switch (status) {
                    case 12002:
                    case 12029:
                    case 12030:
                    case 12031:
                    case 12152:
                    case 13030:
                        isException = true;
                        break;
                }
            }

            return {
                success: success,
                isException: isException
            };
        }
    },

    start: function(data) {
        var me = this,
            options = me.options,
            requestOptions = me.requestOptions,
            isXdr = me.isXdr,
            xhr;

        xhr = me.xhr = me.openRequest(options, requestOptions, me.async, me.username, me.password);

        // XDR doesn't support setting any headers
        if (!isXdr) {
            me.setupHeaders(xhr, options, requestOptions.data, requestOptions.params);
        }

        if (me.async) {
            if (!isXdr) {
                xhr.onreadystatechange = me.bindStateChange();
            }
        }

        if (isXdr) {
            me.processXdrRequest(me, xhr);
        }

        // Parent will set the timeout if needed
        me.callParent([data]);

        // start the request!
        xhr.send(data);

        if (!me.async) {
            return me.onComplete();
        }

        return me;
    },

    /**
     * Aborts an active request.
     */
    abort: function(force) {
        var me = this,
            xhr = me.xhr;

        if (force || me.isLoading()) {
            /*
             * Clear out the onreadystatechange here, this allows us
             * greater control, the browser may/may not fire the function
             * depending on a series of conditions.
             */
            try {
                xhr.onreadystatechange = null;
            }
            catch (e) {
                // Setting onreadystatechange to null can cause problems in IE, see
                // http://www.quirksmode.org/blog/archives/2005/09/xmlhttp_notes_a_1.html
                xhr.onreadystatechange = Ext.emptyFn;
            }

            xhr.abort();

            me.callParent([force]);

            me.onComplete();
            me.cleanup();
        }
    },

    /**
     * Cleans up any left over information from the request
     */
    cleanup: function() {
        this.xhr = null;
        delete this.xhr;
    },

    isLoading: function() {
        var me = this,
            xhr = me.xhr,
            state = xhr && xhr.readyState,
            C = Ext.data.flash && Ext.data.flash.BinaryXhr;

        if (!xhr || me.aborted || me.timedout) {
            return false;
        }

        // if there is a connection and readyState is not 0 or 4, or in case of
        // BinaryXHR, not 4
        if (C && xhr instanceof C) {
            return state !== 4;
        }

        return state !== 0 && state !== 4;
    },

    /**
     * Creates and opens an appropriate XHR transport for a given request on this browser.
     * This logic is contained in an individual method to allow for overrides to process all
     * of the parameters and options and return a suitable, open connection.
     * @private
     */
    openRequest: function(options, requestOptions, isAsync, username, password) {
        var me = this,
            xhr = me.newRequest(options);

        if (username) {
            xhr.open(requestOptions.method, requestOptions.url, isAsync, username, password);
        }
        else {
            if (me.isXdr) {
                xhr.open(requestOptions.method, requestOptions.url);
            }
            else {
                xhr.open(requestOptions.method, requestOptions.url, isAsync);
            }
        }

        if (options.binary || me.binary) {
            if (window.Uint8Array) {
                xhr.responseType = 'arraybuffer';
            }
            else if (xhr.overrideMimeType) {
                // In some older non-IE browsers, e.g. ff 3.6, that do not
                // support Uint8Array, a mime type override is required so that
                // the unprocessed binary data can be read from the responseText
                // (see createResponse())
                xhr.overrideMimeType('text/plain; charset=x-user-defined');
            //<debug>
            }
            else if (!Ext.isIE) {
                Ext.log.warn("Your browser does not support loading binary data using Ajax.");
            //</debug>
            }
        }

        if (options.responseType) {
            xhr.responseType = options.responseType;
        }

        if (options.withCredentials || me.withCredentials) {
            xhr.withCredentials = true;
        }

        return xhr;
    },

    /**
     * Creates the appropriate XHR transport for a given request on this browser. On IE
     * this may be an `XDomainRequest` rather than an `XMLHttpRequest`.
     * @private
     */
    newRequest: function(options) {
        var me = this,
            xhr;

        if (options.binaryData) {
            // This is a binary data request. Handle submission differently for differnet browsers
            if (window.Uint8Array) {
                xhr = me.getXhrInstance();
            }
            else {
                // catch all for all other browser types
                xhr = new Ext.data.flash.BinaryXhr();
            }
        }
        else if (me.cors && Ext.isIE9m) {
            xhr = me.getXdrInstance();
            me.isXdr = true;
        }
        else {
            xhr = me.getXhrInstance();
            me.isXdr = false;
        }

        return xhr;
    },

    /**
     * Setup all the headers for the request
     * @private
     * @param {Object} xhr The xhr object
     * @param {Object} options The options for the request
     * @param {Object} data The data for the request
     * @param {Object} params The params for the request
     */
    setupHeaders: function(xhr, options, data, params) {
        var me = this,
            headers = Ext.apply({}, options.headers || {}, me.defaultHeaders),
            contentType = me.defaultPostHeader,
            jsonData = options.jsonData,
            xmlData = options.xmlData,
            type = 'Content-Type',
            useHeader = me.useDefaultXhrHeader,
            key, header;

        if (!headers.hasOwnProperty(type) && (data || params)) {
            if (data) {
                if (options.rawData) {
                    contentType = 'text/plain';
                }
                else {
                    if (xmlData && Ext.isDefined(xmlData)) {
                        contentType = 'text/xml';
                    }
                    else if (jsonData && Ext.isDefined(jsonData)) {
                        contentType = 'application/json';
                    }
                }
            }

            headers[type] = contentType;
        }

        if (useHeader && !headers['X-Requested-With']) {
            headers['X-Requested-With'] = me.defaultXhrHeader;
        }

        // If undefined/null, remove it and don't set the header.
        // Allow the browser to do so.
        if (headers[type] === undefined || headers[type] === null) {
            delete headers[type];
        }

        // set up all the request headers on the xhr object
        try {
            for (key in headers) {
                if (headers.hasOwnProperty(key)) {
                    header = headers[key];
                    xhr.setRequestHeader(key, header);
                }
            }
        }
        catch (e) {
            // TODO Request shouldn't fire events from its owner
            me.owner.fireEvent('exception', key, header);
        }

        return headers;
    },

    /**
     * Creates the appropriate XDR transport for this browser.
     * - IE 7 and below don't support CORS
     * - IE 8 and 9 support CORS with native XDomainRequest object
     * - IE 10 (and above?) supports CORS with native XMLHttpRequest object
     * @private
     */
    getXdrInstance: function() {
        var xdr;

        if (Ext.ieVersion >= 8) {
            xdr = new XDomainRequest(); // eslint-disable-line no-undef
        }
        else {
            Ext.raise({
                msg: 'Your browser does not support CORS'
            });
        }

        return xdr;
    },

    /**
     * @private
     * Do not remove this method. This is where Ajax simulator injects request stubs.
     */
    getXhrInstance: function() {
        return new XMLHttpRequest();
    },

    processXdrRequest: function(request, xhr) {
        var me = this;

        // Mutate the request object as per XDR spec.
        delete request.headers;

        request.contentType = request.options.contentType || me.defaultXdrContentType;

        xhr.onload = me.bindStateChange(true);
        xhr.onerror = xhr.ontimeout = me.bindStateChange(false);
    },

    processXdrResponse: function(response, xhr) {
        // Mutate the response object as per XDR spec.
        response.getAllResponseHeaders = function() {
            return [];
        };

        response.getResponseHeader = function() {
            return '';
        };

        response.contentType = xhr.contentType || this.defaultXdrContentType;
    },

    bindStateChange: function(xdrResult) {
        var me = this;

        return function() {
            Ext.elevate(function() {
                me.onStateChange(xdrResult);
            });
        };
    },

    onStateChange: function(xdrResult) {
        var me = this,
            xhr = me.xhr;

        // Using CORS with IE doesn't support readyState so we fake it.
        if ((xhr && xhr.readyState === 4) || me.isXdr) {
            me.clearTimer();

            me.onComplete(xdrResult);

            me.cleanup();
        }
    },

    /**
     * To be called when the request has come back from the server
     * @param {Object} xdrResult
     * @return {Object} The response
     * @private
     */
    onComplete: function(xdrResult) {
        var me = this,
            owner = me.owner,
            options = me.options,
            xhr = me.xhr,
            failure = { success: false, isException: false },
            result, success, response;

        if (!xhr || me.destroyed) {
            return me.result = failure;
        }

        try {
            result = Ext.data.request.Ajax.parseStatus(xhr.status, xhr);

            if (result.success) {
                // This is quite difficult to reproduce, however if we abort a request
                // just before it returns from the server, occasionally the status will be
                // returned correctly but the request is still yet to be complete.
                result.success = xhr.readyState === 4;
            }
        }
        catch (e) {
            // In some browsers we can't access the status if the readyState is not 4,
            // so the request has failed
            result = failure;
        }

        success = me.success = me.isXdr ? xdrResult : result.success;

        if (success) {
            response = me.createResponse(xhr);

            if (owner.hasListeners.requestcomplete) {
                owner.fireEvent('requestcomplete', owner, response, options);
            }

            if (options.success) {
                Ext.callback(options.success, options.scope, [response, options]);
            }
        }
        else {
            if (result.isException || me.aborted || me.timedout) {
                response = me.createException(xhr);
            }
            else {
                response = me.createResponse(xhr);
            }

            if (owner.hasListeners.requestexception) {
                owner.fireEvent('requestexception', owner, response, options);
            }

            if (options.failure) {
                Ext.callback(options.failure, options.scope, [response, options]);
            }
        }

        me.result = response;

        if (options.callback) {
            Ext.callback(options.callback, options.scope, [options, success, response]);
        }

        owner.onRequestComplete(me);

        me.callParent([xdrResult]);

        return response;
    },

    /**
     * Creates the response object
     * @param {Object} xhr
     * @private
     */
    createResponse: function(xhr) {
        var me = this,
            isXdr = me.isXdr,
            headers = {},
            lines = isXdr ? [] : xhr.getAllResponseHeaders().replace(/\r\n/g, '\n').split('\n'),
            count = lines.length,
            line, index, key, response;

        while (count--) {
            line = lines[count];
            index = line.indexOf(':');

            if (index >= 0) {
                key = line.substr(0, index).toLowerCase();

                if (line.charAt(index + 1) === ' ') {
                    ++index;
                }

                headers[key] = line.substr(index + 1);
            }
        }

        response = {
            request: me,
            requestId: me.id,
            status: xhr.status,
            statusText: xhr.statusText,
            getResponseHeader: function(header) {
                return headers[header.toLowerCase()];
            },
            getAllResponseHeaders: function() {
                return headers;
            }
        };

        if (isXdr) {
            me.processXdrResponse(response, xhr);
        }

        if (me.binary) {
            response.responseBytes = me.getByteArray(xhr);
        }
        else {
            if (xhr.responseType) {
                response.responseType = xhr.responseType;
            }

            if (xhr.responseType === 'blob') {
                response.responseBlob = xhr.response;
            }
            else if (xhr.responseType === 'json') {
                response.responseJson = xhr.response;
            }
            else if (xhr.responseType === 'document') {
                response.responseXML = xhr.response;
            }
            else {
                // an error is thrown when trying to access responseText or responseXML
                // on an xhr object with responseType with any value but "text" or "",
                // so only attempt to set these properties in the response if we're not
                // dealing with other specified response types
                response.responseText = xhr.responseText;
                response.responseXML = xhr.responseXML;
            }
        }

        return response;
    },

    destroy: function() {
        this.xhr = null;

        this.callParent();
    },

    privates: {
        /**
         * Gets binary data from the xhr response object and returns it as a byte array
         * @param {Object} xhr the xhr response object
         * @return {Uint8Array/Array}
         * @private
         */
        getByteArray: function(xhr) {
            var response = xhr.response,
                responseBody = xhr.responseBody,
                Cls = Ext.data.flash && Ext.data.flash.BinaryXhr,
                byteArray, responseText, len, i;

            if (xhr instanceof Cls) {
                // If this was a BinaryXHR request via flash, we already have the bytes ready
                byteArray = xhr.responseBytes;
            }
            else if (window.Uint8Array) {
                // Modern browsers (including IE10) have a native byte array
                // which can be created by passing the ArrayBuffer (returned as
                // the xhr.response property) to the Uint8Array constructor.
                /* eslint-disable-next-line no-undef */
                byteArray = response ? new Uint8Array(response) : [];
            }
            else if (Ext.isIE9p) {
                // In IE9 and below the responseBody property contains a byte array
                // but it is not directly accessible using javascript.
                // In IE9p we can get the bytes by constructing a VBArray
                // using the responseBody and then converting it to an Array.
                try {
                /* eslint-disable-next-line no-undef */
                    byteArray = new VBArray(responseBody).toArray();
                }
                catch (e) {
                    // If the binary response is empty, the VBArray constructor will
                    // choke on the responseBody.  We can't simply do a null check
                    // on responseBody because responseBody is always falsy when it
                    // contains binary data.
                    byteArray = [];
                }
            }
            else if (Ext.isIE) {
                // IE8 and below also have a VBArray constructor, but throw a
                // "VBArray Expected" error if you try to pass the responseBody to
                // the VBArray constructor.
                // http://msdn.microsoft.com/en-us/library/ye3x9by3%28v=vs.71%29.aspx
                // so we have to use vbscript injection to access the bytes
                if (!this.self.vbScriptInjected) {
                    this.injectVBScript();
                }

                /* eslint-disable-next-line no-undef */
                getIEByteArray(xhr.responseBody, byteArray = []);
            }
            else {
                // in other older browsers make a best-effort attempt to read the
                // bytes from responseText
                byteArray = [];
                responseText = xhr.responseText;
                len = responseText.length;

                for (i = 0; i < len; i++) {
                    // Some characters have an extra byte 0xF7 in the high order
                    // position. Throw away the high order byte and then push the
                    // result onto the byteArray.
                    byteArray.push(responseText.charCodeAt(i) & 0xFF);
                }
            }

            return byteArray;
        },

        /**
         * Injects a vbscript tag containing a 'getIEByteArray' method for reading
         * binary data from an xhr response in IE8 and below.
         * @private
         */
        injectVBScript: function() {
            var scriptTag = document.createElement('script');

            scriptTag.type = 'text/vbscript';

            /* eslint-disable indent */
            scriptTag.text = [
                'Function getIEByteArray(byteArray, out)',
                    'Dim len, i',
                    'len = LenB(byteArray)',
                    'For i = 1 to len',
                        'out.push(AscB(MidB(byteArray, i, 1)))',
                    'Next',
                'End Function'
            ].join('\n');
            /* eslint-enable indent */

            Ext.getHead().dom.appendChild(scriptTag);

            this.self.vbScriptInjected = true;
        }
    }
});
