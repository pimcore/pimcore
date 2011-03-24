// See the dependencies parsejavascript.js and parseregex.js (optionally with parseregex-unicode.js) for options

var JSAndRegexParser = Editor.Parser = (function() {
    // Parser
    var run = function () {},
        regexParser, jsParser, lastRegexSrc,
        regexPG = {}, jsPG = {};

    function simpleStream (s) {
        var str = s, pos = 0;
        return {
            next : function () {
                if (pos >= str.length) {
                    throw StopIteration;
                }
                return str.charAt(pos++);
            }
        };
    }

    function regex () {
        var token;
        try {
            token = regexParser.next();
        }
        catch(e) {
            _setState(js);
            return js();
        }
        _setState(regex);
        return token;
    }
    function js () {
        var token = jsParser.next();
        if (token.type === 'regexp') {
            lastRegexSrc = stringStream(simpleStream(token.content));
            regexParser = RegexParser.make(lastRegexSrc);
            return regex();
        }
        return token;
    }

    function _setState (func) {
        run = func;
    }

    function parseJSAndRegex (stream, basecolumn) {
        JSParser.configure(jsPG);
        RegexParser.configure(regexPG);
        jsParser = JSParser.make(stream, basecolumn);
        _setState(js);

        var iter = {
            next: function() {
                return run(stream);
            },
            copy: function() {
                var _run = run, _lastRegexSrc = lastRegexSrc, 
                    _jsParser = jsParser.copy(),
                    _regexParser = regexParser && regexParser.copy();

                return function (_stream) {
                    stream = _stream;
                    jsParser = _jsParser(_stream);
                    regexParser = _regexParser && _regexParser(_lastRegexSrc);
                    run = _run;
                    return iter;
                };
            }
        };
        return iter;
    }

    // Parser object
    return {
        make: parseJSAndRegex,
        configure: function (parserConfig) {
            for (var opt in parserConfig) {
                if ((/^regex_/).test(opt)) {
                    regexPG[opt.replace(/^regex_/, '')] = parserConfig[opt];
                }
                else { // Doesn't need a js- prefix, but we'll strip if it does
                    jsPG[opt.replace(/^js_/, '')] = parserConfig[opt];
                }
            }
            regexPG.flavor = regexPG.flavor || 'ecma-262-ed3'; // Allow ed5, etc. if specified
            regexPG.literal = true; // This is only for literals, since can't easily detect whether will be used for RegExp
            regexPG.literal_initial = '/'; // Ensure it's always this for JavaScript regex literals
        }
    };
})();
