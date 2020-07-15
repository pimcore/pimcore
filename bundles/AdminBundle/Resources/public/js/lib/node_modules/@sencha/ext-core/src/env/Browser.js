/**
 * @class Ext.env.Browser
 * Provides information about browser.
 *
 * Should not be manually instantiated unless for unit-testing.
 * Access the global instance stored in {@link Ext.browser} instead.
 * @private
 */
(Ext.env || (Ext.env = {})).Browser = function(userAgent, publish) {
// @define Ext.env.Browser
// @define Ext.browser
// @require Ext.Object
// @require Ext.Version

    var me = this,
        browserPrefixes = Ext.Boot.browserPrefixes,
        browserNames = Ext.Boot.browserNames,
        enginePrefixes = me.enginePrefixes,
        engineNames = me.engineNames,
        browserMatch = userAgent.match(new RegExp('((?:' +
                Ext.Object.getValues(browserPrefixes).join(')|(?:') + '))([\\w\\._]+)')),
        engineMatch = userAgent.match(new RegExp('((?:' +
                Ext.Object.getValues(enginePrefixes).join(')|(?:') + '))([\\w\\._]+)')),
        browserName = browserNames.other,
        engineName = engineNames.other,
        browserVersion = '',
        engineVersion = '',
        majorVer = '',
        isWebView = false,
        edgeRE = /(Edge\/)([\w.]+)/,
        ripple = '',
        i, prefix, name;

    /**
     * @property {String}
     * Browser User Agent string.
     */
    me.userAgent = userAgent;

    /**
     * A "hybrid" property, can be either accessed as a method call, for example:
     *
     *     if (Ext.browser.is('IE')) {
     *         // ...
     *     }
     *
     * Or as an object with Boolean properties, for example:
     *
     *     if (Ext.browser.is.IE) {
     *         // ...
     *     }
     *
     * Versions can be conveniently checked as well. For example:
     *
     *     if (Ext.browser.is.IE10) {
     *         // Equivalent to (Ext.browser.is.IE && Ext.browser.version.equals(10))
     *     }
     *
     * __Note:__ Only {@link Ext.Version#getMajor major component} and
     * {@link Ext.Version#getShortVersion simplified} value of the version are available via
     * direct property checking.
     *
     * Supported values are:
     *
     * - IE
     * - Firefox
     * - Safari
     * - Chrome
     * - Opera
     * - WebKit
     * - Gecko
     * - Presto
     * - Trident
     * - WebView
     * - Other
     *
     * @param {String} name The OS name to check.
     * @return {Boolean}
     */
    this.is = function(name) {
        // Since this function reference also acts as a map, we do not want it to be
        // shared between instances, so it is defined here, not on the prototype.
        return !!this.is[name];
    };

    // Edge has a userAgent with All browsers so we manage it separately
    // "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko)
    // Chrome/42.0.2311.135 Safari/537.36 Edge/12.10240"
    if (/Edge\//.test(userAgent)) {
        browserMatch = userAgent.match(edgeRE);
        engineMatch = userAgent.match(edgeRE);
    }

    if (browserMatch) {
        browserName = browserNames[Ext.Object.getKey(browserPrefixes, browserMatch[1])];

        //<feature legacyBrowser>
        if (browserName === 'Safari' && /^Opera/.test(userAgent)) {
            // Prevent Opera 12 and earlier from being incorrectly reported as Safari
            browserName = 'Opera';
        }

        //</feature>
        browserVersion = new Ext.Version(browserMatch[2]);
    }

    if (engineMatch) {
        engineName = engineNames[Ext.Object.getKey(enginePrefixes, engineMatch[1])];
        engineVersion = new Ext.Version(engineMatch[2]);
    }

    if (engineName === 'Trident' && browserName !== 'IE') {
        browserName = 'IE';

        var version = userAgent.match(/.*rv:(\d+.\d+)/); // eslint-disable-line vars-on-top

        if (version && version.length) {
            version = version[1];
            browserVersion = new Ext.Version(version);
        }
    }

    if (browserName && browserVersion) {
        Ext.setVersion(browserName, browserVersion);
    }

    /**
     * @property chromeVersion
     * The current version of Chrome (0 if the browser is not Chrome).
     * @readonly
     * @type Number
     * @member Ext
     */

    /**
     * @property firefoxVersion
     * The current version of Firefox (0 if the browser is not Firefox).
     * @readonly
     * @type Number
     * @member Ext
     */

    /**
     * @property ieVersion
     * The current version of IE (0 if the browser is not IE). This does not account
     * for the documentMode of the current page, which is factored into {@link #isIE8},
     * and {@link #isIE9}. Thus this is not always true:
     *
     *     Ext.isIE8 == (Ext.ieVersion == 8)
     *
     * @readonly
     * @type Number
     * @member Ext
     */

    /**
     * @property isChrome
     * True if the detected browser is Chrome.
     * @readonly
     * @type Boolean
     * @member Ext
     */

    /**
     * @property isGecko
     * True if the detected browser uses the Gecko layout engine (e.g. Mozilla, Firefox).
     * @readonly
     * @type Boolean
     * @member Ext
     */

    /**
     * @property isIE
     * True if the detected browser is Internet Explorer.
     * @readonly
     * @type Boolean
     * @member Ext
     */

    /**
     * @property isIE8
     * True if the detected browser is Internet Explorer 8.x.
     * @readonly
     * @type Boolean
     * @member Ext
     */

    /**
     * @property isIE8m
     * True if the detected browser is Internet Explorer 8.x or lower.
     * @readonly
     * @type Boolean
     * @member Ext
     */

    /**
     * @property isIE8p
     * True if the detected browser is Internet Explorer 8.x or higher.
     * @readonly
     * @type Boolean
     * @member Ext
     */

    /**
     * @property isIE9
     * True if the detected browser is Internet Explorer 9.x.
     * @readonly
     * @type Boolean
     * @member Ext
     */

    /**
     * @property isIE9m
     * True if the detected browser is Internet Explorer 9.x or lower.
     * @readonly
     * @type Boolean
     * @member Ext
     */

    /**
     * @property isIE9p
     * True if the detected browser is Internet Explorer 9.x or higher.
     * @readonly
     * @type Boolean
     * @member Ext
     */

    /**
     * @property isIE10
     * True if the detected browser is Internet Explorer 10.x.
     * @readonly
     * @type Boolean
     * @member Ext
     */

    /**
     * @property isIE10m
     * True if the detected browser is Internet Explorer 10.x or lower.
     * @readonly
     * @type Boolean
     * @member Ext
     */

    /**
     * @property isIE10p
     * True if the detected browser is Internet Explorer 10.x or higher.
     * @readonly
     * @type Boolean
     * @member Ext
     */

    /**
     * @property isIE11
     * True if the detected browser is Internet Explorer 11.x.
     * @readonly
     * @type Boolean
     * @member Ext
     */

    /**
     * @property isIE11m
     * True if the detected browser is Internet Explorer 11.x or lower.
     * @readonly
     * @type Boolean
     * @member Ext
     */

    /**
     * @property isIE11p
     * True if the detected browser is Internet Explorer 11.x or higher.
     * @readonly
     * @type Boolean
     * @member Ext
     */

    /**
     * @property isEdge
     * True if the detected browser is Edge.
     * @readonly
     * @type Boolean
     * @member Ext
     */

    /**
     * @property isLinux
     * True if the detected platform is Linux.
     * @readonly
     * @type Boolean
     * @member Ext
     */

    /**
     * @property isMac
     * True if the detected platform is Mac OS.
     * @readonly
     * @type Boolean
     * @member Ext
     */

    /**
     * @property isOpera
     * True if the detected browser is Opera.
     * @readonly
     * @type Boolean
     * @member Ext
     */

    /**
     * @property isSafari
     * True if the detected browser is Safari.
     * @readonly
     * @type Boolean
     * @member Ext
     */

    /**
     * @property isWebKit
     * True if the detected browser uses WebKit.
     * @readonly
     * @type Boolean
     * @member Ext
     */

    /**
     * @property isWindows
     * True if the detected platform is Windows.
     * @readonly
     * @type Boolean
     * @member Ext
     */

    /**
     * @property operaVersion
     * The current version of Opera (0 if the browser is not Opera).
     * @readonly
     * @type Number
     * @member Ext
     */

    /**
     * @property safariVersion
     * The current version of Safari (0 if the browser is not Safari).
     * @readonly
     * @type Number
     * @member Ext
     */

    /**
     * @property webKitVersion
     * The current version of WebKit (0 if the browser does not use WebKit).
     * @readonly
     * @type Number
     * @member Ext
     */

    // Facebook changes the userAgent when you view a website within their iOS app.
    // For some reason, the strip out information about the browser, so we have to detect
    // that and fake it...
    if (userAgent.match(/FB/) && browserName === 'Other') {
        browserName = browserNames.safari;
        engineName = engineNames.webkit;
    }
    // Detect chrome first as Chrome in Android 8.0 introduced OPR in the user agent
    else if (userAgent.match(/Android.*Chrome/g)) {
        browserName = 'ChromeMobile';
    }
    else {
        browserMatch = userAgent.match(/OPR\/(\d+.\d+)/);

        if (browserMatch) {
            browserName = 'Opera';
            browserVersion = new Ext.Version(browserMatch[1]);
        }
    }

    Ext.apply(this, {
        engineName: engineName,
        engineVersion: engineVersion,
        name: browserName,
        version: browserVersion
    });

    this.setFlag(browserName, true, publish); // e.g., Ext.isIE

    if (browserVersion) {
        majorVer = browserVersion.getMajor() || '';

        //<feature legacyBrowser>
        if (me.is.IE) {
            majorVer = document.documentMode || parseInt(majorVer, 10);

            for (i = 7; i <= 11; ++i) {
                prefix = 'isIE' + i;

                Ext[prefix] = majorVer === i;
                Ext[prefix + 'm'] = majorVer <= i;
                Ext[prefix + 'p'] = majorVer >= i;
            }
        }

        if (me.is.Opera && parseInt(majorVer, 10) <= 12) {
            Ext.isOpera12m = true;
        }
        //</feature>

        Ext.chromeVersion = Ext.isChrome ? majorVer : 0;
        Ext.firefoxVersion = Ext.isFirefox ? majorVer : 0;
        Ext.ieVersion = Ext.isIE ? majorVer : 0;
        Ext.operaVersion = Ext.isOpera ? majorVer : 0;
        Ext.safariVersion = Ext.isSafari ? majorVer : 0;
        Ext.webKitVersion = Ext.isWebKit ? majorVer : 0;

        this.setFlag(browserName + majorVer, true, publish); // Ext.isIE10
        this.setFlag(browserName + browserVersion.getShortVersion());
    }

    for (i in browserNames) {
        if (browserNames.hasOwnProperty(i)) {
            name = browserNames[i];

            this.setFlag(name, browserName === name);
        }
    }

    this.setFlag(name);

    if (engineVersion) {
        this.setFlag(engineName + (engineVersion.getMajor() || ''));
        this.setFlag(engineName + engineVersion.getShortVersion());
    }

    for (i in engineNames) {
        if (engineNames.hasOwnProperty(i)) {
            name = engineNames[i];

            this.setFlag(name, engineName === name, publish);
        }
    }

    this.setFlag('Standalone', !!navigator.standalone);

    // Cross domain access could throw an error
    try {
        ripple = window.top.ripple;
    }
    catch (e) {
        // Do nothing, can't access cross frame so leave it empty
    }

    /* eslint-disable-next-line max-len */
    this.setFlag('Ripple', !!document.getElementById("tinyhippos-injected") && !Ext.isEmpty(ripple));
    this.setFlag('WebWorks', !!window.blackberry);

    if (window.PhoneGap !== undefined || window.Cordova !== undefined ||
        window.cordova !== undefined) {
        isWebView = true;
        this.setFlag('PhoneGap');
        this.setFlag('Cordova');
    }

    // Check if running in UIWebView
    if (/(iPhone|iPod|iPad).*AppleWebKit(?!.*Safari)(?!.*FBAN)/i.test(userAgent)) {
        isWebView = true;
    }

    // Flag to check if it we are in the WebView
    this.setFlag('WebView', isWebView);

    /**
     * @property {Boolean}
     * `true` if browser is using strict mode.
     */
    this.isStrict = Ext.isStrict = document.compatMode === "CSS1Compat";

    /**
     * @property {Boolean}
     * `true` if page is running over SSL.
     */
    this.isSecure = Ext.isSecure;

    // IE10Quirks, Chrome26Strict, etc.
    this.identity = browserName + majorVer + (this.isStrict ? 'Strict' : 'Quirks');
};

Ext.env.Browser.prototype = {
    constructor: Ext.env.Browser,

    engineNames: {
        edge: 'Edge',
        webkit: 'WebKit',
        gecko: 'Gecko',
        presto: 'Presto',
        trident: 'Trident',
        other: 'Other'
    },

    enginePrefixes: {
        edge: 'Edge/',
        webkit: 'AppleWebKit/',
        gecko: 'Gecko/',
        presto: 'Presto/',
        trident: 'Trident/'
    },

    styleDashPrefixes: {
        WebKit: '-webkit-',
        Gecko: '-moz-',
        Trident: '-ms-',
        Presto: '-o-',
        Other: ''
    },

    stylePrefixes: {
        WebKit: 'Webkit',
        Gecko: 'Moz',
        Trident: 'ms',
        Presto: 'O',
        Other: ''
    },

    propertyPrefixes: {
        WebKit: 'webkit',
        Gecko: 'moz',
        Trident: 'ms',
        Presto: 'o',
        Other: ''
    },

    // scope: Ext.env.Browser.prototype

    /**
     * The full name of the current browser.
     * Possible values are:
     *
     * - IE
     * - Firefox
     * - Safari
     * - Chrome
     * - Opera
     * - Other
     * @type String
     * @readonly
     */
    name: null,

    /**
     * Refer to {@link Ext.Version}.
     * @type Ext.Version
     * @readonly
     */
    version: null,

    /**
     * The full name of the current browser's engine.
     * Possible values are:
     *
     * - WebKit
     * - Gecko
     * - Presto
     * - Trident
     * - Other
     * @type String
     * @readonly
     */
    engineName: null,

    /**
     * Refer to {@link Ext.Version}.
     * @type Ext.Version
     * @readonly
     */
    engineVersion: null,

    setFlag: function(name, value, publish) {
        if (value === undefined) {
            value = true;
        }

        this.is[name] = value;
        this.is[name.toLowerCase()] = value;

        if (publish) {
            Ext['is' + name] = value;
        }

        return this;
    },

    getStyleDashPrefix: function() {
        return this.styleDashPrefixes[this.engineName];
    },

    getStylePrefix: function() {
        return this.stylePrefixes[this.engineName];
    },

    getVendorProperyName: function(name) {
        var prefix = this.propertyPrefixes[this.engineName];

        if (prefix.length > 0) {
            return prefix + Ext.String.capitalize(name);
        }

        return name;
    }
};

/**
 * @class Ext.browser
 * @extends Ext.env.Browser
 * @singleton
 * Provides useful information about the current browser.
 *
 * Example:
 *
 *     if (Ext.browser.is.IE) {
 *         // IE specific code here
 *     }
 *
 *     if (Ext.browser.is.WebKit) {
 *         // WebKit specific code here
 *     }
 *
 *     console.log("Version " + Ext.browser.version);
 *
 * For a full list of supported values, refer to {@link #is} property/method.
 *
 */
(function(userAgent) {
Ext.browser = new Ext.env.Browser(userAgent, true);
Ext.userAgent = userAgent.toLowerCase();

/**
 * @property {String} SSL_SECURE_URL
 * URL to a blank file used by Ext when in secure mode for iframe src and onReady src
 * to prevent the IE insecure content warning (`'about:blank'`, except for IE
 * in secure mode, which is `'javascript:""'`).
 * @member Ext
 */
Ext.SSL_SECURE_URL = Ext.isSecure && Ext.isIE ? 'javascript:\'\'' : 'about:blank';
}(Ext.global.navigator.userAgent));
