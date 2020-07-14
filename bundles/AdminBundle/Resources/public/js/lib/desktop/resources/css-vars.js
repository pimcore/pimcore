"use strict";

var _get = function get(object, property, receiver) { if (object === null) object = Function.prototype; var desc = Object.getOwnPropertyDescriptor(object, property); if (desc === undefined) { var parent = Object.getPrototypeOf(object); if (parent === null) { return undefined; } else { return get(parent, property, receiver); } } else if ("value" in desc) { return desc.value; } else { var getter = desc.get; if (getter === undefined) { return undefined; } return getter.call(receiver); } };

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; };

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

(function (f) {
    if ((typeof exports === "undefined" ? "undefined" : _typeof(exports)) === "object" && typeof module !== "undefined") {
        module.exports = f();
    } else if (typeof define === "function" && define.amd) {
        define([], f);
    } else {
        var g;if (typeof window !== "undefined") {
            g = window;
        } else if (typeof global !== "undefined") {
            g = global;
        } else if (typeof self !== "undefined") {
            g = self;
        } else {
            g = this;
        }g.Fashion = f();
    }
})(function () {
    var define, module, exports;return function () {
        function r(e, n, t) {
            function o(i, f) {
                if (!n[i]) {
                    if (!e[i]) {
                        var c = "function" == typeof require && require;if (!f && c) return c(i, !0);if (u) return u(i, !0);var a = new Error("Cannot find module '" + i + "'");throw a.code = "MODULE_NOT_FOUND", a;
                    }var p = n[i] = { exports: {} };e[i][0].call(p.exports, function (r) {
                        var n = e[i][1][r];return o(n || r);
                    }, p, p.exports, r, e, n, t);
                }return n[i].exports;
            }for (var u = "function" == typeof require && require, i = 0; i < t.length; i++) {
                o(t[i]);
            }return o;
        }return r;
    }()({ 1: [function (require, module, exports) {
            var _Fashion$apply;

            var Fashion = require('./src/export/Base.js');
            var CssVariableManager = require('./src/export/css/CssVariableManager.js'),
                css = new CssVariableManager();

            Fashion.apply(Fashion, (_Fashion$apply = {
                css: css,
                CssExport: CssVariableManager,
                Types: require('./src/export/type/Types.js'),
                ValueParser: require('./src/export/parse/ValueParser.js'),
                Type: require('./src/export/type/Type.js'),
                Bool: require('./src/export/type/Bool.js'),
                Literal: require('./src/export/type/Literal.js'),
                ParentheticalExpression: require('./src/export/type/ParentheticalExpression.js'),
                Text: require('./src/export/type/Text.js'),
                Numeric: require('./src/export/type/Numeric.js'),
                List: require('./src/export/type/List.js'),
                Map: require('./src/export/type/Map.js'),
                Color: require('./src/export/type/Color.js'),
                ColorRGBA: require('./src/export/type/ColorRGBA.js'),
                ColorHSLA: require('./src/export/type/ColorHSLA.js'),
                ColorStop: require('./src/export/type/ColorStop.js'),
                FunctionCall: require('./src/export/type/FunctionCall.js'),
                LinearGradient: require('./src/export/type/LinearGradient.js'),
                RadialGradient: require('./src/export/type/RadialGradient.js'),
                Statics: require('./src/export/type/Statics.js'),
                SourceBuilder: require('./src/export/type/SourceBuilder.js')
            }, _defineProperty(_Fashion$apply, "Types", require('./src/export/type/Types.js')), _defineProperty(_Fashion$apply, "TypeVisitor", require('./src/export/type/TypeVisitor.js')), _defineProperty(_Fashion$apply, "Output", require('./src/export/Output.js')), _defineProperty(_Fashion$apply, "Runtime", require('./src/export/Runtime.js')), _Fashion$apply));

            module.exports = Fashion;
        }, { "./src/export/Base.js": 3, "./src/export/Output.js": 4, "./src/export/Runtime.js": 5, "./src/export/css/CssVariableManager.js": 6, "./src/export/parse/ValueParser.js": 7, "./src/export/type/Bool.js": 8, "./src/export/type/Color.js": 9, "./src/export/type/ColorHSLA.js": 10, "./src/export/type/ColorRGBA.js": 11, "./src/export/type/ColorStop.js": 12, "./src/export/type/FunctionCall.js": 13, "./src/export/type/LinearGradient.js": 14, "./src/export/type/List.js": 15, "./src/export/type/Literal.js": 16, "./src/export/type/Map.js": 17, "./src/export/type/Numeric.js": 18, "./src/export/type/ParentheticalExpression.js": 19, "./src/export/type/RadialGradient.js": 20, "./src/export/type/SourceBuilder.js": 21, "./src/export/type/Statics.js": 22, "./src/export/type/Text.js": 23, "./src/export/type/Type.js": 24, "./src/export/type/TypeVisitor.js": 25, "./src/export/type/Types.js": 26 }], 2: [function (require, module, exports) {
            "use strict";

            function getJsName(name) {
                return name.replace(/\-/g, '_').replace(/\//g, '_fs_').replace(/\\/g, '_bs_');
            }

            var NameConverter = function () {
                function NameConverter() {
                    _classCallCheck(this, NameConverter);

                    this.variableNameMap = {};
                }

                _createClass(NameConverter, [{
                    key: "convertName",
                    value: function convertName(name) {
                        var map = this.variableNameMap,
                            converted = map[name];

                        if (converted === undefined) {
                            converted = map[name] = getJsName(name);
                        }
                        return converted;
                    }
                }]);

                return NameConverter;
            }();

            var converter = new NameConverter();

            module.exports = {
                NameConverter: NameConverter,
                getJsName: function getJsName(name) {
                    return converter.convertName(name);
                }
            };
        }, {}], 3: [function (require, module, exports) {
            /*
             * Copyright (c) 2012-2016. Sencha Inc.
             */

            "use strict";

            var NameConverter = require('./../NameConverter.js');

            var debugging = {
                trace: false
            };

            var Base = function Base(config) {
                _classCallCheck(this, Base);

                if (config) {
                    merge(this, config);
                }
            };

            var BaseSet = function () {
                function BaseSet() {
                    _classCallCheck(this, BaseSet);
                }

                _createClass(BaseSet, [{
                    key: "first",
                    value: function first() {
                        return _first(this.items);
                    }
                }, {
                    key: "last",
                    value: function last() {
                        return _last(this.items);
                    }
                }, {
                    key: "tail",
                    value: function tail() {
                        return _tail(this.items);
                    }
                }]);

                return BaseSet;
            }();

            BaseSet.prototype.items = null;

            function _chainFunc() {}

            function apply(target, source) {
                target = target || {};

                if (source) {
                    for (var name in source) {
                        target[name] = source[name];
                    }
                }

                return target;
            }

            function merge(destination, object) {
                destination = destination || {};
                var key, value, sourceKey;

                if (object) {
                    for (key in object) {
                        value = object[key];
                        if (value && value.constructor === Object) {
                            sourceKey = destination[key];
                            if (sourceKey && sourceKey.constructor === Object) {
                                merge(sourceKey, value);
                            } else {
                                destination[key] = value;
                            }
                        } else {
                            destination[key] = value;
                        }
                    }
                }

                return destination;
            }

            var chain = Object.create || function (Parent) {
                _chainFunc.prototype = Parent;
                return new _chainFunc();
            };

            function createMessage(message, source) {
                if (source && source.isFashionScanner) {
                    message += ': ' + source.currentFile + ':' + source.lineNumber;
                } else if (source) {
                    message += ': ' + source.file + ':' + source.lineNumber;
                }

                return message;
            }

            function isFunction(obj) {
                return obj && typeof obj === 'function';
            }

            function trace(message, source) {
                if (debugging.trace) {
                    console.log(createMessage('[DBG] ' + message, source));
                }
            }

            function debug(message, source) {
                console.log(createMessage('[DBG] ' + message, source));
            }

            function log(message, source) {
                console.log(createMessage('[LOG] ' + message, source));
            }

            function info(message, source) {
                console.log(createMessage('[INF] ' + message, source));
            }

            function warn(message, source) {
                console.log(createMessage('[WRN] ' + message, source));
            }

            function error(message, source) {
                console.log(createMessage('[ERR] ' + message, source));
            }

            function raise(message, extra) {
                if (Fashion.inspect) {
                    debugger;
                }

                if (typeof message !== 'string') {
                    extra = message;
                    message = extra.message;
                    delete extra.message;
                }

                var error = new Error(message);
                error.$isFashionError = true;
                throw apply(error, extra);
            }

            function raiseAt(message, source, stack) {
                var extra;

                if (source) {
                    message = createMessage(message, source);

                    if (source.isFashionScanner) {
                        extra = {
                            file: source.currentFile,
                            lineNumber: source.lineNumber
                        };
                    } else {
                        extra = {
                            node: source,
                            lineNumber: source.lineNumber,
                            file: source.file
                        };
                    }
                }

                if (stack) {
                    if (!extra) {
                        extra = {};
                    }
                    extra.fashionStack = stack;
                }

                raise(message, extra);
            }

            function filter(array, func) {
                var result = [];
                for (var i = 0; i < array.length; i++) {
                    var item = array[i];
                    if (func(item, i)) {
                        result.push(item);
                    }
                }
                return result;
            }

            function convert(array, func) {
                var converted = [];
                for (var i = 0; i < array.length; i++) {
                    converted.push(func(array[i]));
                }
                return converted;
            }

            function _first(array) {
                return array.length && array[0];
            }

            function _last(array) {
                return array.length && array[array.length - 1];
            }

            function _tail(array) {
                if (array.length > 2) {
                    return array.slice(1);
                }
                return [];
            }

            function getAllKeys(obj, stop) {
                var keys = [],
                    map = {},
                    i,
                    key,
                    n,
                    names;

                for (; obj && obj !== stop; obj = Object.getPrototypeOf(obj)) {
                    names = Object.getOwnPropertyNames(obj);

                    for (i = 0, n = names.length; i < n; ++i) {
                        key = names[i];

                        if (!map[key]) {
                            map[key] = true;
                            keys.push(key);
                        }
                    }
                }

                return keys;
            }

            function mixin(target, bases) {
                if (!Array.isArray(bases)) {
                    bases = Array.prototype.slice.call(arguments, 1);
                }

                var proto = target.prototype;

                for (var b = 0; b < bases.length; b++) {
                    var base = bases[b],
                        baseProto = base.prototype;

                    getAllKeys(baseProto, Base.prototype).forEach(function (name) {
                        if (name in baseProto) {
                            if (!(name in proto)) {
                                proto[name] = baseProto[name];
                            }
                        }
                    });
                }
            }

            function flatten(array, level, output) {
                output = output || [];
                level = typeof level === 'undefined' ? 1000 : level;

                for (var i = 0; i < array.length; i++) {
                    var item = array[i];
                    if (Array.isArray(item) && level) {
                        flatten(item, level - 1, output);
                    } else {
                        output.push(item);
                    }
                }
                return output;
            }

            module.exports = {
                EmptyArray: [],
                getJsName: NameConverter.getJsName,
                chain: chain,
                Base: Base,
                BaseSet: BaseSet,
                apply: apply,
                merge: merge,
                createMessage: createMessage,
                isFunction: isFunction,
                debugging: debugging,
                trace: trace,
                debug: debug,
                log: log,
                info: info,
                warn: warn,
                error: error,
                raise: raise,
                raiseAt: raiseAt,
                filter: filter,
                convert: convert,
                first: _first,
                last: _last,
                tail: _tail,
                mixin: mixin,
                flatten: flatten
            };
        }, { "./../NameConverter.js": 2 }], 4: [function (require, module, exports) {
            /*
             * Copyright (c) 2012-2016. Sencha Inc.
             */

            "use strict";

            var Fashion = require('./Base.js'),
                Base = Fashion.Base;

            var Output = function (_Base) {
                _inherits(Output, _Base);

                function Output() {
                    _classCallCheck(this, Output);

                    var _this = _possibleConstructorReturn(this, (Output.__proto__ || Object.getPrototypeOf(Output)).call(this));

                    _this.output = '';
                    return _this;
                }

                _createClass(Output, [{
                    key: "space",
                    value: function space() {
                        this.add(' ');
                    }
                }, {
                    key: "add",
                    value: function add(text) {
                        this.output += text;
                    }
                }, {
                    key: "addComment",
                    value: function addComment(text) {
                        this.output += text;
                    }
                }, {
                    key: "indent",
                    value: function indent() {
                        this.indentation += this.indentstr;
                    }
                }, {
                    key: "unindent",
                    value: function unindent() {
                        this.indentation = this.indentation.substr(this.indentstr.length);
                    }
                }, {
                    key: "addln",
                    value: function addln(ln) {
                        this.output += '\n' + this.indentation + (ln || '');
                    }
                }, {
                    key: "addCommentLn",
                    value: function addCommentLn(ln) {
                        if (ln && ln.indexOf('//') === 0) {
                            return;
                        }
                        this.addln(ln);
                    }
                }, {
                    key: "get",
                    value: function get() {
                        return this.output;
                    }
                }, {
                    key: "indentln",
                    value: function indentln(ln) {
                        this.addln(ln);
                        this.indent();
                    }
                }, {
                    key: "unindentln",
                    value: function unindentln(ln) {
                        this.unindent();
                        this.addln(ln);
                    }
                }, {
                    key: "reset",
                    value: function reset() {
                        this.indentation = '';
                        this.output = '';
                    }
                }]);

                return Output;
            }(Base);

            Fashion.apply(Output.prototype, {
                indentation: '',
                output: '',
                isCompressed: false,
                indentstr: '    ',
                splitThreshold: 1000000,
                selectorCount: 0
            });

            module.exports = Output;
        }, { "./Base.js": 3 }], 5: [function (require, module, exports) {
            "use strict";

            var Fashion = require('./Base.js'),
                Base = Fashion.Base;

            var Type = require('./type/Type.js');
            var List = require('./type/List.js');
            var Bool = require('./type/Bool.js');

            var Color = require('./type/Color.js');
            var ColorRGBA = require('./type/ColorRGBA.js');
            var Text = require('./type/Text.js');

            var Literal = require('./type/Literal.js');

            var Statics = require('./type/Statics.js');
            var TypeVisitor = require('./type/TypeVisitor.js');
            var Types = require('./type/Types.js');

            var Scope = function () {
                function Scope(prev) {
                    _classCallCheck(this, Scope);

                    this.prev = prev;
                    this.map = {};
                    this.sourceInfo = null;
                }

                _createClass(Scope, [{
                    key: "get",
                    value: function get(name) {
                        var map = this.map,
                            prev = this,
                            value;

                        while (map) {
                            value = map[name];
                            if (value) {
                                return value;
                            }
                            prev = prev.prev;
                            map = prev && prev.map;
                        }

                        return value;
                    }
                }, {
                    key: "has",
                    value: function has(name) {
                        //return name in this.map;
                        var map = this.map,
                            prev = this;

                        while (map) {
                            if (name in map) {
                                return true;
                            }
                            prev = prev.prev;
                            map = prev && prev.map;
                        }

                        return false;
                    }
                }, {
                    key: "put",
                    value: function put(name, value) {
                        this.map[name] = value;
                        return value;
                    }
                }, {
                    key: "addEntries",
                    value: function addEntries(names) {
                        if (this.prev) {
                            this.prev.addEntries(names);
                        }
                        for (var name in this.map) {
                            names[name] = this.map[name];
                        }
                    }
                }, {
                    key: "getEntries",
                    value: function getEntries(entries) {
                        entries = entries || {};
                        this.addEntries(entries);
                        return entries;
                    }
                }, {
                    key: "getSourceInfo",
                    value: function getSourceInfo() {
                        return this.sourceInfo;
                    }
                }, {
                    key: "getCallStack",
                    value: function getCallStack(stack) {
                        stack = stack || [];
                        if (this.sourceInfo) {
                            stack.push(this.sourceInfo);
                        }
                        if (this.prev) {
                            this.prev.getCallStack(stack);
                        }
                        return stack;
                    }
                }]);

                return Scope;
            }();

            Fashion.apply(Scope.prototype, {
                $isScope: true,
                map: undefined,
                prev: undefined,

                // placeholder used to track what to reset the _currentScope to,
                resetScope: undefined
            });

            var Runtime = function (_Base2) {
                _inherits(Runtime, _Base2);

                function Runtime(config) {
                    _classCallCheck(this, Runtime);

                    var _this2 = _possibleConstructorReturn(this, (Runtime.__proto__ || Object.getPrototypeOf(Runtime)).call(this, config));

                    var me = _this2;
                    me.mixins = {};
                    me.functions = {};
                    me.processors = [];
                    me.registered = {
                        runtime: me,
                        box: Statics.boxType,
                        unbox: Statics.unboxType,
                        isArray: function isArray(array) {
                            return Array.isArray(array);
                        },

                        getRuntime: function getRuntime() {
                            return this.runtime;
                        },

                        handleArgs: function handleArgs(args, keys) {
                            var scope = {},
                                index = 0,
                                key;

                            for (var a = 0; a < args.length; a++) {
                                var arg = args[a];
                                if (arg === undefined) {
                                    continue;
                                }

                                // Named arguments
                                if (arg === true || arg === false) {
                                    scope[keys[index]] = arg;
                                    index++;
                                } else if (arg.type === undefined) {
                                    for (key in arg) {
                                        scope[key.replace(/^\$/, '')] = arg[key];
                                    }
                                }
                                // Required arguments
                                else {
                                        key = keys[index];
                                        if (key instanceof Array) {
                                            key = key[0];
                                            scope[key] = scope[key] || new List();
                                            scope[key].add(arg);
                                        } else {
                                            scope[key] = arg;
                                            index++;
                                        }
                                    }
                            }
                            return scope;
                        },

                        sliceArgs: function sliceArgs(args, start, end) {
                            return this.getRuntime().sliceArgs(args, start, end).items;
                        },

                        tailArgs: function tailArgs(start, args) {
                            var tail = Array.prototype.slice.call(args, start);

                            if (tail.length == 1 && this.isArray(tail)) {
                                tail = tail[0];
                            }

                            return tail;
                        }
                    };
                    return _this2;
                }

                _createClass(Runtime, [{
                    key: "bool",
                    value: function bool(value) {
                        return new Bool(value);
                    }
                }, {
                    key: "color",
                    value: function color(name) {
                        var rgb = Color.map[name],
                            color = new ColorRGBA(rgb[0], rgb[1], rgb[2], rgb[3]);
                        color.stringified = name;
                        return color;
                    }
                }, {
                    key: "quote",
                    value: function quote(value) {
                        if (value.type === 'string') {
                            return value;
                        }

                        return new Text(value.toString());
                    }
                }, {
                    key: "unquote",
                    value: function unquote(value) {
                        if (value.$isFashionType) {
                            return value.unquote();
                        }
                        return new Literal(value.toString());
                    }
                }, {
                    key: "not",
                    value: function not(expression) {
                        return this.box(this.unbox(expression) == false);
                    }
                }, {
                    key: "operate",
                    value: function operate(operation, left, right) {
                        if (left == null || left.$isFashionNull) {
                            if (operation != '==' && operation != '!=') {
                                return Literal.Null;
                            }
                        }
                        if (right == null || right.$isFashionNull) {
                            if (operation != '==' && operation != '!=') {
                                return Literal.Null;
                            }
                        }
                        return left.operate(operation, right);
                    }
                }, {
                    key: "reset",
                    value: function reset() {
                        this._currentScope = null;
                        this._currentCallStackScope = this.createCallStackScope();
                        this._globalScope = this.createScope();
                        this._dynamics = {};
                    }
                }, {
                    key: "run",
                    value: function run(code, metadata) {
                        this.load(code);
                        this.compile(code);
                        return this.execute(metadata);
                    }
                }, {
                    key: "createTypesBlock",
                    value: function createTypesBlock(types) {
                        types = types || this.types;
                        var keys = Object.getOwnPropertyNames(types),
                            buff = [],
                            name;
                        for (var i = 0; i < keys.length; i++) {
                            name = keys[i];
                            buff.push(name + ' = Types.' + name);
                            buff.push("__" + name + ' = ' + name);
                        }

                        if (buff.length === 0) {
                            return '';
                        }
                        return 'var ' + buff.join(',\n    ') + ';\n';
                    }
                }, {
                    key: "createMethodBlock",
                    value: function createMethodBlock(proto) {
                        proto = proto || this.constructor.prototype;

                        var buff = [],
                            keys,
                            name;

                        while (proto) {
                            keys = Object.getOwnPropertyNames(proto);
                            for (var i = 0; i < keys.length; i++) {
                                name = keys[i];
                                if (typeof proto[name] === 'function') {
                                    buff.push("__rt_" + name + ' = __rt.' + name + '.bind(__rt)');
                                }
                            }
                            proto = Object.getPrototypeOf(proto);
                        }

                        if (buff.length === 0) {
                            return '';
                        }
                        return 'var ' + buff.join(',\n    ') + ';\n';
                    }
                }, {
                    key: "createPropertyBlock",
                    value: function createPropertyBlock() {
                        var keys = Object.getOwnPropertyNames(this),
                            buff = [],
                            name;
                        for (var i = 0; i < keys.length; i++) {
                            name = keys[i];
                            buff.push("__rt_" + name + ' = __rt.' + name);
                        }

                        if (buff.length === 0) {
                            return '';
                        }
                        return 'var ' + buff.join(',\n    ') + ';\n';
                    }
                }, {
                    key: "createPrefixedFunctionBody",
                    value: function createPrefixedFunctionBody(code) {
                        code = this.createTypesBlock() + this.createMethodBlock() + this.createPropertyBlock() + code;
                        return code;
                    }
                }, {
                    key: "createWrappedFn",
                    value: function createWrappedFn(code) {
                        return new Function('Types', '__rt', '__gs', '__udf', '__dyn', this.createPrefixedFunctionBody(code));
                    }
                }, {
                    key: "callWrappedFn",
                    value: function callWrappedFn(fn, dynamics) {
                        return fn(Fashion, this, this._globalScope, undefined, dynamics || {});
                    }
                }, {
                    key: "compile",
                    value: function compile(code) {
                        var me = this,
                            theFn;

                        //code = '"use strict";\n' + code;
                        this.code = code;

                        new Function();

                        theFn = this.createWrappedFn(code);

                        this.fn = function (rt, overrides, dyn) {
                            var runtime = rt || me,
                                dynamics = dyn || {};

                            runtime.reset();

                            if (overrides) {
                                if (overrides.$isScope) {
                                    runtime._globalScope = overrides;
                                } else {
                                    runtime._globalScope.map = overrides;
                                }
                            }

                            if (dyn) {
                                runtime._dynamics = dyn;
                            }
                            runtime._currentScope = runtime._globalScope;
                            runtime._scopeStack = [runtime._currentScope];
                            try {
                                theFn(me.types, runtime, runtime._globalScope, undefined, dynamics);
                            } catch (err) {
                                Fashion.raiseAt(err.message || err, null, runtime.getCallStack());
                            }

                            return runtime._globalScope;
                        };

                        return this.fn;
                    }
                }, {
                    key: "execute",
                    value: function execute(metadata) {
                        return this.fn(this, metadata);
                    }
                }, {
                    key: "load",
                    value: function load(code) {
                        this.code = code;
                        return this;
                    }
                }, {
                    key: "registerProcessor",
                    value: function registerProcessor(proc) {
                        this.processors.push(new TypeVisitor(proc));
                    }
                }, {
                    key: "register",
                    value: function register(methods) {
                        if (methods['dynamic']) {
                            Fashion.error('Cannot register javascript function named "dynamic"');
                            delete methods['dynamic'];
                        }
                        if (methods['require']) {
                            Fashion.error('Cannot register javascript function named "require"');
                            delete methods['require'];
                        }
                        Fashion.apply(this.registered, methods);
                    }
                }, {
                    key: "isRegistered",
                    value: function isRegistered(name) {
                        name = this.reserved[name] ? '__' + name : name;
                        return !!this.registered[name];
                    }
                }, {
                    key: "getGlobalScope",
                    value: function getGlobalScope() {
                        return this._globalScope;
                    }
                }, {
                    key: "getCurrentScope",
                    value: function getCurrentScope() {
                        return this._currentScope;
                    }
                }, {
                    key: "getRegisteredFunctions",
                    value: function getRegisteredFunctions() {
                        return this.registered;
                    }
                }, {
                    key: "getFunctions",
                    value: function getFunctions() {
                        return this.functions;
                    }
                }, {
                    key: "getMixins",
                    value: function getMixins() {
                        return this.mixins;
                    }
                }, {
                    key: "createScope",
                    value: function createScope(scope) {
                        var currScope = scope || this._currentScope,
                            newScope = new Scope(currScope);
                        return this.pushScope(newScope);
                    }
                }, {
                    key: "pushScope",
                    value: function pushScope(scope) {
                        scope.resetScope = this._currentScope;
                        this._currentScope = scope;
                        return scope;
                    }
                }, {
                    key: "popScope",
                    value: function popScope() {
                        this._currentScope = this._currentScope.resetScope;
                        return this._currentScope;
                    }
                }, {
                    key: "createCallStackScope",
                    value: function createCallStackScope(scope) {
                        var currScope = scope || this._currentCallStackScope,
                            newScope = new Scope(currScope);
                        return this.pushCallStackScope(newScope);
                    }
                }, {
                    key: "pushCallStackScope",
                    value: function pushCallStackScope(scope) {
                        scope.resetScope = this._currentCallStackScope;
                        this._currentCallStackScope = scope;
                        return scope;
                    }
                }, {
                    key: "popCallStackScope",
                    value: function popCallStackScope() {
                        this._currentCallStackScope = this._currentCallStackScope.resetScope;
                        return this._currentCallStackScope;
                    }
                }, {
                    key: "getCallStack",
                    value: function getCallStack() {
                        if (this._currentCallStackScope) {
                            return this._currentCallStackScope.getCallStack();
                        }
                        return null;
                    }
                }, {
                    key: "pushSourceInfo",
                    value: function pushSourceInfo(info) {
                        if (this._currentCallStackScope) {
                            this._currentCallStackScope.sourceInfo = info;
                        }
                        return true;
                    }
                }, {
                    key: "getSourceInfo",
                    value: function getSourceInfo() {
                        var stack = this._currentCallStackScope,
                            info = stack && stack.sourceInfo;

                        if (info && info.length) {
                            return {
                                lineNumber: info[0],
                                file: info[1]
                            };
                        }
                        return null;
                    }
                }, {
                    key: "get",
                    value: function get(name) {
                        var scope = this.getScopeForName(name),
                            res = scope.map[name];

                        if (typeof res === 'undefined') {
                            if (!(name in scope.map)) {
                                Fashion.raiseAt('Reference to undeclared variable : ' + name, null, this.getCallStack());
                            }
                        }

                        return this.box(res);
                    }
                }, {
                    key: "getScopeForName",
                    value: function getScopeForName(jsName) {
                        var scope = this._currentScope;
                        while (scope) {
                            if (jsName in scope.map) {
                                return scope;
                            }
                            scope = scope.prev;
                        }
                        return this._currentScope;
                    }
                }, {
                    key: "getDefault",
                    value: function getDefault(val) {
                        if (val == null || typeof val === 'undefined') {
                            // === null || undefined
                            return undefined;
                        }

                        if (val.$isFashionNull) {
                            if (this.constructor.allowNullDefaults) {
                                return val;
                            }
                            return undefined;
                        }

                        return this.box(val);
                    }
                }, {
                    key: "getGlobalDefault",
                    value: function getGlobalDefault(jsName) {
                        var obj = this._globalScope.get(jsName);
                        return this.getDefault(obj);
                    }
                }, {
                    key: "getLocalDefault",
                    value: function getLocalDefault(jsName) {
                        var obj = this._currentScope.get(jsName);
                        return this.getDefault(obj);
                    }
                }, {
                    key: "setGlobal",
                    value: function setGlobal(jsName, value, astNodeId) {
                        var currScope = this._globalScope;

                        if (!value || !value.$isFashionLiteral) {
                            value = this.box(value);
                        }

                        value.ast = value.ast || this.getAstNode(astNodeId);
                        currScope.map[jsName] = value;
                        return value;
                    }
                }, {
                    key: "setDynamic",
                    value: function setDynamic(name, value, astNodeId) {
                        var jsName = Fashion.getJsName(name),
                            currScope = this._globalScope,
                            newValue;

                        if (!value || !value.$isFashionLiteral) {
                            value = this.box(value);
                        }

                        value.ast = value.ast || this.getAstNode(astNodeId);

                        if (value.$referenceName || value.$constant) {
                            newValue = value.clone();
                            newValue.$previousReference = value;
                            value = newValue;
                            value.ast = this.getAstNode(astNodeId);
                        } else {
                            value.$referenceName = name;
                        }

                        currScope.map[jsName] = value;
                        return value;
                    }
                }, {
                    key: "setScoped",
                    value: function setScoped(jsName, value) {
                        var currScope = this.getScopeForName(jsName);

                        if (!value || !value.$isFashionLiteral) {
                            value = this.box(value);
                        }

                        currScope.map[jsName] = value;
                        return value;
                    }
                }, {
                    key: "set",
                    value: function set(jsName, value) {
                        var currScope = this._currentScope;

                        if (!value || !value.$isFashionLiteral) {
                            value = this.box(value);
                        }

                        currScope.map[jsName] = value;
                        return value;
                    }
                }, {
                    key: "getDocs",
                    value: function getDocs(id) {
                        if (this.docCache) {
                            return this.docCache.get(id);
                        }
                    }
                }, {
                    key: "getString",
                    value: function getString(id) {
                        if (this.stringCache) {
                            return this.stringCache.get(id);
                        }
                    }
                }, {
                    key: "getAstNode",
                    value: function getAstNode(id) {
                        if (this.nodeCache) {
                            return this.nodeCache.get(id);
                        }
                    }
                }, {
                    key: "applySpread",
                    value: function applySpread(arg) {
                        arg.spread = true;
                        return arg;
                    }
                }, {
                    key: "sliceArgs",
                    value: function sliceArgs(args, start, end) {
                        start = start || 0;
                        end = end || args.length;

                        var filtered = [],
                            newArgs = [],
                            separator = ', ',
                            spread,
                            a,
                            arg;

                        for (a = start; a < end; a++) {
                            arg = args[a];
                            if (!arg) {
                                if (!spread) {
                                    filtered.push(arg);
                                }
                                continue;
                            }
                            if (arg.spread && arg.$isFashionList) {
                                if (spread) {
                                    filtered.push(spread);
                                }
                                spread = arg;
                                separator = spread.separator || separator;
                            } else {
                                filtered.push(arg);
                            }
                        }

                        for (a = 0; a < filtered.length; a++) {
                            arg = filtered[a];
                            separator = arg && arg.splatSeparator || separator;
                            newArgs.push(filtered[a]);
                        }

                        if (spread) {
                            newArgs.push.apply(newArgs, spread.items);
                        }

                        return new List(newArgs, separator);
                    }
                }, {
                    key: "applySpreadArgs",
                    value: function applySpreadArgs(args, name) {
                        var newArgs = [],
                            hadSpread = false,
                            offset = 0,
                            arg,
                            a,
                            item,
                            i,
                            items,
                            key,
                            map,
                            defaults,
                            proc,
                            param,
                            paramName;

                        proc = this.context && this.context.preprocessor;
                        if (proc) {
                            defaults = proc.mixinDeclarations[name];

                            if (defaults) {
                                offset = 1;
                            } else {
                                defaults = proc.functionDeclarations[name];
                            }

                            defaults = defaults && defaults.parameters;
                        }

                        for (a = 0; a < args.length; a++) {
                            arg = args[a];
                            if (arg && arg.spread && arg.$isFashionMap && defaults) {
                                items = arg.items;
                                map = {};
                                for (key in arg.map) {
                                    map['$' + Fashion.getJsName(key)] = arg.map[key];
                                }

                                for (var p = 0; p < defaults.length; p++) {
                                    param = defaults[p];
                                    paramName = Fashion.getJsName(param.name);
                                    if (paramName in map) {
                                        newArgs.push(items[map[paramName]]);
                                        delete map[paramName];
                                    } else if (!param.varArgs) {
                                        newArgs.push(undefined);
                                    }
                                }
                                for (key in map) {
                                    item = items[map[key]];
                                    newArgs.push(item);
                                }
                                hadSpread = true;
                            } else if (arg && arg.spread && arg.$isFashionList) {
                                items = arg.getItems();
                                for (i = 0; i < items.length; i++) {
                                    item = items[i];
                                    item && (item.splatSeparator = arg.separator);
                                    newArgs.push(item);
                                }
                                hadSpread = true;
                            } else if (arg || !hadSpread) {
                                newArgs.push(arg);
                            }
                            // clear the flag indicating the spread argument
                            // so subsequent calls using this same variable will not
                            // be contaminated
                            arg && (arg.spread = undefined);
                        }

                        var misisngParams = this.context && this.context.missingParameters;

                        if (misisngParams && misisngParams == 'error') {
                            if (defaults) {
                                for (var d = 0; d < defaults.length; d++) {
                                    if (!defaults[d].hasOwnProperty('default') && !defaults[d].varArgs) {
                                        if (newArgs[d + offset] === undefined) {
                                            Fashion.raiseAt("No value supplied for argument : " + defaults[d].name, null, this.getCallStack());
                                        }
                                    }
                                }
                            }
                        }
                        return newArgs;
                    }
                }, {
                    key: "warn",
                    value: function warn(arg) {
                        Fashion.warn(arg, this.getSourceInfo());
                    }
                }, {
                    key: "error",
                    value: function error(arg) {
                        Fashion.raiseAt(arg, null, this.getCallStack());
                    }
                }, {
                    key: "debug",
                    value: function debug() {
                        Fashion.debug.apply(Fashion, arguments);
                    }
                }, {
                    key: "setCaches",
                    value: function setCaches(transpiler) {
                        this.docCache = transpiler.docCache;
                        this.stringCache = transpiler.stringCache;
                        this.nodeCache = transpiler.nodeCache;
                    }
                }, {
                    key: "copyRuntimeState",
                    value: function copyRuntimeState(runtime) {
                        this._dynamics = runtime._dynamics;
                        this.registered = runtime.registered;
                        this.functions = runtime.functions;
                        this.mixins = runtime.mixins;
                    }
                }, {
                    key: "test",
                    value: function test(val) {
                        val = this.unbox(val);
                        if (val == null || val === false) {
                            return false;
                        }
                        return true;
                    }
                }, {
                    key: "and",
                    value: function and(a, b) {
                        if (this.test(a)) {
                            return b;
                        }
                        return a;
                    }
                }, {
                    key: "or",
                    value: function or(a, b) {
                        if (this.test(a)) {
                            return a;
                        }
                        return b;
                    }
                }]);

                return Runtime;
            }(Base);

            Fashion.apply(Runtime.prototype, {
                box: Type.box,
                unbox: Type.unbox,
                Scope: Scope,

                isFashionRuntime: true,
                functions: null,
                code: null,
                fn: null,

                stringCache: null,
                docCache: null,
                types: Types,

                _globalScope: null,
                _currentScope: null,
                _dynamics: null,
                context: null,
                reserved: {
                    'if': true,
                    'else': true
                }
            });

            module.exports = Runtime;
        }, { "./Base.js": 3, "./type/Bool.js": 8, "./type/Color.js": 9, "./type/ColorRGBA.js": 11, "./type/List.js": 15, "./type/Literal.js": 16, "./type/Statics.js": 22, "./type/Text.js": 23, "./type/Type.js": 24, "./type/TypeVisitor.js": 25, "./type/Types.js": 26 }], 6: [function (require, module, exports) {
            "use strict";

            var Fashion = require('../Base.js');
            var Runtime = require('../Runtime.js');
            var ValueParser = require('../parse/ValueParser.js');
            var SourceBuilder = require('../type/SourceBuilder.js');

            var CssVariableManager = function () {
                function CssVariableManager() {
                    _classCallCheck(this, CssVariableManager);

                    this.reset();
                }

                _createClass(CssVariableManager, [{
                    key: "reset",
                    value: function reset() {
                        this.initFns = [];
                        this.calcFns = [];
                        this.variableMap = {};
                        this.runtime = null;
                    }
                }, {
                    key: "createRuntime",
                    value: function createRuntime() {
                        return new Runtime();
                    }
                }, {
                    key: "getRuntime",
                    value: function getRuntime() {
                        var me = this,
                            rt = me.runtime;
                        if (!rt) {
                            rt = me.createRuntime();
                            for (var i = 0; i < me.initFns.length; i++) {
                                me.initFns[i](rt);
                            }
                            me.runtime = rt;
                        }
                        return rt;
                    }
                }, {
                    key: "calculate",
                    value: function calculate(vars) {
                        var me = this,
                            rt = me.getRuntime(),
                            globals = {},
                            parser = new ValueParser(),
                            map = me.variableMap,
                            key,
                            scope,
                            sb,
                            name,
                            names,
                            jsName,
                            value,
                            wrapper;

                        scope = new rt.Scope();
                        for (name in vars) {
                            key = Fashion.getJsName(name.replace(me.nameRe, ''));
                            if (key.indexOf('$') !== 0) {
                                key = '$' + key;
                            }
                            scope.put(key, parser.parse(vars[name]));
                        }

                        rt._globalScope = scope;
                        rt._currentScope = scope;
                        for (var i = 0; i < me.calcFns.length; i++) {
                            me.calcFns[i](rt);
                        }

                        sb = new SourceBuilder();

                        vars = {};
                        for (name in map) {
                            names = map[name];
                            for (var i = 0; i < names.length; i++) {
                                key = names[i];
                                jsName = '$' + Fashion.getJsName(key);
                                value = scope.get(jsName);
                                if (value) {
                                    if (value.$isWrapper) {
                                        value = value.value;
                                    }
                                    vars[key] = sb.toSource(value);
                                }
                            }
                        }

                        return vars;
                    }
                }, {
                    key: "applyVariables",
                    value: function applyVariables(vars) {
                        var me = this,
                            map = me.variableMap;

                        for (var selector in map) {
                            var variables = map[selector];
                            var els = document.querySelectorAll(selector);
                            if (els) {
                                for (var i = 0; i < els.length; i++) {
                                    for (var j = 0; j < variables.length; j++) {
                                        var varName = variables[j];
                                        els[i].style.setProperty('--' + varName, vars[varName]);
                                    }
                                }
                            }
                        }
                    }
                }, {
                    key: "setVariables",
                    value: function setVariables(vars) {
                        this.applyVariables(this.calculate(vars));
                    }
                }, {
                    key: "register",
                    value: function register(init, calc, map) {
                        if (init) {
                            this.initFns.push(init);
                        }

                        if (calc) {
                            this.calcFns.push(calc);
                        }

                        if (map) {
                            var vars = this.variableMap;
                            for (var name in map) {
                                var curr = vars[name];
                                if (!curr) {
                                    vars[name] = map[name];
                                } else {
                                    curr.push.apply(curr, map[name]);
                                }
                            }
                        }
                    }
                }, {
                    key: "buildName",
                    value: function buildName(name) {
                        return name.replace(/^--/, '').replace(/^\$/, '');
                    }
                }, {
                    key: "buildJsName",
                    value: function buildJsName(name) {
                        return Fashion.getJsName(name);
                    }
                }, {
                    key: "buildNames",
                    value: function buildNames(names) {
                        var out = {},
                            name;
                        for (name in names) {
                            out[name] = this.buildName(names[name]);
                        }
                        return out;
                    }
                }, {
                    key: "buildJsNames",
                    value: function buildJsNames(names) {
                        var out = {},
                            name;
                        for (name in names) {
                            out[name] = this.buildJsName(names[name]);
                        }
                        return out;
                    }
                }, {
                    key: "getVariables",
                    value: function getVariables() {
                        var me = this,
                            map = me.variableMap,
                            out = {};

                        for (var selector in map) {
                            var variables = map[selector];
                            var els = document.querySelectorAll(selector);
                            if (els) {
                                for (var i = 0; i < els.length; i++) {
                                    for (var j = 0; j < variables.length; j++) {
                                        var varName = variables[j];
                                        out[varName] = els[i].style.getPropertyValue('--' + varName);
                                    }
                                }
                            }
                        }
                        return out;
                    }
                }]);

                return CssVariableManager;
            }();

            Fashion.apply(CssVariableManager.prototype, {
                $isExport: true,
                nameRe: /^--/
            });

            module.exports = CssVariableManager;
        }, { "../Base.js": 3, "../Runtime.js": 5, "../parse/ValueParser.js": 7, "../type/SourceBuilder.js": 21 }], 7: [function (require, module, exports) {
            "use strict";

            var Fashion = require('../Base.js');
            var Type = require('../type/Type.js');
            var Statics = require('../type/Statics.js');
            var Types = require('../type/Types.js'),
                Color = Types.Color,
                Text = Types.Text,
                Numeric = Types.Numeric,
                List = Types.List,
                Bool = Types.Bool,
                Literal = Types.Literal,
                ColorRGBA = Types.ColorRGBA,
                ColorHSLA = Types.ColorHSLA,
                FunctionCall = Types.FunctionCall;

            var Parser = function () {
                function Parser() {
                    _classCallCheck(this, Parser);

                    this.index = 0;
                }

                _createClass(Parser, [{
                    key: "_advance",
                    value: function _advance() {
                        var me = this,
                            buff = '',
                            str = me.str,
                            len = str.length,
                            isString = false,
                            escaped = false,
                            isParen = 0,
                            ch;

                        while (me.index < len) {
                            ch = str[me.index];
                            me.index++;

                            // whitespace
                            if (ch <= ' ') {
                                if (!isString && !isParen) {
                                    if (buff.length) {
                                        break;
                                    }
                                    continue;
                                }
                            }

                            // terminal char
                            if (ch === ';' && !isString && !escaped) {
                                break;
                            }

                            if (ch === '(') {
                                isParen++;
                            }

                            if (ch === ')') {
                                isParen && isParen--;
                            }

                            if (ch === ',' && !isString && !escaped && !isParen) {
                                if (buff.length) {
                                    me.index--;
                                    break;
                                } else {
                                    return ch;
                                }
                            }

                            if (ch === '\\') {
                                if (isString) {
                                    escaped = 1;
                                    me.index++;
                                    continue;
                                }
                            }

                            if (ch === '"' || ch === "'") {
                                if (!isString) {
                                    isString = ch;
                                } else if (isString === ch) {
                                    isString = false;
                                }
                            }

                            escaped = false;
                            buff += ch;
                        }

                        return buff;
                    }
                }, {
                    key: "parseValue",
                    value: function parseValue(token) {
                        var rx = {
                            number: /^(\d+)(px|pt|pc|cm|mm|in|em|rem|ex)?$/g,
                            shortHexColor: /^#([A-Fa-f0-9]{3})$/,
                            longHexColor: /^#([A-Fa-f0-9]{6})$/,
                            functionCall: /^([A-Za-z0-9_]+)\((.*)\)$/,
                            parenList: /^\((.*?)\)$/

                        },
                            match,
                            value;

                        if (token[0] === '"' || token[0] === "'") {
                            value = token = token.substring(1, token.length - 1);
                            return new Text(value, token[0]);
                        }

                        if (token === 'true') {
                            return new Bool(true);
                        }

                        if (token === 'false') {
                            return new Bool(false);
                        }

                        if (token === 'null') {
                            return Literal.Null;
                        }

                        if (token === 'none') {
                            return Literal.None;
                        }

                        if (Fashion.Color.map[token]) {
                            var rgb = Color.map[token],
                                color = new ColorRGBA(rgb[0], rgb[1], rgb[2], rgb[3]);
                            color.stringified = token;
                            return color;
                        }

                        if (match = rx.number.exec(token)) {
                            return new Numeric(parseFloat(match[1]), match[2]);
                        }

                        if (match = rx.shortHexColor.exec(token)) {
                            return ColorRGBA.fromHex(match[1]);
                        }

                        if (match = rx.longHexColor.exec(token)) {
                            return ColorRGBA.fromHex(match[1]);
                        }

                        if (match = rx.functionCall.exec(token)) {
                            var name = match[1],
                                args = this.parse(match[2]).items;
                            if (name === 'hsla' || name === 'hsl') {
                                return new ColorHSLA(Type.unbox(args[0]), Type.unbox(args[1]), Type.unbox(args[2]), Type.unbox(args[3]) || 1);
                            } else if (name === 'rgba' || name === 'rgb') {
                                return new ColorRGBA(Type.unbox(args[0]), Type.unbox(args[1]), Type.unbox(args[2]), Type.unbox(args[3]) || 1);
                            }
                            return new FunctionCall(name, args);
                        }

                        if (match = rx.parenList.exec(token)) {
                            return new FunctionCall(this.parse(match[1]));
                        }

                        return new Fashion.Literal(token);
                    }
                }, {
                    key: "parse",
                    value: function parse(str) {
                        var me = this,
                            tokens = [],
                            values = [],
                            csv = null,
                            token;

                        me.str = str;
                        me.index = 0;

                        while (token = me._advance()) {
                            tokens.push(token);
                        }

                        for (var i = 0; i < tokens.length; i++) {
                            token = tokens[i].trim();
                            if (tokens[i + 1] === ',') {
                                csv = csv || [];
                                csv.push(me.parseValue(token));
                                i++;
                            } else if (csv) {
                                csv.push(me.parseValue(token));
                                values.push(new List(csv, ', '));
                                csv = null;
                            } else {
                                values.push(me.parseValue(token));
                            }
                        }

                        if (values.length === 1) {
                            return values[0];
                        }

                        return new List(values, ' ');
                    }
                }]);

                return Parser;
            }();

            // Fashion.apply(Parser.prototype, {
            //     regex:
            // });

            module.exports = Parser;
        }, { "../Base.js": 3, "../type/Statics.js": 22, "../type/Type.js": 24, "../type/Types.js": 26 }], 8: [function (require, module, exports) {
            /*
             * Copyright (c) 2012-2016. Sencha Inc.
             */

            "use strict";

            var Fashion = require('../Base.js');
            var Type = require('./Type.js');

            var Bool = function (_Type) {
                _inherits(Bool, _Type);

                function Bool(value) {
                    _classCallCheck(this, Bool);

                    var _this3 = _possibleConstructorReturn(this, (Bool.__proto__ || Object.getPrototypeOf(Bool)).call(this));

                    _this3.value = !!value;
                    return _this3;
                }

                _createClass(Bool, [{
                    key: "doVisit",
                    value: function doVisit(visitor) {
                        visitor.bool(this);
                    }
                }, {
                    key: "toString",
                    value: function toString() {
                        return this.value ? 'true' : 'false';
                    }
                }, {
                    key: "copy",
                    value: function copy() {
                        return new Bool(this.value);
                    }
                }]);

                return Bool;
            }(Type);

            Fashion.apply(Bool.prototype, {
                type: 'bool',
                $isFashionBool: true,
                value: null
            });

            Bool.True = new Bool(true);
            Bool.True.$constant = true;

            Bool.False = new Bool(false);
            Bool.False.$constant = true;

            module.exports = Bool;
        }, { "../Base.js": 3, "./Type.js": 24 }], 9: [function (require, module, exports) {
            /*
             * Copyright (c) 2012-2016. Sencha Inc.
             */

            "use strict";

            var Fashion = require('../Base.js');
            var Type = require('./Type.js');
            var Bool = require('./Bool.js');
            var Numeric = require('./Numeric.js');

            var Color = function (_Type2) {
                _inherits(Color, _Type2);

                function Color() {
                    _classCallCheck(this, Color);

                    return _possibleConstructorReturn(this, (Color.__proto__ || Object.getPrototypeOf(Color)).call(this));
                }

                _createClass(Color, [{
                    key: "toBoolean",
                    value: function toBoolean() {
                        return Bool.True;
                    }

                    // These two references need to be left out of the comment section above
                    // so as to prevent ordering issue during builds;

                }, {
                    key: "getRGBA",
                    value: function getRGBA() {
                        return this;
                    }
                }, {
                    key: "getHSLA",
                    value: function getHSLA() {
                        return this;
                    }
                }], [{
                    key: "component",
                    value: function component(color, _component) {
                        var unit = Color.units[_component],
                            type = Color.types[_component],
                            prop = Color.comps[_component],
                            targetColor;

                        if (type == 'hsla') {
                            targetColor = color.getHSLA();
                        } else {
                            targetColor = color.getRGBA();
                        }

                        return new Numeric(targetColor[prop], unit);
                    }
                }, {
                    key: "adjust",
                    value: function adjust(color, component, amount) {
                        var hsl = color.getHSLA().copy(),
                            prop = Color.comps[component],
                            value = amount.value;

                        //    if (component === 'saturation' && hsl.s === 0)  {
                        //        return color.clone();
                        //    }
                        //
                        hsl[prop] += value;

                        hsl.h = Color.constrainDegrees(hsl.h);
                        hsl.s = Color.constrainPercentage(hsl.s);
                        hsl.l = Color.constrainPercentage(hsl.l);

                        return hsl.getRGBA();
                    }
                }, {
                    key: "constrainChannel",
                    value: function constrainChannel(channel) {
                        return Math.max(0, Math.min(channel, 255));
                    }
                }, {
                    key: "constrainPercentage",
                    value: function constrainPercentage(per) {
                        return Math.max(0, Math.min(per, 100));
                    }
                }, {
                    key: "constrainDegrees",
                    value: function constrainDegrees(deg) {
                        deg = deg % 360;
                        return deg < 0 ? 360 + deg : deg;
                    }
                }, {
                    key: "constrainAlpha",
                    value: function constrainAlpha(alpha) {
                        if (alpha === undefined) {
                            return 1;
                        }
                        return Math.max(0, Math.min(alpha, 1));
                    }
                }]);

                return Color;
            }(Type);

            Fashion.apply(Color, {
                units: {
                    lightness: '%',
                    saturation: '%',
                    hue: 'deg'
                },

                types: {
                    red: 'rgba',
                    blue: 'rgba',
                    green: 'rgba',
                    alpha: 'rgba',
                    hue: 'hsla',
                    saturation: 'hsla',
                    lightness: 'hsla'
                },

                comps: {
                    red: 'r',
                    green: 'g',
                    blue: 'b',
                    alpha: 'a',
                    hue: 'h',
                    saturation: 's',
                    lightness: 'l'
                },

                map: {
                    aliceblue: [240, 248, 255],
                    antiquewhite: [250, 235, 215],
                    aqua: [0, 255, 255],
                    aquamarine: [127, 255, 212],
                    azure: [240, 255, 255],
                    beige: [245, 245, 220],
                    bisque: [255, 228, 196],
                    black: [0, 0, 0],
                    blanchedalmond: [255, 235, 205],
                    blue: [0, 0, 255],
                    blueviolet: [138, 43, 226],
                    brown: [165, 42, 42],
                    burlywood: [222, 184, 135],
                    cadetblue: [95, 158, 160],
                    chartreuse: [127, 255, 0],
                    chocolate: [210, 105, 30],
                    coral: [255, 127, 80],
                    cornflowerblue: [100, 149, 237],
                    cornsilk: [255, 248, 220],
                    crimson: [220, 20, 60],
                    cyan: [0, 255, 255],
                    darkblue: [0, 0, 139],
                    darkcyan: [0, 139, 139],
                    darkgoldenrod: [184, 132, 11],
                    darkgray: [169, 169, 169],
                    darkgreen: [0, 100, 0],
                    darkgrey: [169, 169, 169],
                    darkkhaki: [189, 183, 107],
                    darkmagenta: [139, 0, 139],
                    darkolivegreen: [85, 107, 47],
                    darkorange: [255, 140, 0],
                    darkorchid: [153, 50, 204],
                    darkred: [139, 0, 0],
                    darksalmon: [233, 150, 122],
                    darkseagreen: [143, 188, 143],
                    darkslateblue: [72, 61, 139],
                    darkslategray: [47, 79, 79],
                    darkslategrey: [47, 79, 79],
                    darkturquoise: [0, 206, 209],
                    darkviolet: [148, 0, 211],
                    deeppink: [255, 20, 147],
                    deepskyblue: [0, 191, 255],
                    dimgray: [105, 105, 105],
                    dimgrey: [105, 105, 105],
                    dodgerblue: [30, 144, 255],
                    firebrick: [178, 34, 34],
                    floralwhite: [255, 255, 240],
                    forestgreen: [34, 139, 34],
                    fuchsia: [255, 0, 255],
                    gainsboro: [220, 220, 220],
                    ghostwhite: [248, 248, 255],
                    gold: [255, 215, 0],
                    goldenrod: [218, 165, 32],
                    gray: [128, 128, 128],
                    green: [0, 128, 0],
                    greenyellow: [173, 255, 47],
                    grey: [128, 128, 128],
                    honeydew: [240, 255, 240],
                    hotpink: [255, 105, 180],
                    indianred: [205, 92, 92],
                    indigo: [75, 0, 130],
                    ivory: [255, 255, 240],
                    khaki: [240, 230, 140],
                    lavender: [230, 230, 250],
                    lavenderblush: [255, 240, 245],
                    lawngreen: [124, 252, 0],
                    lemonchiffon: [255, 250, 205],
                    lightblue: [173, 216, 230],
                    lightcoral: [240, 128, 128],
                    lightcyan: [224, 255, 255],
                    lightgoldenrodyellow: [250, 250, 210],
                    lightgray: [211, 211, 211],
                    lightgreen: [144, 238, 144],
                    lightgrey: [211, 211, 211],
                    lightpink: [255, 182, 193],
                    lightsalmon: [255, 160, 122],
                    lightseagreen: [32, 178, 170],
                    lightskyblue: [135, 206, 250],
                    lightslategray: [119, 136, 153],
                    lightslategrey: [119, 136, 153],
                    lightsteelblue: [176, 196, 222],
                    lightyellow: [255, 255, 224],
                    lime: [0, 255, 0],
                    limegreen: [50, 205, 50],
                    linen: [250, 240, 230],
                    magenta: [255, 0, 255],
                    maroon: [128, 0, 0],
                    mediumaquamarine: [102, 205, 170],
                    mediumblue: [0, 0, 205],
                    mediumorchid: [186, 85, 211],
                    mediumpurple: [147, 112, 219],
                    mediumseagreen: [60, 179, 113],
                    mediumslateblue: [123, 104, 238],
                    mediumspringgreen: [0, 250, 154],
                    mediumturquoise: [72, 209, 204],
                    mediumvioletred: [199, 21, 133],
                    midnightblue: [25, 25, 112],
                    mintcream: [245, 255, 250],
                    mistyrose: [255, 228, 225],
                    moccasin: [255, 228, 181],
                    navajowhite: [255, 222, 173],
                    navy: [0, 0, 128],
                    oldlace: [253, 245, 230],
                    olive: [128, 128, 0],
                    olivedrab: [107, 142, 35],
                    orange: [255, 165, 0],
                    orangered: [255, 69, 0],
                    orchid: [218, 112, 214],
                    palegoldenrod: [238, 232, 170],
                    palegreen: [152, 251, 152],
                    paleturquoise: [175, 238, 238],
                    palevioletred: [219, 112, 147],
                    papayawhip: [255, 239, 213],
                    peachpuff: [255, 218, 185],
                    peru: [205, 133, 63],
                    pink: [255, 192, 203],
                    plum: [221, 160, 203],
                    powderblue: [176, 224, 230],
                    purple: [128, 0, 128],
                    red: [255, 0, 0],
                    rosybrown: [188, 143, 143],
                    royalblue: [65, 105, 225],
                    saddlebrown: [139, 69, 19],
                    salmon: [250, 128, 114],
                    sandybrown: [244, 164, 96],
                    seagreen: [46, 139, 87],
                    seashell: [255, 245, 238],
                    sienna: [160, 82, 45],
                    silver: [192, 192, 192],
                    skyblue: [135, 206, 235],
                    slateblue: [106, 90, 205],
                    slategray: [119, 128, 144],
                    slategrey: [119, 128, 144],
                    snow: [255, 255, 250],
                    springgreen: [0, 255, 127],
                    steelblue: [70, 130, 180],
                    tan: [210, 180, 140],
                    teal: [0, 128, 128],
                    thistle: [216, 191, 216],
                    tomato: [255, 99, 71],
                    turquoise: [64, 224, 208],
                    violet: [238, 130, 238],
                    wheat: [245, 222, 179],
                    white: [255, 255, 255],
                    whitesmoke: [245, 245, 245],
                    yellow: [255, 255, 0],
                    yellowgreen: [154, 205, 5],
                    transparent: [0, 0, 0, 0]
                }
            });

            Fashion.apply(Color.prototype, {
                type: 'color',
                $isFashionColor: true,
                $isFashionRGBA: false,
                $isFashionHSLA: false,
                $canUnbox: false
            });

            module.exports = Color;
        }, { "../Base.js": 3, "./Bool.js": 8, "./Numeric.js": 18, "./Type.js": 24 }], 10: [function (require, module, exports) {
            /*
             * Copyright (c) 2012-2016. Sencha Inc.
             */

            "use strict";

            var Fashion = require('../Base.js');
            var Color = require('./Color.js');
            var ColorRGBA = require('./ColorRGBA.js');

            var ColorHSLA = function (_Color) {
                _inherits(ColorHSLA, _Color);

                function ColorHSLA(h, s, l, a) {
                    _classCallCheck(this, ColorHSLA);

                    var _this5 = _possibleConstructorReturn(this, (ColorHSLA.__proto__ || Object.getPrototypeOf(ColorHSLA)).call(this));

                    _this5.h = Color.constrainDegrees(h);
                    _this5.s = s;
                    _this5.l = l;
                    if (a !== undefined) {
                        _this5.a = a;
                    }
                    return _this5;
                }

                _createClass(ColorHSLA, [{
                    key: "doVisit",
                    value: function doVisit(visitor) {
                        visitor.hsla(this);
                    }
                }, {
                    key: "operate",
                    value: function operate(operation, right) {
                        return this.getRGBA().operate(operation, right);
                    }
                }, {
                    key: "copy",
                    value: function copy() {
                        return new ColorHSLA(this.h, this.s, this.l, this.a);
                    }
                }, {
                    key: "getRGBA",
                    value: function getRGBA() {
                        return ColorRGBA.fromHSLA(this);
                    }
                }, {
                    key: "toString",
                    value: function toString() {
                        return this.getRGBA().toString();
                    }
                }, {
                    key: "add",
                    value: function add(h, s, l, a) {
                        return new ColorHSLA(Color.constrainDegrees(this.h + h), Color.constrainPercentage(this.s + s), Color.constrainPercentage(this.l + l), Color.constrainAlpha(this.a * a));
                    }
                }, {
                    key: "subtract",
                    value: function subtract(h, s, l) {
                        return this.add(-h, -s, -l);
                    }
                }, {
                    key: "adjustLightness",
                    value: function adjustLightness(percent) {
                        this.l = Color.constrainPercentage(this.l + percent);
                        return this;
                    }
                }, {
                    key: "adjustHue",
                    value: function adjustHue(deg) {
                        this.h = Color.constrainDegrees(this.h + deg);
                        return this;
                    }
                }], [{
                    key: "fromRGBA",
                    value: function fromRGBA(rgba) {
                        if (rgba.$isFashionHSLA) {
                            return rgba.clone();
                        }

                        var r = rgba.r / 255,
                            g = rgba.g / 255,
                            b = rgba.b / 255,
                            a = rgba.a,
                            max = Math.max(r, g, b),
                            min = Math.min(r, g, b),
                            delta = max - min,
                            h = 0,
                            s = 0,
                            l = 0.5 * (max + min);

                        // min==max means achromatic (hue is undefined)
                        if (min != max) {
                            s = l < 0.5 ? delta / (max + min) : delta / (2 - max - min);
                            if (r == max) {
                                h = 60 * (g - b) / delta;
                            } else if (g == max) {
                                h = 120 + 60 * (b - r) / delta;
                            } else {
                                h = 240 + 60 * (r - g) / delta;
                            }
                            if (h < 0) {
                                h += 360;
                            }
                            if (h >= 360) {
                                h -= 360;
                            }
                        }

                        return new ColorHSLA(Color.constrainDegrees(h), Color.constrainPercentage(s * 100), Color.constrainPercentage(l * 100), a);
                    }
                }]);

                return ColorHSLA;
            }(Color);

            ColorRGBA.prototype.getHSLA = function () {
                return ColorHSLA.fromRGBA(this);
            };

            Fashion.apply(ColorHSLA.prototype, {
                type: 'hsla',
                $isFashionHSLA: true,
                h: null,
                s: null,
                l: null,
                a: 1
            });

            module.exports = ColorHSLA;
        }, { "../Base.js": 3, "./Color.js": 9, "./ColorRGBA.js": 11 }], 11: [function (require, module, exports) {
            /*
             * Copyright (c) 2012-2016. Sencha Inc.
             */

            "use strict";

            var Fashion = require('../Base.js');
            var Color = require('./Color.js');

            function hex2(v) {
                var s = v.toString(16);
                if (s.length < 2) {
                    s = '0' + s;
                }
                return s;
            }

            var ColorRGBA = function (_Color2) {
                _inherits(ColorRGBA, _Color2);

                function ColorRGBA(r, g, b, a) {
                    _classCallCheck(this, ColorRGBA);

                    var _this6 = _possibleConstructorReturn(this, (ColorRGBA.__proto__ || Object.getPrototypeOf(ColorRGBA)).call(this));

                    _this6.r = Math.min(0xff, Math.max(0, r));
                    _this6.g = Math.min(0xff, Math.max(0, g));
                    _this6.b = Math.min(0xff, Math.max(0, b));
                    if (a !== undefined) {
                        _this6.a = Math.min(1.0, Math.max(0.0, a));
                    }
                    return _this6;
                }

                _createClass(ColorRGBA, [{
                    key: "doVisit",
                    value: function doVisit(visitor) {
                        visitor.rgba(this);
                    }
                }, {
                    key: "copy",
                    value: function copy() {
                        return new ColorRGBA(this.r, this.g, this.b, this.a);
                    }
                }, {
                    key: "getHSLA",
                    value: function getHSLA() {
                        return null;
                    }
                }, {
                    key: "stringify",
                    value: function stringify() {
                        var me = this,
                            round = Math.round,
                            r = round(me.r),
                            g = round(me.g),
                            b = round(me.b),
                            a = me.a,
                            stringified = '';

                        // If there is no transparency we will use hex value
                        if (a === 1) {
                            stringified = '#' + hex2(r) + hex2(g) + hex2(b);
                        } else {
                            // Else use rgba
                            stringified = 'rgba(' + r + ', ' + g + ', ' + b + ', ' + a + ')';
                        }

                        stringified = stringified.toLowerCase();
                        return stringified;
                    }
                }, {
                    key: "getCompressedValue",
                    value: function getCompressedValue(lowerVal) {
                        var name = ColorRGBA.stringifiedMap[lowerVal],
                            shortName = ColorRGBA.shortFormMap[lowerVal];

                        if (name) {
                            lowerVal = lowerVal.length > name.length ? name : lowerVal;
                        }

                        if (ColorRGBA.useShortValues && shortName) {
                            lowerVal = lowerVal.length > shortName.length ? shortName : lowerVal;
                        }

                        return lowerVal;
                    }
                }, {
                    key: "toString",
                    value: function toString() {
                        if (!this.stringified) {
                            this.stringified = this.getCompressedValue(this.stringify());
                        }
                        return this.stringified;
                    }
                }, {
                    key: "toIeHexStr",
                    value: function toIeHexStr() {
                        var me = this,
                            round = Math.round,
                            r = round(me.r),
                            g = round(me.g),
                            b = round(me.b),
                            a = round(0xff * me.a);

                        return '#' + hex2(a) + hex2(r) + hex2(g) + hex2(b);
                    }
                }, {
                    key: "add",
                    value: function add(r, g, b, a) {
                        return new ColorRGBA(this.r + r, this.g + g, this.b + b, this.a * a);
                    }
                }, {
                    key: "subtract",
                    value: function subtract(r, g, b) {
                        return new ColorRGBA(this.r - r, this.g - g, this.b - b, this.a);
                    }
                }, {
                    key: "multiply",
                    value: function multiply(number) {
                        return new ColorRGBA(this.r * number, this.g * number, this.b * number, this.a);
                    }
                }, {
                    key: "divide",
                    value: function divide(number) {
                        return new ColorRGBA(this.r / number, this.g / number, this.b / number, this.a);
                    }
                }], [{
                    key: "fromHex",
                    value: function fromHex(value) {
                        if (value.charAt(0) == '#') {
                            value = value.substr(1);
                        }

                        var r, g, b;

                        if (value.length === 3) {
                            r = parseInt(value.charAt(0), 16);
                            g = parseInt(value.charAt(1), 16);
                            b = parseInt(value.charAt(2), 16);

                            r = (r << 4) + r;
                            g = (g << 4) + g;
                            b = (b << 4) + b;
                        } else {
                            r = parseInt(value.substring(0, 2), 16);
                            g = parseInt(value.substring(2, 4), 16);
                            b = parseInt(value.substring(4, 6), 16);
                        }

                        var result = new ColorRGBA(r, g, b);
                        if (ColorRGBA.preserveInputStrings) {
                            result.stringified = "#" + value;
                        }
                        return result;
                    }
                }, {
                    key: "fromHSLA",
                    value: function fromHSLA(color) {
                        if (color.$isFashionRGBA) {
                            return color.clone();
                        }

                        var hsla = color,
                            h = hsla.h / 360,
                            s = hsla.s / 100,
                            l = hsla.l / 100,
                            a = hsla.a;

                        var m2 = l <= 0.5 ? l * (s + 1) : l + s - l * s,
                            m1 = l * 2 - m2;

                        function hue(h) {
                            if (h < 0) ++h;
                            if (h > 1) --h;
                            if (h * 6 < 1) return m1 + (m2 - m1) * h * 6;
                            if (h * 2 < 1) return m2;
                            if (h * 3 < 2) return m1 + (m2 - m1) * (2 / 3 - h) * 6;
                            return m1;
                        }

                        var r = Color.constrainChannel(hue(h + 1 / 3) * 0xff),
                            g = Color.constrainChannel(hue(h) * 0xff),
                            b = Color.constrainChannel(hue(h - 1 / 3) * 0xff);

                        return new ColorRGBA(r, g, b, a);
                    }
                }]);

                return ColorRGBA;
            }(Color);

            Fashion.apply(ColorRGBA, {
                stringifiedMap: {
                    'rgba(0, 0, 0, 0)': 'transparent'
                },

                shortFormMap: {},

                useShortValues: true,
                preserveInputStrings: false
            });

            Fashion.apply(ColorRGBA.prototype, {
                type: 'rgba',
                $isFashionRGBA: true,
                r: null,
                g: null,
                b: null,
                a: 1,
                stringified: null,

                "+.number": function number(right) {
                    var value = right.value,
                        unit = right.unit;

                    switch (unit) {
                        case '%':
                            return this.getHSLA().adjustLightness(value).getRGBA();
                        case 'deg':
                            return this.getHSLA().adjustHue(value).getRGBA();
                        default:
                            return this.add(value, value, value, 1);
                    }
                },

                "+.rgba": function rgba(right) {
                    return this.add(right.r, right.g, right.b, right.a);
                },

                "+.hsla": function hsla(right) {
                    return this.getHSLA().add(right.h, right.s, right.l);
                },

                "-.number": function number(right) {
                    var value = right.value,
                        unit = right.unit;
                    switch (unit) {
                        case '%':
                            return this.getHSLA().adjustLightness(-value).getRGBA();
                        case 'deg':
                            return this.getHSLA().adjustHue(-value).getRGBA();
                        default:
                            return this.subtract(value, value, value);
                    }
                },

                "-.rgba": function rgba(right) {
                    return this.subtract(right.r, right.g, right.b);
                },

                "-.hsla": function hsla(right) {
                    return this.getHSLA().subtract(right.h, right.s, right.l);
                },

                "*.number": function number(right) {
                    return this.multiply(right.value);
                },

                "/.number": function number(right) {
                    return this.divide(right.value);
                },

                "*.rgba": function rgba(right) {
                    return new ColorRGBA(this.r * right.r, this.g * right.g, this.b * right.b, this.a * right.a);
                },

                "/.rgba": function rgba(right) {
                    return new ColorRGBA(Math.floor(this.r / right.r), Math.floor(this.g / right.g), Math.floor(this.b / right.b), Math.floor(this.a / right.a));
                }
            });

            module.exports = ColorRGBA;

            (function (ColorRGBA, stringifiedMap, colorMap, shortMap) {
                var colorChars = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'a', 'b', 'c', 'd', 'e', 'f'],
                    names = Object.keys(colorMap),
                    i;

                names.sort();
                for (i = 0; i < names.length; i++) {
                    var name = names[i],
                        val = colorMap[name],
                        color = new ColorRGBA(val[0], val[1], val[2], val[3]),
                        str = color.stringify();

                    stringifiedMap[str] = name;
                }

                colorChars.forEach(function (short1) {
                    var long1 = short1 + short1;
                    colorChars.forEach(function (short2) {
                        var long2 = short2 + short2;
                        colorChars.forEach(function (short3) {
                            var long3 = short3 + short3,
                                shortName = '#' + short1 + short2 + short3,
                                longName = '#' + long1 + long2 + long3;

                            if (shortMap[longName]) {
                                var curr = shortMap[longName];
                                shortName = curr.length > shortName.length ? shortName : curr;
                                //if(curr.indexOf("#") === 0) {
                                //    short = (curr.length > short.length) ? short : curr;
                                //} else {
                                //    short = curr;
                                //}
                            }
                            shortMap[longName] = shortName;
                        });
                    });
                });
            })(ColorRGBA, ColorRGBA.stringifiedMap, Color.map, ColorRGBA.shortFormMap);
        }, { "../Base.js": 3, "./Color.js": 9 }], 12: [function (require, module, exports) {
            /*
             * Copyright (c) 2012-2016. Sencha Inc.
             */

            "use strict";

            var Fashion = require('../Base.js');
            var Type = require('./Type.js');
            var Numeric = require('./Numeric.js');

            var ColorStop = function (_Type3) {
                _inherits(ColorStop, _Type3);

                function ColorStop(color, stop) {
                    _classCallCheck(this, ColorStop);

                    var _this7 = _possibleConstructorReturn(this, (ColorStop.__proto__ || Object.getPrototypeOf(ColorStop)).call(this));

                    _this7.color = color;
                    _this7.stop = stop;
                    return _this7;
                }

                _createClass(ColorStop, [{
                    key: "doVisit",
                    value: function doVisit(visitor) {
                        visitor.colorstop(this);
                    }
                }, {
                    key: "descend",
                    value: function descend(visitor) {
                        visitor.visit(this.color);
                        visitor.visit(this.stop);
                    }
                }, {
                    key: "toString",
                    value: function toString() {
                        var string = this.color.toString(),
                            stop = this.stop;

                        if (stop) {
                            stop = stop.copy();
                            string += ' ';
                            if (!stop.unit) {
                                stop.value *= 100;
                                stop.unit = '%';
                            }
                            string += stop.toString();
                        }

                        return string;
                    }
                }, {
                    key: "toOriginalWebkitString",
                    value: function toOriginalWebkitString() {
                        var stop = this.stop;

                        if (!stop) {
                            stop = new Numeric(0, '%');
                        }

                        stop = stop.copy();
                        if (!stop.unit) {
                            stop.value *= 100;
                            stop.unit = '%';
                        }

                        return 'color-stop(' + stop.toString() + ', ' + this.color.toString() + ')';
                    }
                }, {
                    key: "copy",
                    value: function copy() {
                        return new ColorStop(this.color && this.color.clone(), this.stop && this.stop.clone());
                    }
                }]);

                return ColorStop;
            }(Type);

            Fashion.apply(ColorStop.prototype, {
                type: 'colorstop',
                $isFashionColorStop: true,
                $canUnbox: false,
                color: null,
                stop: null
            });

            module.exports = ColorStop;
        }, { "../Base.js": 3, "./Numeric.js": 18, "./Type.js": 24 }], 13: [function (require, module, exports) {
            /*
             * Copyright (c) 2012-2016. Sencha Inc.
             */

            "use strict";

            var Fashion = require('../Base.js');
            var Type = require('./Type.js');
            var List = require('./List.js');

            var FunctionCall = function (_Type4) {
                _inherits(FunctionCall, _Type4);

                function FunctionCall(name, args) {
                    _classCallCheck(this, FunctionCall);

                    var _this8 = _possibleConstructorReturn(this, (FunctionCall.__proto__ || Object.getPrototypeOf(FunctionCall)).call(this));

                    _this8.name = name;
                    if (Array.isArray(args)) {
                        args = new List(args);
                    }
                    _this8.args = args;
                    return _this8;
                }

                _createClass(FunctionCall, [{
                    key: "toString",
                    value: function toString() {
                        var args = this.args,
                            argsStr;
                        if (Array.isArray(args)) {
                            argsStr = args.join(', ');
                        } else {
                            argsStr = args.toString();
                        }
                        return this.name + "(" + argsStr + ')';
                    }
                }, {
                    key: "doVisit",
                    value: function doVisit(visitor) {
                        visitor.functioncall(this);
                    }
                }, {
                    key: "descend",
                    value: function descend(visitor) {
                        this.args && visitor.visit(this.args);
                    }
                }, {
                    key: "copy",
                    value: function copy() {
                        return new FunctionCall(this.name, this.args && this.args.copy());
                    }
                }]);

                return FunctionCall;
            }(Type);

            Fashion.apply(FunctionCall.prototype, {
                type: 'functioncall',
                $isFashionFunctionCall: true,
                $canUnbox: false,
                name: null,
                args: null
            });

            module.exports = FunctionCall;
        }, { "../Base.js": 3, "./List.js": 15, "./Type.js": 24 }], 14: [function (require, module, exports) {
            /*
             * Copyright (c) 2012-2016. Sencha Inc.
             */

            "use strict";

            var Fashion = require('../Base.js');
            var Type = require('./Type.js');

            var LinearGradient = function (_Type5) {
                _inherits(LinearGradient, _Type5);

                function LinearGradient(position, stops) {
                    _classCallCheck(this, LinearGradient);

                    var _this9 = _possibleConstructorReturn(this, (LinearGradient.__proto__ || Object.getPrototypeOf(LinearGradient)).call(this));

                    _this9.position = position;
                    _this9.stops = stops;
                    return _this9;
                }

                _createClass(LinearGradient, [{
                    key: "doVisit",
                    value: function doVisit(visitor) {
                        visitor.lineargradient(this);
                    }
                }, {
                    key: "descend",
                    value: function descend(visitor) {
                        visitor.visit(this.position);
                        visitor.visit(this.stops);
                    }
                }, {
                    key: "copy",
                    value: function copy() {
                        return new LinearGradient(this.position && this.position.clone(), this.stops && this.stops.clone());
                    }
                }, {
                    key: "gradientPoints",
                    value: function gradientPoints(position) {}
                }, {
                    key: "operate",
                    value: function operate(operation, right) {
                        switch (operation) {
                            case "!=":
                                if (right.type == 'literal' && (right.value == 'null' || right.value == 'none')) {
                                    return true;
                                }
                            case "==":
                                if (right.type == 'literal' && (right.value == 'null' || right.value == 'none')) {
                                    return false;
                                }
                        }
                        return _get(LinearGradient.prototype.__proto__ || Object.getPrototypeOf(LinearGradient.prototype), "operate", this).call(this, operation, right);
                    }
                }, {
                    key: "supports",
                    value: function supports(prefix) {
                        return !!this.vendorPrefixes[prefix.toLowerCase()];
                    }
                }, {
                    key: "toString",
                    value: function toString() {
                        var string = 'linear-gradient(';
                        if (this.position) {
                            string += this.position + ', ';
                        }
                        return string + this.stops + ')';
                    }
                }, {
                    key: "toOriginalWebkitString",
                    value: function toOriginalWebkitString() {
                        // args = []
                        // args << grad_point(position_or_angle || Sass::Script::String.new("top"))
                        // args << linear_end_position(position_or_angle, color_stops)
                        // args << grad_color_stops(color_stops)
                        // args.each{|a| a.options = options}
                        // Sass::Script::String.new("-webkit-gradient(linear, #{args.join(', ')})")
                        //this.gradientPoints(this.position);
                        var args = [],
                            stops = this.stops.items,
                            ln = stops.length,
                            i;

                        args.push('top');
                        args.push('bottom');

                        for (i = 0; i < ln; i++) {
                            args.push(stops[i].toOriginalWebkitString());
                        }

                        return '-webkit-gradient(linear, ' + args.join(', ') + ')';
                    }
                }, {
                    key: "toPrefixedString",
                    value: function toPrefixedString(prefix) {
                        if (prefix === 'owg') {
                            return this.toOriginalWebkitString();
                        }
                        return prefix + this.toString();
                    }
                }]);

                return LinearGradient;
            }(Type);

            Fashion.apply(LinearGradient.prototype, {
                type: 'lineargradient',
                $isFashionLinearGradient: true,
                $canUnbox: false,
                position: null,
                stops: null,
                vendorPrefixes: {
                    webkit: true,
                    moz: true,
                    svg: true,
                    pie: true,
                    css2: true,
                    o: true,
                    owg: true
                }
            });

            module.exports = LinearGradient;
        }, { "../Base.js": 3, "./Type.js": 24 }], 15: [function (require, module, exports) {
            /*
             * Copyright (c) 2012-2016. Sencha Inc.
             */

            "use strict";

            var Fashion = require('../Base.js');
            var Type = require('./Type.js');

            var List = function (_Type6) {
                _inherits(List, _Type6);

                function List(items, separator) {
                    _classCallCheck(this, List);

                    var _this10 = _possibleConstructorReturn(this, (List.__proto__ || Object.getPrototypeOf(List)).call(this));

                    _this10.items = items || [];
                    _this10.separator = typeof separator === 'undefined' ? ' ' : separator;
                    return _this10;
                }

                _createClass(List, [{
                    key: "doVisit",
                    value: function doVisit(visitor) {
                        visitor.list(this);
                    }
                }, {
                    key: "descend",
                    value: function descend(visitor) {
                        for (var i = 0; i < this.items.length; i++) {
                            visitor.visit(this.items[i]);
                        }
                    }
                }, {
                    key: "copy",
                    value: function copy() {
                        var items = this.items,
                            len = items.length,
                            newItems = [];
                        for (var i = 0; i < len; i++) {
                            newItems.push(items[i].clone());
                        }
                        return new List(newItems, this.separator);
                    }
                }, {
                    key: "clone",
                    value: function clone(match, replace) {
                        if (replace && this.matches(match)) {
                            return replace.clone();
                        }
                        var items = this.items,
                            len = items.length,
                            newItems = [];

                        for (var i = 0; i < len; i++) {
                            var item = items[i];
                            if (item) {
                                newItems.push(item.clone(match, replace));
                            } else {
                                newItems.push(item);
                            }
                        }

                        var copy = new List(newItems, this.separator);
                        copy.$referenceName = this.$referenceName;
                        copy.$referenceBase = this.$referenceBase;
                        copy.$previousReference = this.$previousReference;
                        return copy;
                    }
                }, {
                    key: "add",
                    value: function add(item) {
                        return this.items.push(item);
                    }
                }, {
                    key: "get",
                    value: function get(index) {
                        return this.items[index - 1] || null;
                    }
                }, {
                    key: "operate",
                    value: function operate(operation, right) {
                        switch (operation) {
                            case '!=':
                                if (right.$isFashionLiteral) {
                                    if (right.value === 'null' || right.value === 'none') {
                                        return true;
                                    }
                                }
                                break;

                            case '==':
                                if (right.$isFashionLiteral) {
                                    if (right.value === 'null' || right.value === 'none') {
                                        return false;
                                    }
                                }
                                break;
                        }

                        return _get(List.prototype.__proto__ || Object.getPrototypeOf(List.prototype), "operate", this).call(this, operation, right);
                    }
                }, {
                    key: "supports",
                    value: function supports(prefix) {
                        for (var i = 0; i < this.items.length; i++) {
                            var item = this.items[i];

                            if (item.supports(prefix)) {
                                return true;
                            }
                        }

                        return false;
                    }
                }, {
                    key: "toBoolean",
                    value: function toBoolean() {
                        return !!this.items.length;
                    }
                }, {
                    key: "getItems",
                    value: function getItems() {
                        return this.items;
                        // return Fashion.filter(this.items, (item) => {
                        //     var unboxed = Type.unbox(item);
                        //     return unboxed !== null && unboxed !== undefined;
                        // });
                    }
                }, {
                    key: "toString",
                    value: function toString() {
                        return this.items.join(this.separator);
                    }
                }, {
                    key: "unquote",
                    value: function unquote() {
                        var items = [],
                            item;
                        for (var i = 0; i < this.items.length; i++) {
                            item = this.items[i];
                            if (item) {
                                items.push(item.unquote());
                            } else {
                                items.push(item);
                            }
                        }
                        return new List(items, this.separator);
                    }
                }, {
                    key: "toPrefixedString",
                    value: function toPrefixedString(prefix) {
                        var items = [];
                        for (var i = 0; i < this.items.length; i++) {
                            var item = this.items[i];
                            if (item) {
                                items.push(item.toPrefixedString(prefix));
                            }
                        }
                        return items.join(this.separator);
                    }

                    //----------------------------------------------------------------------
                    // Operations

                }, {
                    key: '==.list',
                    value: function list(right) {
                        var equals = this.separator == right.separator && this.items.length == right.items.length;

                        for (var i = 0; equals && i < this.items.length; ++i) {
                            equals = this.items[i].operate("==", right.items[i]);
                        }

                        return equals;
                    }
                }]);

                return List;
            }(Type);

            Fashion.apply(List.prototype, {
                type: 'list',
                $isFashionList: true,
                items: null,
                separator: null
            });

            module.exports = List;
        }, { "../Base.js": 3, "./Type.js": 24 }], 16: [function (require, module, exports) {
            /*
             * Copyright (c) 2012-2016. Sencha Inc.
             */

            "use strict";

            var Fashion = require('../Base.js');
            var Type = require('./Type.js');
            var Numeric = require('./Numeric.js');

            var Literal = function (_Type7) {
                _inherits(Literal, _Type7);

                function Literal(value) {
                    _classCallCheck(this, Literal);

                    var _this11 = _possibleConstructorReturn(this, (Literal.__proto__ || Object.getPrototypeOf(Literal)).call(this));

                    _this11.value = value;
                    return _this11;
                }

                _createClass(Literal, [{
                    key: "doVisit",
                    value: function doVisit(visitor) {
                        visitor.literal(this);
                    }
                }, {
                    key: "_getHash",
                    value: function _getHash() {
                        return this.value;
                    }
                }, {
                    key: "toString",
                    value: function toString() {
                        return this.value || '';
                    }
                }, {
                    key: "toBoolean",
                    value: function toBoolean() {
                        return this.value.length;
                    }
                }, {
                    key: "copy",
                    value: function copy() {
                        return new Literal(this.value);
                    }
                }, {
                    key: '+',
                    value: function _(right) {
                        return new Literal(this.value + right.getHash());
                    }
                }, {
                    key: '+.number',
                    value: function number(right) {
                        if (this.value === null) {
                            return right;
                        }
                        return new Literal(this.value + right.toString());
                    }
                }, {
                    key: '/',
                    value: function _(right) {
                        return new Literal(this.value + '/' + right.getHash());
                    }
                }, {
                    key: '-',
                    value: function _(right) {
                        return new Literal(this.value + '-' + right.getHash());
                    }
                }, {
                    key: '%',
                    value: function _(right) {
                        return new Literal(this.value + '%' + right.getHash());
                    }
                }, {
                    key: "normalizeStart",
                    value: function normalizeStart(startVal) {
                        var start = Type.unbox(startVal) || 0;
                        if (start > 0) {
                            start = start - 1;
                        }

                        if (start < 0) {
                            start = this.value.length + start;
                        }

                        if (start < 0) {
                            start = 0;
                        }

                        return start;
                    }
                }, {
                    key: "normalizeEnd",
                    value: function normalizeEnd(endVal) {
                        var end = Type.unbox(endVal) || -1;
                        if (end > 0) {
                            end = end - 1;
                        }
                        if (end < 0) {
                            end = this.value.length + end;
                        }

                        if (end < 0) {
                            end = 0;
                        }

                        if (end > 0) {
                            end = end + 1;
                        }
                        return end;
                    }
                }, {
                    key: "slice",
                    value: function slice(start, end) {
                        start = this.normalizeStart(start);
                        end = this.normalizeEnd(end);
                        return new Literal(this.value.slice(start, end));
                    }
                }, {
                    key: "toUpperCase",
                    value: function toUpperCase() {
                        return new Literal(this.value.toUpperCase());
                    }
                }, {
                    key: "toLowerCase",
                    value: function toLowerCase() {
                        return new Literal(this.value.toLowerCase());
                    }
                }, {
                    key: "indexOf",
                    value: function indexOf(str) {
                        var idx = this.value.indexOf(str.value);
                        if (idx === -1) {
                            return undefined;
                        }
                        return new Numeric(idx + 1);
                    }
                }, {
                    key: "insert",
                    value: function insert(str, startVal) {
                        var start = Type.unbox(startVal) || 0,
                            inserted = this.value;

                        if (start > 0) {
                            start = Math.min(start - 1, inserted.length);
                        }
                        if (start < 0) {
                            start = inserted.length + start + 1;
                            start = Math.max(start, 0);
                        }

                        inserted = inserted.substring(0, start) + str.value + inserted.substring(start);
                        return new Literal(Literal.deEscape(inserted));
                    }
                }, {
                    key: "toDisplayString",
                    value: function toDisplayString() {
                        var val = this.value;
                        if (val === null) {
                            return "null";
                        }
                        return this.toString();
                    }
                }], [{
                    key: "tryCoerce",
                    value: function tryCoerce(obj) {
                        if (obj.$isFashionNumber) {
                            return undefined;
                        }
                        if (obj.$isFashionString) {
                            return new Literal(obj.value);
                        }
                        if (obj.$isFashionLiteral) {
                            return obj;
                        }
                        return new Literal(obj.getHash());
                    }
                }, {
                    key: "deEscape",
                    value: function deEscape(str) {
                        var buff = '',
                            i,
                            ch;
                        for (i = 0; i < str.length; i++) {
                            ch = str.charAt(i);
                            if (ch === '\\') {
                                i++;
                                ch = str.charAt(i);
                            }
                            buff += ch;
                        }
                        return buff;
                    }
                }]);

                return Literal;
            }(Type);

            Fashion.apply(Literal.prototype, {
                type: 'literal',
                $isFashionLiteral: true,
                value: null
            });

            var FashionNull = function (_Literal) {
                _inherits(FashionNull, _Literal);

                function FashionNull(value) {
                    _classCallCheck(this, FashionNull);

                    return _possibleConstructorReturn(this, (FashionNull.__proto__ || Object.getPrototypeOf(FashionNull)).call(this, value || null));
                }

                _createClass(FashionNull, [{
                    key: "copy",
                    value: function copy() {
                        return new FashionNull(this.value);
                    }
                }]);

                return FashionNull;
            }(Literal);

            Fashion.apply(FashionNull.prototype, {
                $isFashionNull: true,
                $constant: true
            });

            FashionNull.prototype.$isFashionNull = true;

            Literal.Null = new FashionNull(null);
            Literal.None = new Literal('none');

            module.exports = Literal;
        }, { "../Base.js": 3, "./Numeric.js": 18, "./Type.js": 24 }], 17: [function (require, module, exports) {
            /*
             * Copyright (c) 2012-2016. Sencha Inc.
             */

            "use strict";

            var Fashion = require('../Base.js');
            var List = require('./List.js');
            var Type = require('./Type.js');
            var Literal = require('./Literal.js'),
                Null = Literal.Null;

            var Numeric = require('./Numeric.js');

            var Map = function (_List) {
                _inherits(Map, _List);

                function Map(pairs) {
                    _classCallCheck(this, Map);

                    var _this13 = _possibleConstructorReturn(this, (Map.__proto__ || Object.getPrototypeOf(Map)).call(this, pairs));

                    _this13.map = {};
                    if (pairs) {
                        for (var i = 0; i < pairs.length - 1; i += 2) {
                            var key = _this13.toKey(pairs[i]),
                                value = pairs[i + 1];
                            _this13.map[key] = i + 1;
                        }
                    }
                    return _this13;
                }

                _createClass(Map, [{
                    key: "doVisit",
                    value: function doVisit(visitor) {
                        visitor.map(this);
                    }
                }, {
                    key: "descend",
                    value: function descend(visitor) {
                        for (var i = 0; i < this.items.length; i++) {
                            visitor.visit(this.items[i]);
                        }
                    }
                }, {
                    key: "get",
                    value: function get(key) {
                        if (key instanceof Numeric) {
                            key = Type.unbox(key);
                        }

                        if (typeof key === 'number') {
                            return new List([this.items[2 * key - 2], this.items[2 * key - 1]], ' ');
                        }

                        key = this.toKey(key);
                        return this.items[this.map[key]] || Null;
                    }
                }, {
                    key: "getItems",
                    value: function getItems() {
                        var values = [];
                        for (var i = 0; i < this.items.length - 1; i += 2) {
                            var key = this.toKey(this.items[i]);
                            values.push(this.map[key]);
                        }
                        return values;
                    }
                }, {
                    key: "put",
                    value: function put(key, value) {
                        var keyStr = this.toKey(key);
                        if (!this.map.hasOwnProperty(keyStr)) {
                            this.items.push(key, value);
                            this.map[keyStr] = this.items.length - 1;
                        } else {
                            this.items[this.map[keyStr]] = value;
                        }
                    }
                }, {
                    key: "toString",
                    value: function toString() {
                        var str = '',
                            count = 0;
                        for (var i = 0; i < this.items.length - 1; i += 2) {
                            var key = this.toKey(this.items[i]),
                                value = this.map[key];
                            if (value) {
                                if (count > 0) {
                                    str += ', ';
                                }
                                str += key + ": " + value.toString();
                                count++;
                            }
                        }
                        return str;
                    }
                }, {
                    key: "toKey",
                    value: function toKey(key) {
                        return this.unquoteKey(key).toString();
                    }
                }, {
                    key: "unquoteKey",
                    value: function unquoteKey(string) {
                        if (string.$isFashionType) {
                            return string.unquote();
                        }
                        return string;
                    }
                }, {
                    key: "remove",
                    value: function remove(key) {
                        key = this.toKey(key);
                        if (this.map[key]) {
                            var idx = this.map[key];
                            delete this.items[idx - 1];
                            delete this.items[idx];
                            delete this.map[key];
                        }
                    }
                }, {
                    key: "getKeys",
                    value: function getKeys() {
                        var keys = [];
                        for (var i = 0; i < this.items.length; i += 2) {
                            var k = this.items[i];
                            if (k) {
                                keys.push(k);
                            }
                        }
                        return keys;
                    }
                }, {
                    key: "getValues",
                    value: function getValues() {
                        var values = [];
                        for (var i = 1; i < this.items.length; i += 2) {
                            var v = this.items[i];
                            if (v) {
                                values.push(v);
                            }
                        }
                        return values;
                    }
                }, {
                    key: "hasKey",
                    value: function hasKey(key) {
                        key = this.toKey(key);
                        if (this.map.hasOwnProperty(key)) {
                            return true;
                        }
                        return false;
                    }
                }]);

                return Map;
            }(List);

            Fashion.apply(Map.prototype, {
                type: "map",
                $isFashionMap: true,
                $canUnbox: false,
                map: null
            });

            module.exports = Map;
        }, { "../Base.js": 3, "./List.js": 15, "./Literal.js": 16, "./Numeric.js": 18, "./Type.js": 24 }], 18: [function (require, module, exports) {
            /*
             * Copyright (c) 2012-2016. Sencha Inc.
             */

            "use strict";

            var Fashion = require('../Base.js');
            var Type = require('./Type.js');
            var Bool = require('./Bool.js');
            var Literal = require('./Literal.js');

            var Numeric = function (_Type8) {
                _inherits(Numeric, _Type8);

                function Numeric(value, unit, numeratorUnits, denominatorUnits) {
                    _classCallCheck(this, Numeric);

                    var _this14 = _possibleConstructorReturn(this, (Numeric.__proto__ || Object.getPrototypeOf(Numeric)).call(this));

                    _this14.value = value;
                    _this14.unit = unit;
                    if (unit && !numeratorUnits) {
                        _this14.numeratorUnits = [unit];
                    } else {
                        _this14.numeratorUnits = numeratorUnits || [];
                    }
                    _this14.denominatorUnits = denominatorUnits || [];
                    return _this14;
                }

                _createClass(Numeric, [{
                    key: "doVisit",
                    value: function doVisit(visitor) {
                        visitor.number(this);
                    }
                }, {
                    key: "unitless",
                    value: function unitless() {
                        if (this.numeratorUnits && this.numeratorUnits.length) {
                            return false;
                        }

                        if (this.denominatorUnits && this.denominatorUnits.length) {
                            return false;
                        }

                        return true;
                    }
                }, {
                    key: "getUnitStr",
                    value: function getUnitStr() {
                        this.normalizeUnits();
                        var unitStr = this.numeratorUnits.join('*');
                        if (this.denominatorUnits.length) {
                            unitStr += '/' + this.denominatorUnits.join('*');
                        }
                        return unitStr;
                    }
                }, {
                    key: "_getHash",
                    value: function _getHash() {
                        return this.value;
                    }
                }, {
                    key: "stringify",
                    value: function stringify() {
                        this.normalizeUnits();

                        var value = this.value,
                            valStr;

                        // prevent 0.020000000000000004 type numbers in output
                        valStr = Math.round(value * 100000) / 100000 + '';
                        //unitStr = valStr === '0' ? '' : this.getUnitStr();
                        return valStr + this.getUnitStr();
                    }
                }, {
                    key: "toString",
                    value: function toString() {
                        return this.stringify();
                    }
                }, {
                    key: "toBoolean",
                    value: function toBoolean() {
                        return this.unit ? true : !!this.value;
                    }
                }, {
                    key: "copy",
                    value: function copy() {
                        return new Numeric(this.value, this.unit);
                    }
                }, {
                    key: '-.literal',
                    value: function literal(right) {
                        if (this.value === 0 && this.unitless()) {
                            return new Literal(['-', right.toString()].join(''));
                        }
                        return new Literal([this.toString(), '-', right.toString()].join(''));
                    }
                }, {
                    key: '-.string',
                    value: function string(right) {
                        if (this.value === 0 && this.unitless()) {
                            return new Literal(['-', right.toString()].join(''));
                        }
                        return new Literal([this.toString(), '-', right.toString()].join(''));
                    }
                }, {
                    key: '-.number',
                    value: function number(right) {
                        var value = right.value;

                        if (right.unit == '%' && right.unit !== this.unit) {
                            value = this.value * (right.value / 100);
                        }

                        return new Numeric(this.value - value, this.unit || right.unit);
                    }
                }, {
                    key: '+.literal',
                    value: function literal(right) {
                        if (right.$isFashionString) {
                            return new Literal([this.toString(), right.value].join(''));
                        }

                        return new Literal([this.toString(), right.toString()].join(''));
                    }
                }, {
                    key: '+.number',
                    value: function number(right) {
                        var value = right.value;

                        if (right.unit == '%' && right.unit !== this.unit) {
                            value = this.value * (right.value / 100);
                        }

                        return new Numeric(this.value + value, this.unit || right.unit);
                    }
                }, {
                    key: '/',
                    value: function _(right) {
                        return new Numeric(this.value / right.value, this.unit == right.unit ? null : this.unit || right.unit);
                    }
                }, {
                    key: '*',
                    value: function _(right) {
                        return new Numeric(this.value * right.value, this.unit || right.unit);
                    }
                }, {
                    key: '%',
                    value: function _(right) {
                        return new Numeric(this.value % right.value, this.unit || right.unit);
                    }
                }, {
                    key: '**',
                    value: function _(right) {
                        return new Numeric(Math.pow(this.value, right.value), this.unit || right.unit);
                    }
                }, {
                    key: "operate",
                    value: function operate(operation, right) {
                        var unit = this.unit || right.unit,
                            normalized;

                        if (right.$isFashionRGBA || right.$isFashionHSLA) {
                            return new Literal(this + operation + right);
                        }

                        if (right.$isFashionNumber) {
                            return this.numericOperate(operation, right);
                        } else if (right.$isFashionLiteral) {
                            normalized = this.tryCoerce(right);

                            if (normalized) {
                                return this.performOperation(operation, normalized);
                            }
                        }

                        return _get(Numeric.prototype.__proto__ || Object.getPrototypeOf(Numeric.prototype), "operate", this).call(this, operation, right);
                    }
                }, {
                    key: "tryNormalize",
                    value: function tryNormalize(other) {
                        var value = other.value,
                            unit = other.unit;

                        if (other.$isFashionNumber) {
                            switch (this.unit) {
                                case 'mm':
                                    switch (unit) {
                                        case 'in':
                                            return new Numeric(value * 25.4, 'mm');
                                        case 'cm':
                                            return new Numeric(value * 2.54, 'mm');
                                    }
                                    break;

                                case 'cm':
                                    switch (unit) {
                                        case 'in':
                                            return new Numeric(value * 2.54, 'cm');
                                        case 'mm':
                                            return new Numeric(value / 10, 'cm');
                                    }
                                    break;

                                case 'in':
                                    switch (unit) {
                                        case 'mm':
                                            return new Numeric(value / 25.4, 'in');
                                        case 'cm':
                                            return new Numeric(value / 2.54, 'in');
                                    }
                                    break;

                                case 'ms':
                                    switch (unit) {
                                        case 's':
                                            return new Numeric(value * 1000, 'ms');
                                    }
                                    break;

                                case 's':
                                    switch (unit) {
                                        case 'ms':
                                            return new Numeric(value / 1000, 's');
                                    }
                                    break;

                                case 'Hz':
                                    switch (unit) {
                                        case 'kHz':
                                            return new Numeric(value * 1000, 'Hz');
                                    }
                                    break;

                                case 'kHz':
                                    switch (unit) {
                                        case 'Hz':
                                            return new Numeric(value / 1000, 'kHz');
                                    }
                                    break;
                                case '%':
                                    switch (unit) {
                                        default:
                                            return new Numeric(value);
                                    }
                                default:
                                    break;
                            }
                        }

                        return undefined;
                    }
                }, {
                    key: "normalize",
                    value: function normalize(other) {
                        var norm = this.tryNormalize(other);

                        if (norm === undefined) {
                            raise('Could not normalize ' + this + ' with ' + other);
                        }

                        return norm;
                    }
                }, {
                    key: "comparable",
                    value: function comparable(other) {
                        var unit1 = this.unit,
                            unit2 = other.unit;

                        if (!other.$isFashionNumber) {
                            return false;
                        }

                        return unit1 === unit2 || unit1 === 'mm' && (unit2 === 'in' || unit2 === 'cm') || unit1 === 'cm' && (unit2 === 'in' || unit2 === 'mm') || unit1 === 'in' && (unit2 === 'mm' || unit2 === 'cm') || unit1 === 'ms' && unit2 === 's' || unit1 === 's' && unit2 === 'ms' || unit1 === 'Hz' && unit2 === 'kHz' || unit1 === 'kHz' && unit2 === 'Hz';
                    }

                    //---------------------------------------------------------------

                }, {
                    key: "normalizeUnits",
                    value: function normalizeUnits() {
                        if (this.normalized) {
                            return;
                        }

                        this.normalized = true;

                        if (!this.unitless()) {
                            var clean = this.removeCommonUnits(this.numeratorUnits, this.denominatorUnits),
                                converted;

                            //var num = [],
                            //    den = [];
                            //
                            //for(var d = 0; d < clean.den.length; d++) {
                            //    var dn = clean.den[d];
                            //    if(this.convertable(dn)) {
                            //        converted = false;
                            //        for (var n = 0; n < clean.num.length; n++) {
                            //            var nm = clean.num[n];
                            //            if(this.convertable(nm)) {
                            //                this.value = this.value / this.conversionFactor(dn, nm);
                            //                converted = true;
                            //            } else {
                            //                num.push(nm);
                            //            }
                            //        }
                            //        if(!converted) {
                            //            den.push(dn);
                            //        }
                            //    }
                            //}
                            //
                            //this.numeratorUnits = num;
                            //this.denominatorUnits = den;

                            clean.num = Fashion.filter(clean.num, function (val) {
                                return !!val;
                            });
                            clean.den = Fashion.filter(clean.den, function (val) {
                                return !!val;
                            });
                            this.numeratorUnits = clean.num;
                            this.denominatorUnits = clean.den;
                        }
                    }
                }, {
                    key: "numericOperate",
                    value: function numericOperate(operation, right) {
                        this.normalizeUnits();
                        right.normalizeUnits();

                        var me = this,
                            other = right,
                            ops = Numeric.OPERATIONS,
                            moreOps = Numeric.NON_COERCE_OPERATIONS,
                            op = ops[operation],
                            result;

                        if (op) {
                            try {
                                if (me.unitless()) {
                                    me = me.coerceUnits(other.numeratorUnits, other.denominatorUnits);
                                } else {
                                    other = other.coerceUnits(me.numeratorUnits, me.denominatorUnits);
                                }
                            } catch (e) {
                                if (operation == '==') {
                                    return Bool.False;
                                }
                                if (operation == '!=') {
                                    return Bool.True;
                                }
                                throw e;
                            }
                        } else {
                            op = moreOps[operation];
                        }

                        if (op) {
                            result = op(me.value, other.value);
                        }

                        if (typeof result === 'number') {
                            var units = this.computeUnits(me, other, operation);
                            return new Numeric(result, units.num.length ? units.num[0] : null, units.num, units.den);
                        }

                        return new Bool(result);
                    }
                }, {
                    key: "computeUnits",
                    value: function computeUnits(left, right, op) {
                        switch (op) {
                            case '*':
                                return {
                                    num: left.numeratorUnits.slice().concat(right.numeratorUnits),
                                    den: left.denominatorUnits.slice().concat(right.denominatorUnits)
                                };
                            case '/':
                                return {
                                    num: left.numeratorUnits.slice().concat(right.denominatorUnits),
                                    den: left.denominatorUnits.slice().concat(right.numeratorUnits)
                                };
                            default:
                                return {
                                    num: left.numeratorUnits,
                                    den: left.denominatorUnits
                                };
                        }
                    }
                }, {
                    key: "coerceUnits",
                    value: function coerceUnits(units, denominatorUnits) {
                        var value = this.value;
                        if (!this.unitless()) {
                            value = value * this.coercionFactor(this.numeratorUnits, units) / this.coercionFactor(this.denominatorUnits, denominatorUnits);
                        }
                        return new Numeric(value, units && units[0], units, denominatorUnits);
                    }
                }, {
                    key: "coercionFactor",
                    value: function coercionFactor(units, otherUnits) {
                        var res = this.removeCommonUnits(units, otherUnits),
                            fromUnits = res.num,
                            toUnits = res.den;

                        if (fromUnits.length !== toUnits.length || !this.convertable(fromUnits || toUnits)) {
                            Fashion.raise('Incompatible units: ' + fromUnits.join('*') + ' and ' + toUnits.join('*'));
                        }

                        for (var i = 0; i < fromUnits.length; i++) {
                            var fromUnit = fromUnits[i];
                            for (var j = 0; j < toUnits.length; j++) {
                                var toUnit = toUnits[j],
                                    factor = this.conversionFactor(fromUnit, toUnit);

                                if (factor !== null) {
                                    return factor;
                                }
                            }
                        }

                        return 1;
                    }
                }, {
                    key: "conversionFactor",
                    value: function conversionFactor(fromUnit, toUnit) {
                        var cUnits = Numeric.CONVERTABLE_UNITS,
                            cTable = Numeric.CONVERSION_TABLE,
                            factor = null;

                        if (cUnits[fromUnit]) {
                            if (cUnits[toUnit]) {
                                factor = cTable[cUnits[fromUnit]][cUnits[toUnit]];
                            }
                        }

                        if (factor === null && cUnits[toUnit]) {
                            if (cUnits[fromUnit]) {
                                factor = 1.0 / cTable[cUnits[toUnit]][cUnits[fromUnit]];
                            }
                        }

                        return factor;
                    }
                }, {
                    key: "convertable",
                    value: function convertable(units) {
                        if (units && !Array.isArray(units)) {
                            units = [units];
                        }

                        if (units && units.length) {
                            var convertableUnits = Numeric.CONVERTABLE_UNITS;
                            for (var i = 0; i < units.length; i++) {
                                if (convertableUnits[units[i]] === undefined) {
                                    return false;
                                }
                            }
                        }
                        return true;
                    }
                }, {
                    key: "removeCommonUnits",
                    value: function removeCommonUnits(numUnits, denUnits) {
                        var map = {},
                            num = [],
                            den = [],
                            i,
                            unit,
                            unit;

                        for (i = 0; i < numUnits.length; i++) {
                            unit = numUnits[i];
                            map[unit] = (map[unit] || 0) + 1;
                        }

                        for (i = 0; i < denUnits.length; i++) {
                            unit = denUnits[i];
                            map[unit] = (map[unit] || 0) - 1;
                        }

                        for (i = 0; i < numUnits.length; i++) {
                            unit = numUnits[i];
                            if (map[unit] > 0) {
                                num.push(unit);
                                map[unit]--;
                            }
                        }

                        for (i = 0; i < denUnits.length; i++) {
                            unit = denUnits[i];
                            if (map[unit] < 0) {
                                den.push(unit);
                                map[unit]++;
                            }
                        }

                        return {
                            num: num,
                            den: den
                        };
                    }
                }], [{
                    key: "tryGetNumber",
                    value: function tryGetNumber(value) {
                        if (/^\d*$/.test(value)) {
                            value = parseFloat(value);
                        }

                        if (!isNaN(value)) {
                            return new Numeric(value);
                        }

                        return undefined;
                    }
                }, {
                    key: "tryCoerce",
                    value: function tryCoerce(obj) {
                        if (obj.$isFashionNumber) {
                            return obj;
                        }

                        if (obj.$isFashionLiteral) {
                            return this.tryGetNumber(obj.value);
                        }

                        return undefined;
                    }
                }]);

                return Numeric;
            }(Type);

            Fashion.apply(Numeric, {
                OPERATIONS: {
                    '!=': function _(l, r) {
                        return l != r;
                    },
                    '+': function _(l, r) {
                        return l + r;
                    },
                    '-': function _(l, r) {
                        return l - r;
                    },
                    '<=': function _(l, r) {
                        return l <= r;
                    },
                    '<': function _(l, r) {
                        return l < r;
                    },
                    '>': function _(l, r) {
                        return l > r;
                    },
                    '>=': function _(l, r) {
                        return l >= r;
                    },
                    '==': function _(l, r) {
                        return l == r;
                    },
                    '%': function _(l, r) {
                        return Math.abs(l % r);
                    }
                },

                NON_COERCE_OPERATIONS: {
                    '*': function _(l, r) {
                        return l * r;
                    },
                    '**': function _(l, r) {
                        return Math.pow(l, r);
                    },
                    '/': function _(l, r) {
                        return l / r;
                    }
                },

                CONVERTABLE_UNITS: {
                    'in': 0,
                    'cm': 1,
                    'pc': 2,
                    'mm': 3,
                    'pt': 4,
                    'px': 5
                },

                CONVERSION_TABLE: [[1, 2.54, 6, 25.4, 72, 96], // in
                [null, 1, 2.36220473, 10, 28.3464567, 37.795276], // cm
                [null, null, 1, 4.23333333, 12, 16], // pc
                [null, null, null, 1, 2.83464567, 3.7795276], // mm
                [null, null, null, null, 1, 1.3333333], // pt
                [null, null, null, null, null, 1] // px
                ]
            });

            Fashion.apply(Numeric.prototype, {
                type: 'number',
                $isFashionNumber: true,
                value: undefined,
                unit: undefined,

                numeratorUnits: undefined,
                denominatorUnits: undefined,
                normalized: false
            });

            module.exports = Numeric;
        }, { "../Base.js": 3, "./Bool.js": 8, "./Literal.js": 16, "./Type.js": 24 }], 19: [function (require, module, exports) {
            /*
             * Copyright (c) 2012-2016. Sencha Inc.
             */

            "use strict";

            var Fashion = require('../Base.js');
            var Type = require('./Type.js');

            var ParentheticalExpression = function (_Type9) {
                _inherits(ParentheticalExpression, _Type9);

                function ParentheticalExpression(value) {
                    _classCallCheck(this, ParentheticalExpression);

                    var _this15 = _possibleConstructorReturn(this, (ParentheticalExpression.__proto__ || Object.getPrototypeOf(ParentheticalExpression)).call(this));

                    _this15.value = value;
                    return _this15;
                }

                _createClass(ParentheticalExpression, [{
                    key: "toString",
                    value: function toString() {
                        return '(' + (this.value && this.value.toString()) + ')';
                    }
                }, {
                    key: "doVisit",
                    value: function doVisit(visitor) {
                        visitor.parenthetical(this);
                    }
                }]);

                return ParentheticalExpression;
            }(Type);

            Fashion.apply(ParentheticalExpression.prototype, {
                value: null,
                type: 'parenthetical'
            });

            module.exports = ParentheticalExpression;
        }, { "../Base.js": 3, "./Type.js": 24 }], 20: [function (require, module, exports) {
            /*
             * Copyright (c) 2012-2016. Sencha Inc.
             */

            "use strict";

            var Fashion = require('../Base.js');
            var Type = require('./Type.js');

            var RadialGradient = function (_Type10) {
                _inherits(RadialGradient, _Type10);

                function RadialGradient(position, shape, stops) {
                    _classCallCheck(this, RadialGradient);

                    var _this16 = _possibleConstructorReturn(this, (RadialGradient.__proto__ || Object.getPrototypeOf(RadialGradient)).call(this));

                    _this16.position = position;
                    _this16.stops = stops;
                    _this16.shape = shape;
                    return _this16;
                }

                _createClass(RadialGradient, [{
                    key: "doVisit",
                    value: function doVisit(visitor) {
                        visitor.radialgradient(this);
                    }
                }, {
                    key: "descend",
                    value: function descend(visitor) {
                        visitor.visit(this.position);
                        visitor.visit(this.stops);
                        visitor.visit(this.shape);
                    }
                }, {
                    key: "copy",
                    value: function copy() {
                        return new RadialGradient(this.position, this.shape, this.stops);
                    }
                }, {
                    key: "toString",
                    value: function toString() {
                        var string = 'radial-gradient(';

                        if (this.position) {
                            string += this.position + ', ';
                        }

                        if (this.shape) {
                            string += this.shape + ', ';
                        }

                        return string + this.stops + ')';
                    }
                }, {
                    key: "toOriginalWebkitString",
                    value: function toOriginalWebkitString() {
                        var args = [],
                            stops = this.stops.items,
                            ln = stops.length,
                            i;

                        args.push('center 0%');
                        args.push('center 100%');

                        for (i = 0; i < ln; i++) {
                            args.push(stops[i].toOriginalWebkitString());
                        }

                        return '-webkit-gradient(radial, ' + args.join(', ') + ')';
                    }
                }, {
                    key: "supports",
                    value: function supports(prefix) {
                        return ['owg', 'webkit'].indexOf(prefix.toLowerCase()) !== -1;
                    }
                }, {
                    key: "toPrefixedString",
                    value: function toPrefixedString(prefix) {
                        if (prefix === 'owg') {
                            return this.toOriginalWebkitString();
                        }
                        return prefix + this.toString();
                    }
                }, {
                    key: "gradientPoints",
                    value: function gradientPoints(position) {
                        //position = (position.type === 'list') ? position.clone() : new Fashion.List([position]);
                        //console.log('gradientpoints', position);
                    }
                }]);

                return RadialGradient;
            }(Type);

            Fashion.apply(RadialGradient.prototype, {
                type: 'radialgradient',
                $isFashionRadialGradient: true,
                $canUnbox: false,
                position: null,
                stops: null,
                shape: null
            });

            module.exports = RadialGradient;
        }, { "../Base.js": 3, "./Type.js": 24 }], 21: [function (require, module, exports) {
            "use strict";

            var Fashion = require('../Base.js');
            var TypeVisitor = require('./TypeVisitor.js');
            var Output = require('../Output.js');

            var SourceBuilder = function (_TypeVisitor) {
                _inherits(SourceBuilder, _TypeVisitor);

                function SourceBuilder(cfg) {
                    _classCallCheck(this, SourceBuilder);

                    var _this17 = _possibleConstructorReturn(this, (SourceBuilder.__proto__ || Object.getPrototypeOf(SourceBuilder)).call(this, cfg));

                    _this17.nullFound = false;
                    return _this17;
                }

                _createClass(SourceBuilder, [{
                    key: "list",
                    value: function list(obj) {
                        var output = this.output,
                            items = obj.items,
                            len = output.output.length,
                            sep = obj.separator,
                            sepLen = sep && sep.length,
                            hasSpace = sep && sep.indexOf(' ') > -1,
                            prev = output.output,
                            delta;

                        for (var i = 0; i < items.length; i++) {
                            if (items[i] && !items[i].$isFashionNull) {
                                this.visit(items[i]);
                                delta = output.output.length - len;
                                if (!delta && sepLen && i > 0) {
                                    output.output = prev;
                                }
                                prev = output.output;
                                if (i < items.length - 1) {
                                    if (sepLen) {
                                        output.add(sep);
                                        if (!hasSpace) {
                                            output.space();
                                        }
                                    }
                                }
                                len = output.output.length;
                            } else {
                                this.nullFound = true;
                            }
                        }
                    }
                }, {
                    key: "map",
                    value: function map(obj) {
                        var output = this.output,
                            items = obj.items,
                            key,
                            value;

                        if (this.currDeclaration) {
                            Fashion.raise('(' + obj.toString() + ") isn't a valid CSS value.");
                        }

                        for (var i = 0; i < items.length - 1; i += 2) {
                            key = items[i];
                            value = items[i + 1];
                            if (key && value) {
                                if (i > 0) {
                                    output.add(',');
                                    output.space();
                                }

                                this.visit(key);
                                output.add(': ');
                                //output.space();
                                this.visit(value);
                            }
                        }
                    }
                }, {
                    key: "literal",
                    value: function literal(obj) {
                        obj.value && this.output.add(obj.value);
                    }
                }, {
                    key: "string",
                    value: function string(obj) {
                        var output = this.output;
                        output.add(obj.quoteChar);
                        output.add(obj.value);
                        output.add(obj.quoteChar);
                    }
                }, {
                    key: "functioncall",
                    value: function functioncall(obj) {
                        var output = this.output;
                        output.add(obj.name);
                        output.add('(');
                        this.visit(obj.args);
                        output.add(')');
                    }
                }, {
                    key: "parenthetical",
                    value: function parenthetical(obj) {
                        this.output.add('(');
                        this.visit(obj.value);
                        this.output.add(')');
                    }
                }, {
                    key: "number",
                    value: function number(obj) {
                        var val = obj.stringify();
                        if (val.indexOf('.') === '.' && !this.output.isCompressed) {
                            val = "0" + val;
                        }
                        this.output.add(val);
                    }
                }, {
                    key: "bool",
                    value: function bool(obj) {
                        this.output.add(obj.value ? 'true' : 'false');
                    }
                }, {
                    key: "hsla",
                    value: function hsla(obj) {
                        this.output.add(obj.toString());
                    }
                }, {
                    key: "rgba",
                    value: function rgba(obj) {
                        this.output.add(obj.toString());
                    }
                }, {
                    key: "colorstop",
                    value: function colorstop(obj) {
                        var output = this.output,
                            stop = obj.stop;

                        this.visit(obj.color);

                        if (stop) {
                            stop = stop.clone();
                            output.add(' ');
                            if (!stop.unit) {
                                stop.value *= 100;
                                stop.unit = '%';
                            }
                            this.visit(stop);
                        }
                    }
                }, {
                    key: "lineargradient",
                    value: function lineargradient(obj) {
                        var output = this.output;
                        output.add("linear-gradient(");
                        if (obj.position) {
                            this.visit(obj.position);
                            output.add(',');
                            output.space();
                        }
                        this.visit(obj.stops);
                        output.add(')');
                    }
                }, {
                    key: "radialgradient",
                    value: function radialgradient(obj) {
                        var output = this.output;
                        output.add("radial-gradient(");
                        if (obj.position) {
                            this.visit(obj.position);
                            output.add(',');
                            output.space();
                        }
                        if (obj.shape) {
                            this.visit(obj.shape);
                            output.add(',');
                            output.space();
                        }
                        this.visit(obj.stops);
                        output.add(')');
                    }
                }, {
                    key: "toSource",
                    value: function toSource(obj, output) {
                        this.output = output || new Output();
                        this.visit(obj);
                        return this.output.get();
                    }
                }], [{
                    key: "toSource",
                    value: function toSource(obj, output) {
                        var sb = new SourceBuilder();
                        return sb.toSource(obj, output);
                    }
                }]);

                return SourceBuilder;
            }(TypeVisitor);

            Fashion.apply(SourceBuilder.prototype, {
                output: null
            });

            module.exports = SourceBuilder;
        }, { "../Base.js": 3, "../Output.js": 4, "./TypeVisitor.js": 25 }], 22: [function (require, module, exports) {
            "use strict";

            var Fashion = require('../Base.js');
            var Type = require('./Type.js');
            var Text = require('./Text.js');
            var Numeric = require('./Numeric.js');

            var Bool = require('./Bool.js'),
                True = Bool.True,
                False = Bool.False;

            var Literal = require('./Literal.js'),
                Null = Literal.Null;

            module.exports = {
                unboxType: function unboxType(expression) {
                    var val = expression;
                    if (val && val.$isFashionType && val.$canUnbox) {
                        val = val.value;
                        if (expression.$isFashionString || expression.$isFashionLiteral) {
                            if (val === 'none' || val === 'null') {
                                val = null;
                            }
                        } else if (expression.$isFashionList) {
                            val = expression.items;
                        }
                    }
                    return val;
                },
                boxType: function boxType(expression) {
                    if (expression && expression.$isFashionType) {
                        return expression;
                    }

                    if (expression == null) {
                        // null || undefined
                        return Null;
                    }
                    if (expression === true) {
                        return True;
                    }
                    if (expression === false) {
                        return False;
                    }

                    var typeOf = typeof expression === "undefined" ? "undefined" : _typeof(expression);
                    switch (typeOf) {
                        case 'string':
                            return new Text(expression);
                        case 'number':
                            return new Numeric(expression);
                        default:
                            break;
                    }

                    return expression;
                }
            };

            Fashion.apply(Type, {
                box: module.exports.boxType,
                unbox: module.exports.unboxType
            });
        }, { "../Base.js": 3, "./Bool.js": 8, "./Literal.js": 16, "./Numeric.js": 18, "./Text.js": 23, "./Type.js": 24 }], 23: [function (require, module, exports) {
            /*
             * Copyright (c) 2012-2016. Sencha Inc.
             */

            "use strict";

            var Fashion = require('../Base.js');
            var Literal = require('./Literal.js');

            var Text = function (_Literal2) {
                _inherits(Text, _Literal2);

                function Text(value, quoteChar) {
                    _classCallCheck(this, Text);

                    var _this18 = _possibleConstructorReturn(this, (Text.__proto__ || Object.getPrototypeOf(Text)).call(this, value));

                    if (quoteChar !== undefined) {
                        if (Text.preferDoubleQuotes && quoteChar === '') {
                            _this18.quoteChar = '';
                        } else {
                            _this18.quoteChar = quoteChar;
                        }
                    }
                    return _this18;
                }

                _createClass(Text, [{
                    key: "doVisit",
                    value: function doVisit(visitor) {
                        visitor.string(this);
                    }
                }, {
                    key: "toString",
                    value: function toString() {
                        return this.quoteChar + this.value + this.quoteChar;
                    }
                }, {
                    key: "unquote",
                    value: function unquote() {
                        return new Literal(this.value);
                    }
                }, {
                    key: "copy",
                    value: function copy() {
                        return new Text(this.value, this.quoteChar);
                    }
                }, {
                    key: "slice",
                    value: function slice(start, end) {
                        return new Text(_get(Text.prototype.__proto__ || Object.getPrototypeOf(Text.prototype), "slice", this).call(this, start, end).value, this.quoteChar);
                    }
                }, {
                    key: "toUpperCase",
                    value: function toUpperCase() {
                        return new Text(this.value.toUpperCase(), this.quoteChar);
                    }
                }, {
                    key: "toLowerCase",
                    value: function toLowerCase() {
                        return new Text(this.value.toLowerCase(), this.quoteChar);
                    }
                }, {
                    key: "insert",
                    value: function insert(str, startVal) {
                        return new Text(_get(Text.prototype.__proto__ || Object.getPrototypeOf(Text.prototype), "insert", this).call(this, str, startVal).value, this.quoteChar);
                    }
                }], [{
                    key: "tryCoerce",
                    value: function tryCoerce(obj) {
                        if (obj.$isFashionNumber) {
                            return undefined;
                        }
                        if (obj.$isFashionLiteral) {
                            return new Text(obj.value);
                        }

                        return new Text(obj.getHash());
                    }
                }]);

                return Text;
            }(Literal);

            Text.preferDoubleQuotes = false;

            Fashion.apply(Text.prototype, {
                type: 'string',
                $isFashionString: true,
                value: null,
                quoteChar: '"',

                '+': function _(right) {
                    return new Text(this.value + right.getHash());
                },
                '+.number': function number(right) {
                    return new Text(this.value + right.toString());
                },
                '/': function _(right) {
                    return new Text(this.value + '/' + right.getHash());
                }
            });

            module.exports = Text;
        }, { "../Base.js": 3, "./Literal.js": 16 }], 24: [function (require, module, exports) {
            /*
             * Copyright (c) 2012-2016. Sencha Inc.
             */

            "use strict";

            var Fashion = require('../Base.js');

            var Type = function () {
                function Type() {
                    _classCallCheck(this, Type);
                }

                _createClass(Type, [{
                    key: "coerce",
                    value: function coerce(obj) {
                        var converted = this.tryCoerce(obj);
                        return converted || obj;
                    }
                }, {
                    key: "_getHash",
                    value: function _getHash() {
                        if (this.visitTarget) {
                            return this.visitTarget.toString();
                        }
                        return this.toString();
                    }
                }, {
                    key: "getHash",
                    value: function getHash() {
                        if (this._hash == null) {
                            this._hash = this._getHash();
                        }
                        return this._hash;
                    }
                }, {
                    key: "tryCoerce",
                    value: function tryCoerce(obj) {
                        var me = this;

                        if (me.constructor === obj.constructor) {
                            return obj;
                        }

                        if (me.constructor.tryCoerce) {
                            return me.constructor.tryCoerce(obj);
                        }

                        return undefined;
                    }
                }, {
                    key: "supports",
                    value: function supports(prefix) {
                        return false;
                    }
                }, {
                    key: "operate",
                    value: function operate(operation, right) {
                        return this.performOperation(operation, this.coerce(right));
                    }
                }, {
                    key: "performOperation",
                    value: function performOperation(operation, right) {
                        // check for <op>.<type> name for class-specific impl,
                        // eg, ==.color or +.list
                        var method = this[operation + "." + right.type] || this[operation];

                        if (!method) {
                            Fashion.raise("Failed to find method for operation " + operation + " on type " + right.type + " with value " + right + ".");
                        }

                        var res = method.call(this, right);

                        if (!res || !res.$isFashionType) {
                            res = Type.box(res);
                        }

                        return res;
                    }
                }, {
                    key: '==',
                    value: function _(right) {
                        return this.getHash() === right.getHash();
                    }
                }, {
                    key: '!=',
                    value: function _(right) {
                        return this.getHash() !== right.getHash();
                    }
                }, {
                    key: '>=',
                    value: function _(right) {
                        return this.getHash() >= right.getHash();
                    }
                }, {
                    key: '<=',
                    value: function _(right) {
                        return this.getHash() <= right.getHash();
                    }
                }, {
                    key: '>',
                    value: function _(right) {
                        return this.getHash() > right.getHash();
                    }
                }, {
                    key: '<',
                    value: function _(right) {
                        return this.getHash() < right.getHash();
                    }
                }, {
                    key: '+',
                    value: function _(right) {
                        return this.getHash() + right.getHash();
                    }
                }, {
                    key: "copy",
                    value: function copy() {
                        return this;
                    }
                }, {
                    key: "matches",
                    value: function matches(match) {
                        if (match && match == this.toString()) {
                            return true;
                        }
                        return false;
                    }
                }, {
                    key: "clone",
                    value: function clone(match, replace) {
                        if (replace && this.matches(match)) {
                            return replace.copy();
                        }
                        var copy = this.copy();
                        copy.ast = this.ast;
                        copy.$referenceName = this.$referenceName;
                        copy.$referenceBase = this.$referenceBase;
                        copy.$previousReference = this.$previousReference;
                        return copy;
                    }
                }, {
                    key: "unquote",
                    value: function unquote() {
                        return this;
                    }
                }, {
                    key: "toPrefixedString",
                    value: function toPrefixedString(prefix) {
                        return this.toString();
                    }
                }, {
                    key: "doVisit",
                    value: function doVisit(visitor) {}
                }, {
                    key: "descend",
                    value: function descend(visitoir) {}

                    /**
                     * A mechanism that enables searching upwards in the type tree for comments with a
                     * particular control tag.  The search begins locally first on the specified node,
                     * and continues upwards until either an enable or disable tag is specified, or the
                     * the root of the tree is reached with no tags specified.
                     *
                     * By testing for both positive and negative matches locally, features can be enabled
                     * or disabled at specific points, potentially overriding state set at a more
                     * generic scope.  Ex:
                     *
                     *      //# fashion -ingline
                     *      @font-face {
                     *          src: url(foo.eot);
                     *          src: url(foo.svg);
                     *          //# fashion +inline
                     *          src: url(foo.ttf);
                     *      }
                     *
                     * @param tag The tag to search for.
                     * @param prefix An optional prefix, such as 'fashion warn'.  Defaults to 'fashion'
                     * @param enable A regex indicating a match for the enable state (+tag).
                     * @param disable A regex indicating a match for the disable state (-tag)
                     * @returns {any} true for enable | false for disable | null for unspecified
                     */

                }, {
                    key: "hasTag",
                    value: function hasTag(tag, prefix, enable, disable) {
                        prefix = prefix || "fashion";
                        enable = enable || new RegExp('^\\s*//#\\s*' + prefix + '\\s*\\+?' + tag + "\s*$");
                        disable = disable || new RegExp('^\\s*//#\\s*' + prefix + '\\s*\\-' + tag + '\\s*$');
                        var docs = this.docs;
                        if (docs && docs.length) {
                            for (var d = 0; d < this.docs.length; d++) {
                                var doc = docs[d];
                                if (enable.test(doc)) {
                                    return true;
                                }
                                if (disable.test(doc)) {
                                    return false;
                                }
                            }
                        }

                        if (this.parentNode) {
                            return this.parentNode.hasTag(tag, prefix, enable, disable);
                        }

                        return null;
                    }
                }, {
                    key: "toDisplayString",
                    value: function toDisplayString() {
                        return '[' + this.constructor.name + ' : ' + this.toString() + ']';
                    }
                }]);

                return Type;
            }();

            Fashion.apply(Type.prototype, {
                visitTarget: undefined,
                $isFashionType: true,
                $canUnbox: true,

                $isFashionLiteral: false,
                $isFashionNumber: false,
                $isFashionString: false,
                $isFashionBool: false,

                $constant: false,

                /**
                 * if this value is a global variable, this field will store the global
                 * variable name by which this value is referenced.
                 */
                $referenceName: undefined,

                $referenceBase: undefined,
                $previousReference: undefined,

                value: undefined,
                unit: undefined,
                parentNode: undefined,
                docs: undefined,
                ast: undefined
            });

            module.exports = Type;
        }, { "../Base.js": 3 }], 25: [function (require, module, exports) {
            "use strict";

            var Fashion = require('../Base.js');

            var TypeVisitor = function () {
                function TypeVisitor(cfg) {
                    _classCallCheck(this, TypeVisitor);

                    if (cfg) {
                        Fashion.apply(this, cfg);
                    }
                }

                _createClass(TypeVisitor, [{
                    key: "literal",
                    value: function literal(obj) {
                        obj.descend(this);
                    }
                }, {
                    key: "bool",
                    value: function bool(obj) {
                        obj.descend(this);
                    }
                }, {
                    key: "string",
                    value: function string(obj) {
                        obj.descend(this);
                    }
                }, {
                    key: "number",
                    value: function number(obj) {
                        obj.descend(this);
                    }
                }, {
                    key: "map",
                    value: function map(obj) {
                        obj.descend(this);
                    }
                }, {
                    key: "functioncall",
                    value: function functioncall(obj) {
                        obj.descend(this);
                    }
                }, {
                    key: "parenthetical",
                    value: function parenthetical(obj) {
                        obj.descend(this);
                    }
                }, {
                    key: "list",
                    value: function list(obj) {
                        obj.descend(this);
                    }
                }, {
                    key: "hsla",
                    value: function hsla(obj) {
                        obj.descend(this);
                    }
                }, {
                    key: "rgba",
                    value: function rgba(obj) {
                        obj.descend(this);
                    }
                }, {
                    key: "colorstop",
                    value: function colorstop(obj) {
                        obj.descend(this);
                    }
                }, {
                    key: "lineargradient",
                    value: function lineargradient(obj) {
                        obj.descend(this);
                    }
                }, {
                    key: "radialgradient",
                    value: function radialgradient(obj) {
                        obj.descend(this);
                    }
                }, {
                    key: "visitItem",
                    value: function visitItem(obj) {
                        obj.doVisit(this);
                    }
                }, {
                    key: "visit",
                    value: function visit(obj) {
                        while (obj && obj.visitTarget !== undefined) {
                            obj = obj.visitTarget;
                        }
                        if (obj) {
                            if (Array.isArray(obj)) {
                                for (var i = 0; i < obj.length; i++) {
                                    this.visit(obj[i]);
                                }
                            } else {
                                this.visitItem(obj);
                            }
                        }
                    }

                    /**
                     * this is an extension point for allowing overrides of the entry visit method
                     * when called duing the post-processing mechanism in CSS.ts
                     * @param obj
                     */

                }, {
                    key: "execute",
                    value: function execute(obj, context) {
                        this.visit(obj);
                    }
                }]);

                return TypeVisitor;
            }();

            TypeVisitor.prototype.context = null;

            module.exports = TypeVisitor;
        }, { "../Base.js": 3 }], 26: [function (require, module, exports) {
            "use strict";

            var Bool = require('./Bool.js');
            var Literal = require('./Literal.js');

            var Types = {
                Bool: Bool,
                Literal: Literal,
                Text: require('./Text.js'),
                Numeric: require('./Numeric.js'),
                Color: require('./Color.js'),
                ColorRGBA: require('./ColorRGBA.js'),
                ColorHSLA: require('./ColorHSLA.js'),
                ColorStop: require('./ColorStop.js'),
                LinearGradient: require('./LinearGradient.js'),
                RadialGradient: require('./RadialGradient.js'),
                List: require('./List.js'),
                Map: require('./Map.js'),
                ParentheticalExpression: require('./ParentheticalExpression.js'),
                FunctionCall: require('./FunctionCall.js'),
                Null: Literal.Null,
                None: Literal.None,
                True: Bool.True,
                False: Bool.False
            };

            module.exports = Types;
        }, { "./Bool.js": 8, "./Color.js": 9, "./ColorHSLA.js": 10, "./ColorRGBA.js": 11, "./ColorStop.js": 12, "./FunctionCall.js": 13, "./LinearGradient.js": 14, "./List.js": 15, "./Literal.js": 16, "./Map.js": 17, "./Numeric.js": 18, "./ParentheticalExpression.js": 19, "./RadialGradient.js": 20, "./Text.js": 23 }] }, {}, [1])(1);
});
(function(Fashion){
	var __udf = undefined,
	    Types = Fashion.Types,
	    __strings = {
    _: "$color",
    $: "$color_name",
    A: "$colorLookup",
    a: "$color_variant"
},

	    __names = Fashion.css.buildNames(__strings),

	    __jsNames = Fashion.css.buildJsNames(__strings);
var Bool = Types.Bool,
    __Bool = Bool,
    Literal = Types.Literal,
    __Literal = Literal,
    Text = Types.Text,
    __Text = Text,
    Numeric = Types.Numeric,
    __Numeric = Numeric,
    Color = Types.Color,
    __Color = Color,
    ColorRGBA = Types.ColorRGBA,
    __ColorRGBA = ColorRGBA,
    ColorHSLA = Types.ColorHSLA,
    __ColorHSLA = ColorHSLA,
    ColorStop = Types.ColorStop,
    __ColorStop = ColorStop,
    LinearGradient = Types.LinearGradient,
    __LinearGradient = LinearGradient,
    RadialGradient = Types.RadialGradient,
    __RadialGradient = RadialGradient,
    List = Types.List,
    __List = List,
    Map = Types.Map,
    __Map = Map,
    ParentheticalExpression = Types.ParentheticalExpression,
    __ParentheticalExpression = ParentheticalExpression,
    FunctionCall = Types.FunctionCall,
    __FunctionCall = FunctionCall,
    Null = Types.Null,
    __Null = Null,
    None = Types.None,
    __None = None,
    True = Types.True,
    __True = True,
    False = Types.False,
    __False = False,
    Ruleset = Types.Ruleset,
    __Ruleset = Ruleset,
    Declaration = Types.Declaration,
    __Declaration = Declaration,
    SelectorPart = Types.SelectorPart,
    __SelectorPart = SelectorPart,
    CompoundSelector = Types.CompoundSelector,
    __CompoundSelector = CompoundSelector,
    MultiPartSelector = Types.MultiPartSelector,
    __MultiPartSelector = MultiPartSelector,
    SelectorList = Types.SelectorList,
    __SelectorList = SelectorList,
    SelectorProperty = Types.SelectorProperty,
    __SelectorProperty = SelectorProperty;

	Fashion.css.register(function(__rt) {
__rt.register({
    map_get:  function (map, key) {
                return map.get(key);
            },
    lighten:  function (color, amount) {
                if (color == null || color.$isFashionNull) {
                    return Literal.Null;
                }
                if (color.type !== 'hsla' && color.type !== 'rgba') {
                    Fashion.raise(color + ' is not a color for \'lighten\'');
                }
                if (amount.type !== 'number') {
                    Fashion.raise(amount + ' is not a number for \'lighten\'');
                }
                if (amount.value !== Color.constrainPercentage(amount.value)) {
                    Fashion.raise('Amount ' + amount + ' must be between 0% and 100% for \'lighten\'');
                }

                return Color.adjust(color, 'lightness', amount);
            },
    darken:  function (color, amount) {
                if (color == null || color.$isFashionNull) {
                    return Literal.Null;
                }
                if (color.type !== 'hsla' && color.type !== 'rgba') {
                    Fashion.raise(color + ' is not a color for \'darken\'');
                }
                if (amount.type !== 'number') {
                    Fashion.raise(amount + ' is not a number for \'darken\'');
                }

                if (amount.value !== Color.constrainPercentage(amount.value)) {
                    Fashion.raise('Amount ' + amount + ' must be between 0% and 100% for \'darken\'');
                }

                amount = amount.clone();
                amount.value *= -1;
                return Color.adjust(color, 'lightness', amount);
            },
    rgba:  function (red, green, blue, alpha, color) {
                var colorInst;

                if (!!red && !!color) {
                    Fashion.raise("Unsupported arguments to RGBA");
                }

                if (color && !red) {
                    if (color.$isFashionColor) {
                        colorInst = color;
                    } else {
                        Fashion.raise("Unsupported arguments to RGBA");
                    }
                } else if (red && red.$isFashionColor) {
                    colorInst = red;
                }

                if (colorInst) {
                    alpha = green || alpha;
                    colorInst = colorInst.getRGBA();
                    red = new Numeric(colorInst.r);
                    green = new Numeric(colorInst.g);
                    blue = new Numeric(colorInst.b);
                }

                if (!red || !red.$isFashionNumber) {
                    if (red == null || red.$isFashionNull) {
                        return Literal.Null;
                    }
                    Fashion.raise(red + ' is not a number for \'rgba\' red');
                }
                if (!green || !green.$isFashionNumber) {
                    if (green == null || green.$isFashionNull) {
                        return Literal.Null;
                    }
                    Fashion.raise(green + ' is not a number for \'rgba\' green');
                }
                if (!blue || !blue.$isFashionNumber) {
                    if (blue == null || blue.$isFashionNull) {
                        return Literal.Null;
                    }
                    Fashion.raise(blue + ' is not a number for \'rgba\' blue');
                }
                if (!alpha || !alpha.$isFashionNumber) {
                    if (alpha == null || alpha.$isFashionNull) {
                        return Literal.Null;
                    }
                    Fashion.raise(alpha + ' is not a number for \'rgba\' alpha');
                }

                if (red.unit == '%') {
                    red = new Numeric(Color.constrainPercentage(red.value) / 100 * 255);
                } else if (red.value !== Color.constrainChannel(red.value)) {
                    Fashion.raise('Color value ' + red + ' must be between 0 and 255 inclusive for \'rgba\'');
                }

                if (green.unit == '%') {
                    green = new Numeric(Color.constrainPercentage(green.value) / 100 * 255);
                } else if (green.value !== Color.constrainChannel(green.value)) {
                    Fashion.raise('Color value ' + green + ' must be between 0 and 255 inclusive for \'rgba\'');
                }

                if (blue.unit == '%') {
                    blue = new Numeric(Color.constrainPercentage(blue.value) / 100 * 255);
                } else if (blue.value !== Color.constrainChannel(blue.value)) {
                    Fashion.raise('Color value ' + blue + ' must be between 0 and 255 inclusive for \'rgba\'');
                }

                if (alpha.unit == '%') {
                    alpha = new Numeric(Color.constrainPercentage(alpha.value) / 100);
                } else if (alpha.value !== Color.constrainAlpha(alpha.value)) {
                    Fashion.raise('Alpha channel ' + alpha + ' must be between 0 and 1 inclusive for \'rgba\'');
                }

                return new ColorRGBA(red.value, green.value, blue.value, alpha.value);
            },
    mix:  function (color_1, color_2, weight) {
                if (color_1 == null || color_1.$isFashionNull) {
                    return Literal.Null;
                }
                if (color_2 == null || color_2.$isFashionNull) {
                    return Literal.Null;
                }
                
                weight = (weight !== undefined) ? weight : new Numeric(50, '%');

                if (color_1.type !== 'hsla' && color_1.type !== 'rgba') {
                    Fashion.raise('arg 1 ' + color_1 + ' is not a color for \'mix\'');
                }
                if (color_2.type !== 'hsla' && color_2.type !== 'rgba') {
                    Fashion.raise('arg 2 ' + color_2 + ' is not a color for \'mix\'');
                }
                if (weight.type !== 'number') {
                    Fashion.raise('arg 3 ' + weight + ' is not a number for \'mix\'');
                }
                if (weight.value !== Color.constrainPercentage(weight.value)) {
                    Fashion.raise('Weight ' + weight + ' must be between 0% and 100% for \'mix\'');
                }

                color_1 = color_1.getRGBA();
                color_2 = color_2.getRGBA();

                weight = weight.value / 100;

                var factor = (weight * 2) - 1,
                    alpha = color_1.a - color_2.a,
                    weight1 = (((factor * alpha == -1) ? factor : (factor + alpha) / (1 + factor * alpha)) + 1) / 2,
                    weight2 = 1 - weight1;

                return new ColorRGBA(
                    (weight1 * color_1.r) + (weight2 * color_2.r),
                    (weight1 * color_1.g) + (weight2 * color_2.g),
                    (weight1 * color_1.b) + (weight2 * color_2.b),
                    (weight * color_1.a) + ((1 - weight) * color_2.a)
                );
            }
});
var __rt_constructor = __rt.constructor.bind(__rt),
    __rt_bool = __rt.bool.bind(__rt),
    __rt_color = __rt.color.bind(__rt),
    __rt_quote = __rt.quote.bind(__rt),
    __rt_unquote = __rt.unquote.bind(__rt),
    __rt_not = __rt.not.bind(__rt),
    __rt_operate = __rt.operate.bind(__rt),
    __rt_reset = __rt.reset.bind(__rt),
    __rt_run = __rt.run.bind(__rt),
    __rt_createTypesBlock = __rt.createTypesBlock.bind(__rt),
    __rt_createMethodBlock = __rt.createMethodBlock.bind(__rt),
    __rt_createPropertyBlock = __rt.createPropertyBlock.bind(__rt),
    __rt_createPrefixedFunctionBody = __rt.createPrefixedFunctionBody.bind(__rt),
    __rt_createWrappedFn = __rt.createWrappedFn.bind(__rt),
    __rt_callWrappedFn = __rt.callWrappedFn.bind(__rt),
    __rt_compile = __rt.compile.bind(__rt),
    __rt_execute = __rt.execute.bind(__rt),
    __rt_load = __rt.load.bind(__rt),
    __rt_registerProcessor = __rt.registerProcessor.bind(__rt),
    __rt_register = __rt.register.bind(__rt),
    __rt_isRegistered = __rt.isRegistered.bind(__rt),
    __rt_getGlobalScope = __rt.getGlobalScope.bind(__rt),
    __rt_getCurrentScope = __rt.getCurrentScope.bind(__rt),
    __rt_getRegisteredFunctions = __rt.getRegisteredFunctions.bind(__rt),
    __rt_getFunctions = __rt.getFunctions.bind(__rt),
    __rt_getMixins = __rt.getMixins.bind(__rt),
    __rt_createScope = __rt.createScope.bind(__rt),
    __rt_pushScope = __rt.pushScope.bind(__rt),
    __rt_popScope = __rt.popScope.bind(__rt),
    __rt_createCallStackScope = __rt.createCallStackScope.bind(__rt),
    __rt_pushCallStackScope = __rt.pushCallStackScope.bind(__rt),
    __rt_popCallStackScope = __rt.popCallStackScope.bind(__rt),
    __rt_getCallStack = __rt.getCallStack.bind(__rt),
    __rt_pushSourceInfo = __rt.pushSourceInfo.bind(__rt),
    __rt_getSourceInfo = __rt.getSourceInfo.bind(__rt),
    __rt_get = __rt.get.bind(__rt),
    __rt_getScopeForName = __rt.getScopeForName.bind(__rt),
    __rt_getDefault = __rt.getDefault.bind(__rt),
    __rt_getGlobalDefault = __rt.getGlobalDefault.bind(__rt),
    __rt_getLocalDefault = __rt.getLocalDefault.bind(__rt),
    __rt_setGlobal = __rt.setGlobal.bind(__rt),
    __rt_setDynamic = __rt.setDynamic.bind(__rt),
    __rt_setScoped = __rt.setScoped.bind(__rt),
    __rt_set = __rt.set.bind(__rt),
    __rt_getDocs = __rt.getDocs.bind(__rt),
    __rt_getString = __rt.getString.bind(__rt),
    __rt_getAstNode = __rt.getAstNode.bind(__rt),
    __rt_applySpread = __rt.applySpread.bind(__rt),
    __rt_sliceArgs = __rt.sliceArgs.bind(__rt),
    __rt_applySpreadArgs = __rt.applySpreadArgs.bind(__rt),
    __rt_warn = __rt.warn.bind(__rt),
    __rt_error = __rt.error.bind(__rt),
    __rt_debug = __rt.debug.bind(__rt),
    __rt_setCaches = __rt.setCaches.bind(__rt),
    __rt_copyRuntimeState = __rt.copyRuntimeState.bind(__rt),
    __rt_test = __rt.test.bind(__rt),
    __rt_and = __rt.and.bind(__rt),
    __rt_or = __rt.or.bind(__rt),
    __rt_box = __rt.box.bind(__rt),
    __rt_unbox = __rt.unbox.bind(__rt),
    __rt_Scope = __rt.Scope.bind(__rt),
    __rt_constructor = __rt.constructor.bind(__rt),
    __rt___defineGetter__ = __rt.__defineGetter__.bind(__rt),
    __rt___defineSetter__ = __rt.__defineSetter__.bind(__rt),
    __rt_hasOwnProperty = __rt.hasOwnProperty.bind(__rt),
    __rt___lookupGetter__ = __rt.__lookupGetter__.bind(__rt),
    __rt___lookupSetter__ = __rt.__lookupSetter__.bind(__rt),
    __rt_propertyIsEnumerable = __rt.propertyIsEnumerable.bind(__rt),
    __rt_constructor = __rt.constructor.bind(__rt),
    __rt_toString = __rt.toString.bind(__rt),
    __rt_toLocaleString = __rt.toLocaleString.bind(__rt),
    __rt_valueOf = __rt.valueOf.bind(__rt),
    __rt_isPrototypeOf = __rt.isPrototypeOf.bind(__rt);
var __rt_context = __rt.context,
    __rt_mixins = __rt.mixins,
    __rt_functions = __rt.functions,
    __rt_processors = __rt.processors,
    __rt_registered = __rt.registered,
    __rt_deferredContent = __rt.deferredContent,
    __rt_registerSelectorHooks = __rt.registerSelectorHooks,
    __rt_registerAtRuleHook = __rt.registerAtRuleHook,
    __rt_registerStyleHooks = __rt.registerStyleHooks,
    __rt_registerFunctionCallHooks = __rt.registerFunctionCallHooks,
    __rt_docCache = __rt.docCache,
    __rt_stringCache = __rt.stringCache,
    __rt_nodeCache = __rt.nodeCache,
    __rt_code = __rt.code,
    __rt_fn = __rt.fn,
    __rt__currentScope = __rt._currentScope,
    __rt__currentCallStackScope = __rt._currentCallStackScope,
    __rt__globalScope = __rt._globalScope,
    __rt__dynamics = __rt._dynamics,
    __rt_css = __rt.css,
    __rt_rulesets = __rt.rulesets,
    __rt_extenders = __rt.extenders,
    __rt__scopeStack = __rt._scopeStack;
Fashion.apply(__rt.functions, {
    material_color:  function ($color_name, $color_variant) {
    __rt_createScope(__rt_functions.material_color && __rt_functions.material_color.createdScope);
    var $color_name = $color_name || __Null;
    __rt_set(__strings.$, $color_name, true);
    var $color_variant = $color_variant || new __Text("500", "'");
    __rt_set(__strings.a, $color_variant, true);
    __rt_set(__strings.A, __rt_box(__rt_registered.map_get.apply(__rt.registered, __rt_applySpreadArgs([
        __rt_get("$material_colors"), 
        __rt_get(__strings.$)]))));
    if(__rt_unbox(__rt_get(__strings.A))) {
        __rt_set(__strings._, __rt_box(__rt.registered.map_get.apply(__rt.registered, __rt_applySpreadArgs([
            __rt_get(__strings.A), 
            __rt_get(__strings.a)]))));
        if(__rt_unbox(__rt_get(__strings._))) {
            var $$$r = __rt_get(__strings._);
            __rt_popScope();
            return $$$r;
        }
        else {
            __rt_warn(__rt_unbox(__rt.operate("+",__rt.operate("+",__rt.operate("+",__rt.operate("+",new __Text("=> ERROR: COLOR NOT FOUND! <= | ", "\""), __rt_get(__strings.$)), new __Text(",", "\"")), __rt_get(__strings.a)), new __Text(" combination did not match any of the material design colors.", "\""))));
        }
    }
    else {
        __rt_warn(__rt_unbox(__rt.operate("+",__rt.operate("+",new __Text("=> ERROR: COLOR NOT FOUND! <= | ", "\""), __rt_get(__strings.$)), new __Text(" did not match any of the material design colors.", "\""))));
    }
    var $$$r = __ColorRGBA.fromHex("#ff0000");
    __rt_popScope();
    return $$$r;
},
    material_foreground_color:  function ($color_name) {
    __rt_createScope(__rt_functions.material_foreground_color && __rt_functions.material_foreground_color.createdScope);
    var $color_name = $color_name || __Null;
    __rt_set(__strings.$, $color_name, true);
    __rt_set(__strings._, __rt_box(__rt.registered.map_get.apply(__rt.registered, __rt_applySpreadArgs([
        __rt_get("$material_foreground_colors"), 
        __rt_get(__strings.$)]))));
    if(__rt_unbox(__rt_get(__strings._))) {
        var $$$r = __rt_get(__strings._);
        __rt_popScope();
        return $$$r;
    }
    else {
        __rt_warn(__rt_unbox(__rt.operate("+",__rt.operate("+",new __Text("=> ERROR: COLOR NOT FOUND! <= | ", "\""), __rt_get(__strings.$)), new __Text(" did not match any of the material design colors.", "\""))));
    }
    var $$$r = __ColorRGBA.fromHex("#ff0000");
    __rt_popScope();
    return $$$r;
}
});
},
 function(__rt) {
var __rt_constructor = __rt.constructor.bind(__rt),
    __rt_bool = __rt.bool.bind(__rt),
    __rt_color = __rt.color.bind(__rt),
    __rt_quote = __rt.quote.bind(__rt),
    __rt_unquote = __rt.unquote.bind(__rt),
    __rt_not = __rt.not.bind(__rt),
    __rt_operate = __rt.operate.bind(__rt),
    __rt_reset = __rt.reset.bind(__rt),
    __rt_run = __rt.run.bind(__rt),
    __rt_createTypesBlock = __rt.createTypesBlock.bind(__rt),
    __rt_createMethodBlock = __rt.createMethodBlock.bind(__rt),
    __rt_createPropertyBlock = __rt.createPropertyBlock.bind(__rt),
    __rt_createPrefixedFunctionBody = __rt.createPrefixedFunctionBody.bind(__rt),
    __rt_createWrappedFn = __rt.createWrappedFn.bind(__rt),
    __rt_callWrappedFn = __rt.callWrappedFn.bind(__rt),
    __rt_compile = __rt.compile.bind(__rt),
    __rt_execute = __rt.execute.bind(__rt),
    __rt_load = __rt.load.bind(__rt),
    __rt_registerProcessor = __rt.registerProcessor.bind(__rt),
    __rt_register = __rt.register.bind(__rt),
    __rt_isRegistered = __rt.isRegistered.bind(__rt),
    __rt_getGlobalScope = __rt.getGlobalScope.bind(__rt),
    __rt_getCurrentScope = __rt.getCurrentScope.bind(__rt),
    __rt_getRegisteredFunctions = __rt.getRegisteredFunctions.bind(__rt),
    __rt_getFunctions = __rt.getFunctions.bind(__rt),
    __rt_getMixins = __rt.getMixins.bind(__rt),
    __rt_createScope = __rt.createScope.bind(__rt),
    __rt_pushScope = __rt.pushScope.bind(__rt),
    __rt_popScope = __rt.popScope.bind(__rt),
    __rt_createCallStackScope = __rt.createCallStackScope.bind(__rt),
    __rt_pushCallStackScope = __rt.pushCallStackScope.bind(__rt),
    __rt_popCallStackScope = __rt.popCallStackScope.bind(__rt),
    __rt_getCallStack = __rt.getCallStack.bind(__rt),
    __rt_pushSourceInfo = __rt.pushSourceInfo.bind(__rt),
    __rt_getSourceInfo = __rt.getSourceInfo.bind(__rt),
    __rt_get = __rt.get.bind(__rt),
    __rt_getScopeForName = __rt.getScopeForName.bind(__rt),
    __rt_getDefault = __rt.getDefault.bind(__rt),
    __rt_getGlobalDefault = __rt.getGlobalDefault.bind(__rt),
    __rt_getLocalDefault = __rt.getLocalDefault.bind(__rt),
    __rt_setGlobal = __rt.setGlobal.bind(__rt),
    __rt_setDynamic = __rt.setDynamic.bind(__rt),
    __rt_setScoped = __rt.setScoped.bind(__rt),
    __rt_set = __rt.set.bind(__rt),
    __rt_getDocs = __rt.getDocs.bind(__rt),
    __rt_getString = __rt.getString.bind(__rt),
    __rt_getAstNode = __rt.getAstNode.bind(__rt),
    __rt_applySpread = __rt.applySpread.bind(__rt),
    __rt_sliceArgs = __rt.sliceArgs.bind(__rt),
    __rt_applySpreadArgs = __rt.applySpreadArgs.bind(__rt),
    __rt_warn = __rt.warn.bind(__rt),
    __rt_error = __rt.error.bind(__rt),
    __rt_debug = __rt.debug.bind(__rt),
    __rt_setCaches = __rt.setCaches.bind(__rt),
    __rt_copyRuntimeState = __rt.copyRuntimeState.bind(__rt),
    __rt_test = __rt.test.bind(__rt),
    __rt_and = __rt.and.bind(__rt),
    __rt_or = __rt.or.bind(__rt),
    __rt_box = __rt.box.bind(__rt),
    __rt_unbox = __rt.unbox.bind(__rt),
    __rt_Scope = __rt.Scope.bind(__rt),
    __rt_constructor = __rt.constructor.bind(__rt),
    __rt___defineGetter__ = __rt.__defineGetter__.bind(__rt),
    __rt___defineSetter__ = __rt.__defineSetter__.bind(__rt),
    __rt_hasOwnProperty = __rt.hasOwnProperty.bind(__rt),
    __rt___lookupGetter__ = __rt.__lookupGetter__.bind(__rt),
    __rt___lookupSetter__ = __rt.__lookupSetter__.bind(__rt),
    __rt_propertyIsEnumerable = __rt.propertyIsEnumerable.bind(__rt),
    __rt_constructor = __rt.constructor.bind(__rt),
    __rt_toString = __rt.toString.bind(__rt),
    __rt_toLocaleString = __rt.toLocaleString.bind(__rt),
    __rt_valueOf = __rt.valueOf.bind(__rt),
    __rt_isPrototypeOf = __rt.isPrototypeOf.bind(__rt);
var __rt_context = __rt.context,
    __rt_mixins = __rt.mixins,
    __rt_functions = __rt.functions,
    __rt_processors = __rt.processors,
    __rt_registered = __rt.registered,
    __rt_deferredContent = __rt.deferredContent,
    __rt_registerSelectorHooks = __rt.registerSelectorHooks,
    __rt_registerAtRuleHook = __rt.registerAtRuleHook,
    __rt_registerStyleHooks = __rt.registerStyleHooks,
    __rt_registerFunctionCallHooks = __rt.registerFunctionCallHooks,
    __rt_docCache = __rt.docCache,
    __rt_stringCache = __rt.stringCache,
    __rt_nodeCache = __rt.nodeCache,
    __rt_code = __rt.code,
    __rt_fn = __rt.fn,
    __rt__currentScope = __rt._currentScope,
    __rt__currentCallStackScope = __rt._currentCallStackScope,
    __rt__globalScope = __rt._globalScope,
    __rt__dynamics = __rt._dynamics,
    __rt_css = __rt.css,
    __rt_rulesets = __rt.rulesets,
    __rt_extenders = __rt.extenders,
    __rt__scopeStack = __rt._scopeStack;
__rt_setDynamic("$dark-mode", __rt_getGlobalDefault("$dark_mode") || __False, 0);
__rt_setDynamic("$base_color_name", __rt_getGlobalDefault("$base_color_name") || new __Text("blue", "'"), 1);
__rt_setDynamic("$material-colors", __rt_getGlobalDefault("$material_colors") || new __Map([new __Text("red", "'"), new __Map([new __Text("50", "'"), __ColorRGBA.fromHex("#ffebee"), new __Text("100", "'"), __ColorRGBA.fromHex("#ffcdd2"), new __Text("200", "'"), __ColorRGBA.fromHex("#ef9a9a"), new __Text("300", "'"), __ColorRGBA.fromHex("#e57373"), new __Text("400", "'"), __ColorRGBA.fromHex("#ef5350"), new __Text("500", "'"), __ColorRGBA.fromHex("#f44336"), new __Text("600", "'"), __ColorRGBA.fromHex("#e53935"), new __Text("700", "'"), __ColorRGBA.fromHex("#d32f2f"), new __Text("800", "'"), __ColorRGBA.fromHex("#c62828"), new __Text("900", "'"), __ColorRGBA.fromHex("#b71c1c"), new __Text("a100", "'"), __ColorRGBA.fromHex("#ff8a80"), new __Text("a200", "'"), __ColorRGBA.fromHex("#ff5252"), new __Text("a400", "'"), __ColorRGBA.fromHex("#ff1744"), new __Text("a700", "'"), __ColorRGBA.fromHex("#d50000")]), new __Text("pink", "'"), new __Map([new __Text("50", "'"), __ColorRGBA.fromHex("#fce4ec"), new __Text("100", "'"), __ColorRGBA.fromHex("#f8bbd0"), new __Text("200", "'"), __ColorRGBA.fromHex("#f48fb1"), new __Text("300", "'"), __ColorRGBA.fromHex("#f06292"), new __Text("400", "'"), __ColorRGBA.fromHex("#ec407a"), new __Text("500", "'"), __ColorRGBA.fromHex("#e91e63"), new __Text("600", "'"), __ColorRGBA.fromHex("#d81b60"), new __Text("700", "'"), __ColorRGBA.fromHex("#c2185b"), new __Text("800", "'"), __ColorRGBA.fromHex("#ad1457"), new __Text("900", "'"), __ColorRGBA.fromHex("#880e4f"), new __Text("a100", "'"), __ColorRGBA.fromHex("#ff80ab"), new __Text("a200", "'"), __ColorRGBA.fromHex("#ff4081"), new __Text("a400", "'"), __ColorRGBA.fromHex("#f50057"), new __Text("a700", "'"), __ColorRGBA.fromHex("#c51162")]), new __Text("purple", "'"), new __Map([new __Text("50", "'"), __ColorRGBA.fromHex("#f3e5f5"), new __Text("100", "'"), __ColorRGBA.fromHex("#e1bee7"), new __Text("200", "'"), __ColorRGBA.fromHex("#ce93d8"), new __Text("300", "'"), __ColorRGBA.fromHex("#ba68c8"), new __Text("400", "'"), __ColorRGBA.fromHex("#ab47bc"), new __Text("500", "'"), __ColorRGBA.fromHex("#9c27b0"), new __Text("600", "'"), __ColorRGBA.fromHex("#8e24aa"), new __Text("700", "'"), __ColorRGBA.fromHex("#7b1fa2"), new __Text("800", "'"), __ColorRGBA.fromHex("#6a1b9a"), new __Text("900", "'"), __ColorRGBA.fromHex("#4a148c"), new __Text("a100", "'"), __ColorRGBA.fromHex("#ea80fc"), new __Text("a200", "'"), __ColorRGBA.fromHex("#e040fb"), new __Text("a400", "'"), __ColorRGBA.fromHex("#d500f9"), new __Text("a700", "'"), __ColorRGBA.fromHex("#aa00ff")]), new __Text("deep-purple", "'"), new __Map([new __Text("50", "'"), __ColorRGBA.fromHex("#ede7f6"), new __Text("100", "'"), __ColorRGBA.fromHex("#d1c4e9"), new __Text("200", "'"), __ColorRGBA.fromHex("#b39ddb"), new __Text("300", "'"), __ColorRGBA.fromHex("#9575cd"), new __Text("400", "'"), __ColorRGBA.fromHex("#7e57c2"), new __Text("500", "'"), __ColorRGBA.fromHex("#673ab7"), new __Text("600", "'"), __ColorRGBA.fromHex("#5e35b1"), new __Text("700", "'"), __ColorRGBA.fromHex("#512da8"), new __Text("800", "'"), __ColorRGBA.fromHex("#4527a0"), new __Text("900", "'"), __ColorRGBA.fromHex("#311b92"), new __Text("a100", "'"), __ColorRGBA.fromHex("#b388ff"), new __Text("a200", "'"), __ColorRGBA.fromHex("#7c4dff"), new __Text("a400", "'"), __ColorRGBA.fromHex("#651fff"), new __Text("a700", "'"), __ColorRGBA.fromHex("#6200ea")]), new __Text("indigo", "'"), new __Map([new __Text("50", "'"), __ColorRGBA.fromHex("#e8eaf6"), new __Text("100", "'"), __ColorRGBA.fromHex("#c5cae9"), new __Text("200", "'"), __ColorRGBA.fromHex("#9fa8da"), new __Text("300", "'"), __ColorRGBA.fromHex("#7986cb"), new __Text("400", "'"), __ColorRGBA.fromHex("#5c6bc0"), new __Text("500", "'"), __ColorRGBA.fromHex("#3f51b5"), new __Text("600", "'"), __ColorRGBA.fromHex("#3949ab"), new __Text("700", "'"), __ColorRGBA.fromHex("#303f9f"), new __Text("800", "'"), __ColorRGBA.fromHex("#283593"), new __Text("900", "'"), __ColorRGBA.fromHex("#1a237e"), new __Text("a100", "'"), __ColorRGBA.fromHex("#8c9eff"), new __Text("a200", "'"), __ColorRGBA.fromHex("#536dfe"), new __Text("a400", "'"), __ColorRGBA.fromHex("#3d5afe"), new __Text("a700", "'"), __ColorRGBA.fromHex("#304ffe")]), new __Text("blue", "'"), new __Map([new __Text("50", "'"), __ColorRGBA.fromHex("#e3f2fd"), new __Text("100", "'"), __ColorRGBA.fromHex("#bbdefb"), new __Text("200", "'"), __ColorRGBA.fromHex("#90caf9"), new __Text("300", "'"), __ColorRGBA.fromHex("#64b5f6"), new __Text("400", "'"), __ColorRGBA.fromHex("#42a5f5"), new __Text("500", "'"), __ColorRGBA.fromHex("#2196f3"), new __Text("600", "'"), __ColorRGBA.fromHex("#1e88e5"), new __Text("700", "'"), __ColorRGBA.fromHex("#1976d2"), new __Text("800", "'"), __ColorRGBA.fromHex("#1565c0"), new __Text("900", "'"), __ColorRGBA.fromHex("#0d47a1"), new __Text("a100", "'"), __ColorRGBA.fromHex("#82b1ff"), new __Text("a200", "'"), __ColorRGBA.fromHex("#448aff"), new __Text("a400", "'"), __ColorRGBA.fromHex("#2979ff"), new __Text("a700", "'"), __ColorRGBA.fromHex("#2962ff")]), new __Text("light-blue", "'"), new __Map([new __Text("50", "'"), __ColorRGBA.fromHex("#e1f5fe"), new __Text("100", "'"), __ColorRGBA.fromHex("#b3e5fc"), new __Text("200", "'"), __ColorRGBA.fromHex("#81d4fa"), new __Text("300", "'"), __ColorRGBA.fromHex("#4fc3f7"), new __Text("400", "'"), __ColorRGBA.fromHex("#29b6f6"), new __Text("500", "'"), __ColorRGBA.fromHex("#03a9f4"), new __Text("600", "'"), __ColorRGBA.fromHex("#039be5"), new __Text("700", "'"), __ColorRGBA.fromHex("#0288d1"), new __Text("800", "'"), __ColorRGBA.fromHex("#0277bd"), new __Text("900", "'"), __ColorRGBA.fromHex("#01579b"), new __Text("a100", "'"), __ColorRGBA.fromHex("#80d8ff"), new __Text("a200", "'"), __ColorRGBA.fromHex("#40c4ff"), new __Text("a400", "'"), __ColorRGBA.fromHex("#00b0ff"), new __Text("a700", "'"), __ColorRGBA.fromHex("#0091ea")]), new __Text("cyan", "'"), new __Map([new __Text("50", "'"), __ColorRGBA.fromHex("#e0f7fa"), new __Text("100", "'"), __ColorRGBA.fromHex("#b2ebf2"), new __Text("200", "'"), __ColorRGBA.fromHex("#80deea"), new __Text("300", "'"), __ColorRGBA.fromHex("#4dd0e1"), new __Text("400", "'"), __ColorRGBA.fromHex("#26c6da"), new __Text("500", "'"), __ColorRGBA.fromHex("#00bcd4"), new __Text("600", "'"), __ColorRGBA.fromHex("#00acc1"), new __Text("700", "'"), __ColorRGBA.fromHex("#0097a7"), new __Text("800", "'"), __ColorRGBA.fromHex("#00838f"), new __Text("900", "'"), __ColorRGBA.fromHex("#006064"), new __Text("a100", "'"), __ColorRGBA.fromHex("#84ffff"), new __Text("a200", "'"), __ColorRGBA.fromHex("#18ffff"), new __Text("a400", "'"), __ColorRGBA.fromHex("#00e5ff"), new __Text("a700", "'"), __ColorRGBA.fromHex("#00b8d4")]), new __Text("teal", "'"), new __Map([new __Text("50", "'"), __ColorRGBA.fromHex("#e0f2f1"), new __Text("100", "'"), __ColorRGBA.fromHex("#b2dfdb"), new __Text("200", "'"), __ColorRGBA.fromHex("#80cbc4"), new __Text("300", "'"), __ColorRGBA.fromHex("#4db6ac"), new __Text("400", "'"), __ColorRGBA.fromHex("#26a69a"), new __Text("500", "'"), __ColorRGBA.fromHex("#009688"), new __Text("600", "'"), __ColorRGBA.fromHex("#00897b"), new __Text("700", "'"), __ColorRGBA.fromHex("#00796b"), new __Text("800", "'"), __ColorRGBA.fromHex("#00695c"), new __Text("900", "'"), __ColorRGBA.fromHex("#004d40"), new __Text("a100", "'"), __ColorRGBA.fromHex("#a7ffeb"), new __Text("a200", "'"), __ColorRGBA.fromHex("#64ffda"), new __Text("a400", "'"), __ColorRGBA.fromHex("#1de9b6"), new __Text("a700", "'"), __ColorRGBA.fromHex("#00bfa5")]), new __Text("green", "'"), new __Map([new __Text("50", "'"), __ColorRGBA.fromHex("#e8f5e9"), new __Text("100", "'"), __ColorRGBA.fromHex("#c8e6c9"), new __Text("200", "'"), __ColorRGBA.fromHex("#a5d6a7"), new __Text("300", "'"), __ColorRGBA.fromHex("#81c784"), new __Text("400", "'"), __ColorRGBA.fromHex("#66bb6a"), new __Text("500", "'"), __ColorRGBA.fromHex("#4caf50"), new __Text("600", "'"), __ColorRGBA.fromHex("#43a047"), new __Text("700", "'"), __ColorRGBA.fromHex("#388e3c"), new __Text("800", "'"), __ColorRGBA.fromHex("#2e7d32"), new __Text("900", "'"), __ColorRGBA.fromHex("#1b5e20"), new __Text("a100", "'"), __ColorRGBA.fromHex("#b9f6ca"), new __Text("a200", "'"), __ColorRGBA.fromHex("#69f0ae"), new __Text("a400", "'"), __ColorRGBA.fromHex("#00e676"), new __Text("a700", "'"), __ColorRGBA.fromHex("#00c853")]), new __Text("light-green", "'"), new __Map([new __Text("50", "'"), __ColorRGBA.fromHex("#f1f8e9"), new __Text("100", "'"), __ColorRGBA.fromHex("#dcedc8"), new __Text("200", "'"), __ColorRGBA.fromHex("#c5e1a5"), new __Text("300", "'"), __ColorRGBA.fromHex("#aed581"), new __Text("400", "'"), __ColorRGBA.fromHex("#9ccc65"), new __Text("500", "'"), __ColorRGBA.fromHex("#8bc34a"), new __Text("600", "'"), __ColorRGBA.fromHex("#7cb342"), new __Text("700", "'"), __ColorRGBA.fromHex("#689f38"), new __Text("800", "'"), __ColorRGBA.fromHex("#558b2f"), new __Text("900", "'"), __ColorRGBA.fromHex("#33691e"), new __Text("a100", "'"), __ColorRGBA.fromHex("#ccff90"), new __Text("a200", "'"), __ColorRGBA.fromHex("#b2ff59"), new __Text("a400", "'"), __ColorRGBA.fromHex("#76ff03"), new __Text("a700", "'"), __ColorRGBA.fromHex("#64dd17")]), new __Text("lime", "'"), new __Map([new __Text("50", "'"), __ColorRGBA.fromHex("#f9fbe7"), new __Text("100", "'"), __ColorRGBA.fromHex("#f0f4c3"), new __Text("200", "'"), __ColorRGBA.fromHex("#e6ee9c"), new __Text("300", "'"), __ColorRGBA.fromHex("#dce775"), new __Text("400", "'"), __ColorRGBA.fromHex("#d4e157"), new __Text("500", "'"), __ColorRGBA.fromHex("#cddc39"), new __Text("600", "'"), __ColorRGBA.fromHex("#c0ca33"), new __Text("700", "'"), __ColorRGBA.fromHex("#afb42b"), new __Text("800", "'"), __ColorRGBA.fromHex("#9e9d24"), new __Text("900", "'"), __ColorRGBA.fromHex("#827717"), new __Text("a100", "'"), __ColorRGBA.fromHex("#f4ff81"), new __Text("a200", "'"), __ColorRGBA.fromHex("#eeff41"), new __Text("a400", "'"), __ColorRGBA.fromHex("#c6ff00"), new __Text("a700", "'"), __ColorRGBA.fromHex("#aeea00")]), new __Text("yellow", "'"), new __Map([new __Text("50", "'"), __ColorRGBA.fromHex("#fffde7"), new __Text("100", "'"), __ColorRGBA.fromHex("#fff9c4"), new __Text("200", "'"), __ColorRGBA.fromHex("#fff59d"), new __Text("300", "'"), __ColorRGBA.fromHex("#fff176"), new __Text("400", "'"), __ColorRGBA.fromHex("#ffee58"), new __Text("500", "'"), __ColorRGBA.fromHex("#ffeb3b"), new __Text("600", "'"), __ColorRGBA.fromHex("#fdd835"), new __Text("700", "'"), __ColorRGBA.fromHex("#fbc02d"), new __Text("800", "'"), __ColorRGBA.fromHex("#f9a825"), new __Text("900", "'"), __ColorRGBA.fromHex("#f57f17"), new __Text("a100", "'"), __ColorRGBA.fromHex("#ffff8d"), new __Text("a200", "'"), __ColorRGBA.fromHex("#ffff00"), new __Text("a400", "'"), __ColorRGBA.fromHex("#ffea00"), new __Text("a700", "'"), __ColorRGBA.fromHex("#ffd600")]), new __Text("amber", "'"), new __Map([new __Text("50", "'"), __ColorRGBA.fromHex("#fff8e1"), new __Text("100", "'"), __ColorRGBA.fromHex("#ffecb3"), new __Text("200", "'"), __ColorRGBA.fromHex("#ffe082"), new __Text("300", "'"), __ColorRGBA.fromHex("#ffd54f"), new __Text("400", "'"), __ColorRGBA.fromHex("#ffca28"), new __Text("500", "'"), __ColorRGBA.fromHex("#ffc107"), new __Text("600", "'"), __ColorRGBA.fromHex("#ffb300"), new __Text("700", "'"), __ColorRGBA.fromHex("#ffa000"), new __Text("800", "'"), __ColorRGBA.fromHex("#ff8f00"), new __Text("900", "'"), __ColorRGBA.fromHex("#ff6f00"), new __Text("a100", "'"), __ColorRGBA.fromHex("#ffe57f"), new __Text("a200", "'"), __ColorRGBA.fromHex("#ffd740"), new __Text("a400", "'"), __ColorRGBA.fromHex("#ffc400"), new __Text("a700", "'"), __ColorRGBA.fromHex("#ffab00")]), new __Text("orange", "'"), new __Map([new __Text("50", "'"), __ColorRGBA.fromHex("#fff3e0"), new __Text("100", "'"), __ColorRGBA.fromHex("#ffe0b2"), new __Text("200", "'"), __ColorRGBA.fromHex("#ffcc80"), new __Text("300", "'"), __ColorRGBA.fromHex("#ffb74d"), new __Text("400", "'"), __ColorRGBA.fromHex("#ffa726"), new __Text("500", "'"), __ColorRGBA.fromHex("#ff9800"), new __Text("600", "'"), __ColorRGBA.fromHex("#fb8c00"), new __Text("700", "'"), __ColorRGBA.fromHex("#f57c00"), new __Text("800", "'"), __ColorRGBA.fromHex("#ef6c00"), new __Text("900", "'"), __ColorRGBA.fromHex("#e65100"), new __Text("a100", "'"), __ColorRGBA.fromHex("#ffd180"), new __Text("a200", "'"), __ColorRGBA.fromHex("#ffab40"), new __Text("a400", "'"), __ColorRGBA.fromHex("#ff9100"), new __Text("a700", "'"), __ColorRGBA.fromHex("#ff6d00")]), new __Text("deep-orange", "'"), new __Map([new __Text("50", "'"), __ColorRGBA.fromHex("#fbe9e7"), new __Text("100", "'"), __ColorRGBA.fromHex("#ffccbc"), new __Text("200", "'"), __ColorRGBA.fromHex("#ffab91"), new __Text("300", "'"), __ColorRGBA.fromHex("#ff8a65"), new __Text("400", "'"), __ColorRGBA.fromHex("#ff7043"), new __Text("500", "'"), __ColorRGBA.fromHex("#ff5722"), new __Text("600", "'"), __ColorRGBA.fromHex("#f4511e"), new __Text("700", "'"), __ColorRGBA.fromHex("#e64a19"), new __Text("800", "'"), __ColorRGBA.fromHex("#d84315"), new __Text("900", "'"), __ColorRGBA.fromHex("#bf360c"), new __Text("a100", "'"), __ColorRGBA.fromHex("#ff9e80"), new __Text("a200", "'"), __ColorRGBA.fromHex("#ff6e40"), new __Text("a400", "'"), __ColorRGBA.fromHex("#ff3d00"), new __Text("a700", "'"), __ColorRGBA.fromHex("#dd2c00")]), new __Text("brown", "'"), new __Map([new __Text("50", "'"), __ColorRGBA.fromHex("#efebe9"), new __Text("100", "'"), __ColorRGBA.fromHex("#d7ccc8"), new __Text("200", "'"), __ColorRGBA.fromHex("#bcaaa4"), new __Text("300", "'"), __ColorRGBA.fromHex("#a1887f"), new __Text("400", "'"), __ColorRGBA.fromHex("#8d6e63"), new __Text("500", "'"), __ColorRGBA.fromHex("#795548"), new __Text("600", "'"), __ColorRGBA.fromHex("#6d4c41"), new __Text("700", "'"), __ColorRGBA.fromHex("#5d4037"), new __Text("800", "'"), __ColorRGBA.fromHex("#4e342e"), new __Text("900", "'"), __ColorRGBA.fromHex("#3e2723")]), new __Text("grey", "'"), new __Map([new __Text("50", "'"), __ColorRGBA.fromHex("#fafafa"), new __Text("100", "'"), __ColorRGBA.fromHex("#f5f5f5"), new __Text("200", "'"), __ColorRGBA.fromHex("#eeeeee"), new __Text("300", "'"), __ColorRGBA.fromHex("#e0e0e0"), new __Text("400", "'"), __ColorRGBA.fromHex("#bdbdbd"), new __Text("500", "'"), __ColorRGBA.fromHex("#9e9e9e"), new __Text("600", "'"), __ColorRGBA.fromHex("#757575"), new __Text("700", "'"), __ColorRGBA.fromHex("#616161"), new __Text("800", "'"), __ColorRGBA.fromHex("#424242"), new __Text("900", "'"), __ColorRGBA.fromHex("#212121")]), new __Text("blue-grey", "'"), new __Map([new __Text("50", "'"), __ColorRGBA.fromHex("#eceff1"), new __Text("100", "'"), __ColorRGBA.fromHex("#cfd8dc"), new __Text("200", "'"), __ColorRGBA.fromHex("#b0bec5"), new __Text("300", "'"), __ColorRGBA.fromHex("#90a4ae"), new __Text("400", "'"), __ColorRGBA.fromHex("#78909c"), new __Text("500", "'"), __ColorRGBA.fromHex("#607d8b"), new __Text("600", "'"), __ColorRGBA.fromHex("#546e7a"), new __Text("700", "'"), __ColorRGBA.fromHex("#455a64"), new __Text("800", "'"), __ColorRGBA.fromHex("#37474f"), new __Text("900", "'"), __ColorRGBA.fromHex("#263238"), new __Text("1000", "'"), __ColorRGBA.fromHex("#11171a")])]), 2);
__rt_setDynamic("$base-color", __rt_getGlobalDefault("$base_color") || __rt_box((__rt_functions.material_color || material_color__fn).apply(__rt.functions, __rt_applySpreadArgs([
    __rt_get("$base_color_name"), 
    new __Text("500", "'")]))), 3);
__rt_setDynamic("$base-highlight-color", __rt_getGlobalDefault("$base_highlight_color") || __rt_box((__rt.functions.material_color || material_color__fn).apply(__rt.functions, __rt_applySpreadArgs([
    __rt_get("$base_color_name"), 
    new __Text("300", "'")]))), 4);
__rt_setDynamic("$base-light-color", __rt_getGlobalDefault("$base_light_color") || __rt_box((__rt.functions.material_color || material_color__fn).apply(__rt.functions, __rt_applySpreadArgs([
    __rt_get("$base_color_name"), 
    new __Text("100", "'")]))), 5);
__rt_setDynamic("$base-dark-color", __rt_getGlobalDefault("$base_dark_color") || __rt_box((__rt.functions.material_color || material_color__fn).apply(__rt.functions, __rt_applySpreadArgs([
    __rt_get("$base_color_name"), 
    new __Text("700", "'")]))), 6);
__rt_setDynamic("$base-pressed-color", __rt_getGlobalDefault("$base_pressed_color") || __rt_box(__rt_registered.darken.apply(__rt.registered, __rt_applySpreadArgs([
    (__rt_test(__rt_get("$dark_mode")) ? __rt_box(__rt.registered.darken.apply(__rt.registered, __rt_applySpreadArgs([
        __rt_get("$base_color"), 
        new __Numeric(15, "%")]))) : __rt_box(__rt.registered.lighten.apply(__rt.registered, __rt_applySpreadArgs([
        __rt_get("$base_color"), 
        new __Numeric(15, "%")])))), 
    new __Numeric(0, "%")]))), 7);
__rt_setDynamic("$base-focused-color", __rt_getGlobalDefault("$base_focused_color") || __rt_box((__rt.functions.material_color || material_color__fn).apply(__rt.functions, __rt_applySpreadArgs([
    __rt_get("$base_color_name"), 
    new __Text("400", "'")]))), 8);
__rt_setDynamic("$base-invisible-color", __rt_getGlobalDefault("$base_invisible_color") || __rt_box(__rt.registered.rgba.apply(__rt.registered, __rt_applySpreadArgs([
    __rt_get("$base_color"), 
    new __Numeric(0), 
    __udf, 
    __udf, 
    __udf]))), 9);
__rt_setDynamic("$material-foreground-colors", __rt_getGlobalDefault("$material_foreground_colors") || new __Map([new __Text("red", "'"), __ColorRGBA.fromHex("#fff"), new __Text("pink", "'"), __ColorRGBA.fromHex("#fff"), new __Text("purple", "'"), __ColorRGBA.fromHex("#fff"), new __Text("deep-purple", "'"), __ColorRGBA.fromHex("#fff"), new __Text("indigo", "'"), __ColorRGBA.fromHex("#fff"), new __Text("blue", "'"), __ColorRGBA.fromHex("#fff"), new __Text("light-blue", "'"), __ColorRGBA.fromHex("#fff"), new __Text("cyan", "'"), __ColorRGBA.fromHex("#fff"), new __Text("teal", "'"), __ColorRGBA.fromHex("#fff"), new __Text("green", "'"), __ColorRGBA.fromHex("#fff"), new __Text("light-green", "'"), __ColorRGBA.fromHex("#222"), new __Text("lime", "'"), __ColorRGBA.fromHex("#222"), new __Text("yellow", "'"), __ColorRGBA.fromHex("#222"), new __Text("amber", "'"), __ColorRGBA.fromHex("#222"), new __Text("orange", "'"), __ColorRGBA.fromHex("#222"), new __Text("deep-orange", "'"), __ColorRGBA.fromHex("#fff"), new __Text("brown", "'"), __ColorRGBA.fromHex("#fff"), new __Text("grey", "'"), __ColorRGBA.fromHex("#222"), new __Text("blue-grey", "'"), __ColorRGBA.fromHex("#fff")]), 10);
__rt_setDynamic("$base-foreground-color", __rt_getGlobalDefault("$base_foreground_color") || __rt_box((__rt.functions.material_foreground_color || material_foreground_color__fn).apply(__rt.functions, __rt_applySpreadArgs([
    __rt_get("$base_color_name")]))), 11);
__rt_setDynamic("$accent_color_name", __rt_getGlobalDefault("$accent_color_name") || new __Text("grey", "'"), 12);
__rt_setDynamic("$accent-color", __rt_getGlobalDefault("$accent_color") || __rt_box((__rt.functions.material_color || material_color__fn).apply(__rt.functions, __rt_applySpreadArgs([
    __rt_get("$accent_color_name"), 
    new __Text("500", "'")]))), 13);
__rt_setDynamic("$accent-light-color", __rt_getGlobalDefault("$accent_light_color") || __rt_box((__rt.functions.material_color || material_color__fn).apply(__rt.functions, __rt_applySpreadArgs([
    __rt_get("$accent_color_name"), 
    new __Text("100", "'")]))), 14);
__rt_setDynamic("$accent-dark-color", __rt_getGlobalDefault("$accent_dark_color") || __rt_box((__rt.functions.material_color || material_color__fn).apply(__rt.functions, __rt_applySpreadArgs([
    __rt_get("$accent_color_name"), 
    new __Text("700", "'")]))), 15);
__rt_setDynamic("$accent-pressed-color", __rt_getGlobalDefault("$accent_pressed_color") || (__rt_test(__rt_get("$dark_mode")) ? __rt_box(__rt.registered.darken.apply(__rt.registered, __rt_applySpreadArgs([
    __rt_get("$accent_color"), 
    new __Numeric(15, "%")]))) : __rt_box(__rt.registered.lighten.apply(__rt.registered, __rt_applySpreadArgs([
    __rt_get("$accent_color"), 
    new __Numeric(15, "%")])))), 16);
__rt_setDynamic("$accent-invisible-color", __rt_getGlobalDefault("$accent_invisible_color") || __rt_box(__rt.registered.rgba.apply(__rt.registered, __rt_applySpreadArgs([
    __rt_get("$accent_color"), 
    new __Numeric(0), 
    __udf, 
    __udf, 
    __udf]))), 17);
__rt_setDynamic("$accent-foreground-color", __rt_getGlobalDefault("$accent_foreground_color") || __rt_box((__rt.functions.material_foreground_color || material_foreground_color__fn).apply(__rt.functions, __rt_applySpreadArgs([
    __rt_get("$accent_color_name")]))), 18);
__rt_setDynamic("$confirm-color", __rt_getGlobalDefault("$confirm_color") || __rt_box((__rt.functions.material_color || material_color__fn).apply(__rt.functions, __rt_applySpreadArgs([
    new __Text("light-green", "'"), 
    new __Text("600", "'")]))), 19);
__rt_setDynamic("$confirm-pressed-color", __rt_getGlobalDefault("$confirm_pressed_color") || (__rt_test(__rt_get("$dark_mode")) ? __rt_box(__rt.registered.darken.apply(__rt.registered, __rt_applySpreadArgs([
    __rt_get("$confirm_color"), 
    new __Numeric(15, "%")]))) : __rt_box(__rt.registered.lighten.apply(__rt.registered, __rt_applySpreadArgs([
    __rt_get("$confirm_color"), 
    new __Numeric(15, "%")])))), 20);
__rt_setDynamic("$alert-color", __rt_getGlobalDefault("$alert_color") || __rt_box((__rt.functions.material_color || material_color__fn).apply(__rt.functions, __rt_applySpreadArgs([
    new __Text("red", "'"), 
    new __Text("800", "'")]))), 21);
__rt_setDynamic("$alert-pressed-color", __rt_getGlobalDefault("$alert_pressed_color") || (__rt_test(__rt_get("$dark_mode")) ? __rt_box(__rt.registered.darken.apply(__rt.registered, __rt_applySpreadArgs([
    __rt_get("$alert_color"), 
    new __Numeric(15, "%")]))) : __rt_box(__rt.registered.lighten.apply(__rt.registered, __rt_applySpreadArgs([
    __rt_get("$alert_color"), 
    new __Numeric(15, "%")])))), 22);
__rt_setDynamic(__strings._, __rt_getGlobalDefault(__strings._) || (__rt_test(__rt_get("$dark_mode")) ? __ColorRGBA.fromHex("#ffffff") : __ColorRGBA.fromHex("#111111")), 23);
__rt_setDynamic("$reverse-color", __rt_getGlobalDefault("$reverse_color") || (__rt_test(__rt_get("$dark_mode")) ? __ColorRGBA.fromHex("#222") : __ColorRGBA.fromHex("#fff")), 24);
__rt_setDynamic("$highlight-color", __rt_getGlobalDefault("$highlight_color") || __rt_box(__rt.registered.rgba.apply(__rt.registered, __rt_applySpreadArgs([
    __rt_get(__strings._), 
    new __Numeric(0.54), 
    __udf, 
    __udf, 
    __udf]))), 25);
__rt_setDynamic("$disabled-color", __rt_getGlobalDefault("$disabled_color") || __rt_box(__rt.registered.rgba.apply(__rt.registered, __rt_applySpreadArgs([
    __rt_get(__strings._), 
    new __Numeric(0.38), 
    __udf, 
    __udf, 
    __udf]))), 26);
__rt_setDynamic("$reverse-disabled-color", __rt_getGlobalDefault("$reverse_disabled_color") || __rt_box(__rt.registered.rgba.apply(__rt.registered, __rt_applySpreadArgs([
    __rt_get("$reverse_color"), 
    new __Numeric(0.38), 
    __udf, 
    __udf, 
    __udf]))), 27);
__rt_setDynamic("$divider-color", __rt_getGlobalDefault("$divider_color") || __rt_box(__rt.registered.mix.apply(__rt.registered, __rt_applySpreadArgs([
    __rt_get(__strings._), 
    __rt_get("$reverse_color"), 
    new __Numeric(12, "%")]))), 28);
__rt_setDynamic("$selected-background-color", __rt_getGlobalDefault("$selected_background_color") || (__rt_test(__rt_get("$dark_mode")) ? __rt_get("$base_dark_color") : __rt_box((__rt.functions.material_color || material_color__fn).apply(__rt.functions, __rt_applySpreadArgs([
    new __Text("grey", "'"), 
    new __Text("300", "'")])))), 29);
__rt_setDynamic("$hovered-background-color", __rt_getGlobalDefault("$hovered_background_color") || (__rt_test(__rt_get("$dark_mode")) ? __ColorRGBA.fromHex("#4d4d4d") : __rt_box((__rt.functions.material_color || material_color__fn).apply(__rt.functions, __rt_applySpreadArgs([
    new __Text("grey", "'"), 
    new __Text("200", "'")])))), 30);
__rt_setDynamic("$header-background-color", __rt_getGlobalDefault("$header_background_color") || (__rt_test(__rt_get("$dark_mode")) ? __rt_box((__rt.functions.material_color || material_color__fn).apply(__rt.functions, __rt_applySpreadArgs([
    new __Text("grey", "'"), 
    new __Text("800", "'")]))) : __rt_box((__rt.functions.material_color || material_color__fn).apply(__rt.functions, __rt_applySpreadArgs([
    new __Text("grey", "'"), 
    new __Text("100", "'")])))), 31);
__rt_setDynamic("$faded-color", __rt_getGlobalDefault("$faded_color") || (__rt_test(__rt_get("$dark_mode")) ? __ColorRGBA.fromHex("#4d4d4d") : __ColorRGBA.fromHex("#e1e1e1")), 32);
__rt_setDynamic("$background-color", __rt_getGlobalDefault("$background_color") || (__rt_test(__rt_get("$dark_mode")) ? __ColorRGBA.fromHex("#303030") : __ColorRGBA.fromHex("#fafafa")), 33);
__rt_setDynamic("$alt-background-color", __rt_getGlobalDefault("$alt_background_color") || (__rt_test(__rt_get("$dark_mode")) ? __ColorRGBA.fromHex("#3a3a3a") : __ColorRGBA.fromHex("#f5f5f5")), 34);
__rt_setDynamic("$reverse-background-color", __rt_getGlobalDefault("$reverse_background_color") || (__rt_test(__rt_get("$dark_mode")) ? __ColorRGBA.fromHex("#fafafa") : __ColorRGBA.fromHex("#303030")), 35);
__rt_setDynamic("$reverse-alt-background-color", __rt_getGlobalDefault("$reverse_alt_background_color") || (__rt_test(__rt_get("$dark_mode")) ? __ColorRGBA.fromHex("#f5f5f5") : __ColorRGBA.fromHex("#3a3a3a")), 36);
__rt_setDynamic("$overlay-color", __rt_getGlobalDefault("$overlay_color") || (__rt_test(__rt_get("$dark_mode")) ? __rt_box(__rt.registered.rgba.apply(__rt.registered, __rt_applySpreadArgs([
    __ColorRGBA.fromHex("#fff"), 
    new __Numeric(0.03), 
    __udf, 
    __udf, 
    __udf]))) : __rt_box(__rt.registered.rgba.apply(__rt.registered, __rt_applySpreadArgs([
    __ColorRGBA.fromHex("#000"), 
    new __Numeric(0.03), 
    __udf, 
    __udf, 
    __udf])))), 37);
__rt_setDynamic("$content-padding", __rt_getGlobalDefault("$content_padding") || new __Numeric(16, "px"), 38);
},
 {
	":root": [
		"dark-mode",
		"base-color",
		"base-highlight-color",
		"base-light-color",
		"base-dark-color",
		"base-pressed-color",
		"base-focused-color",
		"base-invisible-color",
		"base-foreground-color",
		"accent-color",
		"accent-light-color",
		"accent-dark-color",
		"accent-pressed-color",
		"accent-invisible-color",
		"accent-foreground-color",
		"confirm-color",
		"confirm-pressed-color",
		"alert-color",
		"alert-pressed-color",
		__names._,
		"reverse-color",
		"highlight-color",
		"disabled-color",
		"reverse-disabled-color",
		"divider-color",
		"selected-background-color",
		"hovered-background-color",
		"header-background-color",
		"faded-color",
		"background-color",
		"alt-background-color",
		"reverse-background-color",
		"reverse-alt-background-color",
		"overlay-color",
		"content-padding"
	],
	"html": [
		"base_color_name",
		"accent_color_name"
	]});
})(Fashion);