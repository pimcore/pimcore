---
title: Pimcore Templates
description: Overview of Twig templates in Pimcore, including template locations, controller rendering, and editables.
---

# Pimcore Templates

Templates are located in `templates/[controller]/[action].html.twig`,
but [Symfony-style locations](https://symfony.com/doc/current/best_practices.html#use-the-default-directory-structure) 
also work (both, controller as well as action without their suffix).

Pimcore uses the [Twig templating engine](https://twig.symfony.com/doc/3.x/).
Check the [Pimcore Demo](https://github.com/pimcore/demo-enterprise/tree/2026.x) for practical examples.

## Rendering Templates

Use Symfony's `#[Template]` attribute or render the view directly from your controller:

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

You can also assign a template directly in a document's settings in Pimcore Studio.
That template is used for auto-rendering when the controller does not return a response.

## Editables

Editables are placeholders in templates that become interactive input widgets in Pimcore Studio's edit mode
and render their content on the frontend:

```twig
<h1>{{ pimcore_input('headline') }}</h1>
{{ pimcore_wysiwyg('content') }}
```

See [Editables](./03_Editables/README.md) for the full list of available editable types.

If you store an editable in a variable, pipe it through the `raw` filter to prevent HTML escaping:

```twig
{% set content = pimcore_wysiwyg('content') %}
{{ content|raw }}
```

## Next Steps

- [Template Inheritance and Layouts](./01_Template_Inheritance_and_Layouts.md) - define reusable page structures
- [Twig Extensions](./02_Twig_Extensions/README.md) - Pimcore's Twig functions, filters, and tags
- [Editables](./03_Editables/README.md) - all available editable types
- [Thumbnails](./04_Pimcore_Thumbnails.md) - image handling in templates
