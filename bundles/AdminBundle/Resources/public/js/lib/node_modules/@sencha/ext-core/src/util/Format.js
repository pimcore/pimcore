/**
 * @class Ext.util.Format
 *  
 * This class is a centralized place for formatting functions. It includes
 * functions to format various different types of data, such as text, dates and numeric values.
 *  
 * ## Localization
 *
 * This class contains several options for localization. These can be set once the library
 * has loaded, all calls to the functions from that point will use the locale settings
 * that were specified.
 *
 * Options include:
 *
 * - thousandSeparator
 * - decimalSeparator
 * - currencyPrecision
 * - currencySign
 * - currencyAtEnd
 *
 * This class also uses the default date format defined here: {@link Ext.Date#defaultFormat}.
 *
 * ## Using with renderers
 *
 * There are two helper functions that return a new function that can be used in conjunction with
 * grid renderers:
 *  
 *     columns: [{
 *         dataIndex: 'date',
 *         renderer: Ext.util.Format.dateRenderer('Y-m-d')
 *     }, {
 *         dataIndex: 'time',
 *         renderer: Ext.util.Format.numberRenderer('0.000')
 *     }]
 *  
 * Functions that only take a single argument can also be passed directly:
 *
 *     columns: [{
 *         dataIndex: 'cost',
 *         renderer: Ext.util.Format.usMoney
 *     }, {
 *         dataIndex: 'productCode',
 *         renderer: Ext.util.Format.uppercase
 *     }]
 *  
 * ## Using with XTemplates
 *
 * XTemplates can also directly use Ext.util.Format functions:
 *  
 *     new Ext.XTemplate([
 *         'Date: {startDate:date("Y-m-d")}',
 *         'Cost: {cost:usMoney}'
 *     ]);
 *
 * @singleton
 */
Ext.define('Ext.util.Format', function() {
    var me; // holds our singleton instance

    return {
        requires: [
            'Ext.Error',
            'Ext.Number',
            'Ext.String',
            'Ext.Date'
        ],

        singleton: true,

        /**
         * The global default date format.
         */
        defaultDateFormat: 'm/d/Y',

        /**
         * @property {String} thousandSeparator
         * The character that the {@link #number} function uses as a thousand separator.
         *
         * This may be overridden in a locale file.
         * @locale
         */
        thousandSeparator: ',',

        /**
         * @property {String} decimalSeparator
         * The character that the {@link #number} function uses as a decimal point.
         *
         * This may be overridden in a locale file.
         * @locale
         */
        decimalSeparator: '.',

        /**
         * @property {Number} currencyPrecision
         * The number of decimal places that the {@link #currency} function displays.
         *
         * This may be overridden in a locale file.
         * @locale
         */
        currencyPrecision: 2,

        /**
         * @property {String} currencySign
         * The currency sign that the {@link #currency} function displays.
         *
         * This may be overridden in a locale file.
         * @locale
         */
        currencySign: '$',

        /**
         * @property {String} currencySpacer
         * True to add a space between the currency and the value
         *
         * This may be overridden in a locale file.
         * @since 6.2.0
         * @locale
         */
        currencySpacer: '',

        /**
         * @property {String} percentSign
         * The percent sign that the {@link #percent} function displays.
         *
         * This may be overridden in a locale file.
         * @locale
         */
        percentSign: '%',

        /**
         * @property {Boolean} currencyAtEnd
         * This may be set to <code>true</code> to make the {@link #currency} function
         * append the currency sign to the formatted value.
         *
         * This may be overridden in a locale file.
         * @locale
         */
        currencyAtEnd: false,

        stripTagsRe: /<\/?[^>]+>/gi,
        stripScriptsRe: /(?:<script.*?>)((\n|\r|.)*?)(?:<\/script>)/ig,
        nl2brRe: /\r?\n/g,
        hashRe: /#+$/,
        allHashes: /^#+$/,

        // Match a format string characters to be able to detect remaining "literal" characters
        formatPattern: /[\d,.#]+/,

        // A RegExp to remove from a number format string, all characters except digits and '.'
        formatCleanRe: /[^\d.#]/g,

        // A RegExp to remove from a number format string, all characters except digits
        // and the local decimal separator. Created on first use. The local decimal separator
        // character must be initialized for this to be created.
        I18NFormatCleanRe: null,

        // Cache ofg number formatting functions keyed by format string
        formatFns: {},

        constructor: function() {
            me = this; // we are a singleton, so cache our this pointer in scope
        },

        /**
         * Returns a non-breaking space ("NBSP") for any "blank" value.
         * @param {Mixed} value
         * @param {Boolean} [strict=true] Pass `false` to convert all falsey values to an
         * NBSP. By default, only '', `null` and `undefined` will be converted.
         * @return {Mixed}
         * @since 6.2.0
         */
        nbsp: function(value, strict) {
            strict = strict !== false;

            if (strict ? value === '' || value == null : !value) {
                value = '\xA0';
            }

            return value;
        },

        /**
         * Checks a reference and converts it to empty string if it is undefined.
         * @param {Object} value Reference to check
         * @return {Object} Empty string if converted, otherwise the original value
         */
        undef: function(value) {
            return value !== undefined ? value : "";
        },

        /**
         * Checks a reference and converts it to the default value if it's empty.
         * @param {Object} value Reference to check
         * @param {String} [defaultValue=""] The value to insert of it's undefined.
         * @return {String}
         */
        defaultValue: function(value, defaultValue) {
            return value !== undefined && value !== '' ? value : defaultValue;
        },

        /**
         * Returns a substring from within an original string.
         * @param {String} value The original text
         * @param {Number} start The start index of the substring
         * @param {Number} length The length of the substring
         * @return {String} The substring
         * @method
         */
        substr: 'ab'.substr(-1) !== 'b'
            ? function(value, start, length) {
                var str = String(value);

                return (start < 0)
                    ? str.substr(Math.max(str.length + start, 0), length)
                    : str.substr(start, length);
            }
            : function(value, start, length) {
                return String(value).substr(start, length);
            },

        /**
         * Converts a string to all lower case letters.
         * @param {String} value The text to convert
         * @return {String} The converted text
         */
        lowercase: function(value) {
            return String(value).toLowerCase();
        },

        /**
         * Converts a string to all upper case letters.
         * @param {String} value The text to convert
         * @return {String} The converted text
         */
        uppercase: function(value) {
            return String(value).toUpperCase();
        },

        /**
         * Format a number as US currency.
         * @param {Number/String} value The numeric value to format
         * @return {String} The formatted currency string
         */
        usMoney: function(value) {
            return me.currency(value, '$', 2);
        },

        /**
         * Format a number as a currency.
         * @param {Number/String} value The numeric value to format
         * @param {String} [currencySign] The currency sign to use (defaults to
         * {@link #currencySign})
         * @param {Number} [decimals] The number of decimals to use for the currency
         * (defaults to {@link #currencyPrecision})
         * @param {Boolean} [end] True if the currency sign should be at the end of the string
         * (defaults to {@link #currencyAtEnd})
         * @param {String} [currencySpacer] True to add a space between the currency and value
         * @return {String} The formatted currency string
         */
        currency: function(value, currencySign, decimals, end, currencySpacer) {
            var negativeSign = '',
                format = ",0",
                i = 0;

            value = value - 0;

            if (value < 0) {
                value = -value;
                negativeSign = '-';
            }

            decimals = Ext.isDefined(decimals) ? decimals : me.currencyPrecision;
            format += (decimals > 0 ? '.' : '');

            for (; i < decimals; i++) {
                format += '0';
            }

            value = me.number(value, format);

            if (currencySpacer == null) {
                currencySpacer = me.currencySpacer;
            }

            if ((end || me.currencyAtEnd) === true) {
                return Ext.String.format("{0}{1}{2}{3}", negativeSign, value, currencySpacer,
                                         currencySign || me.currencySign);
            }
            else {
                return Ext.String.format("{0}{1}{2}{3}", negativeSign,
                                         currencySign || me.currencySign, currencySpacer, value);
            }
        },

        /**
         * Formats the passed date using the specified format pattern.
         * Note that this uses the native Javascript Date.parse() method and is therefore subject
         * to its idiosyncrasies. Most formats assume the local timezone unless specified.
         * One notable exception is 'YYYY-MM-DD' (note the dashes) which is typically interpreted
         * in UTC and can cause date shifting.
         * 
         * @param {String/Date} value The value to format. Strings must conform to the format
         * expected by the JavaScript Date object's
         * [parse() method](http://developer.mozilla.org/en-US/docs/JavaScript/Reference/Global_Objects/Date/parse).
         * @param {String} [format] Any valid date format string. Defaults to
         * {@link Ext.Date#defaultFormat}.
         * @return {String} The formatted date string.
         */
        date: function(value, format) {
            if (!value) {
                return "";
            }

            if (!Ext.isDate(value)) {
                value = new Date(Date.parse(value));
            }

            return Ext.Date.dateFormat(value, format || Ext.Date.defaultFormat);
        },

        /**
         * Returns a date rendering function that can be reused to apply a date format multiple
         * times efficiently.
         * @param {String} format Any valid date format string. Defaults to
         * {@link Ext.Date#defaultFormat}.
         * @return {Function} The date formatting function
         */
        dateRenderer: function(format) {
            return function(v) {
                return me.date(v, format);
            };
        },

        /**
         * Returns the given number as a base 16 string at least `digits` in length. If
         * the number is fewer digits, 0's are prepended as necessary. If `digits` is
         * negative, the absolute value is the *exact* number of digits to return. In this
         * case, if then number has more digits, only the least significant digits are
         * returned.
         *
         *      expect(Ext.util.Format.hex(0x12e4, 2)).toBe('12e4');
         *      expect(Ext.util.Format.hex(0x12e4, -2)).toBe('e4');
         *      expect(Ext.util.Format.hex(0x0e, 2)).toBe('0e');
         *
         * @param {Number} value The number to format in hex.
         * @param {Number} digits
         * @return {string}
         */
        hex: function(value, digits) {
            var s = parseInt(value || 0, 10).toString(16);

            if (digits) {
                if (digits < 0) {
                    digits = -digits;

                    if (s.length > digits) {
                        s = s.substring(s.length - digits);
                    }
                }

                while (s.length < digits) {
                    s = '0' + s;
                }
            }

            return s;
        },

        /**
         * Returns this result:
         *
         *      value || orValue
         *
         * The usefulness of this formatter method is in templates. For example:
         *
         *      {foo:or("bar")}
         *
         * @param {Boolean} value The "if" value.
         * @param {Mixed} orValue
         */
        or: function(value, orValue) {
            return value || orValue;
        },

        /**
         * If `value` is a number, returns the argument from that index. For example
         *
         *      var s = Ext.util.Format.pick(2, 'zero', 'one', 'two');
         *      // s === 'two'
         *
         * Otherwise, `value` is treated in a truthy/falsey manner like so:
         *
         *      var s = Ext.util.Format.pick(null, 'first', 'second');
         *      // s === 'first'
         *
         *      s = Ext.util.Format.pick({}, 'first', 'second');
         *      // s === 'second'
         *
         * The usefulness of this formatter method is in templates. For example:
         *
         *      {foo:pick("F","T")}
         *
         *      {bar:pick("first","second","third")}
         *
         * @param {Boolean} value The "if" value.
         * @param {Mixed} firstValue
         * @param {Mixed} secondValue
         */
        pick: function(value, firstValue, secondValue) {
            var ret;

            if (Ext.isNumber(value)) {
                ret = arguments[value + 1];

                if (ret) {
                    return ret;
                }
            }

            return value ? secondValue : firstValue;
        },

        /**
         * Compares `value` against `threshold` and returns:
         *
         * - if `value` < `threshold` then it returns `below`
         * - if `value` > `threshold` then it returns `above`
         * - if `value` = `threshold` then it returns `equal` or `above` when `equal` is missing
         *
         * The usefulness of this formatter method is in templates. For example:
         *
         *      {foo:lessThanElse(0, 'negative', 'positive')}
         *
         *      {bar:lessThanElse(200, 'lessThan200', 'greaterThan200', 'equalTo200')}
         *
         * @param {Number} value Value that will be checked
         * @param {Number} threshold Value to compare against
         * @param {Mixed} below Value to return when `value` < `threshold`
         * @param {Mixed} above Value to return when `value` > `threshold`.
         * If `value` = `threshold` and `equal` is missing then `above` is returned.
         * @param {Mixed} equal Value to return when `value` = `threshold`
         * @return {Mixed}
         */
        lessThanElse: function(value, threshold, below, above, equal) {
            var v = Ext.Number.from(value, 0),
                t = Ext.Number.from(threshold, 0),
                missing = !Ext.isDefined(equal);

            return v < t ? below : (v > t ? above : (missing ? above : equal));
        },

        /**
         * Checks if `value` is a positive or negative number and returns the proper param.
         *
         * The usefulness of this formatter method is in templates. For example:
         *
         *      {foo:sign("clsNegative","clsPositive")}
         *
         * @param {Number} value
         * @param {Mixed} negative
         * @param {Mixed} positive
         * @param {Mixed} zero
         * @return {Mixed}
         */
        sign: function(value, negative, positive, zero) {
            if (zero === undefined) {
                zero = positive;
            }

            return me.lessThanElse(value, 0, negative, positive, zero);
        },

        /**
         * Strips all HTML tags.
         * @param {Object} value The text from which to strip tags
         * @return {String} The stripped text
         */
        stripTags: function(value) {
            return !value ? value : String(value).replace(me.stripTagsRe, "");
        },

        /**
         * Strips all script tags.
         * @param {Object} value The text from which to strip script tags
         * @return {String} The stripped text
         */
        stripScripts: function(value) {
            return !value ? value : String(value).replace(me.stripScriptsRe, "");
        },

        /**
         * @method
         * Simple format for a file size (xxx bytes, xxx KB, xxx MB).
         * @param {Number/String} size The numeric value to format
         * @return {String} The formatted file size
         */
        fileSize: (function() {
            var byteLimit = 1024,
                kbLimit = 1048576,
                mbLimit = 1073741824;

            return function(size) {
                var out;

                if (size < byteLimit) {
                    if (size === 1) {
                        out = '1 byte';
                    }
                    else {
                        out = size + ' bytes';
                    }
                }
                else if (size < kbLimit) {
                    out = (Math.round(((size * 10) / byteLimit)) / 10) + ' KB';
                }
                else if (size < mbLimit) {
                    out = (Math.round(((size * 10) / kbLimit)) / 10) + ' MB';
                }
                else {
                    out = (Math.round(((size * 10) / mbLimit)) / 10) + ' GB';
                }

                return out;
            };
        })(),

        /**
         * It does simple math for use in a template, for example:
         *
         *     var tpl = new Ext.Template('{value} * 10 = {value:math("* 10")}');
         *
         * @return {Function} A function that operates on the passed value.
         * @method
         */
        math: (function() {
            var fns = {};

            return function(v, a) {
                if (!fns[a]) {
                    fns[a] = Ext.functionFactory('v', 'return v ' + a + ';');
                }

                return fns[a](v);
            };
        }()),

        /**
         * Rounds the passed number to the required decimal precision.
         * @param {Number/String} value The numeric value to round.
         * @param {Number} [precision] The number of decimal places to which to round the
         * first parameter's value. If `undefined` the `value` is passed to `Math.round`
         * otherwise the value is returned unmodified.
         * @return {Number} The rounded value.
         */
        round: function(value, precision) {
            var result = Number(value);

            if (typeof precision === 'number') {
                precision = Math.pow(10, precision);
                result = Math.round(value * precision) / precision;
            }
            else if (precision === undefined) {
                result = Math.round(result);
            }

            return result;
        },

        /**
         * Formats the passed number according to the passed format string.
         *
         * The number of digits after the decimal separator character specifies the number of
         * decimal places in the resulting string. The *local-specific* decimal character is
         * used in the result.
         *
         * The *presence* of a thousand separator character in the format string specifies that
         * the *locale-specific* thousand separator (if any) is inserted separating thousand groups.
         *
         * By default, "," is expected as the thousand separator, and "." is expected as the decimal
         * separator.
         *
         * Locale-specific characters are always used in the formatted output when inserting
         * thousand and decimal separators. These can be set using the {@link #thousandSeparator}
         * and {@link #decimalSeparator} options.
         *
         * The format string must specify separator characters according to US/UK conventions
         * ("," as the thousand separator, and "." as the decimal separator)
         *
         * To allow specification of format strings according to local conventions for separator
         * characters, add the string `/i` to the end of the format string. This format depends
         * on the {@link #thousandSeparator} and {@link #decimalSeparator} options. For example,
         * if using European style separators, then the format string can be specified
         * as `'0.000,00'`. This would be equivalent to using `'0,000.00'` when using US style
         * formatting.
         *
         * Examples (123456.789):
         * 
         * - `0` - (123457) show only digits, no precision
         * - `0.00` - (123456.79) show only digits, 2 precision
         * - `0.0000` - (123456.7890) show only digits, 4 precision
         * - `0,000` - (123,457) show comma and digits, no precision
         * - `0,000.00` - (123,456.79) show comma and digits, 2 precision
         * - `0,0.00` - (123,456.79) shortcut method, show comma and digits, 2 precision
         * - `0.####` - (123,456.789) Allow maximum 4 decimal places, but do not right pad
         * with zeroes
         * - `0.00##` - (123456.789) Show at least 2 decimal places, maximum 4, but do not
         * right pad with zeroes
         *
         * @param {Number} v The number to format.
         * @param {String} formatString The way you would like to format this text.
         * @return {String} The formatted number.
         */
        number: function(v, formatString) {
            var formatFn;

            if (!formatString) {
                return v;
            }

            if (isNaN(v)) {
                return '';
            }

            formatFn = me.formatFns[formatString];

            // Generate formatting function to be cached and reused keyed by the format string.
            // This results in a 100% performance increase over analyzing the format string
            // each invocation.
            if (!formatFn) {
                // eslint-disable-next-line vars-on-top
                var originalFormatString = formatString,
                    comma = me.thousandSeparator,
                    decimalSeparator = me.decimalSeparator,
                    precision = 0,
                    trimPart = '',
                    hasComma,
                    splitFormat,
                    extraChars,
                    trimTrailingZeroes,
                    code, len;

                // The "/i" suffix allows caller to use a locale-specific formatting string.
                // Clean the format string by removing all but numerals and the decimal separator.
                // Then split the format string into pre and post decimal segments according to
                // *what* the decimal separator is. If they are specifying "/i", they are using
                // the local convention in the format string.
                if (formatString.substr(formatString.length - 2) === '/i') {
                    // In a vast majority of cases, the separator will never change
                    // over the lifetime of the application.
                    // So we'll only regenerate this if we really need to
                    if (!me.I18NFormatCleanRe || me.lastDecimalSeparator !== decimalSeparator) {
                        me.I18NFormatCleanRe = new RegExp('[^\\d\\' + decimalSeparator + '#]', 'g');
                        me.lastDecimalSeparator = decimalSeparator;
                    }

                    formatString = formatString.substr(0, formatString.length - 2);
                    hasComma = formatString.indexOf(comma) !== -1;
                    splitFormat =
                        formatString.replace(me.I18NFormatCleanRe, '').split(decimalSeparator);
                }
                else {
                    hasComma = formatString.indexOf(',') !== -1;
                    splitFormat = formatString.replace(me.formatCleanRe, '').split('.');
                }

                extraChars = formatString.replace(me.formatPattern, '');

                if (splitFormat.length > 2) {
                    //<debug>
                    Ext.raise({
                        sourceClass: "Ext.util.Format",
                        sourceMethod: "number",
                        value: v,
                        formatString: formatString,
                        msg: "Invalid number format, should have no more than 1 decimal"
                    });
                    //</debug>
                }
                else if (splitFormat.length === 2) {
                    precision = splitFormat[1].length;

                    // Formatting ending in .##### means maximum 5 trailing significant digits
                    trimTrailingZeroes = splitFormat[1].match(me.hashRe);

                    if (trimTrailingZeroes) {
                        len = trimTrailingZeroes[0].length;
                        // Need to escape, since this will be '.' by default
                        // eslint-disable-next-line max-len
                        trimPart = 'trailingZeroes=new RegExp(Ext.String.escapeRegex(utilFormat.decimalSeparator) + "*0{0,' + len + '}$")';
                    }
                }

                // The function we create is called immediately and returns a closure
                // which has access to vars and some fixed values; RegExes and the format string.
                code = [
                    'var utilFormat=Ext.util.Format,extNumber=Ext.Number,neg,absVal,fnum,parts' +
                        (hasComma ? ',thousandSeparator,thousands=[],j,n,i' : '') +
                        (extraChars ? ',formatString="' + formatString + '",formatPattern=/[\\d,\\.#]+/' : '') + // eslint-disable-line max-len
                        ',trailingZeroes;' +
                    'return function(v){' +
                    'if(typeof v!=="number"&&isNaN(v=extNumber.from(v,NaN)))return"";' +
                    'neg=v<0;',
                    'absVal=Math.abs(v);',
                    'fnum=Ext.Number.toFixed(absVal, ' + precision + ');',
                    trimPart, ';'
                ];

                if (hasComma) {
                    // If we have to insert commas...

                    // split the string up into whole and decimal parts if there are decimals
                    if (precision) {
                        code[code.length] = 'parts=fnum.split(".");';
                        code[code.length] = 'fnum=parts[0];';
                    }

                    code[code.length] =
                        'if(absVal>=1000) {';
                    code[code.length] = 'thousandSeparator=utilFormat.thousandSeparator;' +
                            'thousands.length=0;' +
                            'j=fnum.length;' +
                            'n=fnum.length%3||3;' +
                            'for(i=0;i<j;i+=n){' +
                                'if(i!==0){' +
                                    'n=3;' +
                                '}' +
                                'thousands[thousands.length]=fnum.substr(i,n);' +
                            '}' +
                            'fnum=thousands.join(thousandSeparator);' +
                        '}';

                    if (precision) {
                        code[code.length] = 'fnum += utilFormat.decimalSeparator+parts[1];';
                    }

                }
                else if (precision) {
                    // If they are using a weird decimal separator, split and concat using it
                    code[code.length] = 'if(utilFormat.decimalSeparator!=="."){' +
                        'parts=fnum.split(".");' +
                        'fnum=parts[0]+utilFormat.decimalSeparator+parts[1];' +
                    '}';
                }

                /*
                 * Edge case. If we have a very small negative number it will get rounded to 0,
                 * however the initial check at the top will still report as negative. Replace
                 * everything but 1-9 and check if the string is empty to determine a 0 value.
                 */
                code[code.length] = 'if(neg&&fnum!=="' +
                                    (precision ? '0.' + Ext.String.repeat('0', precision) : '0') +
                                    '") { fnum="-"+fnum; }';

                if (trimTrailingZeroes) {
                    code[code.length] = 'fnum=fnum.replace(trailingZeroes,"");';
                }

                code[code.length] = 'return ';

                // If there were extra characters around the formatting string,
                // replace the format string part with the formatted number.
                if (extraChars) {
                    code[code.length] = 'formatString.replace(formatPattern, fnum);';
                }
                else {
                    code[code.length] = 'fnum;';
                }

                code[code.length] = '};';

                formatFn = me.formatFns[originalFormatString] =
                    Ext.functionFactory('Ext', code.join(''))(Ext);
            }

            return formatFn(v);
        },

        /**
         * Returns a number rendering function that can be reused to apply a number format multiple
         * times efficiently.
         *
         * @param {String} format Any valid number format string for {@link #number}
         * @return {Function} The number formatting function
         */
        numberRenderer: function(format) {
            return function(v) {
                return me.number(v, format);
            };
        },

        /**
         * Formats the passed number as a percentage according to the passed format string.
         * The number should be between 0 and 1 to represent 0% to 100%.
         *
         * @param {Number} value The percentage to format.
         * @param {String} [formatString="0"] See {@link #number} for details.
         * @return {String} The formatted percentage.
         */
        percent: function(value, formatString) {
            return me.number(value * 100, formatString || '0') + me.percentSign;
        },

        repeat: function(value, text, sep) {
            return Ext.String.repeat(text, value, sep);
        },

        /**
         * Formats an object of name value properties as HTML element attribute values
         * suitable for using when creating textual markup.
         * @param {Object} attributes An object containing the HTML attributes as properties
         * e.g.: `{height:40, vAlign:'top'}`
         */
        attributes: function(attributes) {
            var result, name;

            if (typeof attributes === 'object') {
                result = [];

                for (name in attributes) {
                    if (attributes.hasOwnProperty(name)) {
                        result.push(name, '="', name === 'style'
                            ? Ext.DomHelper.generateStyles(attributes[name], null, true)
                            : Ext.htmlEncode(attributes[name]), '" ');
                    }
                }

                attributes = result.join('');
            }

            return attributes || '';
        },

        /**
         * Selectively return the plural form of a word based on a numeric value.
         * 
         * For example, the following template would result in "1 Comment".  If the 
         * value of `count` was 0 or greater than 1, the result would be "x Comments".
         * 
         *     var tpl = new Ext.XTemplate('{count:plural("Comment")}');
         *     
         *     tpl.apply({
         *         count: 1
         *     }); // returns "1 Comment"
         * 
         * Examples using the static `plural` method call:
         * 
         *     Ext.util.Format.plural(2, 'Comment');
         *     // returns "2 Comments"
         * 
         *     Ext.util.Format.plural(4, 'person', 'people');
         *     // returns "4 people"
         *
         * @param {Number} value The value to compare against
         * @param {String} singular The singular form of the word
         * @param {String} [plural] The plural form of the word (defaults to the 
         * singular form with an "s" appended)
         * @return {String} output The pluralized output of the passed singular form
         */
        plural: function(value, singular, plural) {
            return value + ' ' + (value === 1 ? singular : (plural ? plural : singular + 's'));
        },

        /**
         * Converts newline characters to the HTML tag `<br/>`
         *
         * @param {String} v The string value to format.
         * @return {String} The string with embedded `<br/>` tags in place of newlines.
         */
        nl2br: function(v) {
            return Ext.isEmpty(v) ? '' : v.replace(me.nl2brRe, '<br/>');
        },

        /**
         * @method capitalize
         * @inheritdoc Ext.String#method-capitalize
         * Alias for {@link Ext.String#capitalize}.
         */
        capitalize: Ext.String.capitalize,

        /**
         * @method uncapitalize
         * @inheritdoc Ext.String#method-uncapitalize
         * Alias for {@link Ext.String#uncapitalize}.
         */
        uncapitalize: Ext.String.uncapitalize,

        /**
         * @method ellipsis
         * @inheritdoc Ext.String#method-ellipsis
         * Alias for {@link Ext.String#ellipsis}.
         */
        ellipsis: Ext.String.ellipsis,

        /**
         * @method escape
         * @inheritdoc Ext.String#method-escape
         * Alias for {@link Ext.String#escape}.
         */
        escape: Ext.String.escape,

        /**
         * @method escapeRegex
         * @inheritdoc Ext.String#method-escapeRegex
         * Alias for {@link Ext.String#escapeRegex}.
         */
        escapeRegex: Ext.String.escapeRegex,

        /**
         * @method htmlDecode
         * @inheritdoc Ext.String#method-htmlDecode
         * Alias for {@link Ext.String#htmlDecode}.
         */
        htmlDecode: Ext.String.htmlDecode,

        /**
         * @method htmlEncode
         * @inheritdoc Ext.String#method-htmlEncode
         * Alias for {@link Ext.String#htmlEncode}.
         */
        htmlEncode: Ext.String.htmlEncode,

        /**
         * @method leftPad
         * @inheritdoc Ext.String#method-leftPad
         * Alias for {@link Ext.String#leftPad}.
         */
        leftPad: Ext.String.leftPad,

        /**
         * @method toggle
         * @inheritdoc Ext.String#method-toggle
         * Alias for {@link Ext.String#toggle}.
         */
        toggle: Ext.String.toggle,

        /**
         * @method trim
         * @inheritdoc Ext.String#method-trim
         * Alias for {@link Ext.String#trim}.
         */
        trim: Ext.String.trim,

        /**
         * Parses a number or string representing margin sizes into an object.
         * Supports CSS-style margin declarations (e.g. 10, "10", "10 10", "10 10 10" and
         * "10 10 10 10" are all valid options and would return the same result).
         *
         * @param {Number/String} box The encoded margins
         * @return {Object} An object with margin sizes for top, right, bottom and left
         */
        parseBox: function(box) {
            box = box || 0;

            if (typeof box === 'number') {
                return {
                    top: box,
                    right: box,
                    bottom: box,
                    left: box
                };
            }

            // eslint-disable-next-line vars-on-top
            var parts = box.split(' '),
                ln = parts.length;

            if (ln === 1) {
                parts[1] = parts[2] = parts[3] = parts[0];
            }
            else if (ln === 2) {
                parts[2] = parts[0];
                parts[3] = parts[1];
            }
            else if (ln === 3) {
                parts[3] = parts[1];
            }

            return {
                top: parseInt(parts[0], 10) || 0,
                right: parseInt(parts[1], 10) || 0,
                bottom: parseInt(parts[2], 10) || 0,
                left: parseInt(parts[3], 10) || 0
            };
        },

        /**
         * Resolves the specified resource `url` with an optional `prefix`. This resolution
         * is based on {@link Ext#resolveResource}. The prefix is intended to be used for
         * a package or resource pool identifier.
         *
         * @param {String} url The resource url to resolve
         * @param {String} [prefix] A prefix/identifier to include in the resolution.
         * @return {String}
         */
        resource: function(url, prefix) {
            prefix = prefix || '';

            return Ext.resolveResource(prefix + url);
        },

        /**
         * Formats the given value using `encodeURI`.
         * @param {String} value The value to encode.
         * @returns {string}
         * @since 6.2.0
         */
        uri: function(value) {
            return encodeURI(value);
        },

        /**
         * Formats the given value using `encodeURIComponent`.
         * @param {String} value The value to encode.
         * @returns {string}
         * @since 6.2.0
         */
        uriCmp: function(value) {
            return encodeURIComponent(value);
        },

        wordBreakRe: /[\W\s]+/,

        /**
         * Returns the word at the given `index`. Spaces and punctuation are considered
         * as word separators by default. For example:
         *
         *      console.log(Ext.util.Format.word('Hello, my name is Bob.', 2);
         *      // == 'name'
         *
         * @param {String} value The sentence to break into words.
         * @param {Number} index The 0-based word index.
         * @param {String/RegExp} [sep="[\W\s]+"] The pattern by which to separate words.
         * @return {String} The requested word or empty string.
         */
        word: function(value, index, sep) {
            var re = sep ? (typeof sep === 'string' ? new RegExp(sep) : sep) : me.wordBreakRe,
                parts = (value || '').split(re);

            return parts[index || 0] || '';
        }
    };
});
