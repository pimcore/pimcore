(function () {
    window.pimcore = window.pimcore || {};
    window.pimcore.targeting = window.pimcore.targeting || {};

    var cookieNames = {
        visitorId: '_pc_vis',
        visitorIdHistory: '_pc_vis_h'
    };

    // see http://clubmate.fi/setting-and-reading-cookies-with-javascript/
    var Cookie = {
        set: function (name, value, days) {
            var expires;
            if (days) {
                var date = new Date();
                date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                expires = "; expires=" + date.toGMTString();
            }
            else {
                expires = "";
            }

            document.cookie = name + "=" + value + expires + "; path=/";
        },

        get: function (name) {
            var nameEQ = name + "=";
            var ca = document.cookie.split(';');
            for (var i = 0; i < ca.length; i++) {
                var c = ca[i];
                while (c.charAt(0) === ' ') {
                    c = c.substring(1, c.length);
                }
                if (c.indexOf(nameEQ) === 0) {
                    return c.substring(nameEQ.length, c.length);
                }
            }

            return null;
        }
    };

    var util = {
        featureDetect: (function () {
            // tests are taken from to modernizr (MIT license)
            // https://github.com/Modernizr/Modernizr/tree/master/feature-detects
            var tests = {
                localStorage: function () {
                    var v = 'test';

                    try {
                        localStorage.setItem(v, v);
                        localStorage.removeItem(v);

                        return true;
                    } catch (e) {
                        return false;
                    }
                },

                sessionStorage: function() {
                    var v = 'test';

                    try {
                        sessionStorage.setItem(v, v);
                        sessionStorage.removeItem(v);

                        return true;
                    } catch (e) {
                        return false;
                    }
                },

                json: function () {
                    return 'JSON' in window && 'parse' in JSON && 'stringify' in JSON;
                }
            };

            var results = {};

            return function (type) {
                if ('undefined' === typeof tests[type]) {
                    throw new Error('Test ' + type + ' is not defined');
                }

                if ('undefined' === typeof results[type]) {
                    results[type] = tests[type].call();
                }

                return results[type];
            };
        }()),

        logger: {
            canLog: function(type) {
                if ('undefined' === typeof type) {
                    type = 'log';
                }

                return ('undefined' !== typeof window.console && 'function' === typeof window.console[type]);
            }
        },

        listen: function (elem, evnt, func) {
            if (elem.addEventListener) {  // W3C DOM
                elem.addEventListener(evnt, func, false);
            } else if (elem.attachEvent) { // IE DOM
                return elem.attachEvent("on" + evnt, func);
            }
        },

        contentLoaded: function (win, fn) {
            var done = false, top = true,

                doc = win.document, root = doc.documentElement,

                add = doc.addEventListener ? 'addEventListener' : 'attachEvent',
                rem = doc.addEventListener ? 'removeEventListener' : 'detachEvent',
                pre = doc.addEventListener ? '' : 'on',

                init = function (e) {
                    if (e.type === 'readystatechange' && doc.readyState !== 'complete') {
                        return;
                    }
                    (e.type === 'load' ? win : doc)[rem](pre + e.type, init, false);
                    if (!done && (done = true)) {
                        fn.call(win, e.type || e);
                    }
                },

                poll = function () {
                    try {
                        root.doScroll('left');
                    } catch (e) {
                        setTimeout(poll, 50);
                        return;
                    }
                    init('poll');
                };

            if (doc.readyState === 'complete') {
                fn.call(win, 'lazy');
            } else {
                if (doc.createEventObject && root.doScroll) {
                    try {
                        top = !win.frameElement;
                    } catch (e) {
                    }
                    if (top) {
                        poll();
                    }
                }
                doc[add](pre + 'DOMContentLoaded', init, false);
                doc[add](pre + 'readystatechange', init, false);
                win[add](pre + 'load', init, false);
            }
        }
    };

    var User = (function() {
        var generateVisitorId = function(length) {
            var chars = '0123456789abcdef';

            var result = '';
            for (var i = length; i > 0; --i) {
                result += chars[Math.floor(Math.random() * chars.length)];
            }

            return result;
        };

        var User = function () {
            this.data = {
                sessionId: null,
                visitorId: null,
                visitorIds: [],
                activityLog: []
            };

            this.load();

            if (!this.data.visitorId) {
                this.setVisitorId(generateVisitorId(16));
                this.save();
            }
        };

        User.prototype.setVisitorId = function (id) {
            if (!id) {
                return;
            }

            if (this.data.visitorId) {
                // don't do anything if ID is already set
                if (id === this.data.visitorId) {
                    return;
                } else {
                    // store last visitor ID in list
                    this.data.visitorIds.push(this.data.visitorId);
                }
            }

            util.logger.canLog('info') && console.info('[TARGETING] Setting visitor ID to', id);

            this.data.visitorId = id;
        };

        User.prototype.addActivityLog = function (data) {
            data.sessionId = this.data.sessionId;
            data.visitorId = this.data.visitorId;
            data.timestamp = (new Date()).getTime();

            util.logger.canLog('info') && console.info('TRACK ACTIVITY', data, this);

            this.data.activityLog.unshift(data);

            return this;
        };

        User.prototype.load = function () {
            var data = {};

            if (util.featureDetect('localStorage')) {
                var storedData = localStorage.getItem("pimcore_targeting_userdata");
                data = JSON.parse(storedData);
            }

            if (data) {
                for (var key in data) {
                    if (data.hasOwnProperty(key)) {
                        this.data[key] = data[key];
                    }
                }
            }

            var cookieVisitorId = Cookie.get(cookieNames.visitorId);
            if (cookieVisitorId) {
                this.setVisitorId(cookieVisitorId);
            }

            // check / generate sessionId
            var nowTimestamp = (new Date()).getTime();

            if (0 === this.data.activityLog.length) {
                this.data.sessionId = nowTimestamp;
                util.logger.canLog('info') && console.info("No previous activity - new sessionId: " + this.data.sessionId);
            } else {
                var lastActivity = this.data.activityLog[0];
                if (lastActivity.timestamp < (nowTimestamp - (30 * 60 * 1000))) {
                    this.data.sessionId = nowTimestamp; // session expired
                    util.logger.canLog('info') && console.info("Previous session expired, new sessionId: " + this.data.sessionId);
                } else {
                    this.data.sessionId = lastActivity.sessionId;
                    util.logger.canLog('info') && console.info("SessionId present: " + this.data.sessionId);
                }
            }
        };

        User.prototype.save = function () {
            if (util.featureDetect('localStorage')) {
                localStorage.setItem("pimcore_targeting_userdata", JSON.stringify(this.data));
            }

            // set visitor ID cookie
            if (this.data.visitorId) {
                Cookie.set(cookieNames.visitorId, this.data.visitorId, 365);
            }

            // set cookie with last 10 visitor IDs
            if (this.data.visitorIds.length > 0) {
                Cookie.set(cookieNames.visitorIdHistory, this.data.visitorIds.slice(-5), 365);
            }
        };

        return User;
    }());

    var user = new User();

    window.pimcore.targeting.api = {
        setVisitorId: function (id) {
            user.setVisitorId(id);
            user.save();
        }
    };

    // track links
    util.contentLoaded(window, function () {
        try {
            var linkElements = document.querySelectorAll("a");
            var linkClickHandler = function (ev) {
                user.addActivityLog({
                    type: "linkClicked",
                    href: el.getAttribute("href")
                }).save();
            };

            for (var le = 0; le < linkElements.length; le++) {
                util.listen(linkElements[le], "click", linkClickHandler);
            }
        } catch (e) {
            util.logger.canLog('error') && console.error(e);
        }
    });
}());
