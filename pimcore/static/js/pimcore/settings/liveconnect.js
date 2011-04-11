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
    loginWindow: null,

    loginPrompt: function () {

        this.loginWindow = new Ext.Window({
            width: 300,
            height: 220,
            modal: true,
            html: '<iframe id="pimcore_liveconnect_iframe" allowTransparency="true" src="http://www.pimcore.org/liveconnect/?source=' + urlencode(window.location.href) + '" width="280" height="180" frameborder="0"></iframe>'
        });

        this.loginWindow.show();
    },

    loginCallback: function (token) {
        this.setToken(token);
        this.loginWindow.close();

        if(typeof this.callback == "function") {
            this.callback();
        }
    },

    login: function (callback) {
        if(empty(this.token)) {
            this.loginPrompt();

            this.callback = callback;
        } else {
            callback();
        }
    },

    getToken: function (callback) {
        return this.token;
    },

    setToken: function (token) {
        this.token = token;
    }
};