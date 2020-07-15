/**
 * This class manages a pending form submit. Instances of this type are created by the
 * `{@link Ext.data.Connection#request}` method.
 * @since 6.0.0
 */
Ext.define('Ext.data.request.Form', {
    extend: 'Ext.data.request.Base',
    alias: 'request.form',

    start: function(data) {
        var me = this,
            options = me.options,
            requestOptions = me.requestOptions;

        // Parent will set the timeout
        me.callParent([data]);

        me.form = me.upload(options.form, requestOptions.url, requestOptions.data, options);

        return me;
    },

    abort: function(force) {
        var me = this,
            frame;

        if (me.isLoading()) {

            try {
                frame = me.frame.dom;

                if (frame.stop) {
                    frame.stop();
                }
                else {
                    frame.document.execCommand('Stop');
                }
            }
            catch (e) {
                // ignore
            }
        }

        me.callParent([force]);

        me.onComplete();
        me.cleanup();
    },

    /*
     * Clean up any left over information from the form submission.
     */
    cleanup: function() {
        var me = this,
            frame = me.frame;

        if (frame) {
            // onComplete hasn't fired yet if frame != null so need to clean up
            frame.un('load', me.onComplete, me);
            Ext.removeNode(frame);
        }

        me.frame = me.form = null;
    },

    isLoading: function() {
        return !!this.frame;
    },

    /**
     * Uploads a form using a hidden iframe.
     * @param {String/HTMLElement/Ext.dom.Element} form The form to upload
     * @param {String} url The url to post to
     * @param {String} params Any extra parameters to pass
     * @param {Object} options The initial options
     * @private
     */
    upload: function(form, url, params, options) {
        form = Ext.getDom(form);
        options = options || {};

        /* eslint-disable-next-line vars-on-top */
        var frameDom = document.createElement('iframe'),
            frame = Ext.get(frameDom),
            id = frame.id,
            hiddens = [],
            encoding = 'multipart/form-data',
            buf = {
                target: form.target,
                method: form.method,
                encoding: form.encoding,
                enctype: form.enctype,
                action: form.action
            },
            addField = function(name, value) {
                hiddenItem = document.createElement('input');
                Ext.fly(hiddenItem).set({
                    type: 'hidden',
                    value: value,
                    name: name
                });
                form.appendChild(hiddenItem);
                hiddens.push(hiddenItem);
            },
            hiddenItem, obj, value, name, vLen, v, hLen, h;

        /*
         * Originally this behaviour was modified for Opera 10 to apply the secure URL after
         * the frame had been added to the document. It seems this has since been corrected in
         * Opera so the behaviour has been reverted, the URL will be set before being added.
         */
        frame.set({
            name: id,
            cls: Ext.baseCSSPrefix + 'hidden-display',
            src: Ext.SSL_SECURE_URL,
            tabIndex: -1
        });

        document.body.appendChild(frameDom);
        document.body.appendChild(form);

        // This is required so that IE doesn't pop the response up in a new window.
        if (document.frames) {
            document.frames[id].name = id;
        }

        Ext.fly(form).set({
            target: id,
            method: 'POST',
            enctype: encoding,
            encoding: encoding,
            action: url || buf.action
        });

        // add dynamic params
        if (params) {
            obj = Ext.Object.fromQueryString(params) || {};

            for (name in obj) {
                if (obj.hasOwnProperty(name)) {
                    value = obj[name];

                    if (Ext.isArray(value)) {
                        vLen = value.length;

                        for (v = 0; v < vLen; v++) {
                            addField(name, value[v]);
                        }
                    }
                    else {
                        addField(name, value);
                    }
                }
            }
        }

        this.frame = frame;

        frame.on({
            load: this.onComplete,
            scope: this,
            // Opera introduces multiple 'load' events, so account for extras as well
            single: !Ext.isOpera
        });

        form.submit();
        document.body.removeChild(form);

        // Restore form to previous settings
        Ext.fly(form).set(buf);

        for (hLen = hiddens.length, h = 0; h < hLen; h++) {
            Ext.removeNode(hiddens[h]);
        }

        return form;
    },

    getDoc: function() {
        var frame = this.frame.dom;

        return (frame && (frame.contentWindow.document || frame.contentDocument)) ||
                (window.frames[frame.id] || {}).document;
    },

    getTimeout: function() {
        // For a form post, since it can include large file uploads, we do not use the
        // default timeout from the owner. Only explicit timeouts passed in the options
        // are meaningful here.
        return this.options.timeout;
    },

    /**
     * Callback handler for the upload function. After we've submitted the form via the
     * iframe this creates a bogus response object to simulate an XHR and populates its
     * responseText from the now-loaded iframe's document body (or a textarea inside the
     * body). We then clean up by removing the iframe.
     * @private
     */
    onComplete: function() {
        var me = this,
            frame = me.frame,
            owner = me.owner,
            options = me.options,
            callback, doc, success, contentNode, response;

        // Nulled out frame means onComplete was fired already
        if (!frame) {
            return;
        }

        if (me.aborted || me.timedout) {
            me.result = response = me.createException();
            response.responseXML = null;
            response.responseText = Ext.encode({
                success: false,
                message: Ext.String.trim(response.statusText)
            });

            response.request = me;
            callback = options.failure;
            success = false;
        }
        else {
            try {
                doc = me.getDoc();

                // bogus response object
                me.result = response = {
                    responseText: '',
                    responseXML: null,
                    request: me
                };

                // Opera will fire an extraneous load event on about:blank
                // We want to ignore this since the load event will be fired twice
                if (doc) {
                    // TODO: See if this still applies vs Current opera-webkit releases
                    if (Ext.isOpera && doc.location === Ext.SSL_SECURE_URL) {
                        return;
                    }

                    if (doc.body) {
                        // Response sent as Content-Type: text/json or text/plain.
                        // Browser will embed it in a <pre> element.
                        // Note: The statement below tests the result of an assignment.
                        if ((contentNode = doc.body.firstChild) &&
                            /pre/i.test(contentNode.tagName)) {
                            response.responseText = contentNode.textContent ||
                                                    contentNode.innerText;
                        }
                        // Response sent as Content-Type: text/html. We must still support
                        // JSON response wrapped in textarea.
                        // Note: The statement below tests the result of an assignment.
                        else if ((contentNode = doc.getElementsByTagName('textarea')[0])) {
                            response.responseText = contentNode.value;
                        }
                        // Response sent as Content-Type: text/html with no wrapping. Scrape
                        // JSON response out of text
                        else {
                            response.responseText = doc.body.textContent || doc.body.innerText;
                        }
                    }

                    // in IE the document may still have a body even if returns XML.
                    // TODO What is this about?
                    response.responseXML = doc.XMLDocument || doc;
                    callback = options.success;
                    success = true;
                    response.status = 200;
                }
                else {
                    Ext.raise("Could not acquire a suitable connection for the " +
                              "file upload service.");
                }
            }
            catch (e) {
                me.result = response = me.createException();

                // Report any error in the message property
                response.status = 400;
                response.statusText = (e.message || e.description) + '';
                response.responseText = Ext.encode({
                    success: false,
                    message: Ext.String.trim(response.statusText)
                });
                response.responseXML = null;

                callback = options.failure;
                success = false;
            }
        }

        me.frame = null;
        me.success = success;

        owner.fireEvent(success ? 'requestcomplete' : 'requestexception', owner, response, options);

        Ext.callback(callback, options.scope, [response, options]);
        Ext.callback(options.callback, options.scope, [options, success, response]);

        owner.onRequestComplete(me);

        // Must defer slightly to permit full exit from load event before destruction
        Ext.asap(frame.destroy, frame);

        me.callParent();
    },

    destroy: function() {
        this.cleanup();
        this.callParent();
    }
});
