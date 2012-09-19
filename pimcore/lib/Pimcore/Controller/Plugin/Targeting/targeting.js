
(function () {

    /**
     * get parameters and global variables used by this javascript
     *
     * _ptr -> redirect action (GET)
     * _ptc -> programmatically redirect action (GET)
     * _ptd -> targeting content (JAVASCRIPT)
     */

    /* TESTS */
    var tests = {
        url: function (params) {
            var regexp = new RegExp(params["url"]);
            if(regexp.test(window.location.href)) {
                return true;
            }
            return false;
        },

        browser: function (params) {
            if(params["browser"] == user["environment"]["browser"]) {
                return true;
            }
            return false;
        },

        country: function (params) {
            if(util.toString(params["country"]).toLowerCase() == util.toString(user["location"]["country"]).toLowerCase()) {
                return true;
            }
            return false;
        },

        language: function (params) {
            if(util.toString(params["language"]).toLowerCase() == util.toString(user["language"]).toLowerCase()) {
                return true;
            }
            return false;
        },

        event: function (params) {
            for(var i=0; i<user["events"].length; i++) {
                if(user["events"][i]["key"] == params["key"] && user["events"][i]["value"] == params["value"]) {
                    return true;
                }
                if(user["events"][i]["key"] == params["key"] && !user["events"][i]["value"] && !params["value"]) {
                    return true;
                }
            }
            return false;
        },

        geopoint: function (params) {
            if(util.geoDistance(user["location"]["latitude"], user["location"]["longitude"], params["latitude"], params["longitude"]) < params["radius"]) {
                return true;
            }
            return false;
        },

        referringsite: function (params) {
            if(params["referrer"]) {
                var regexp = new RegExp(params["referrer"]);
                if(regexp.test(user["referrer"]["source"])) {
                    return true;
                }
            }
            return false;
        },

        searchengine: function (params) {
            if(user["referrer"]["searchengine"]) {
                if(params["searchengine"]) {
                    return /google|yahoo|bing/.test(user["referrer"]["searchengine"]);
                }
                return true;
            }
        },

        vistitedpagebefore: function (params) {
            if(params["url"]) {
                var regexp = new RegExp(params["url"]);
                for(var i=0; i<(user["history"].length-1); i++) { // not the current page
                    if(regexp.test(user["history"][i])) {
                        return true;
                    }
                }
            }
            return false;
        },

        vistitedpagesbefore: function (params) {
            if(params["number"]) {
                return ((user["history"].length-1) >= params["number"]);
            }
            return false;
        },

        timeonsite: function (params) {
            if(params["hours"] || params["minutes"] || params["seconds"]) {

                var msecs = 0;
                if(params["hours"]) {
                    msecs += (params["hours"] * 3600000);
                }
                if(params["minutes"]) {
                    msecs += (params["minutes"] * 60000);
                }
                if(params["seconds"]) {
                    msecs += (params["seconds"] * 1000);
                }

                var date = new Date();
                if( date.getTime() > (user["behavior"]["enterTime"] + msecs) ) {
                    return true;
                }
            }
            return false;
        },

        linkclicked: function (params) {
            if(params["url"]) {
                var regexp = new RegExp(params["url"]);
                for(var i=0; i<(user["behavior"]["linksClicked"].length-1); i++) { // not the current page
                    if(regexp.test(util.toString(user["behavior"]["linksClicked"][i]))) {
                        return true;
                    }
                }
            }
            return false;
        },

        linksclicked: function (params) {
            if(params["number"]) {
                return ((user["behavior"]["linksClicked"].length-1) >= params["number"]);
            }
            return false;
        },

        hardwareplatform: function (params) {
            if(!params["platform"] || params["platform"] == user["environment"]["hardwareplatform"]) {
                return true;
            }
            return false;
        },

        operatingsystem: function (params) {
            if(!params["system"] || params["system"] == user["environment"]["os"]) {
                return true;
            }
            return false;
        }
    };


    /* UTILITY FUNCTIONS */
    var util = {

        log: function (msg) {
            // debug
            if (typeof console != "undefined" && typeof console["log"] == "function") {
                console.log(msg);
            }
        },

        toString: function (val) {
            if(typeof val != "string") {
                val = "";
            }
            return val;
        },

        executeInsertedScripts: function (domelement) {
            var scripts = [];


            ret = domelement.childNodes;
            for (var i = 0; ret[i]; i++) {
                if (scripts && util.nodeName(ret[i], "script") && (!ret[i].type || ret[i].type.toLowerCase() === "text/javascript")) {
                    scripts.push(ret[i].parentNode ? ret[i].parentNode.removeChild(ret[i]) : ret[i]);
                }
            }

            for (script in scripts) {
                util.evalScript(scripts[script]);
            }
        },

        nodeName:function (elem, name) {
            return elem.nodeName && elem.nodeName.toUpperCase() === name.toUpperCase();
        },

        evalScript:function (elem) {

            var data = ( elem.text || elem.textContent || elem.innerHTML || "" );
            var head = document.getElementsByTagName("head")[0] || document.documentElement,
                script = document.createElement("script");

            script.type = "text/javascript";


            try {
                script.appendChild(document.createTextNode(data));
            } catch (e) {
                // IE8 Workaround
                script.text = data;
            }
            head.insertBefore(script, head.firstChild);
            head.removeChild(script);
            if (elem.parentNode) {
                elem.parentNode.removeChild(elem);
            }
        },

        geoDistance: function (lat1, lon1, lat2, lon2) {
            var R = 6371; // km
            var dLat = (lat2-lat1) * Math.PI / 180;
            var dLon = (lon2-lon1) * Math.PI / 180;
            var lat1 = lat1 * Math.PI / 180;
            var lat2 = lat2 * Math.PI / 180;

            var a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                    Math.sin(dLon/2) * Math.sin(dLon/2) * Math.cos(lat1) * Math.cos(lat2);
            var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            var d = R * c;

            return d;
        },

        listen: function (elem, evnt, func) {
            if (elem.addEventListener)  // W3C DOM
                elem.addEventListener(evnt,func,false);
            else if (elem.attachEvent) { // IE DOM
                 var r = elem.attachEvent("on"+evnt, func);
            return r;
            }
        },

        contentLoaded: function (win, fn) {
            var done = false, top = true,

            doc = win.document, root = doc.documentElement,

            add = doc.addEventListener ? 'addEventListener' : 'attachEvent',
            rem = doc.addEventListener ? 'removeEventListener' : 'detachEvent',
            pre = doc.addEventListener ? '' : 'on',

            init = function(e) {
                if (e.type == 'readystatechange' && doc.readyState != 'complete') return;
                (e.type == 'load' ? win : doc)[rem](pre + e.type, init, false);
                if (!done && (done = true)) fn.call(win, e.type || e);
            },

            poll = function() {
                try { root.doScroll('left'); } catch(e) { setTimeout(poll, 50); return; }
                init('poll');
            };

            if (doc.readyState == 'complete') fn.call(win, 'lazy');
            else {
                if (doc.createEventObject && root.doScroll) {
                    try { top = !win.frameElement; } catch(e) { }
                    if (top) poll();
                }
                doc[add](pre + 'DOMContentLoaded', init, false);
                doc[add](pre + 'readystatechange', init, false);
                win[add](pre + 'load', init, false);
            }
        },

        getCookie: function (c_name) {
            var i, x, y, ARRcookies = document.cookie.split(";");
            for (i = 0; i < ARRcookies.length; i++) {
                x = ARRcookies[i].substr(0, ARRcookies[i].indexOf("="));
                y = ARRcookies[i].substr(ARRcookies[i].indexOf("=") + 1);
                x = x.replace(/^\s+|\s+$/g, "");
                if (x == c_name) {
                    return decodeURIComponent(y);
                }
            }
        },

        unsetCookie: function(name) {
            document.cookie = name + '=; expires=Thu, 01-Jan-70 00:00:01 GMT;';
        }
    };

    /* App */
    var app = {

        matchesForProgrammatically: [],

        processTargets: function () {
            // process targets
            if(typeof window["_ptd"] != "undefined") {
                var target;
                var matchingTargets = [];

                for(var t=0; t<_ptd.length; t++) {
                    target = _ptd[t];

                    if(target.conditions.length > 0) {
                        try {
                            if(app.testConditions(target.conditions)) {
                                matchingTargets.push(target);
                            }
                        } catch (e) {
                            util.log(e);
                        }
                    }
                }

                for(t=0; t<matchingTargets.length; t++) {
                    app.callActions(matchingTargets[t]);
                }

                //util.log(matchingTargets);
                app.callTargets(app.matchesForProgrammatically);
            }
        },

        testConditions: function (conditions) {
            var res, cond = "";
            for(var i=0; i<conditions.length; i++) {
                res = false;
                try {
                    res = tests[conditions[i]["type"]](conditions[i]);
                } catch (e) {
                    util.log(e);
                }

                if(conditions[i]["operator"] && i > 0) {
                    if(conditions[i]["operator"] == "and") {
                        cond += " && ";
                    }
                    if(conditions[i]["operator"] == "or") {
                        cond += " || ";
                    }
                    if(conditions[i]["operator"] == "and_not") {
                        cond += " && !";
                    }
                }

                if(conditions[i]["bracketLeft"]) {
                    cond += "(";
                }

                cond += JSON.stringify(res);

                if(conditions[i]["bracketRight"]) {
                    cond += ")";
                }
            }

            cond = "(" + cond + ")";
            return eval(cond);
        },

        callActions: function (target) {
            var actions = target["actions"];
            util.log("call actions for target: " + target["id"] + " | " + target["name"]);

            // redirects
            try {
                var regexp = new RegExp("_ptr=" + target.id);
                if(actions["redirectEnabled"] && actions["redirectUrl"].length > 0 && !regexp.test(window.location.href)) {
                    window.location.href = actions["redirectUrl"] + (actions["redirectUrl"].indexOf("?") < 0 ? "?" : "&") + "_ptr=" + target.id;
                }
            } catch (e) {
                util.log(e);
            }

            // event
            try {
                if(actions["eventEnabled"] && actions["eventKey"]) {
                    user["events"].push({
                        key: actions["eventKey"],
                        value: actions["eventValue"]
                    });
                    app.saveUser();
                }
            } catch (e) {
                util.log(e);
            }

            // snippet
            try {
                if(actions["codesnippetEnabled"] && actions["codesnippetCode"] && actions["codesnippetSelector"]) {
                    util.contentLoaded(window, function () {
                        try {
                            var pos = actions["codesnippetPosition"] ? actions["codesnippetPosition"] : "end";
                            var el = document.querySelector(actions["codesnippetSelector"]);
                            if(el) {
                                var frag = document.createDocumentFragment();
                                var temp = document.createElement('div');

                                temp.innerHTML = actions["codesnippetCode"];

                                while (temp.firstChild) {
                                    frag.appendChild(temp.firstChild);
                                }

                                if(pos == "end") {
                                    el.appendChild(frag);
                                } else if (pos == "beginning") {
                                    el.insertBefore(frag, el.firstChild);
                                } else if (pos == "replace") {
                                    el.innerHTML = actions["codesnippetCode"];
                                }

                                util.executeInsertedScripts(el);
                            }
                        } catch (e) {
                            util.log(e);
                        }
                    });
                }
            } catch (e) {
                util.log(e);
            }

            // programmatically
            try {
                if(actions["programmaticallyEnabled"]) {
                    app.matchesForProgrammatically.push(target["id"]);
                }
            } catch (e) {
                util.log(e);
            }
        },

        callTargets: function (targets) {
            if(targets.length > 0 && !/_ptc=/.test(window.location.href)) {
                window.location.href = "?_ptc=" + targets.join(",");
            }
        },

        saveUser: function() {
            localStorage.setItem("pimcore_targeting_user", JSON.stringify(user));
        }
    };


    // common used vars
    var ua = navigator.userAgent.toLowerCase();
    var now = new Date();


    // create user object
    var user = null;
    try {
        user = localStorage.getItem("pimcore_targeting_user");
        user = JSON.parse(user);
    } catch (e) {
        user = null;
    }

    if(!user) {
        user = {};
    }


    try {
        if(!user["location"]) {
            user["location"] = {
                latitude: google.loader.ClientLocation.latitude,
                longitude: google.loader.ClientLocation.longitude,
                country: google.loader.ClientLocation.address.country_code
            }
        }
    } catch (e) {
        util.log(e);
    }

    try {
        if(!user["history"]) {
            user["history"] = [];
        }

        if(!/_ptc=/.test(window.location.href)) {
            user["history"].push(location.href);
        }
    } catch (e) {
        util.log(e);
    }

    try {
        if(!user["language"]) {
            user["language"] = navigator.browserLanguage ? navigator.browserLanguage : navigator.language;
        }
    } catch (e) {
        util.log(e);
    }

    try {
        if(!user["environment"] || true) {

            user["environment"] = {};

            user["environment"]["browser"] = "undefined";
            if(/opera/.test(ua)) {
                user["environment"]["browser"] = "opera";
            } else if (/\bchrome\b/.test(ua)) {
                user["environment"]["browser"] = "chrome";
            } else if (/safari/.test(ua)) {
                user["environment"]["browser"] = "safari";
            } else if (/msie/.test(ua)) {
                user["environment"]["browser"] = "ie";
            } else if (/gecko/.test(ua)) {
                user["environment"]["browser"] = "firefox";
            }

            user["environment"]["os"] = "undefined";
            if(/windows/.test(ua)) {
                user["environment"]["os"] = "windows";
            } else if (/linux/.test(ua)) {
                user["environment"]["os"] = "linux";
            } else if (/iphone|ipad/.test(ua)) {
                user["environment"]["environment"]["os"] = "ios";
            } else if (/mac/.test(ua)) {
                user["environment"]["os"] = "macos";
            } else if (/android/.test(ua)) {
                user["environment"]["os"] = "android";
            }

            user["environment"]["hardwareplatform"] = "desktop";
            if(/iphone|android|mobile/.test(ua)) {
                user["environment"]["hardwareplatform"] = "mobile";
            } else if (/ipad|tablet/.test(ua)) {
                user["environment"]["hardwareplatform"] = "tablet";
            }
        }
    } catch (e) {
        util.log(e);
    }


    try {
        if(!user["events"]) {
            user["events"] = [];
        }

        // get new events
        var newEvents = util.getCookie("pimcore__~__targeting");
        if(newEvents) {
            newEvents = JSON.parse(newEvents);
            util.unsetCookie("pimcore__~__targeting");

            for(var ev=0; ev<newEvents.length; ev++) {
                user["events"].push(newEvents[ev]);
            }
        }
    } catch (e) {
        util.log(e);
    }

    try {
        if(!user["referrer"]) {
            user["referrer"] = {};
            user["referrer"]["source"] = !document.referrer ? "direct" : document.referrer;

            user["referrer"]["searchengine"] = "";

            if(document.referrer) {
                if(/google/.test(document.referrer)) {
                    user["referrer"]["searchengine"] = "google";
                } else if (/bing/.test(document.referrer)) {
                    user["referrer"]["searchengine"] = "bing";
                } else if (/yahoo/.test(document.referrer)) {
                    user["referrer"]["searchengine"] = "yahoo";
                }
            }
        }
    } catch (e) {
        util.log(e);
    }

    try {
        if(!user["behavior"]) {
            user["behavior"] = {};
        }

        if(!user["behavior"]["enterTime"]) {
            user["behavior"]["enterTime"] = now.getTime();
        }

        if(!user["behavior"]["linksClicked"]) {
            user["behavior"]["linksClicked"] = [];
        }
    } catch (e) {
        util.log(e);
    }

    // dom stuff
    util.contentLoaded(window, function () {
        try {
            var linkElements = document.querySelectorAll("a");
            for (var le = 0; le < linkElements.length; le++) {
                util.listen(linkElements[le], "click", function (ev) {
                    try {
                        var el = ev.target ? ev.target : ev.srcElement;
                        user["behavior"]["linksClicked"].push(el.getAttribute("href"));
                        app.saveUser();
                    } catch (e) {
                        util.log(e);
                    }
                });
            }
        } catch (e) {
            util.log(e);
        }
    });

    util.log(user);

    app.saveUser();
    app.processTargets();

})();

