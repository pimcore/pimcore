Ext.define("Ext.locale.it.form.field.ComboBox", {
    override: "Ext.form.field.ComboBox",

    valueNotFoundText: undefined
}, function() {
    Ext.apply(Ext.form.field.ComboBox.prototype.defaultListConfig, {
        loadingText: "Caricamento..."
    });
});
