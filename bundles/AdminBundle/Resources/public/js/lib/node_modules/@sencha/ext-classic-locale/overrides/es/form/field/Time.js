Ext.define("Ext.locale.es.form.field.Time", {
    override: "Ext.form.field.Time",
    minText: "La hora en este campo debe ser igual o posterior a {0}",
    maxText: "La hora en este campo debe ser igual o anterior a {0}",
    invalidText: "{0} no es una hora válida",
    format: "g:i A",
    altFormats: "g:ia|g:iA|g:i a|g:i A|h:i|g:i|H:i|ga|ha|gA|h a|g a|g A|gi|hi|gia|hia|g|H"
});
