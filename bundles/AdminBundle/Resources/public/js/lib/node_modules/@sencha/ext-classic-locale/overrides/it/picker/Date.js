Ext.define("Ext.locale.it.picker.Date", {
    override: 'Ext.picker.Date',

    todayText: 'Oggi',
    ariaTitle: 'Scegli Data: {0}',
    ariaTitleDateFormat: 'F d',
    todayTip: '{0} (Barra spaziatrice)',
    minText: 'Data precedente alla data minima',
    ariaMinText: 'La data \u00E8 minore di quella minima consentita',
    maxText: 'Data successiva alla data massima',
    ariaMaxText: 'La data \u00E8 maggiore di quella massima consentita',
    disabledDaysText: 'Disabilitato',
    ariaDisabledDaysText: 'Questo giorno \u00E8 disabilitato',
    disabledDatesText: 'Disabilitato',
    ariaDisabledDatesText: 'Questa data \u00E8 disabilitata',
    nextText: 'Mese successivo (CTRL+Destra)',
    prevText: 'Mese precedente (CTRL+Sinistra)',
    monthYearText: 'Scegli un Mese (CTRL+Sopra/Sotto per cambiare anno)',
    monthYearFormat: 'F Y',
    startDay: 0,
    longDayFormat: 'd F Y'
});
