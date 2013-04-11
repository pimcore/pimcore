pimcore.registerNS("pimcore.plugin.example");

pimcore.plugin.example = Class.create(pimcore.plugin.admin, {
    getClassName: function() {
        return "pimcore.plugin.example";
    },

    initialize: function() {
        pimcore.plugin.broker.registerPlugin(this);
    },
 
    pimcoreReady: function (params,broker){
        // alert("Example Ready!");
    }
});

var examplePlugin = new pimcore.plugin.example();

