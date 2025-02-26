# WYSIWYG Editable

## General

Similar to Textarea and Input you can use the WYSIWYG editable in the templates to provide rich-text editing. Quill is installed by default in our demo. Another editor can be installed via the wysiwyg-events you find in `events.js`

## Configuration

| Name           | Type     | Description                                                                                                                               |
|----------------|----------|-------------------------------------------------------------------------------------------------------------------------------------------|
| `height`       | bool     | Set true to reload the page in editmode after selecting an item                                                                           |
| `width`        | integer  | Width of the select box in pixel                                                                                                          |
| `class`        | string   | A CSS class that is added to the surrounding container of this element in editmode                                                        |
| `placeholder`  | string   | A text shown in the field when it is empty to guide the user about the expected type of input.                                            |
| `defaultValue` | string   | A default value for the available options. Note: This value needs to be saved before calling getData() or use setDataFromResource().      |
| `required`     | boolean  | (default: false) set to true to make field value required for publish                                                                     |

## Example

```twig
{{ pimcore_wysiwyg("myWYSIWYG", {
    "height": 600,
    "width": 1100,
    "placeholder": "Enter you content"
}) }}
```

## Enable Quill 
Quill is the new recommended editor. Please check the bundle [readme](https://github.com/pimcore/quill-bundle/blob/1.x/README.md) for installation instructions.

## Enable TinyMce (deprecated)
In Pimcore 11 the default editor changed from CKEditor to TinyMCE and has been moved into [PimcoreTinymceBundle](https://github.com/pimcore/pimcore/blob/11.x/bundles/TinymceBundle/README.md). Check the bundle readme for installation instructions.

## Add a Custom Editor
Make sure that you add the Editor to `pimcore.wysiwyg.editors`. This array can be used to have different editors for different use cases (documents, objects ...):
```javascript
if(!parent.pimcore.wysiwyg) {
    parent.pimcore.wysiwyg = {};
    parent.pimcore.wysiwyg.editors = [];
}
parent.pimcore.wysiwyg.editors.push("Custom_Editor");
```

The Editor als needs to dispatch the `pimcore.events.changeWysiwyg` to set the value from the WYSIWYG-Field in the core.
```javascript
document.dispatchEvent(new CustomEvent(pimcore.events.changeWysiwyg, {
    detail: {
        e: {target:{id: textareaId}},
        data: this.activeEditor.getSemanticHTML(), //text of the editor-field
        context: e.detail.context //the context in which the editor is registered (object, document ...) 
    }
}));
```

Please use the events from `event.js` to bind your Editor on the field and to configure it.
For more details please take a look at the `QuillBundle`. 

## Extending symfony HTML sanitizer configuration

Wysiwyg editables are using symfony HTML sanitizer in order to clean the HTML content from unwanted tags and parameters. Default configuration is defined like this:
```
framework:
    html_sanitizer:
        sanitizers:
            pimcore.wysiwyg_sanitizer:
                max_input_length: -1
                allow_attributes:
                    pimcore_type: '*'
                    pimcore_id: '*'
                allow_relative_links: true
                allow_relative_medias: true
                allow_elements:
                    span: [ 'class', 'style', 'id' ]
                    div: [ 'class', 'style', 'id' ]
                    p: [ 'class', 'style', 'id' ]
                    strong: 'class'
                    em: 'class'
                    h1: [ 'class', 'id' ]
                    h2: [ 'class', 'id' ]
                    h3: [ 'class', 'id' ]
                    h4: [ 'class', 'id' ]
                    h5: [ 'class', 'id' ]
                    h6: [ 'class', 'id' ]
                    a: [ 'class', 'id', 'href', 'target', 'title', 'rel', 'style' ]
                    table: [ 'class', 'style', 'cellspacing', 'cellpadding', 'border', 'width', 'height', 'id' ]
                    colgroup: 'class'
                    col: [ 'class', 'style', 'id' ]
                    thead: [ 'class', 'id' ]
                    tbody: [ 'class', 'id' ]
                    tr: [ 'class', 'id' ]
                    td: [ 'class', 'id' ]
                    th: [ 'class', 'id', 'scope' ]
                    ul: [ 'class', 'style', 'id' ]
                    li: [ 'class', 'style', 'id' ]
                    ol: [ 'class', 'style', 'id' ]
                    u: [ 'class', 'id' ]
                    i: [ 'class', 'id' ]
                    b: [ 'class', 'id' ]
                    caption: [ 'class', 'id' ]
                    sub: [ 'class', 'id' ]
                    sup: [ 'class', 'id' ]
                    blockquote: [ 'class', 'id' ]
                    s: [ 'class', 'id' ]
                    iframe: [ 'frameborder', 'height', 'longdesc', 'name', 'sandbox', 'scrolling', 'src', 'title', 'width' ]
                    br: ''
                    img: [ 'class', 'id', 'alt', 'style', 'src' ]
                    hr: ''
```
If you want to adapt this configuration please have a look at the [symfony documentation](https://symfony.com/doc/current/html_sanitizer.html). Add your custom configuration to you project, e.g. to `config/packages/html_sanitizer.yaml`

> Note: When using API to set WYSIWYG data, please pass encoded characters for html entities e.g. `<`,`>`, `&` etc.
> The data is encoded by the sanitizer before persisting into db and the same encoded data will be returned by the API.
