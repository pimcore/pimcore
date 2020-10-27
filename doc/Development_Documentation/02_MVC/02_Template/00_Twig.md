# Twig

Pimcore fully supports the Twig templating engine, you can use Twig exactly as documented in:

* [Twig Documentation](https://twig.symfony.com/doc/2.x/)
* [Symfony Templating Documentation](https://symfony.com/doc/3.4/templating.html)


If you use the Pimcore's default [FrontendController](https://github.com/pimcore/pimcore/blob/master/lib/Controller/FrontendController.php),
it will set a special attribute on the request which mimics the [@Template](https://symfony.com/doc/3.0/bundles/SensioFrameworkExtraBundle/annotations/view.html)
annotation and tries to auto-render a view with the same name as the controller action if the controller does not return
a response (see [TemplateControllerInterface](https://github.com/pimcore/pimcore/blob/master/lib/Controller/TemplateControllerInterface.php)
for details). 


Just use annotations or render the view directly to use Twig:

```php
<?php

namespace AppBundle\Controller;

use Pimcore\Controller\FrontendController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class MyController extends FrontendController
{
    /**
     * The annotation will automatically resolve the view to MyController/myAnnotatedAction.html.twig
     * 
     * @Template() 
     */
    public function myAnnotatedAction()
    {   
    }
    
    public function directRenderAction()
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

Although we're working on adding Twig examples throughout the documentation where applicable, here follows a list of 
available Twig extensions. You can take a look at the [implementations](https://github.com/pimcore/pimcore/tree/master/pimcore/lib/Pimcore/Twig)
for further details. Note that all of Pimcore's Twig extensions are prefixed with `pimcore` to avoid naming collisions.

### Pimcore editables

You can use any Pimcore document editable in Twig by prefixing it with `pimcore_` and using the same arguments as in PHP:

<div class="code-section">

```twig
<h1>{{ pimcore_input('headline') }}</h1>

{{ pimcore_wysiwyg('content') }}

{{ pimcore_select('type', { reload: true, store: [["video","video"], ["image","image"]] }) }}
```

```php
<h1><?= $this->input('headline') ?></h1>

<?= $this->wysiwyg('content') ?>

<?= $this->select('type', ['reload' => true, 'store' => [["video","video"], ["image","image"]]]) ?>
```

</div>

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

The following functions can be used to load Pimcore objects in a template (use `<type>`::getById()`):

* `pimcore_document`
* `pimcore_site`
* `pimcore_asset`
* `pimcore_object`

```twig
{% set myObject = pimcore_object(123) %}
{{ myObject.getTitle() }}
```

For documents, Pimcore also provides a function to handle hardlinks through the `pimcore_document_wrap_hardlink` method.

See [PimcoreObjectExtension](https://github.com/pimcore/pimcore/blob/master/lib/Twig/Extension/PimcoreObjectExtension.php)
for details.


#### Subrequests

The following methods use the matching PHP templating view helpers to render subrequests:

* `pimcore_inc`
* `pimcore_action`


```twig
{# render an action #}
{{ pimcore_action('sidebarBox', 'Blog', null, { items: count }) }}

{# include another document #}
{{ pimcore_inc('/snippets/foo') }}
```

See [Template Extensions](./02_Template_Extensions) for details.


#### Templating Extensions

The following extensions can directly be used on Twig. See [Template Extensions](./02_Template_Extensions) for a 
detailed description of every helper:

* `pimcore_head_link`
* `pimcore_head_meta`
* `pimcore_head_script`
* `pimcore_head_style`
* `pimcore_head_title`
* `pimcore_inline_script`
* `pimcore_placeholder`
* `pimcore_cache`
* `pimcore_url`


#### Block elements

As Twig does not provide a `while` control structure which is needed to iterate a [Block](../../03_Documents/01_Editables/06_Block.md)
editable, we introduced a function called `pimcore_iterate_block` to allow walking through every block element:

```twig
{% for i in pimcore_iterate_block(pimcore_block('contentblock')) %}
    <h2>{{ pimcore_input('subline') }}</h2>
    {{ pimcore_wysiwyg('content') }}
{% endfor %}
```

### Tests

#### `instanceof`

Can be used to test if an object is an instance of a given class.

```twig
{% if image is instanceof('\\Pimcore\\Model\\Asset\\Image') %}
    {# ... #}
{% endif %}
```
