(function () {
    var visitorIdCookieName = '_pc_vis';

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
        log: function (msg) {
            if (typeof console !== "undefined" && typeof console["log"] === "function") {
                console.log(msg);
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
        var User = function () {
            this.data = {
                sessionId: null,
                visitorId: null,
                activityLog: []
            };

            this.load();
        };

        User.prototype.setVisitorId = function (id) {
            console.log('[TARGETING] Setting visitor ID to', id);

            this.data.visitorId = id;
        };

        User.prototype.addActivityLog = function (data) {
            data.sessionId = this.data.sessionId;
            data.visitorId = this.data.visitorId;
            data.timestamp = (new Date()).getTime();

            console.log('TRACK ACTIVITY', data, this);

            this.data.activityLog.unshift(data);

            return this;
        };

        User.prototype.load = function () {
            var data = localStorage.getItem("pimcore_targeting_userdata");
            data = JSON.parse(data);

            if (data) {
                this.data = data;
            }

            var cookieVisitorId = Cookie.get(visitorIdCookieName);
            if (cookieVisitorId) {
                this.setVisitorId(cookieVisitorId);
            }

            // check / generate sessionId
            var sessionId;
            var nowTimestamp = (new Date()).getTime();

            if (0 === this.data.activityLog.length) {
                this.data.sessionId = nowTimestamp;
                util.log("No previous activity - new sessionId: " + this.data.sessionId);
            } else {
                var lastActivity = this.data.activityLog[0];
                if (lastActivity.timestamp < (nowTimestamp - (30 * 60 * 1000))) {
                    this.data.sessionId = nowTimestamp; // session expired
                    util.log("Previous session expired, new sessionId: " + this.data.sessionId);
                } else {
                    this.data.sessionId = lastActivity.sessionId;
                    util.log("SessionId present: " + this.data.sessionId);
                }
            }
        };

        User.prototype.save = function () {
            localStorage.setItem("pimcore_targeting_userdata", JSON.stringify(this.data));

            if (this.data.visitorId) {
                Cookie.set(visitorIdCookieName, this.data.visitorId, 365);
            }
        };

        return User;
    }());

    var user = new User();

    // track page views
    user.addActivityLog({
        type: "pageView",
        url: location.href
    }).save();

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
        } catch (e12) {
            util.log(e12);
        }
    });

    window.pimcore = window.pimcore || {};
    window.pimcore.Targeting = {
        setVisitorId: function (id) {
            user.setVisitorId(id);
            user.save();
        }
    };
}());
