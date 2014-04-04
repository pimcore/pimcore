/*global user,util,google,localStorage*/
(function () {

    /**
     * get parameters and global variables used by this javascript
     *
     * _ptr -> redirect action (GET)
     * _ptc -> programmatically redirect action (GET)
     * _ptp -> persona variant of document page (GET)
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
            if(user["location"] &&
                util.toString(params["country"]).toLowerCase()
                                                    == util.toString(user["location"]["country"]).toLowerCase()) {
                return true;
            }
            return false;
        },

        language: function (params) {
            var needle = util.toString(params["language"]).replace("_", "-").toLocaleLowerCase();
            var userLocale = util.toString(user["language"]).toLowerCase();
            if(needle == userLocale) {
                return true;
            }

            // check only the language without the terretory
            if(needle.indexOf("-") < 0 && userLocale.indexOf("-")) {
                userLocale = userLocale.split("-")[0];
                if(needle == userLocale) {
                    return true;
                }
            }

            return false;
        },

        event: function (params) {
            for(var al=0; al<user["activityLog"].length; al++) {
                if(user["activityLog"][al]["type"] == "event") {
                    if(user["activityLog"][al]["event"]["key"] == params["key"]) {
                        if(user["activityLog"][al]["event"]["value"] == params["value"] || !params["value"]) {
                            return true;
                        }
                    }
                }
            }

            return false;
        },

        geopoint: function (params) {
            if(user["location"] &&
                util.geoDistance(user["location"]["latitude"], user["location"]["longitude"], params["latitude"],
                                                                            params["longitude"]) < params["radius"]) {
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

                for(var al=0; al<user["activityLog"].length; al++) {
                    if(user["activityLog"][al]["type"] == "pageView" && regexp.test(user["activityLog"][al]["url"])) {
                        return true;
                    }
                }
            }
            return false;
        },

        vistitedpagesbefore: function (params) {
            if(params["number"]) {

                var count = 0;
                for(var al=0; al<user["activityLog"].length; al++) {
                    if(user["activityLog"][al]["type"] == "pageView") {
                        count++;
                    }
                }

                return (count >= params["number"]);
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


                var firstActivity;
                for(var al=0; al<user["activityLog"].length; al++) {
                    if(user["activityLog"][al]["sessionId"] == sessionId) {
                        firstActivity = user["activityLog"][al];
                    }
                }

                var date = new Date();
                if( date.getTime() > (firstActivity["timestamp"] + msecs) ) {
                    return true;
                }
            }
            return false;
        },

        linkclicked: function (params) {
            if(params["url"]) {
                var regexp = new RegExp(params["url"]);

                for(var al=0; al<user["activityLog"].length; al++) {
                    if(user["activityLog"][al]["type"] == "linkClicked") {
                        if(regexp.test(user["activityLog"][al]["href"])) {
                            return true;
                        }
                    }
                }
            }
            return false;
        },

        linksclicked: function (params) {
            if(params["number"]) {

                var count = 0;
                for(var al=0; al<user["activityLog"].length; al++) {
                    if(user["activityLog"][al]["type"] == "linkClicked") {
                        count++;
                    }
                }

                return (count >= params["number"]);
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
        },

        persona: function (params) {
            if(user["persona"] == params["persona"]
                || util.in_array(params["persona"], user["personas"])) {
                var personaData = util.getPersonaDataById(params["persona"]);

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

        in_array: function (needle, haystack, argStrict) {
            var key = '',
                strict = !! argStrict;

            if (strict) {
                for (key in haystack) {
                    if (haystack[key] === needle) {
                        return true;
                    }
                }
            } else {
                for (key in haystack) {
                    if (haystack[key] == needle) {
                        return true;
                    }
                }
            }

            return false;
        },

        array_keys: function (input, search_value, argStrict) {
            var search = typeof search_value !== 'undefined',
                tmp_arr = [],
                strict = !!argStrict,
                include = true,
                key = '';

            if (input && typeof input === 'object' && input.change_key_case) { // Duck-type check for our own array()-created PHPJS_Array
                return input.keys(search_value, argStrict);
            }

            for (key in input) {
                if (input.hasOwnProperty(key)) {
                    include = true;
                    if (search) {
                        if (strict && input[key] !== search_value) {
                            include = false;
                        }
                        else if (input[key] != search_value) {
                            include = false;
                        }
                    }

                    if (include) {
                        tmp_arr[tmp_arr.length] = key;
                    }
                }
            }

            return tmp_arr;
        },

        executeInsertedScripts: function (domelement) {
            var scripts = [];

            var ret = domelement.childNodes;
            for (var i = 0; ret[i]; i++) {
                if (scripts && util.nodeName(ret[i], "script")
                                            && (!ret[i].type || ret[i].type.toLowerCase() === "text/javascript")) {
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
            lat1 = lat1 * Math.PI / 180;
            lat2 = lat2 * Math.PI / 180;

            var a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                    Math.sin(dLon/2) * Math.sin(dLon/2) * Math.cos(lat1) * Math.cos(lat2);
            var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            var d = R * c;

            return d;
        },

        listen: function (elem, evnt, func) {
            if (elem.addEventListener) {  // W3C DOM
                elem.addEventListener(evnt,func,false);
            } else if (elem.attachEvent) { // IE DOM
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
                if (e.type == 'readystatechange' && doc.readyState != 'complete') {
                    return;
                }
                (e.type == 'load' ? win : doc)[rem](pre + e.type, init, false);
                if (!done && (done = true)) {
                    fn.call(win, e.type || e);
                }
            },

            poll = function() {
                try { root.doScroll('left'); } catch(e) { setTimeout(poll, 50); return; }
                init('poll');
            };

            if (doc.readyState == 'complete') {
                fn.call(win, 'lazy');
            } else {
                if (doc.createEventObject && root.doScroll) {
                    try { top = !win.frameElement; } catch(e) { }
                    if (top) {
                        poll();
                    }
                }
                doc[add](pre + 'DOMContentLoaded', init, false);
                doc[add](pre + 'readystatechange', init, false);
                win[add](pre + 'load', init, false);
            }
        },

        getPersonaDataById: function (id) {
            if(window["pimcore"] && window["pimcore"]["targeting"] && window["pimcore"]["targeting"]["personas"]) {
                var personas = window["pimcore"]["targeting"]["personas"];
                for(var i=0; i<personas.length; i++) {
                    if(personas[i]["id"] == id) {
                        return personas[i];
                    }
                }
            }
            return null;
        },

        getPersonaAmounts: function () {
            var personaMatches = {};

            for(var pc=0; pc<user["personas"].length; pc++) {
                if(!personaMatches[user["personas"][pc]]) {
                    personaMatches[user["personas"][pc]] = 0;
                }

                personaMatches[user["personas"][pc]]++;
            }

            return personaMatches;


        },

        getPrimaryPersona: function () {
            var personaMatch, personaData;
            var personaAmounts = util.getPersonaAmounts();

            var personaMatchesKeys = util.array_keys(personaAmounts);
            var personaMatchesLastAmount = 0;
            for(pc=0; pc<personaMatchesKeys.length; pc++) {
                if(personaAmounts[personaMatchesKeys[pc]] > personaMatchesLastAmount) {
                    personaData = util.getPersonaDataById(personaMatchesKeys[pc]);
                    if(personaData && personaAmounts[personaMatchesKeys[pc]] >= personaData["threshold"]) {
                        personaMatch = personaMatchesKeys[pc];
                        personaMatchesLastAmount = personaAmounts[personaMatchesKeys[pc]];
                    }
                }
            }

            return personaMatch;
        },

        isGet: function () {
            if(window["pimcore"] && window["pimcore"]["targeting"] && window["pimcore"]["targeting"]["dataPush"]
                && window["pimcore"]["targeting"]["dataPush"]["method"]) {
                if(window["pimcore"]["targeting"]["dataPush"]["method"] == "get") {
                    return true;
                } else {
                    return false;
                }
            }

            // default true
            return false;
        }
    };

    /* App */
    var app = {

        matchesForProgrammatically: [],

        processTargets: function () {
            // process targets
            var targets = pimcore.targeting["targetingRules"];
            if(typeof targets != "undefined") {
                var target;
                var callAction = true;
                var matchingTargets = [];

                for(var t=0; t<targets.length; t++) {
                    target = targets[t];

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

                    callAction = true;

                    var lastTargetCall = null;
                    for(var al=0; al<user["activityLog"].length; al++) {
                        if(user["activityLog"][al]["type"] == "targetRule") {
                            if(user["activityLog"][al]["id"] == matchingTargets[t]["id"]) {
                                lastTargetCall = user["activityLog"][al];
                                break;
                            }
                        }
                    }

                    if(lastTargetCall) {
                        if(matchingTargets[t]["scope"] == "user") {
                            callAction = false;
                        } else if(matchingTargets[t]["scope"] == "session") {
                            if(lastTargetCall["sessionId"] == sessionId) {
                                callAction = false;
                            }
                        }
                    }

                    if(callAction) {
                        addActivityLog({
                            type: "targetRule",
                            id: matchingTargets[t]["id"],
                            scope: matchingTargets[t]["scope"]
                        });
                        app.callActions(matchingTargets[t]);
                    }
                }

                app.saveUser(); // save user before redirecting
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
                if(actions["redirectEnabled"] && actions["redirectUrl"].length > 0 && util.isGet()
                                              && !regexp.test(window.location.href)) {
                    window.location.href = actions["redirectUrl"]
                                            + (actions["redirectUrl"].indexOf("?") < 0 ? "?" : "&")
                                            + "_ptr=" + target.id;
                }
            } catch (e) {
                util.log(e);
            }

            // event
            try {
                if(actions["eventEnabled"] && actions["eventKey"]) {
                    addActivityLog({
                        type: "event",
                        event: {
                            key: actions["eventKey"],
                            value: actions["eventValue"]
                        }
                    });
                    app.saveUser();
                }
            } catch (e2) {
                util.log(e2);
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
                        } catch (e3) {
                            util.log(e3);
                        }
                    });
                }
            } catch (e4) {
                util.log(e4);
            }

            // programmatically
            try {
                if(actions["programmaticallyEnabled"]) {
                    app.matchesForProgrammatically.push(target["id"]);
                }
            } catch (e5) {
                util.log(e5);
            }

            // append persona
            try {
                if(actions["personaEnabled"]) {
                    user["personas"].push(actions["personaId"]);
                    util.log("persona global targeting condition matched -> put ID " + actions["personaId"] + " onto the stack");
                }
            } catch (e6) {
                util.log(e6);
            }
        },

        callTargets: function (targets) {
            if(targets.length > 0 && !/_ptc=/.test(window.location.href) && util.isGet()) {
                window.location.href = window.location.href + (window.location.href.indexOf("?") < 0 ? "?" : "&")
                    + "_ptc=" + targets.join(",");
            }
        },

        saveUser: function() {
            localStorage.setItem("pimcore_targeting_user", JSON.stringify(user));
        }
    };










    // common used vars
    var ua = navigator.userAgent.toLowerCase();
    var now = new Date();
    var nowTimestamp = now.getTime();
    var sessionId;


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

    // unset current assigned persona
    if(user["persona"]) {
        delete user["persona"];
    }

    var addActivityLog = function (data) {
        data["timestamp"] = nowTimestamp;
        data["sessionId"] = sessionId;

        user["activityLog"].unshift(data);
    };

    // check / generate sessionId
    if(!user["activityLog"] || !user["activityLog"][0]) {
        sessionId = nowTimestamp;
        user["activityLog"] = [];
        util.log("no previous activity - new sessionId: " + sessionId);
    } else {
        var lastActivity = user["activityLog"][0];
        if(lastActivity["timestamp"] < (nowTimestamp-(30*60*1000))) {
            sessionId = nowTimestamp; // session expired
            util.log("previous session expired, new sessionId: " + sessionId);
        } else {
            sessionId = lastActivity["sessionId"];
            util.log("sessionId present: " + sessionId);
        }
    }

    try {
        if(!user["location"] && window["pimcore"] && pimcore["location"] && pimcore["location"]["latitude"]) {
            user["location"] = {
                latitude: pimcore["location"]["latitude"],
                longitude: pimcore["location"]["longitude"],
                country: pimcore["location"]["country"]["code"]
            };
        }
    } catch (e5) {
        util.log(e5);
    }



    // do not add programmatic actions and persona content to history
    if(!/_pt(c|p)=/.test(window.location.href) || user["activityLog"].length < 1) {
        addActivityLog({
            type: "pageView",
            url: location.href
        });
    }


    try {
        if(!user["language"]) {
            user["language"] = navigator.browserLanguage ? navigator.browserLanguage : navigator.language;
        }
    } catch (e7) {
        util.log(e7);
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
    } catch (e8) {
        util.log(e8);
    }


    // push data
    var pushData = pimcore.targeting["dataPush"];
    // get new events
    if(pushData["events"] && pushData["events"].length > 0) {
        for(var ev=0; ev<pushData["events"].length; ev++) {
            addActivityLog({
                type: "event",
                event: pushData["events"][ev]
            });
        }
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
    } catch (e10) {
        util.log(e10);
    }

    // collecting personas
    if(!user["personas"]) {
        user["personas"] = [];
    }

    try {
        // here we check the entry conditions
        var personas = pimcore.targeting["personas"];
        if(typeof personas != "undefined" && user["personas"].length < 1) {
            for(var pi=0; pi<personas.length; pi++) {
                if(personas[pi].conditions.length > 0) {
                    try {
                        if(app.testConditions(personas[pi].conditions)) {
                            user["personas"].push(personas[pi]["id"]);
                            util.log("persona entry condition ID " + personas[pi]["id"] + " matched -> put it onto the stack");
                        }
                    } catch (e) {
                        util.log(e);
                    }
                }
            }
        }
    } catch (e11) {
        util.log(e11);
    }

    try {
        if(pushData["personas"] && pushData["personas"].length > 0) {
            for(var ev=0; ev<pushData["personas"].length; ev++) {
                user["personas"].push(pushData["personas"][ev]);
                util.log("persona ID " + pushData["personas"][ev]
                    + " is assigned to the current document -> put it onto the stack");
            }
        }
    } catch (e9) {
        util.log(e9);
    }



    try {
        if(!user["persona"] && user["personas"] && user["personas"].length > 0) {
            var personaMatch = util.getPrimaryPersona();
            if(personaMatch) {
                user["persona"] = personaMatch;
                console.log("use persona ID " + personaMatch + " as primary persona for this page")
            }
        }
    } catch (e16) {

    }


    // dom stuff
    util.contentLoaded(window, function () {
        try {
            var linkElements = document.querySelectorAll("a");
            for (var le = 0; le < linkElements.length; le++) {
                util.listen(linkElements[le], "click", function (ev) {
                    try {
                        var el = ev.target ? ev.target : ev.srcElement;
                        addActivityLog({
                            type: "linkClicked",
                            href: el.getAttribute("href")
                        })

                        app.saveUser();
                    } catch (e) {
                        util.log(e);
                    }
                });
            }
        } catch (e12) {
            util.log(e12);
        }
    });

    app.saveUser();
    app.processTargets();

    window.pimcore["targeting"]["user"] = user;

    var pageVariants = pimcore["targeting"]["dataPush"]["personaPageVariants"];
    var pageVariantMatches = {};
    var pageVariantMatch;
    var personaData;

    if(pageVariants && pageVariants.length > 0 && !/_ptp=/.test(window.location.href)) {
        // get the most accurate persona out of the collected data from visited pages
        if(user["personas"] && user["personas"].length > 0) {

            pageVariantMatches = util.getPersonaAmounts();
            var pageVariantMatchesKeys = util.array_keys(pageVariantMatches);
            var pageVariantMatchesLastAmount = 0;

            for(var pc=0; pc<pageVariantMatchesKeys.length; pc++) {
                if(pageVariantMatches[pageVariantMatchesKeys[pc]] > pageVariantMatchesLastAmount) {
                    personaData = util.getPersonaDataById(pageVariantMatchesKeys[pc]);
                    if(personaData
                        && pageVariantMatches[pageVariantMatchesKeys[pc]] >= personaData["threshold"]
                        && util.in_array(personaData["id"], pageVariants)) {
                        pageVariantMatch = pageVariantMatchesKeys[pc];
                        pageVariantMatchesLastAmount = pageVariantMatches[pageVariantMatchesKeys[pc]];
                    }
                }
            }
        }

        if(pageVariantMatch && util.isGet()) {
            // redirect to the persona specific version of the current page
            window.location.href = window.location.href + (window.location.href.indexOf("?") < 0 ? "?" : "&")
                + "_ptp=" + pageVariantMatch;
        }
    }

    window.pimcore["targeting"]["user"] = user;

})();
