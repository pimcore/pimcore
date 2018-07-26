var Class = (function() {
    function isFunction(object) {
        return typeof object === "function";
    }
    function argumentNames(obj) {
        var names = obj.toString().match(
                /^[\s\(]*function[^(]*\(([^)]*)\)/)[1].replace(
                /\/\/.*?[\r\n]|\/\*(?:.|[\r\n])*?\*\//g, '').replace(
                /\s+/g, '').split(',');
        return names.length == 1 && !names[0] ? [] : names;
    }
    function update(array, args) {
        var arrayLength = array.length, length = args.length;
        while (length--)
            array[arrayLength + length] = args[length];
        return array;
    }
    function wrap(wrapper) {
        var __method = this;
        return function() {
            var a = update( [ __method.bind(this) ], arguments);
            return wrapper.apply(this, a);
        }
    }
    function subclass() {
    }
    function create() {
        var parent = null, properties = Array.from(arguments);
        if (isFunction(properties[0]))
            parent = properties.shift();

        function klass() {
            this.initialize.apply(this, arguments);
        }

        Object.assign(klass, Class.Methods);
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
            klass.prototype.initialize = function() {};

        klass.prototype.constructor = klass;
        return klass;
    }

    function addMethods(source) {
        var ancestor = this.superclass && this.superclass.prototype;
        var properties = Object.keys(source);

        for ( var i = 0, length = properties.length; i < length; i++) {
            var property = properties[i], value = source[property];
            if (ancestor && isFunction(value)
                    && argumentNames(value)[0] == "$super") {
                var method = value;

                value = wrap.bind((function(m) {
                    return function() {
                        return ancestor[m].apply(this, arguments);
                    };
                })(property))(method);

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
