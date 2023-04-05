# WYSIWYG Editable

## General

Similar to Textarea and Input you can use the WYSIWYG editable in the templates to provide rich-text editing. TinyMce is installed by default in our demo. Another editor can be installed via the wysiwyg-events you find in `events.js`

## Add a Custom Editor
Make sure that you add the Editor to `pimcore.wysiwyg.editors`. This array can be used to have different editors for different use cases(documents, objects ...):
```javascript
if(!parent.pimcore.wysiwyg) {
    parent.pimcore.wysiwyg = {};
    parent.pimcore.wysiwyg.editors = [];
}
parent.pimcore.wysiwyg.editors.push('Custom_Ediotor');
```

The Editor als needs to dispatch the `pimcore.events.changeWysiwyg` to set the value from the WYSIWYG-Field in the core.
```javascript
document.dispatchEvent(new CustomEvent(pimcore.events.changeWysiwyg, {
    detail: {
        e: eChange,
        data: tinymce.activeEditor.contentAreaContainer.innerHTML, //text of the editor-field
        context: e.detail.context //the context in which the editor is registered (object, document ...) 
    }
}));
```

Please use the events from `event.js` to bind your Editor on the field and to configure it.
For more details please take a look at the `TinymceBundle`. 