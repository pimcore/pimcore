# Pimcore Templates

## Introduction

In general the templates are located in: `templates/[controller]/[action].html.twig` 
but [Symfony-style locations](https://symfony.com/doc/4.4/best_practices.html#use-the-default-directory-structure) also work (both controller as well as action without their suffix).  

Pimcore uses the Twig templating engine, you can use Twig exactly as documented in:

* [Twig Documentation](https://twig.symfony.com/doc/3.x/)
* [Symfony Templating Documentation](https://symfony.com/doc/current/templating.html)
* Check also our [Demo](https://github.com/pimcore/demo) as starting point

Just use attributes or render the view directly to use Twig:

```php
<?php

namespace App\Controller;

use Pimcore\Controller\FrontendController;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\HttpFoundation\Response;

class MyController extends FrontendController
{
    /**
     * The attribute will resolve the defined view
     */
    #[Template('content/default.html.twig', vars: ['param1' => 'value1'])]
    public function attributeAction(): void
    {   
    }
    
    public function directRenderAction(): Response
    {
        return $this->render('my/custom/action.html.twig', ['param1' => 'value1']);
    }
}
```

Of course, you can just set a custom template for Pimcore documents in the admin interface and that
template will be used for auto-rendering when the controller does not return a response.


## Twig Reference

To make Pimcore's functions available in Twig templates, Pimcore implements a set of extensions. Please see our [Demo](https://github.com/pimcore/demo)
as first reference how to use Pimcore with Twig. 

You can take a look at the [implementations](https://github.com/pimcore/pimcore/tree/11.x/lib/Twig)
for further details. Note that all of Pimcore's Twig extensions are prefixed with `pimcore` to avoid naming collisions.

### Pimcore Editables

```twig
<h1>{{ pimcore_input('headline') }}</h1>

{{ pimcore_wysiwyg('content') }}

{{ pimcore_select('type', { reload: true, store: [["video","video"], ["image","image"]] }) }}
```

Please note that if you store the editable in a variable, you'll need to pipe it through the raw filter on output if it
generates HTML as otherwise the HTML will be escaped by twig.

```twig
{% set content = pimcore_wysiwyg('content') %}

{# this will be escaped HTML #}
{{ content }}

{# HTML will be rendered #}
{{ content|raw }}
```

### Functions

#### Loading Objects

The following functions can be used to load Pimcore elements from within a template:

* `pimcore_document`
* `pimcore_document_by_path`
* `pimcore_site`
* `pimcore_asset`
* `pimcore_asset_by_path`
* `pimcore_object`
* `pimcore_object_by_path`

```twig
{% set myObject = pimcore_object(123) %}
{{ myObject.getTitle() }}
```
or
```twig
{% set myObject = pimcore_object_by_path("/path/to/my/object") %}
{{ myObject.title }}
```

For documents, Pimcore also provides a function to handle hardlinks through the `pimcore_document_wrap_hardlink` method.

See [PimcoreObjectExtension](https://github.com/pimcore/pimcore/blob/11.x/lib/Twig/Extension/PimcoreObjectExtension.php)
for details.


#### Subrequests

```twig
{# include another document #}
{{ pimcore_inc('/snippets/foo') }}
```

See [Template Extensions](./02_Template_Extensions/README.md) for details.


#### Templating Extensions

The following extensions can directly be used on Twig. See [Template Extensions](./02_Template_Extensions/README.md) for a 
detailed description of every helper:

**Functions:**
* `pimcore_head_link`
* `pimcore_head_meta`
* `pimcore_head_script`
* `pimcore_head_style`
* `pimcore_head_title`
* `pimcore_inline_script`
* `pimcore_placeholder`
* `pimcore_url`
* `pimcore_cache` (deprecated)

**Tags:**
* `pimcorecache`

#### Block elements

As Twig does not provide a `while` control structure which is needed to iterate a [Block](../../03_Documents/01_Editables/06_Block.md)
editable, we introduced a function called `pimcore_iterate_block` to allow walking through every block element:

```twig
{% pimcoreblock "contentblock" %}
    <h2>{{ pimcore_input("subline") }}</h2>
    {{ pimcore_wysiwyg("content") }}
{% endpimcoreblock %}
```

### Tests

#### `instanceof`

Can be used to test if an object is an instance of a given class.

```twig
{% if image is instanceof('\\Pimcore\\Model\\Asset\\Image') %}
    {# ... #}
{% endif %}
```

####  Pimcore Specialities

Pimcore provides a few special functionalities to make templates even more powerful. 
These are explained in following sub chapters:

* [Template inheritance and Layouts](./01_Layouts.md) - Use layouts and template inheritance to define everything that repeats on a page. 
* [Template Extensions](./02_Template_Extensions/README.md) - Use twig extensions for things like includes, translations, cache, glossary, etc.
* [Thumbnails](./04_Thumbnails.md) - Learn how to include images into templates with using Thumbnails.
