/**
 * A Ext.mixin.Observable subclass that is provided for backward compatibility.
 * Applications should avoid using this class, and use Ext.mixin.Observable instead.
 */
Ext.define('Ext.util.Observable', {
    extend: 'Ext.mixin.Observable',

    // The constructor of Ext.util.Observable instances processes the config object by
    // calling Ext.apply(this, config); instead of this.initConfig(config);
    $applyConfigs: true
});
