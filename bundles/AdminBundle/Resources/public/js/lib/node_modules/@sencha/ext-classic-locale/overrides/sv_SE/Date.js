/**
 * Swedish translation (utf8-encoding)
 * By Erik Andersson, Monator Technologies
 * 24 April 2007
 * Changed by Cariad, 29 July 2007
 */
Ext.onReady(function() {
    if (Ext.Date) {
        Ext.Date.monthNames = ["januari", "februari", "mars", "april", "maj", "juni", "juli", "augusti", "september", "oktober", "november", "december"];
        Ext.Date.dayNames = ["söndag", "måndag", "tisdag", "onsdag", "torsdag", "fredag", "lördag"];

        Ext.Date.formatCodes.a = "(m.getHours() < 12 ? 'em' : 'fm')";
        Ext.Date.formatCodes.A = "(m.getHours() < 12 ? 'EM' : 'FM')";
        Ext.Date.parseCodes.a = {
            g: 1,
            c: "if (/(em)/i.test(results[{0}])) {\n" + "if (!h || h == 12) { h = 0; }\n" + "} else { if (!h || h < 12) { h = (h || 0) + 12; }}",
            s: "(em|fm|EM|FM)",
            calcAtEnd: true
        };
        Ext.Date.parseCodes.A = {
            g: 1,
            c: "if (/(em)/i.test(results[{0}])) {\n" + "if (!h || h == 12) { h = 0; }\n" + "} else { if (!h || h < 12) { h = (h || 0) + 12; }}",
            s: "(EM|FM|em|fm)",
            calcAtEnd: true
        };
    }
});
