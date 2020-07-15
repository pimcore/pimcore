var color, toolbarIsDynamic, head, meta;

Ext.require('Ext.theme.Material');

if (Ext.platformTags.android &&
    Ext.platformTags.chrome &&
    Ext.manifest.material &&
    Ext.manifest.material.toolbar) {
    color = Ext.manifest.material.toolbar.color;
    toolbarIsDynamic = Ext.manifest.material.toolbar.dynamic;
    head = document.head;

    if (toolbarIsDynamic && Ext.supports.CSSVariables) {
        color = getComputedStyle(document.body).getPropertyValue('--primary-color-md');
        color = color.replace(/ /g, '').replace(/^#(?:\\3)?/, '#');
    }

    if (color) {
        meta = document.createElement('meta');
        meta.setAttribute('name', 'theme-color');
        meta.setAttribute('content', color);
        head.appendChild(meta);
    }
}

Ext.namespace('Ext.theme.is').Material = true;
Ext.theme.name = 'Material';
