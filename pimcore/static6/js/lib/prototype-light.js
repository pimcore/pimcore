var Prototype = {
  Version: '1.6.1',

  Browser: (function(){
    var ua = navigator.userAgent;
    var isOpera = Object.prototype.toString.call(window.opera) == '[object Opera]';
    return {
      IE:             !!window.attachEvent && !isOpera,
      Opera:          isOpera,
      WebKit:         ua.indexOf('AppleWebKit/') > -1,
      Gecko:          ua.indexOf('Gecko') > -1 && ua.indexOf('KHTML') === -1,
      MobileSafari:   /Apple.*Mobile.*Safari/.test(ua)
    }
  })(),

  BrowserFeatures: {
    XPath: !!document.evaluate,
    SelectorsAPI: !!document.querySelector,
    ElementExtensions: (function() {
      var constructor = window.Element || window.HTMLElement;
      return !!(constructor && constructor.prototype);
    })(),
    SpecificElementExtensions: (function() {
      if (typeof window.HTMLDivElement !== 'undefined')
        return true;

      var div = document.createElement('div');
      var form = document.createElement('form');
      var isSupported = false;

      if (div['__proto__'] && (div['__proto__'] !== form['__proto__'])) {
        isSupported = true;
      }

      div = form = null;

      return isSupported;
    })()
  },

  ScriptFragment: '<script[^>]*>([\\S\\s]*?)<\/script>',
  JSONFilter: /^\/\*-secure-([\s\S]*)\*\/\s*$/,

  emptyFunction: function() { },
  K: function(x) { return x }
};


/* Based on Alex Arnell's inheritance implementation. */

function $A(iterable) {
    if (!iterable)
        return [];
    if ('toArray' in Object(iterable))
        return iterable.toArray();
    var length = iterable.length || 0, results = new Array(length);
    while (length--)
        results[length] = iterable[length];
    return results;
}

var Class = (function() {
    function subclass() {
    }
    ;
    function create() {
        var parent = null, properties = $A(arguments);
        if (Object.isFunction(properties[0]))
            parent = properties.shift();

        function klass() {
            this.initialize.apply(this, arguments);
        }

        Object.extend(klass, Class.Methods);
        klass.superclass = parent;
        klass.subclasses = [];

        if (parent) {
            subclass.prototype = parent.prototype;
            klass.prototype = new subclass;
            parent.subclasses.push(klass);
        }

        for ( var i = 0; i < properties.length; i++)
            klass.addMethods(properties[i]);

        if (!klass.prototype.initialize)
            klass.prototype.initialize = Prototype.emptyFunction;

        klass.prototype.constructor = klass;
        return klass;
    }

    function addMethods(source) {
        var ancestor = this.superclass && this.superclass.prototype;
        var properties = Object.keys(source);

        if (!Object.keys( {
            toString : true
        }).length) {
            if (source.toString != Object.prototype.toString)
                properties.push("toString");
            if (source.valueOf != Object.prototype.valueOf)
                properties.push("valueOf");
        }

        for ( var i = 0, length = properties.length; i < length; i++) {
            var property = properties[i], value = source[property];
            if (ancestor && Object.isFunction(value)
                    && value.argumentNames().first() == "$super") {
                var method = value;
                value = (function(m) {
                    return function() {
                        return ancestor[m].apply(this, arguments);
                    };
                })(property).wrap(method);

                value.valueOf = method.valueOf.bind(method);
                value.toString = method.toString.bind(method);
            }
            this.prototype[property] = value;
        }

        return this;
    }

    return {
        create : create,
        Methods : {
            addMethods : addMethods
        }
    };
})();
(function() {

    var _toString = Object.prototype.toString;

    function extend(destination, source) {
        for ( var property in source)
            destination[property] = source[property];
        return destination;
    }

    function inspect(object) {
        try {
            if (isUndefined(object))
                return 'undefined';
            if (object === null)
                return 'null';
            return object.inspect ? object.inspect() : String(object);
        } catch (e) {
            if (e instanceof RangeError)
                return '...';
            throw e;
        }
    }

    function toQueryString(object) {
        return $H(object).toQueryString();
    }

    function toHTML(object) {
        return object && object.toHTML ? object.toHTML() : String
                .interpret(object);
    }

    function keys(object) {
        var results = [];
        for ( var property in object)
            results.push(property);
        return results;
    }

    function values(object) {
        var results = [];
        for ( var property in object)
            results.push(object[property]);
        return results;
    }

    function clone(object) {
        return extend( {}, object);
    }

    function isElement(object) {
        return !!(object && object.nodeType == 1);
    }

    function isArray(object) {
        return _toString.call(object) == "[object Array]";
    }

    function isHash(object) {
        return object instanceof Hash;
    }

    function isFunction(object) {
        return typeof object === "function";
    }

    function isString(object) {
        return _toString.call(object) == "[object String]";
    }

    function isNumber(object) {
        return _toString.call(object) == "[object Number]";
    }

    function isUndefined(object) {
        return typeof object === "undefined";
    }

    extend(Object, {
        extend : extend,
        inspect : inspect,
        toQueryString : toQueryString,
        toHTML : toHTML,
        keys : keys,
        values : values,
        clone : clone,
        isElement : isElement,
        isArray : isArray,
        isHash : isHash,
        isFunction : isFunction,
        isString : isString,
        isNumber : isNumber,
        isUndefined : isUndefined
    });
})();
Object.extend(Function.prototype,
        (function() {
            var slice = Array.prototype.slice;

            function update(array, args) {
                var arrayLength = array.length, length = args.length;
                while (length--)
                    array[arrayLength + length] = args[length];
                return array;
            }

            function merge(array, args) {
                array = slice.call(array, 0);
                return update(array, args);
            }

            function argumentNames() {
                var names = this.toString().match(
                        /^[\s\(]*function[^(]*\(([^)]*)\)/)[1].replace(
                        /\/\/.*?[\r\n]|\/\*(?:.|[\r\n])*?\*\//g, '').replace(
                        /\s+/g, '').split(',');
                return names.length == 1 && !names[0] ? [] : names;
            }

            function bind(context) {
                if (arguments.length < 2 && Object.isUndefined(arguments[0]))
                    return this;
                var __method = this, args = slice.call(arguments, 1);
                return function() {
                    var a = merge(args, arguments);
                    return __method.apply(context, a);
                }
            }

            function bindAsEventListener(context) {
                var __method = this, args = slice.call(arguments, 1);
                return function(event) {
                    var a = update( [ event || window.event ], args);
                    return __method.apply(context, a);
                }
            }

            function curry() {
                if (!arguments.length)
                    return this;
                var __method = this, args = slice.call(arguments, 0);
                return function() {
                    var a = merge(args, arguments);
                    return __method.apply(this, a);
                }
            }

            function delay(timeout) {
                var __method = this, args = slice.call(arguments, 1);
                timeout = timeout * 1000
                return window.setTimeout(function() {
                    return __method.apply(__method, args);
                }, timeout);
            }

            function defer() {
                var args = update( [ 0.01 ], arguments);
                return this.delay.apply(this, args);
            }

            function wrap(wrapper) {
                var __method = this;
                return function() {
                    var a = update( [ __method.bind(this) ], arguments);
                    return wrapper.apply(this, a);
                }
            }

            function methodize() {
                if (this._methodized)
                    return this._methodized;
                var __method = this;
                return this._methodized = function() {
                    var a = update( [ this ], arguments);
                    return __method.apply(null, a);
                };
            }

            return {
                argumentNames : argumentNames,
                bind : bind,
                bindAsEventListener : bindAsEventListener,
                curry : curry,
                delay : delay,
                defer : defer,
                wrap : wrap,
                methodize : methodize
            }
        })());

function $w(string) {
    if (!Object.isString(string))
        return [];
    string = string.strip();
    return string ? string.split(/\s+/) : [];
}

Array.from = $A;

Array.from = $A;

(function() {
    var arrayProto = Array.prototype, slice = arrayProto.slice, _each = arrayProto.forEach; // use
                                                                                            // native
                                                                                            // browser
                                                                                            // JS
                                                                                            // 1.6
                                                                                            // implementation
                                                                                            // if
                                                                                            // available

    function each(iterator) {
        for ( var i = 0, length = this.length; i < length; i++)
            iterator(this[i]);
    }
    if (!_each)
        _each = each;

    function clear() {
        this.length = 0;
        return this;
    }

    function first() {
        return this[0];
    }

    function last() {
        return this[this.length - 1];
    }

    function compact() {
        return this.select(function(value) {
            return value != null;
        });
    }

    function flatten() {
        return this.inject( [], function(array, value) {
            if (Object.isArray(value))
                return array.concat(value.flatten());
            array.push(value);
            return array;
        });
    }

    function without() {
        var values = slice.call(arguments, 0);
        return this.select(function(value) {
            return !values.include(value);
        });
    }

    function reverse(inline) {
        return (inline !== false ? this : this.toArray())._reverse();
    }

    function uniq(sorted) {
        return this.inject( [],
                function(array, value, index) {
                    if (0 == index
                            || (sorted ? array.last() != value : !array
                                    .include(value)))
                        array.push(value);
                    return array;
                });
    }

    function intersect(array) {
        return this.uniq().findAll(function(item) {
            return array.detect(function(value) {
                return item === value
            });
        });
    }

    function clone() {
        return slice.call(this, 0);
    }

    function size() {
        return this.length;
    }

    function inspect() {
        return '[' + this.map(Object.inspect).join(', ') + ']';
    }

    function indexOf(item, i) {
        i || (i = 0);
        var length = this.length;
        if (i < 0)
            i = length + i;
        for (; i < length; i++)
            if (this[i] === item)
                return i;
        return -1;
    }

    function lastIndexOf(item, i) {
        i = isNaN(i) ? this.length : (i < 0 ? this.length + i : i) + 1;
        var n = this.slice(0, i).reverse().indexOf(item);
        return (n < 0) ? n : i - n - 1;
    }

    function concat() {
        var array = slice.call(this, 0), item;
        for ( var i = 0, length = arguments.length; i < length; i++) {
            item = arguments[i];
            if (Object.isArray(item) && !('callee' in item)) {
                for ( var j = 0, arrayLength = item.length; j < arrayLength; j++)
                    array.push(item[j]);
            } else {
                array.push(item);
            }
        }
        return array;
    }

    Object.extend(arrayProto, Enumerable);

    if (!arrayProto._reverse)
        arrayProto._reverse = arrayProto.reverse;

    Object.extend(arrayProto, {
        _each : _each,
        clear : clear,
        first : first,
        last : last,
        compact : compact,
        flatten : flatten,
        without : without,
        reverse : reverse,
        uniq : uniq,
        intersect : intersect,
        clone : clone,
        toArray : clone,
        size : size,
        inspect : inspect
    });

    var CONCAT_ARGUMENTS_BUGGY = (function() {
        return [].concat(arguments)[0][0] !== 1;
    })(1, 2)

    if (CONCAT_ARGUMENTS_BUGGY)
        arrayProto.concat = concat;

    if (!arrayProto.indexOf)
        arrayProto.indexOf = indexOf;
    if (!arrayProto.lastIndexOf)
        arrayProto.lastIndexOf = lastIndexOf;
})();

var Enumerable = (function() {
    function each(iterator, context) {
        var index = 0;
        try {
            this._each(function(value) {
                iterator.call(context, value, index++);
            });
        } catch (e) {
            if (e != $break)
                throw e;
        }
        return this;
    }

    function eachSlice(number, iterator, context) {
        var index = -number, slices = [], array = this.toArray();
        if (number < 1)
            return array;
        while ((index += number) < array.length)
            slices.push(array.slice(index, index + number));
        return slices.collect(iterator, context);
    }

    function all(iterator, context) {
        iterator = iterator || Prototype.K;
        var result = true;
        this.each(function(value, index) {
            result = result && !!iterator.call(context, value, index);
            if (!result)
                throw $break;
        });
        return result;
    }

    function any(iterator, context) {
        iterator = iterator || Prototype.K;
        var result = false;
        this.each(function(value, index) {
            if (result = !!iterator.call(context, value, index))
                throw $break;
        });
        return result;
    }

    function collect(iterator, context) {
        iterator = iterator || Prototype.K;
        var results = [];
        this.each(function(value, index) {
            results.push(iterator.call(context, value, index));
        });
        return results;
    }

    function detect(iterator, context) {
        var result;
        this.each(function(value, index) {
            if (iterator.call(context, value, index)) {
                result = value;
                throw $break;
            }
        });
        return result;
    }

    function findAll(iterator, context) {
        var results = [];
        this.each(function(value, index) {
            if (iterator.call(context, value, index))
                results.push(value);
        });
        return results;
    }

    function grep(filter, iterator, context) {
        iterator = iterator || Prototype.K;
        var results = [];

        if (Object.isString(filter))
            filter = new RegExp(RegExp.escape(filter));

        this.each(function(value, index) {
            if (filter.match(value))
                results.push(iterator.call(context, value, index));
        });
        return results;
    }

    function include(object) {
        if (Object.isFunction(this.indexOf))
            if (this.indexOf(object) != -1)
                return true;

        var found = false;
        this.each(function(value) {
            if (value == object) {
                found = true;
                throw $break;
            }
        });
        return found;
    }

    function inGroupsOf(number, fillWith) {
        fillWith = Object.isUndefined(fillWith) ? null : fillWith;
        return this.eachSlice(number, function(slice) {
            while (slice.length < number)
                slice.push(fillWith);
            return slice;
        });
    }

    function inject(memo, iterator, context) {
        this.each(function(value, index) {
            memo = iterator.call(context, memo, value, index);
        });
        return memo;
    }

    function invoke(method) {
        var args = $A(arguments).slice(1);
        return this.map(function(value) {
            return value[method].apply(value, args);
        });
    }

    function max(iterator, context) {
        iterator = iterator || Prototype.K;
        var result;
        this.each(function(value, index) {
            value = iterator.call(context, value, index);
            if (result == null || value >= result)
                result = value;
        });
        return result;
    }

    function min(iterator, context) {
        iterator = iterator || Prototype.K;
        var result;
        this.each(function(value, index) {
            value = iterator.call(context, value, index);
            if (result == null || value < result)
                result = value;
        });
        return result;
    }

    function partition(iterator, context) {
        iterator = iterator || Prototype.K;
        var trues = [], falses = [];
        this
                .each(function(value, index) {
                    (iterator.call(context, value, index) ? trues : falses)
                            .push(value);
                });
        return [ trues, falses ];
    }

    function pluck(property) {
        var results = [];
        this.each(function(value) {
            results.push(value[property]);
        });
        return results;
    }

    function reject(iterator, context) {
        var results = [];
        this.each(function(value, index) {
            if (!iterator.call(context, value, index))
                results.push(value);
        });
        return results;
    }

    function sortBy(iterator, context) {
        return this.map(function(value, index) {
            return {
                value : value,
                criteria : iterator.call(context, value, index)
            };
        }).sort(function(left, right) {
            var a = left.criteria, b = right.criteria;
            return a < b ? -1 : a > b ? 1 : 0;
        }).pluck('value');
    }

    function toArray() {
        return this.map();
    }

    function zip() {
        var iterator = Prototype.K, args = $A(arguments);
        if (Object.isFunction(args.last()))
            iterator = args.pop();

        var collections = [ this ].concat(args).map($A);
        return this.map(function(value, index) {
            return iterator(collections.pluck(index));
        });
    }

    function size() {
        return this.toArray().length;
    }

    function inspect() {
        return '#<Enumerable:' + this.toArray().inspect() + '>';
    }

    return {
        each : each,
        eachSlice : eachSlice,
        all : all,
        every : all,
        any : any,
        some : any,
        collect : collect,
        map : collect,
        detect : detect,
        findAll : findAll,
        select : findAll,
        filter : findAll,
        grep : grep,
        include : include,
        member : include,
        inGroupsOf : inGroupsOf,
        inject : inject,
        invoke : invoke,
        max : max,
        min : min,
        partition : partition,
        pluck : pluck,
        reject : reject,
        sortBy : sortBy,
        toArray : toArray,
        entries : toArray,
        zip : zip,
        size : size,
        inspect : inspect,
        find : detect
    };
})();
