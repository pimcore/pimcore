/* Simple parser for Regular Expressions */
/* Thanks to Marijn for patiently addressing some questions and to 
 * Steven Levithan for XRegExp http://xregexp.com/ for pointing the way to the regexps for the regex (I
 * only discovered his own regex syntax editor RegexPal later on) */

/*
// Possible future to-dos:
0) Unicode plugin fix for astral and update for Unicode 6.0.0
1) Allow parsing of string escaped regular expressions (e.g., \\ as \ and regular \n as an actual newline) and
     could potentially integrate into parsing for other languages where known (e.g., string inside RegExp constructor)
2) If configured, allow parsing of replace strings (e.g., $1, \1)
3) Shared classes
      a) As with ranges and unicode classes, could also try to supply equivalents which list all
              characters inside a whole character class
      b) Add common classes for ranges, inside char. classes, and allow for alternation of colors
4) Groups
      a) detect max's (or even inner_group_mode) from CSS if specified as "max-available"
          _cssPropertyExists('span.regex-max-available') || _cssPropertyExists('.regex-max-available');
      b) Remove inner_group_mode and just always both uniform and type-based styles? (first improve
            inner_group_mode performance when on)
5) Expand configuration for different flavors of regex or language implementations corresponding to config
     options
6) Allow free-spacing mode to work with newlines?
*/

/**
 * OPTIONS (SETUP):
 * "possible_flags": {String} All flags to support (as a string of joined characters); default is 'imgxs';
 *                                                      if 'literal' is on, this will check for validity against the literal flags, but
 *                                                      use the literal flags
 * "flags": {String} List of flags to use on this query; will be added to any literals; default is ''
 * "literal": {Boolean} Whether inside a literal or not
 * "literal_initial": {String} The initial character to surround regular expression (optionally followed by flags);
 *                                                  A forward slash ("/"), by default
 *
 * OPTIONS (STYLING)
 * "inner_group_mode": {'type'|'uniform'} Indicates how (if at all) to style content inside groups; if by "type",
 *                                                                              the class will be assigned by type; if "uniform", a class named
 *                                                                              "regex-group-<lev>-<seq>" where <lev> is the nesting level and
 *                                                                              <seq> is the sequence number; the "uniform" option allows grouped
 *                                                                              content to be styled consistently (but potentially differently from
 *                                                                              the parentheses themselves) no matter the type of group
 * "max_levels": {Number|String} Maximum number of nesting levels before class numbers repeat
 * "max_alternating": {Number|String} Maximum number of alternating sequences at the same level before
 *                                                                          class numbers repeat
 *
 * OPTIONS (PATTERNS)
 * "flavor": {'all'|'ecma-262-ed5'|'ecma-262-ed3'} Sets defaults for the following patterns according to the regex flavor
 *
 * "unicode_mode": {'simple'|'validate'|'store'} Mode for handling Unicode classes; unless 'simple' is chosen, may
 *                                                                                      affect performance given initial need to load and subsequent need
 *                                                                                      to parse and validate (i.e., selectively color), and, if 'store' is chosen,
 *                                                                                      also makes these (many) additional values on the token object, e.g.,
 *                                                                                      for use by activeTokens for adding tooltips; Default is 'simple' which
 *                                                                                      simply checks that the values are plausible;
 *                                                                                      Note: To include Unicode validation, you must include your
 *                                                                                                      CodeMirror parserfile in this sequence:
 *                                                                                          parserfile: ["parseregex.js", "parseregex-unicode.js"]
 * "unicode_classes": {Boolean} Whether to accept all Unicode classes (unless overridden); default is true
 * "unicode_blocks": {Boolean} Whether to accept Unicode blocks (overrides unicode_classes default); e.g., \p{InArabic}
 * "unicode_scripts": {Boolean} Whether to accept Unicode scripts (overrides unicode_classes default); e.g., \p{Hebrew}
 * "unicode_categories": {Boolean} Whether to accept Unicode categories (overrides unicode_classes default); e.g., \p{Ll} (for lower-case letters)
 * "named_backreferences": {Boolean} Whether to accept named backreferences; default is true
 * "empty_char_class": {Boolean} Whether to allow [] or [^] as character classes; default is true
 * "mode_modifier": {Boolean} Whether to allow (?imsx) mode modifiers
 *
 * NOTE
 * You can add the following to your CodeMirror configuration object in order to get simple tooltips showing the
 *     character equivalent of an escape sequence:
     activeTokens : function (spanNode, tokenObject, editor) {
        if (tokenObject.equivalent) {
            spanNode.title = tokenObject.equivalent;
        }
    },
....or this more advanced one which adds ranges (though you may wish to change the character limit, being
    aware that browsers have a limitation on tooltip size, unless you were to also use a tooltip library which used
    this information to expand the visible content):
    activeTokens : (function () {
        var charLimit = 500;
        var lastEquivalent, rangeBegan, lastRangeHyphen;

        function _buildTitle (beginChar, endChar) {
            var beginCode = beginChar.charCodeAt(0), endCode = endChar.charCodeAt(0);
            var title = '';
            if (endCode - beginCode <= charLimit) {
                for (var i = beginCode; i <= endCode; i++) {
                    title += String.fromCharCode(i);
                }
            }
            return title;
        }

        return function (spanNode, token, editor) {
            var content = token.content;
            if (lastEquivalent && token.style === 'regex-class-range-hyphen') {
                rangeBegan = true;
                lastRangeHyphen = spanNode;
            }
            else if (rangeBegan) {
                var beginChar = lastEquivalent;
                var endChar = (token.equivalent || content);
                lastRangeHyphen.title = _buildTitle(beginChar, endChar);
                rangeBegan = false;
                lastEquivalent = null;
                //editor.reparseBuffer(); // title must be redrawn since already added previously (do below instead as locking up on undo?); too intensive to call this, but keeping here for record
            }
            else if (content === ']') {
                rangeBegan = false;
            }
            else {
                rangeBegan = false;
                // Fix: 'regex-unicode-class-inside' not supported and should not be since it wouldn't make sense as a starting range?
                lastEquivalent = token.equivalent || content;
            }

            if (token.display) {
                spanNode.title = token.display;
            }
            else if (token.equivalent) {
                if (token.unicode) {
                    var range = /(.)-(.)/g;
                    spanNode.title = token.equivalent.replace(range,
                        function (n0, n1, n2) {
                            return _buildTitle(n1, n2);
                        }
                    );
                    // editor.reparseBuffer(); // too intensive to call this, but keeping here for record
                }
                else {
                    spanNode.title = token.equivalent;
                    // editor.reparseBuffer(); // too intensive to call this, but keeping here for record
                }
            }
        };
    }()),
 */


var RegexParser = Editor.Parser = (function() {
    
    var regexConfigBooleanOptions = ['unicode_blocks', 'unicode_scripts', 'unicode_categories',
                                                        'unicode_classes', 'named_backreferences', 'empty_char_class', 'mode_modifier'],
        regexConfigStringOptions = ['unicode_mode'],  // Just for record-keeping
        possibleFlags = 'imgxs',
        config = {}, ucl;
    // Resettable
    var initialFound, endFound, charClassRangeBegun, negatedCharClass, mode_modifier_begun, flags = '',
        groupTypes, groupCounts;
        
    config.literal_initial = '/';
    
    // Adapted from tokenize.js (not distinctly treating whitespace except for newlines)
    function noWSTokenizer (source, state) {
      var tokenizer = {
        state: state,
        take: function(type) {
          if (typeof(type) == "string")
            type = {style: type, type: type};
          type.content = (type.content || "") + source.get();
          type.value = type.content + source.get();
          return type;
        },
        next: function () {
          if (!source.more()) throw StopIteration;
          var type;
          if (source.equals("\n")) {
            source.next();
            return this.take("whitespace");
          }
          while (!type)
            type = this.state(source, function(s) {tokenizer.state = s;});
          return this.take(type);
        }
      };
      return tokenizer;
    }
    
    // Private static utilities
    function _expandRange (type, name) { // More efficient than unpack in targeting only which we need (though ideally would not need to convert at all)
        var codePt = /\w{4}/g,
            unicode = RegexParser.unicode, group = unicode[type];
        if (group.hasOwnProperty(name)) {
            // group[name] = group[name].replace(codePt, '\\u$&'); // We shouldn't need this unless we start validating against a matching string
            return group[name].replace(codePt,
                function (n0) {
                    if (n0 === '002D') {
                        return 'U+' + n0; // Leave genuine hyphens unresolved so they won't get confused with range hyphens
                    }
                    return String.fromCharCode(parseInt('0x' + n0, 16)); // Fix: Would be more efficient to store as Unicode characters like this from the start
                });
        }
        return false;
    }

    function _copyObj (obj, deep) {
        var ret = {};
        for (var p in obj) {
            if (obj.hasOwnProperty(p)) {
                if (deep && typeof obj[p] === 'object' && obj[p] !== null) {
                    ret[p] = _copyObj(obj[p], deep);
                }
                else {
                    ret[p] = obj[p];
                }
            }
        }
        return ret;
    }

    function _forEach (arr, h) {
        for (var i = 0, arrl = arr.length; i < arrl; i++) {
            h(arr[i], i);
        }
    }
    function _setOptions (arr, value) {
        arr = typeof arr === 'string' ? [arr] : arr;
        _forEach(arr, function (item) {
            config[item] = value;
        });
    }

    function _cssPropertyExists (selectorText) {
        var i = 0, j = 0, dsl = 0, crl = 0, ss, d = document,
            _getPropertyFromStyleSheet =
                function (ss, selectorText) {
                    var rules = ss.cssRules ? ss.cssRules : ss.rules; /* Mozilla or IE */
                    for (j = 0, crl = rules.length; j < crl; j++) {
                        var rule = rules[j];
                        try {
                            if (rule.type === CSSRule.STYLE_RULE && rule.selectorText === selectorText) {
                                return true;
                            }
                        }
                        catch (err) { /* IE */
                            if (rule.selectorText === selectorText) {
                                return true;
                            }
                        }
                    }
                    return false;
                };

        var value;
        for (i = 0, dsl = d.styleSheets.length; i < dsl; i++) {
            ss = d.styleSheets[i];
            value = _getPropertyFromStyleSheet(ss, selectorText);
            if (value) {
                break;
            }
        }
        return value;
    }

    function _addFlags (f) {
        if ((/[^a-z]/).test(f)) { // Could insist on particular flags
            throw 'Invalid flag supplied to the regular expression parser';
        }
        flags += f;
    }
    function _setPossibleFlags (f) {
        if ((/[^a-z]/).test(f)) { // Could insist on particular flags
            throw 'Invalid flag supplied to the regular expression parser';
        }
        possibleFlags = f;
    }
    function _esc (s) {
        return s.replace(/"[.\\+*?\[\^\]$(){}=!<>|:\-]/g, '\\$&');
    }

    var tokenizeRegex = (function() {
        // Private utilities
        function _hasFlag (f) {
            return flags.indexOf(f) > -1;
        }
        function _lookAheadMatches (source, regex) {
            var matches = source.lookAheadRegex(regex, true);
            if (matches && matches.length > 1) { // Allow us to return the position of a match out of alternates
                for (var i = matches.length - 1; i >= 0; i--) {
                    if (matches[i] != null) {
                        return i;
                    }
                }
            }
            return 0;
        }

        function validateUnicodeClass (negated, place, source) {
            var neg = ' regex-' + negated + 'unicode', ret = 'regex-unicode-class-' + place + neg,
                name, unicode = RegexParser.unicode;
            if (!unicode) {
                throw 'Unicode plugin of the regular expression parser not properly loaded';
            }
            if (config.unicode_categories) {
                var categories = source.lookAheadRegex(/^\\[pP]{\^?([A-Z][a-z]?)}/, true);
                if (categories) {
                    name = categories[1];
                    if (unicode.categories[name]) {
                        return ret + '-category-' + place + neg + '-category-' + name + '-' + place;
                    }
                    return 'regex-bad-sequence';
                }
            }
            if (config.unicode_blocks) {
                var blocks = source.lookAheadRegex(/^\\[pP]{\^?(In[A-Z][^}]*)}/, true);
                if (blocks) {
                    name = blocks[1];
                    if (unicode.blocks[name]) {
                        return ret + '-block-' + place + neg + '-block-' + name + '-' + place;
                    }
                    return 'regex-bad-sequence';
                }
            }
            if (config.unicode_scripts) {
                var scripts = source.lookAheadRegex(/^\\[pP]{\^?([^}]*)}/, true);
                if (scripts) {
                    name = scripts[1];
                    if (unicode.scripts[name]) {
                        return ret + '-script-' + place + neg + '-script-' + name + '-' + place;
                    }
                    return 'regex-bad-sequence';
                }
            }
            return false;
        }

        function unicode_class (source, place) {
            var ret = 'regex-unicode-class-' + place + ' ', negated = '';
            if (source.lookAheadRegex(/^\\P/) || source.lookAheadRegex(/^\\p{\^/)) {
                negated = 'negated';
            }
            else if (source.lookAheadRegex(/^\\P{\^/)) { // Double-negative
                return false;
            }
            switch (config.unicode_mode) {
                case 'validate': case 'store':
                    return validateUnicodeClass(negated, place, source);
                case 'simple': // Fall-through
                default:
                    // generic: /^\\[pP]{\^?[^}]*}/
                    if (config.unicode_categories && source.lookAheadRegex(/^\\[pP]{\^?[A-Z][a-z]?}/, true)) {
                        return ret + 'regex-' + negated + 'unicode-category';
                    }
                    if (config.unicode_blocks && source.lookAheadRegex(/^\\[pP]{\^?In[A-Z][^}]*}/, true)) {
                        return ret + 'regex-' + negated + 'unicode-block';
                    }
                    if (config.unicode_scripts && source.lookAheadRegex(/^\\[pP]{\^?[^}]*}/, true)) {
                        return ret + 'regex-' + negated + 'unicode-script';
                    }
                    break;
            }
            return false;
        }

        // State functions
        // Changed [\s\S] to [^\n] to avoid accidentally grabbing a terminating (auto-inserted place-holder?) newline
        var inside_class_meta = /^\\(?:([0-3][0-7]{0,2}|[4-7][0-7]?)|(x[\dA-Fa-f]{2})|(u[\dA-Fa-f]{4})|(c[A-Za-z])|(-\\]^)|([bBdDfnrsStvwW0])|([^\n]))/;
        function inside_class (source, setState) {
            var ret;
            if (source.lookAhead(']', true)) {
                // charClassRangeBegun = false; // Shouldn't be needed
                setState(customOutsideClass);
                return 'regex-class-end';
            }
            if (negatedCharClass && source.lookAhead('^', true)) {
                negatedCharClass = false;
                return 'regex-class-negator';
            }
            if (source.lookAhead('-', true)) {
                if (!charClassRangeBegun) {
                    ret = 'regex-class-initial-hyphen';
                }
                else if (source.equals(']')) {
                    ret = 'regex-class-final-hyphen';
                }
                else {
                    return 'regex-class-range-hyphen';
                }
            }
            else if (!source.equals('\\')) {
                var ch = source.next();
                if (config.literal && ch === config.literal_initial) {
                    return 'regex-bad-character';
                }
                ret = 'regex-class-character';
            }
            else if ((ucl = unicode_class(source, 'inside'))) {
                ret = ucl;
            }
            else if (source.lookAheadRegex(/^\\(\n|$)/)) { // Treat an ending backslash like a bad 
                                                                                              //    character to avoid auto-adding of extra text
                source.next();
                ret = 'regex-bad-character';
            }
            else {
                switch (_lookAheadMatches(source, inside_class_meta)) {
                    case 1:
                        ret = 'regex-class-octal';
                        break;
                    case 2:
                        ret = 'regex-class-hex';
                        break;
                    case 3:
                        ret = 'regex-class-unicode-escape';
                        break;
                    case 4:
                        ret = 'regex-class-ascii-control';
                        break;
                    case 5:
                        ret = 'regex-class-escaped-special';
                        break;
                    case 6:
                        ret = 'regex-class-special-escape';
                        break;
                    case 7:
                        ret = 'regex-class-extra-escaped';
                        break;
                    default:
                        throw 'Unexpected character inside class, beginning ' + 
                                        source.lookAheadRegex(/^[\s\S]+$/)[0] + ' and of length '+
                                        source.lookAheadRegex(/^[\s\S]+$/)[0].length; // Shouldn't reach here
                }
            }
            // Fix: Add this as a token property in the parser instead?
            if (charClassRangeBegun) {
                charClassRangeBegun = false;
                ret += '-end-range';
            }
            else if (source.equals('-')) {
                charClassRangeBegun = true;
                ret += '-begin-range';
            }
            return ret;
        }

        // Changed [\s\S] to [^\n] to avoid accidentally grabbing a terminating (auto-inserted place-holder?) newline
        var outside_class_meta = /^(?:\\(?:(0(?:[0-3][0-7]{0,2}|[4-7][0-7]?)?)|([1-9]\d*)|(x[\dA-Fa-f]{2})|(u[\dA-Fa-f]{4})|(c[A-Za-z])|([bBdDfnrsStvwW0])|([?*+])|([.\\[$|{])|([^\n]))|([?*+]\??)|({\d+(?:,\d*)?}\??))/;
        function outside_class (source, setState) {
            if ((ucl = unicode_class(source, 'outside'))) {
                return ucl;
            }
            switch (_lookAheadMatches(source, outside_class_meta)) {
                case 1:
                    return 'regex-octal';
                case 2:
                    return 'regex-ascii';
                case 3:
                    return 'regex-hex';
                case 4:
                    return 'regex-unicode-escape';
                case 5:
                    return 'regex-ascii-control';
                case 6:
                    return 'regex-special-escape';
                case 7:
                    return 'regex-quantifier-escape'; // Fix: could probably just merge with escaped-special
                case 8:
                    return 'regex-escaped-special';
                case 9:
                    return 'regex-extra-escaped';
                case 10:
                    return 'regex-quantifiers';
                case 11:
                    return 'regex-repetition';
                default:
                    if (config.literal && source.lookAhead(config.literal_initial, true)) {
                        if (!initialFound) {
                            initialFound = true;
                            return 'regex-literal-begin';
                        }
                        endFound = true;
                        setState(beginFlags);
                        return 'regex-literal-end';
                    }
                    if (source.lookAhead('|', true)) {
                        return 'regex-alternator';
                    }
                    if (source.lookAheadRegex(/^\\$/, true)) {
                        return 'regex-bad-character';
                    }
                    source.next();
                    return 'regex-character';
            }
        }
        function beginFlags (source, setState) {
            var endFlags = source.lookAheadRegex(new RegExp('^[' + possibleFlags + ']*', ''), true);
            if (endFlags == null) {
                // Unrecognized flag used in regular expression literal
                return 'regex-bad-character';
            }
            // Already confirmed validity earlier
            setState(finished);
            return 'regex-flags';
        }
        function finished () {
            throw StopIteration;
        }

        function customOutsideClass (source, setState) {
            if (config.named_backreferences && source.lookAheadRegex(/^\\k<([\w$]+)>/, true)) {
                return 'regex-named-backreference';
            }
            if (_hasFlag('x') && source.lookAheadRegex(/^(?:#.*)+?/, true)) { // Fix: lookAheadRegex will avoid new lines; added extra '?' at end
                // Regex should be /^(?:\s+|#.*)+?/  but this was problematic
                return 'regex-free-spacing-mode';
            }

            if (source.lookAhead('[', true)) {
                if (source.lookAheadRegex(/^\^?]/, true)) {
                    return config.empty_char_class ? 'regex-empty-class' : 'regex-bad-character';
                }
                if (source.equals('^')) {
                    negatedCharClass = true;
                }
                setState(inside_class);
                return 'regex-class-begin';
            }

            // Unmatched ending parentheses
            if (source.lookAhead(')', true)) {
                return 'regex-ending-group';
            }
            if (source.lookAhead('(', true)) {
                if (config.mode_modifier && mode_modifier_begun) {
                    var mode_modifier = source.lookAheadRegex(/^\?([imsx]+)\)/, true);
                    if (mode_modifier) { // We know it should exist if we're here
                        // addFlags(mode_modifier[1]); // Handle flags earlier
                        mode_modifier_begun = false;
                        return 'regex-mode-modifier';
                    }
                }
                if (source.lookAheadRegex(/^\?#[^)]*\)/, true)) { // No apparent nesting of comments?
                    return 'regex-comment-pattern';
                }

                var ret;
                if (source.lookAheadRegex(/^(?!\?)/, true)) {
                    ret = 'regex-capturing-group';
                }
                if (source.lookAheadRegex(/^\?<([$\w]+)>/, true)) {
                    ret = 'regex-named-capturing-group';
                }
                if (source.lookAheadRegex(/^\?[:=!]/, true)) {
                    ret = 'regex-grouping';
                }
                if (!ret) {
                    return 'regex-bad-character'; // 'Uncaught parenthetical in tokenizing regular expression';
                }
                return ret;
            }
            return outside_class(source, setState);
        }

        return function(source, startState) {
            return noWSTokenizer(source, startState || customOutsideClass);
        };
    })();


    function resetStateVariables () {
        initialFound = false, endFound = false, charClassRangeBegun = false, negatedCharClass = false,
        mode_modifier_begun = false;
        flags = '';
        if (config.flags) { // Reset to configuration value
            _addFlags(config.flags);
        }
        groupTypes = [],
            groupCounts = {
                'capturing-group': {currentCount: 0},
                'named-capturing-group': {currentCount: 0},
                'grouping': {currentCount: 0}
            };
    }

    // Parser
    function parseRegex (source) {
        resetStateVariables();

        var tokens = tokenizeRegex(source);

        if (config.literal && !source.equals(config.literal_initial)) {
            throw 'Regular expression literals must include a beginning "'+config.literal_initial+'"';
        }

        if (config.literal) {
            var regex = new RegExp('^[\\s\\S]*' + _esc(config.literal_initial) + '([' + possibleFlags + ']*)$', '');
            var endFlags = source.lookAheadRegex(regex);
            if (endFlags == null) {
                 // Unrecognized flag used in regular expression literal
            }
            else {
                _addFlags(endFlags[1]);
            }
        }
        else if (config.mode_modifier) { // Fix: We are not allowing both a mode modifier and
                                                                      // literal syntax (presumably redundant)
            var mode_modifier = source.lookAheadRegex(/^\(\?([imsx]+)\)/, true);
            if (mode_modifier) {
                mode_modifier_begun = true;
                _addFlags(mode_modifier[1]);
            }
        }
        var iter = {
            next: function() {
                try {
                    var level_num,
                        token = tokens.next(),
                        style = token.style,
                        content = token.content,
                        lastChildren, currentChildren, currentCount, currentGroupStyle,
                        type = style.replace(/^regex-/, '');

                    switch (type) {
                        case 'ending-group':
                            if (!groupTypes.length) {
                                // Closing parenthesis without an opening one
                                token.style = 'regex-bad-character';
                            }
                            else {
                                level_num = config.max_levels ?
                                    ((groupTypes.length % config.max_levels) || config.max_levels) : groupTypes.length;
                                var popped = groupTypes.pop();
                                // Allow numbered classes
                                currentChildren = groupCounts[popped];
                                while (currentChildren && currentChildren.currentChildren &&
                                                currentChildren.currentChildren.currentChildren) { // Find lowest level parent
                                    currentChildren = currentChildren.currentChildren;
                                }
                                delete currentChildren.currentChildren; // Use parent to delete children
                                currentCount = currentChildren.currentCount; // Use parent as new child to get current count
                                currentCount = config.max_alternating ?
                                    ((currentCount % config.max_alternating) || config.max_alternating) : currentCount;

                                currentGroupStyle = level_num + '-' + currentCount;
                                token.style = 'regex-ending-' + popped + ' regex-ending-' + popped + currentGroupStyle;
                                if (config.inner_group_mode === 'uniform') { // 'type' is automatically processed for ending
                                    token.style += ' regex-group-' + currentGroupStyle;
                                }
                            }
                            break;
                        case 'capturing-group':
                        case 'named-capturing-group':
                        case 'grouping':
                            lastChildren = groupCounts[type],
                                    currentChildren = groupCounts[type].currentChildren;
                            while (currentChildren) {
                                lastChildren = currentChildren;
                                currentChildren = currentChildren.currentChildren;
                            }
                            currentCount = ++lastChildren.currentCount;
                            if (!lastChildren.currentChildren) {
                                lastChildren.currentChildren = {currentCount: 0};
                            }

                            groupTypes.push(type);
                            level_num = config.max_levels ?
                                ((groupTypes.length % config.max_levels) || config.max_levels) : groupTypes.length;
                            // Allow numbered classes
                            currentCount = config.max_alternating ?
                                ((currentCount % config.max_alternating) || config.max_alternating) : currentCount;
                            currentGroupStyle = level_num + '-' + currentCount;
                            var currentStyle = ' ' + token.style;


                            if (config.inner_group_mode) {
                                token.style += config.inner_group_mode === 'type' ?
                                                            currentStyle + currentGroupStyle :
                                                            ' regex-group-' + currentGroupStyle;
                                token.style += ' ' + style + currentGroupStyle;
                            }
                            else {
                                token.style += currentStyle + currentGroupStyle;
                            }
                            lastChildren.currentGroupStyle = currentGroupStyle;
                            lastChildren.currentStyle = currentStyle;
                            break;
                        // Allow ability to extract information on character equivalence, e.g., for use on tooltips
                        case 'class-octal': case 'octal': // Fall-through
                        case 'class-octal-begin-range': case 'class-octal-end-range': // Fall-through
                        case 'class-ascii-begin-range': case 'class-ascii-end-range': // Fall-through
                        case 'class-ascii': case 'ascii': // Firefox apparently treats ascii here as octals
                            token.equivalent = String.fromCharCode(parseInt(content.replace(/^\\/, ''), 8));
                            break;
                        case 'class-hex': case 'hex': // Fall-through
                        case 'class-hex-begin-range': case 'class-hex-end-range': // Fall-through
                        case 'class-unicode-escape': case 'class-unicode-escape-begin-range':  // Fall-through
                        case 'class-unicode-escape-end-range': case 'unicode-escape':
                            token.equivalent = String.fromCharCode(parseInt('0x'+content.replace(/^\\(x|u)/, ''), 16));
                            break;
                        case 'class-ascii-control-begin-range': case 'class-ascii-control-end-range': // Fall-through
                        case 'class-ascii-control': case 'ascii-control':
                            token.equivalent = String.fromCharCode(content.replace(/^\\c/, '').charCodeAt(0) - 64);
                            break;
                        case 'class-special-escape': case 'class-special-escape-begin-range': // Fall-through
                        case 'class-special-escape-end-range': case 'special-escape':
                            // Others to ignore (though some (\d, \s, \w) could have theirs listed): bBdDsSwW
                            var chr = content.replace(/^\\/, ''),
                                pos = 'fnrtv'.indexOf(chr),
                                specialEquivs = '\f\n\r\t\v';
                            if (pos !== -1) { // May not be visible without conversion to codepoints
                                var c = specialEquivs.charAt(pos);
                                var hex = c.charCodeAt(0).toString(16).toUpperCase();
                                token.display = 'U+' + Array(5 - hex.length).join('0') + hex;
                                token.equivalent = c;
                            }
                            break;
                        case 'regex-class-escaped-special': case 'regex-class-escaped-special-begin-range':
                        case 'regex-class-escaped-special-end-range':
                        case 'class-extra-escaped-begin-range': case 'class-extra-escaped-end-range':
                        case 'class-extra-escaped': case 'extra-escaped':
                            token.equivalent = content.replace(/^\\/, '');
                            break;
                        default:
                            if (config.unicode_mode === 'store') {
                                if (config.unicode_categories) {
                                    var cat = type.match(/regex-unicode-category-(\w+?)-(?:outside|inside)/);
                                    if (cat) {
                                        token.equivalent = _expandRange('categories', cat[1]) || '';
                                        token.unicode = true;
                                        break;
                                    }
                                }
                                if (config.unicode_blocks) {
                                    var block = type.match(/regex-unicode-block-(\w+)-(?:outside|inside)/);
                                    if (block) {
                                        token.equivalent = _expandRange('blocks', block[1]) || '';
                                        token.unicode = true;
                                        break;
                                    }
                                }
                                if (config.unicode_scripts) {
                                    var script = type.match(/regex-unicode-script-(\w+)-(?:outside|inside)/);
                                    if (script) {
                                        token.equivalent = _expandRange('scripts', script[1]) || '';
                                        token.unicode = true;
                                        break;
                                    }
                                }
                            }
                            break;
                    }
                    if (config.inner_group_mode && type !== 'ending-group' && type !== 'capturing-group' && type !== 'named-capturing-group' &&
                            type !== 'grouping') {
                        level_num = config.max_levels ?
                            ((groupTypes.length % config.max_levels) || config.max_levels) : groupTypes.length;
                        // Allow numbered classes
                        var last = groupTypes[groupTypes.length - 1];
                        if (last) {
                            currentChildren = groupCounts[last];
                            while (currentChildren && currentChildren.currentChildren &&
                                            currentChildren.currentChildren.currentChildren) { // Find lowest level parent
                                currentChildren = currentChildren.currentChildren;
                            }
                            token.style += config.inner_group_mode === 'type' ? 
                                                            currentChildren.currentStyle + currentChildren.currentGroupStyle :
                                                            ' regex-group-' + currentChildren.currentGroupStyle;
                        }
                    }
                    if (!source.more()) {
                        if (groupTypes.length) { // Opening group without a closing parenthesis
                            token.style = 'regex-bad-character';
                        }
                        else if (config.literal && !endFound) {
                            //throw 'Regular expression literals must include a (non-escaped) ending "' +
                            //                config.literal_initial + '" (with optional flags).';
                            token.style = 'regex-bad-character';
                        }
                    }
                }
                catch (e) {
if (e != StopIteration) {
    alert(e + '::'+e.lineNumber);
}
                    throw e;
                }
                return token;
            },
            copy: function() {
                var _initialFound = initialFound, _charClassRangeBegun = charClassRangeBegun,
                    _negatedCharClass = negatedCharClass, _flags = flags,
                    _endFound = endFound, _groupTypes = groupTypes,
                    _mode_modifier_begun = mode_modifier_begun,
                    _tokenState = tokens.state,
                    _groupCounts = _copyObj(groupCounts, true);
                return function(source) {
                    initialFound = _initialFound;
                    charClassRangeBegun = _charClassRangeBegun;
                    negatedCharClass = _negatedCharClass;
                    flags = _flags;
                    endFound = _endFound;
                    groupTypes = _groupTypes;
                    mode_modifier_begun = _mode_modifier_begun;
                    tokens = tokenizeRegex(source, _tokenState);
                    groupCounts = _groupCounts;
                    return iter;
                };
            }
        };
        return iter;
    }

    // Parser object
    return {
        make: parseRegex,
        configure: function (parserConfig) {
            var unicode = this.unicode;

            // Overridable
            _setOptions('unicode_mode', 'simple');
            _setOptions(regexConfigBooleanOptions, false);
            if (parserConfig.unicode_classes) {
                _setOptions(['unicode_blocks', 'unicode_scripts', 'unicode_categories'], true);
            }
            switch (parserConfig.flavor) {
                case 'ecma-262-ed5':
                    _setOptions(['empty_char_class'], true);
                    // Fall-through
                case 'ecma-262-ed3':
                    config.possible_flags = 'gim'; // If wish for Firefox 'y', add it on parserConfig
                    break;
                case 'all':
                default:
                    _setOptions(regexConfigBooleanOptions, true);
                    break;
            }

            // Setting with possible overrides
            for (var opt in parserConfig) {
                if ((/^regex_/).test(opt)) { // Use for compatibility with JS+Regex
                    config[opt.replace(/^regex_/, '')] = parserConfig[opt];
                    continue;
                }
                config[opt] = parserConfig[opt];
            }

            // Post-processing
            if (config.possible_flags) {
                _setPossibleFlags(config.possible_flags);
            }

            if (config.unicode_mode !== 'simple') {
                if (!unicode) {
                    throw 'You must include the parseregex-unicode.js file in order to use validate or storage mode Unicode';
                }
            }
        }
    };
})();
