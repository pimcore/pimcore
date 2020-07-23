Ext.define("Ext.locale.it.form.field.Date", {
    override: "Ext.form.field.Date",

    format: "d/m/Y",
    ariaFormat: 'M j Y',
    altFormats: "d-m-y|d-m-Y|d/m|d-m|dm|dmy|dmY|d|Y-m-d",
    disabledDaysText: "Disabilitato",
    ariaDisabledDaysText: "Questo giorno \u00E8 disabilitato",
    disabledDatesText: "Disabilitato",
    ariaDisabledDatesText: "Questa data non pu\u00F2 essere selezionata",
    minText: "La data deve essere maggiore o uguale a {0}",
    ariaMinText: "La data deve essere maggiore o uguale a {0}",
    maxText: "La data deve essere minore o uguale a {0}",
    ariaMaxText: "La data deve essere minore o uguale a {0}",
    invalidText: "{0} non \u00E8 una data valida, deve essere nel formato {1}",
    formatText: "Il formato richiesto \u00E8 {1}"
});
