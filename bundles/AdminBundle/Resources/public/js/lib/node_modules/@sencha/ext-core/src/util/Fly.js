/**
 * This class is a base for classes that want to provide a `fly` static method.
 *
 * For example:
 *
 *      Ext.define('Foo.util.Thing', {
 *          extend: 'Ext.util.Fly',
 *
 *          // useful stuff
 *      });
 *
 *      var thing = Ext.util.Thing.fly(42);  // passes 42 to the reset method
 *
 *      // use "thing"
 *
 *      thing.release();   // return to the pool for future reuse
 *
 * @private
 */
Ext.define('Ext.util.Fly', {
    inheritableStatics: {
        flyPoolSize: 2,

        /**
         * @method
         * Returns a flyweight instance. These instances should be returned when no
         * longer needed by calling `release`.
         *
         * Additional arguments passed to this method will be passed on to the `reset`
         * method.
         *
         * @return {Ext.util.Fly} the flyweight instance
         */
        fly: function() {
            var T = this,
                flyweights = T.flyweights || (T.flyweights = []),
                instance = flyweights.length ? flyweights.pop() : new T();

            instance.reset.apply(instance, arguments);

            return instance;
        }
    },

    /**
     * This method should be called when a flyweight instance is no longer needed and
     * should be returned to the flyweight pool.
     */
    release: function() {
        var me = this,
            T = me.self,
            flyweights = T.flyweights || (T.flyweights = []);

        me.reset();

        if (flyweights.length < T.flyPoolSize) {
            flyweights.push(me);
        }
    },

    /**
     * Resets this instance to prepare for use. Derived classes may accept additional
     * arguments.
     *
     * When called with no arguments, the class should relinquish any resources it can
     * and prepare to wait for potential reuse.
     *
     * @method reset
     * @chainable
     * @return {Ext.util.Fly} this
     */
    reset: Ext.emptyFn
});
