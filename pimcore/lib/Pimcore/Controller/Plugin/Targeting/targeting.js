
(function () {

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
        debug(e);
    }

    try {
        if(!user["history"]) {
            user["history"] = [];
        }
        user["history"].push(location.href);
    } catch (e) {
        debug(e);
    }

    try {
        if(!user["language"]) {
            user["language"] = navigator.language;
        }
    } catch (e) {
        debug(e);
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

            user["environment"]["mobile"] = false;
            if(/iphone|ipad|android|mobile/.test(ua)) {
                user["environment"]["mobile"] = true;
            }
        }
    } catch (e) {
        debug(e);
    }


    try {
        if(!user["events"]) {
            user["events"] = [];
        }

        // get new events
        var newEvents = getCookie("pimcore__~__targeting");
        if(newEvents) {
            newEvents = JSON.parse(newEvents);
            unsetCookie("pimcore__~__targeting");

            for(var ev=0; ev<newEvents.length; ev++) {
                user["events"].push(newEvents[ev]);
            }
        }
    } catch (e) {
        debug(e);
    }

    try {
        if(!user["referrer"]) {
            user["referrer"] = {};
            user["referrer"]["source"] = !document.referrer ? "direct" : document.referrer;

            if(/google/.test(ua)) {
                user["referrer"]["searchengine"] = "google";
            } else if (/bing/.test(ua)) {
                user["referrer"]["searchengine"] = "bing";
            } else if (/yahoo/.test(ua)) {
                user["referrer"]["searchengine"] = "yahoo";
            }
        }
    } catch (e) {
        debug(e);
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
        debug(e);
    }

    // dom stuff
    contentLoaded(window, function () {
        try {
            var linkElements = document.querySelectorAll("a");
            for (var le = 0; le < linkElements.length; le++) {
                listen(linkElements[le], "click", function (ev) {
                    try {
                        var el = ev.target ? ev.target : ev.srcElement;
                        user["behavior"]["linksClicked"].push(el.getAttribute("href"));
                        saveUser();
                    } catch (e) {
                        debug(e);
                    }
                });
            }
        } catch (e) {
            debug(e);
        }
    });

    saveUser();


    debug(user);

    return;

    // process targets
    if(typeof window["_ptd"] != "undefined") {
        var target;
        for(var t=0; t<_ptd.length; t++) {
            target = _ptd[t];

            if(target.conditions.length > 0) {
                if(conditionTest(target.conditions)) {
                    actionsFire(target.actions);
                }
            }
        }
    }

    function conditionTest (conditions) {
        return true;
    }

    function actionsFire (actions) {
        console.log(actions);
    }


    /* METHODS */

    function saveUser() {
        localStorage.setItem("pimcore_targeting_user", JSON.stringify(user));
    }


    /* UTILITY FUNCTIONS */

    function listen(elem, evnt, func) {
        if (elem.addEventListener)  // W3C DOM
            elem.addEventListener(evnt,func,false);
        else if (elem.attachEvent) { // IE DOM
             var r = elem.attachEvent("on"+evnt, func);
    	return r;
        }
    }

    function contentLoaded(win, fn) {
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
    }

    function getCookie(c_name) {
        var i, x, y, ARRcookies = document.cookie.split(";");
        for (i = 0; i < ARRcookies.length; i++) {
            x = ARRcookies[i].substr(0, ARRcookies[i].indexOf("="));
            y = ARRcookies[i].substr(ARRcookies[i].indexOf("=") + 1);
            x = x.replace(/^\s+|\s+$/g, "");
            if (x == c_name) {
                return decodeURIComponent(y);
            }
        }
    }

    function unsetCookie(name) {
        document.cookie = name + '=; expires=Thu, 01-Jan-70 00:00:01 GMT;';
    }


    function debug(message) {
        console.log(message);
    }

})();

