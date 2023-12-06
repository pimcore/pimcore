# WYSIWYG Editable

## General

Similar to Textarea and Input you can use the WYSIWYG editable in the templates to provide rich-text editing. TinyMce is installed by default in our demo. Another editor can be installed via the wysiwyg-events you find in `events.js`

## Enable TinyMce

Add the bundle in your `config/bundles.php` file:

```php
\Pimcore\Bundle\TinymceBundle\PimcoreTinymceBundle::class => ['all' => true],
```

After this, make sure PimcoreTinymceBundle is installed with `bin/console pimcore:bundle:list`.
If it is not installed, you can install it with `bin/console pimcore:bundle:install PimcoreTinymceBundle`.

## Add a Custom Editor
Make sure that you add the Editor to `pimcore.wysiwyg.editors`. This array can be used to have different editors for different use cases(documents, objects ...):
```javascript
if(!parent.pimcore.wysiwyg) {
    parent.pimcore.wysiwyg = {};
    parent.pimcore.wysiwyg.editors = [];
}
parent.pimcore.wysiwyg.editors.push('Custom_Editor');
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

## Extending symfony HTML sanitizer configuration

Wysiwyg editables are using symfony HTML sanitizer in order to clean the HTML content from unwanted tags and parameters. Default configuration is defined like this:
```
framework:
    html_sanitizer:
        sanitizers:
            pimcore.wysiwyg_sanitizer:
                allow_elements:
                    p: ['class', 'style']
                    strong: 'class'
                    em: 'class'
                    h1: 'class'
                    a: ['class', 'href', 'target', 'title', 'rel']
                    table: ['class', 'style', 'cellspacing', 'cellpadding', 'border', 'width', 'height']
                    colgroup: 'class'
                    col: ['class', 'style']
                    tbody: 'class'
                    tr: 'class'
                    td: 'class'
                    ul: ['class', 'style']
                    li: ['class', 'style']
                    ol: ['class', 'style']
```
If you want to adapt this configuration please have a look at the [symfony documentation](https://symfony.com/doc/current/html_sanitizer.html). Add your custom configuration to you project, e.g. to `config/packages/html_sanitizer.yaml`

> Note: When using API to set WYSIWYG data, please pass encoded characters for html entities e.g. `<`,`>`, `&` etc.
> The data is encoded by the sanitizer before persisting into db and the same encoded data will be returned by the API.
