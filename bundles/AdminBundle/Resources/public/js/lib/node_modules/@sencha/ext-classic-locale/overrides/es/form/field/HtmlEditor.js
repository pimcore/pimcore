Ext.define("Ext.locale.es.form.field.HtmlEditor", {
    override: "Ext.form.field.HtmlEditor",
    createLinkText: "Por favor proporcione la URL para el enlace:"
}, function() {
    Ext.apply(Ext.form.field.HtmlEditor.prototype, {
        buttonTips: {
            bold: {
                title: 'Negritas (Ctrl+B)',
                text: 'Transforma el texto seleccionado en Negritas.',
                cls: Ext.baseCSSPrefix + 'html-editor-tip'
            },
            italic: {
                title: 'Itálica (Ctrl+I)',
                text: 'Transforma el texto seleccionado en Itálicas.',
                cls: Ext.baseCSSPrefix + 'html-editor-tip'
            },
            underline: {
                title: 'Subrayado (Ctrl+U)',
                text: 'Subraya el texto seleccionado.',
                cls: Ext.baseCSSPrefix + 'html-editor-tip'
            },
            increasefontsize: {
                title: 'Aumentar la fuente',
                text: 'Aumenta el tamaño de la fuente',
                cls: Ext.baseCSSPrefix + 'html-editor-tip'
            },
            decreasefontsize: {
                title: 'Reducir la fuente',
                text: 'Reduce el tamaño de la fuente.',
                cls: Ext.baseCSSPrefix + 'html-editor-tip'
            },
            backcolor: {
                title: 'Color de fondo',
                text: 'Modifica el color de fondo del texto seleccionado.',
                cls: Ext.baseCSSPrefix + 'html-editor-tip'
            },
            forecolor: {
                title: 'Color de la fuente',
                text: 'Modifica el color del texto seleccionado.',
                cls: Ext.baseCSSPrefix + 'html-editor-tip'
            },
            justifyleft: {
                title: 'Alinear a la izquierda',
                text: 'Alinea el texto a la izquierda.',
                cls: Ext.baseCSSPrefix + 'html-editor-tip'
            },
            justifycenter: {
                title: 'Centrar',
                text: 'Centrar el texto.',
                cls: Ext.baseCSSPrefix + 'html-editor-tip'
            },
            justifyright: {
                title: 'Alinear a la derecha',
                text: 'Alinea el texto a la derecha.',
                cls: Ext.baseCSSPrefix + 'html-editor-tip'
            },
            insertunorderedlist: {
                title: 'Lista de viñetas',
                text: 'Inicia una lista con viñetas.',
                cls: Ext.baseCSSPrefix + 'html-editor-tip'
            },
            insertorderedlist: {
                title: 'Lista numerada',
                text: 'Inicia una lista numerada.',
                cls: Ext.baseCSSPrefix + 'html-editor-tip'
            },
            createlink: {
                title: 'Enlace',
                text: 'Inserta un enlace de hipertexto.',
                cls: Ext.baseCSSPrefix + 'html-editor-tip'
            },
            sourceedit: {
                title: 'Código Fuente',
                text: 'Pasar al modo de edición de código fuente.',
                cls: Ext.baseCSSPrefix + 'html-editor-tip'
            }
        }
    });
});
