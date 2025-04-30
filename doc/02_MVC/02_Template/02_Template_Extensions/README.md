# Twig Extensions

## Introduction

Twig Extensions are functions, filters and more that offer special functionality to increase usability of view scripts. 

Following an overview of some [Twig Extensions](https://twig.symfony.com/doc/2.x/#reference):   
- `render` 
- `render_esi` 
- `controller` 
- `asset` 
- `csrf_token`  
- `path` 
- `absolute_url` 
- `translator` 
- `trans`
- `url` 
- `relative_path` 

  
In addition to the standard Twig extensions, Pimcore adds some additional powerful extensions. 
  
## Pimcore Twig Extensions

All Twig extension functions are described below in detail, the following tables give just a short overview of all available extensions.

| Extension                                                               | Description                                                                       |
|-------------------------------------------------------------------------|-----------------------------------------------------------------------------------|
| `pimcorecache`                                                          | Simple in-template caching functionality                                          |
| `pimcore_cache()` (deprecated)                                          | Simple in-template caching functionality (deprecated legacy version)              |
| `pimcore_device()`                                                      | Helps implementing adaptive designs                                               |
| `pimcore_glossary`                                                      | Twig Filter: Apply filter on content to pass it to Glossary engine                |
| `pimcore_placeholder()`                                                 | Adding and embedding custom placeholders, e.g. for special header tags, etc.      |
| `pimcore_head_link()`                                                   | Embeding / managing referenced stylesheets (alternative to `assets()`)            |
| `pimcore_head_meta()`                                                   | Managing your \<meta\> elements in your HTML document                             |
| `pimcore_head_script()`                                                 | Managing your \<scripts\> elements                                                |
| `pimcore_head_style()`                                                  | Managing inline styles (pendant to `headLink()` for inline styles)                |
| `pimcore_head_title()`                                                  | Create and store the HTML document's `<title>` for later retrieval and output     |
| `pimcore_inc()`                                                         | Use this function to directly include a Pimcore document                          |
| `pimcore_inline_script`                                                 | Managing inline scripts (pendant to `headScript()` for inline scripts)            |
| `pimcore_build_nav()`, `pimcore_render_nav()`, `pimcore_nav_renderer()` | Embed and build navigations based on the document structure                       |
| `pimcore_url()`                                                         | An alternative to `url()` and `path()`                                            |
| `pimcore_website_config()`                                              | Fetch website settings or specific setting (first param: key) for the current site |
| `pimcore_image_thumbnail()`                                             | Returns a path to a given thumbnail on image                                      |
| `pimcore_image_thumbnail_html()`                                        | Returns html for displaying the thumbnail image                                   |
| `pimcore_supported_locales()`                                           | Use this function to get a list of supported locales                              |

Pimcore also adds some Twig tests for evaluating boolean conditions e.g.
```twig
{# using 'instaceof' checks if object is instanceof provided classname #}
{% if (product is instanceof('App\\Model\\Product\\Car')) %}
    ...
{% endif %}

{# using 'pimcore_data_object' checks if object is instanceof \Pimcore\Model\DataObject\Concrete #}
{% if (product is pimcore_data_object) %}
 ...
{% endif %}
```

The following table gives an overview of all available tests:

| Test                      | Description                                                                      |
|---------------------------|----------------------------------------------------------------------------------|
| `instanceof(classname)`                | Checks if an object is an instance of a given class                 |
| `pimcore_asset`                        | Checks if object is instanceof Asset                                |
| `pimcore_asset_archive`                | Checks if object is instanceof Asset\Archive                        |
| `pimcore_asset_audio`                  | Checks if object is instanceof Asset\Audio                          |
| `pimcore_asset_document`               | Checks if object is instanceof Asset\Document                       |
| `pimcore_asset_folder`                 | Checks if object is instanceof Asset\Folder                         |
| `pimcore_asset_image`                  | Checks if object is instanceof Asset\Image                          |
| `pimcore_asset_text`                   | Checks if object is instanceof Asset\Text                           |
| `pimcore_asset_unknown`                | Checks if object is instanceof Asset\Unknown                        |
| `pimcore_asset_video`                  | Checks if object is instanceof Asset\Video                          |
| `pimcore_data_object`                  | Checks if object is instanceof DataObject\Concrete                  |
| `pimcore_data_object_folder`           | Checks if object is instanceof DataObject\Folder                    |
| `pimcore_data_object_class(classname)` | Checks if object is instanceof Pimcore\Model\DataObject\{Classname} |
| `pimcore_data_object_gallery`          | Checks if object is instanceof DataObject\Data\ImageGallery         |
| `pimcore_data_object_hotspot_image`    | Checks if object is instanceof DataObject\Data\Hotspotimage         |
| `pimcore_document`                     | Checks if object is instanceof Document                             |
| `pimcore_document_email`               | Checks if object is instanceof Document\Email                       |
| `pimcore_document_folder`              | Checks if object is instanceof Document\Folder                      |
| `pimcore_document_hardlink`            | Checks if object is instanceof Document\Hardlink                    |
| `pimcore_document_page`                | Checks if object is instanceof Document\Page                        |
| `pimcore_document_link`                | Checks if object is instanceof Document\Link                        |
| `pimcore_document_page_snippet`        | Checks if object is instanceof Document\PageSnippet                 |
| `pimcore_document_snippet`             | Checks if object is instanceof Document\Snippet                     |

The following tests are only available if the [PimcoreWebToPrintBundle](https://pimcore.com/docs/platform/Web_To_Print/) is enabled and installed:

| Test                      | Description                                                                      |
|---------------------------|----------------------------------------------------------------------------------|
| `pimcore_document_print`               | Checks if object is instanceof PrintAbstract               |
| `pimcore_document_print_container`     | Checks if object is instanceof Printcontainer              |
| `pimcore_document_print_page`          | Checks if object is instanceof Printpage                   |

The following tests are only available if the [PimcoreNewsletterBundle](https://pimcore.com/docs/platform/Newsletter/) is enabled and installed:

| Test                      | Description                                                                      |
|---------------------------|----------------------------------------------------------------------------------|
| `pimcore_document_newsletter`          | Checks if object is instanceof Newsletter                  |

You can also create your own custom Twig Extension to make certain functionalities available to your views.  
Here you can find an example how to [create](https://symfony.com/doc/current/templating/twig_extension.html)
your own Twig Extension.

### `pimcorecache`

This is an implementation of an in-template cache. You can use this to cache some parts directly in the template,
independent of the other global definable caching functionality. This can be useful for templates which need a lot
of calculation or require a huge amount of objects (like navigations, ...).

`{% pimcorecache "cache_key" tags([...]) ttl(int) force(bool) %}`


| Name        | Type             | Description                                                                                                                                                                                                                              |
|-------------|------------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `cache_key` | string           | Key/name of cache item                                                                                                                                                                                                                   |
| `tags`      | string, string[] | One or multiple additional cache tags. The `in_template` cache tag is automatically added to all items. When no ttl is defined the `output` cache tag is additionally added.                                                             |
| `ttl`       | int              | Time to life - lifetime in seconds. If you define no ttl the behavior is like the output cache, so if you make any change in Pimcore, the cache will be flushed. When specifying a lifetime this is independent from changes in the CMS. |
| `force`     | bool             | Force caching, even when request is done within Pimcore admin interface                                                                                                                                                                  |

##### Examples

```twig
{% pimcorecache "test_cache_key" ttl(60) %}
    <h1>This is some cached microtime</h1>
    {{ 'now'|date('U') }}
{% endpimcorecache %}
```

```twig
{# example with all options #}
{% pimcorecache "test_cache_key" ttl(60) tags(['custom_tag']) force(true) %}
    <h1>This is some cached microtime</h1>
    {{ 'now'|date('U') }}
{% endpimcorecache %}
```
    
### `pimcore_cache` (deprecated)
This is a deprecated alternative approach to the `pimcorecache` extension. Use `pimcorecache` instead.

`pimcore_cache( name, lifetime, force)`

| Name         | Type         | Description  |
|--------------|--------------|--------------|
| `name`       | string       | Name of cache item |
| `lifetime`   | int          | Lifetime in seconds. If you define no lifetime the behavior is like the output cache, so if you make any change in Pimcore, the cache will be flushed. When specifying a lifetime this is independent from changes in the CMS. |
| `force`      | bool         | Force caching, even when request is done within Pimcore admin interface |

##### Example

```twig
{% set cache = pimcore_cache("test_cache_key", 60) %}
{% if not cache.start() %}
    <h1>This is some cached microtime</h1>
    {{ 'now'|date('U') }}
    {% do cache.end() %}
{% endif %}
```

### `pimcore_device`

This extension makes it easy to implement "Adaptive Design" in Pimcore. 

##### Arguments
| Name         | Type         | Description  |
|--------------|--------------|--------------|
| `default`    | string       | *optional* Default if no device can be detected |

##### Example
```twig
{% set device = pimcore_device('desktop') %}
{% if device.isPhone() %}
    This is my phone content
{% elseif device.isTablet() %}
    This text is shown on a tablet
{% elseif device.isDesktop() %}
    This is for default desktop Browser
{% endif %}
```
   
For details also see [Adaptive Design](../../../19_Development_Tools_and_Details/21_Adaptive_Design_Helper.md).

### `pimcore_glossary`

The `pimcore_glossary` filter replaces glossary terms. See [Glossary](../../../18_Tools_and_Features/21_Glossary.md) for details.

```twig
{% apply pimcore_glossary %}
My content
{% endapply %}
``` 

### `pimcore_placeholder` 
See [Placeholder Template Extension](00_Placeholder.md)

### `pimcore_head_link` 
See [HeadLink Template Extension](01_HeadLink.md)

### `pimcore_head_meta` 
See [HeadMeta Template Extension](02_HeadMeta.md)

### `pimcore_head_script` 
See [HeadScript Template Extension](03_HeadScript.md)

### `pimcore_head_style` 
See [HeadStyle Template Extension](04_HeadStyle.md)

### `pimcore_head_title` 
See [HeadTitle Template Extension](05_HeadTitle.md)


### `pimcore_inc` 
Use `pimcore_inc()` to include documents (eg. snippets) within views. 
This is especially useful for footers, headers, navigations, sidebars, teasers, ...

`pimcore_inc(document, params, cacheEnabled)`

| Name           | Type         | Description  |
|----------------|--------------|--------------|
| `document`     | PageSnippet &#124; int &#124; string  | Document to include, can be either an ID, a path or even the Document object itself |
| `params`       |  array       | Is optional and should be an array with key value pairs. |
| `enabledCache` |  bool        | Is true by default, set it to false to disable the cache. Hashing is done across source and parameters to ensure a consistent result. |
 
 ##### Example
```twig
{#include path#}
{{ pimcore_inc("/shared/boxes/buttons") }}
 
{#include ID#}
{{ pimcore_inc(256) }}

{#include object#}
{% set doc = pimcore_doc(477) %}
{{ pimcore_inc(doc, {param: 'value'}) }}
  
{#disable caching#}
{{ pimcore_inc(123, null, false) }}
```

When passing parameters to something included with pimcore_inc(), these parameters are not automatically passed to Twig.
The parameters are passed as attributes to the included document, and should be passed to Twig via the document's controller action.

Example:

index.html.twig
```twig
{{ pimcore_inc('/some/other/document', { 'parameterToPass': parameterToPass }) }}
``` 

IndexController.php (whatever controller / method is designated for /some/other/document in the document tree)
```php
public function otherDocumentAction(Request $request): array
{
    return ['parameterToPass' => $request->query->get('parameterToPass')];
}
```

more Convenient way
```php
public function otherDocumentAction(Request $request): Response
{
    return $this->render(":Default:someOtherDocument.html.twig", ['parameterToPass' => $request->query->get('parameterToPass')]);
}
```


someOtherDocument.html.twig (whatever Twig template is actually for /some/other/document in the document tree)
```twig
...
{{ parameterToPass }}
...
```


### `pimcore_inline_script` 
See [InlineScript Template Extension](06_InlineScript.md)

### Navigation

* `pimcore_build_nav`
* `pimcore_render_nav`
* `pimcore_nav_renderer`

Used to interact with navigations. See [Navigation](../../../03_Documents/03_Navigation.md) for details. Simplified example:

```twig
{% set navigation = pimcore_build_nav({
    active: document,
    root: navRootDocument
}) %}
{{ pimcore_render_nav(navigation) }}

{# you can also fetch the renderer instance and call custom render methods #}
{% set renderer = pimcore_nav_renderer('menu') %}
{{ renderer.render(navigation) }}
```


### `pimcore_url` 
An alternative to `url()` and `path()` which used Url. 

```twig
{{ pimcore_url(params, name, reset, encode, relative) }}
```

All parameters are optional here:

| Name         | Type   | Description  |
|--------------|--------|--------------|
| `params`     | array  | Route params. If object is passed in the params then link generator will be used to generate Url  |
| `name`       | string | Route name |
| `reset`      | bool   | Is false by default, set it to false to avoid merging parameters from request  |
| `encode`     | bool   | Is true by default, set it to false to disable encoding |
| `relative`   | bool   | Is false by default, set it to true to generate a relative path based on the current request path |

 ##### Example
```twig
{% set object = pimcore_object(769) %}
{{  pimcore_url({'object': object}) }}
```
