Ext.define("Ext.locale.it.form.field.HtmlEditor", {
    override: "Ext.form.field.HtmlEditor",

    createLinkText: 'Inserire un URL per il link:'
}, function() {
    Ext.apply(Ext.form.field.HtmlEditor.prototype, {
        buttonTips: {
            bold: {
                title: 'Grassetto (Ctrl+B)',
                text: 'Testo selezionato in Grassetto.',
                cls: Ext.baseCSSPrefix + 'html-editor-tip'
            },
            italic: {
                title: 'Corsivo (Ctrl+I)',
                text: 'Testo selezionato in Corsivo.',
                cls: Ext.baseCSSPrefix + 'html-editor-tip'
            },
            underline: {
                title: 'Sottolinea (Ctrl+U)',
                text: 'Sottolinea il testo selezionato.',
                cls: Ext.baseCSSPrefix + 'html-editor-tip'
            },
            increasefontsize: {
                title: 'Ingrandisci testo',
                text: 'Aumenta la dimensione del carattere.',
                cls: Ext.baseCSSPrefix + 'html-editor-tip'
            },
            decreasefontsize: {
                title: 'Riduci testo',
                text: 'Diminuisce la dimensione del carattere.',
                cls: Ext.baseCSSPrefix + 'html-editor-tip'
            },
            backcolor: {
                title: 'Colore evidenziazione testo',
                text: 'Modifica il colore di sfondo del testo selezionato.',
                cls: Ext.baseCSSPrefix + 'html-editor-tip'
            },
            forecolor: {
                title: 'Colore carattere',
                text: 'Modifica il colore del testo selezionato.',
                cls: Ext.baseCSSPrefix + 'html-editor-tip'
            },
            justifyleft: {
                title: 'Allinea a sinistra',
                text: 'Allinea il testo a sinistra.',
                cls: Ext.baseCSSPrefix + 'html-editor-tip'
            },
            justifycenter: {
                title: 'Centra',
                text: 'Centra il testo.',
                cls: Ext.baseCSSPrefix + 'html-editor-tip'
            },
            justifyright: {
                title: 'Allinea a destra',
                text: 'Allinea il testo a destra.',
                cls: Ext.baseCSSPrefix + 'html-editor-tip'
            },
            insertunorderedlist: {
                title: 'Elenco puntato',
                text: 'Inserisci un elenco puntato.',
                cls: Ext.baseCSSPrefix + 'html-editor-tip'
            },
            insertorderedlist: {
                title: 'Elenco numerato',
                text: 'Inserisci un elenco numerato.',
                cls: Ext.baseCSSPrefix + 'html-editor-tip'
            },
            createlink: {
                title: 'Collegamento',
                text: 'Trasforma il testo selezionato in un collegamanto.',
                cls: Ext.baseCSSPrefix + 'html-editor-tip'
            },
            sourceedit: {
                title: 'Sorgente',
                text: 'Passa alla modalit\u00E0 modifica del sorgente.',
                cls: Ext.baseCSSPrefix + 'html-editor-tip'
            }
        }
    });
});
