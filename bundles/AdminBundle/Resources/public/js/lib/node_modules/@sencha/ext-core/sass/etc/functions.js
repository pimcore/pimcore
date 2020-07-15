exports.init = function(runtime) {
    runtime.register({
        map_merge: function(map1, map2) {
            var ret = new Fashion.Map(map1 && map1.items);

            if (map2) {
                for (var items = map2.items, i = 0; i < items.length; i += 2) {
                    ret.put(items[i], items[i + 1]);
                }
            }

            return ret;
        },

        /**
         * Inspects the arguments in current scope, filters them down to only the ones
         * supported by a pair of mixins, and returns the result as a fashion map.
         * 
         * @param mixinName1
         * @param mixinName2
         * @returns {Fashion.Map}
         * @private
         */
        intersect_arguments: function(mixinName1, mixinName2) {
            var r = runtime;

            mixinName1 = mixinName1.value.replace(/-/g, '_');
            mixinName2 = mixinName2.value.replace(/-/g, '_');

            var preprocessor = runtime.context.preprocessor,
                currentScopeArgs = runtime._currentScope.map,
                mixin1 = preprocessor.mixinDeclarations[mixinName1],
                mixin2 = preprocessor.mixinDeclarations[mixinName2],
                supportedArgs1 = {},
                supportedArgs2 = {},
                args = new Fashion.Map,
                name, translatedName;

            mixin1.parameters.forEach(function(param) {
                supportedArgs1[param.name] = 1;
            });

            mixin2.parameters.forEach(function(param) {
                supportedArgs2[param.name] = 1;
            });

            for (name in currentScopeArgs) {
                translatedName = name.replace(/_/g, '-');

                if (supportedArgs1[translatedName] && supportedArgs2[translatedName]) {
                    args.put(runtime.box(translatedName.substr(1)), currentScopeArgs[name]);
                }
            }

            return args;
        }
    });
};
