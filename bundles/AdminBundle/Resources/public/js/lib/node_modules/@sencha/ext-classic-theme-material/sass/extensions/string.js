exports.init = function (runtime) {
    runtime.register({
        str_replace_regex: function (str, expression, value, global) {
            var rt = this.getRuntime();

            str = rt.unbox(str);
            expression = rt.unbox(expression);
            value = rt.unbox(value);
            global = rt.unbox(global);
            return str.replace(new RegExp(expression, global ? 'g' : ''), value);
        }
    });
};
