/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

pimcore.registerNS("pimcore.settings.liveconnect");
pimcore.settings.liveconnect = {

    token: "",
    callback: null,
    failure: null,
    loginWindow: null,

    loginPrompt: function () {

        this.loginWindow = new Ext.Window({
            width: 300,
            height: 220,
            modal: true,
            closeAction: "close",
            listeners: {
                "close": function () {
                    if(typeof this.failure == "function") {
                        this.failure();
                    }
                }.bind(this)
            },
            html: '<iframe id="pimcore_liveconnect_iframe" allowTransparency="true" src="https://www.pimcore.org/liveconnect/?source=' + urlencode(window.location.href) + '" width="280" height="180" frameborder="0"></iframe>'
        });


        this.loginWindow.show();
    },

    loginCallback: function (token) {

        if(token) {
            this.setToken(token);
            this.loginWindow.close();

            if(typeof this.callback == "function") {
                this.callback();
            }

            this.addToStatusBar();
        }
    },

    login: function (callback, failure) {
        if(empty(this.token)) {
            this.loginPrompt();

            this.callback = callback;
            this.failure = failure;
        } else {
            if(typeof callback == "function") {
                callback();
            }
        }
    },

    getToken: function (callback) {
        return this.token;
    },

    setToken: function (token) {
        this.token = token;

        try {
            window.clearTimeout(this.timeout);
        } catch (e) {
            // no timeout registered yet
        }

        // timeout is 5 minutes (300000)
        this.timeout = window.setTimeout(function () {
            this.setToken(null);
            this.removeFromStatusBar();

            window.clearTimeout(this.timeout);
        }.bind(this), 300000);
    },

    isConnected: function () {
        if(this.getToken()) {
            return true;
        }

        return false;
    },

    addToStatusBar: function () {
        var statusbar = Ext.getCmp("pimcore_statusbar");
        statusbar.insert(1, '-');
        statusbar.insert(2, '<div class="pimcore_statusbar_liveconnect">Live Connect</div>');
        statusbar.doLayout();
    },

    removeFromStatusBar: function () {
        var statusbar = Ext.getCmp("pimcore_statusbar");
        
        statusbar.remove(statusbar.get(2));
        statusbar.remove(statusbar.get(1));
    }
};