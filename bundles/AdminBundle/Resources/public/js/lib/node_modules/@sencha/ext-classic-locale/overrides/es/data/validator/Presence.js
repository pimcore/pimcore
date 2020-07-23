Ext.define("Ext.locale.es.data.validator.Presence", {
    override: "Ext.data.validator.Presence",
    message: "Este campo es obligatorio",
    getMessage: function() {
        var me = this;

        return me.message || me.config.message;
    }
});
