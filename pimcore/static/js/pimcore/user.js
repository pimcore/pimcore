pimcore.registerNS("pimcore.user");

pimcore.user = Class.create({

    initialize: function(object) {
        Object.extend(this, object);
    },

    isAllowed: function (type) {
       
        if (this.permissionInfo[type]) {
            return this.permissionInfo[type].granted;
        }
        return false;
    }
});