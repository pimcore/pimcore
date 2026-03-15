---
title: Pimcore Templates
description: Overview of Twig templates in Pimcore, including template locations, controller rendering, and editables.
---

# Pimcore Templates

Templates define the visual structure of a document and control which parts of a page
are editable by editors in Pimcore Studio.
A template combines standard HTML/Twig markup with Pimcore [Editables](./03_Editables/README.md),
placeholders that turn into interactive input widgets (text fields, WYSIWYG editors,
image pickers, etc.) when an editor opens the page in Pimcore Studio's edit mode.

Beyond simple content fields, special editables like [Block](./03_Editables/06_Block.md)
and [Areablock](./03_Editables/02_Areablock/README.md) give editors the ability
to make structural changes to the page, adding, removing, and reordering content sections
without touching the template code itself.

Templates are standard [Twig](https://twig.symfony.com/doc/3.x/) files located in
`templates/[controller]/[action].html.twig`, while
[Symfony's directory conventions](https://symfony.com/doc/current/best_practices.html#use-the-default-directory-structure)
also work.
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
See [MVC in Pimcore](../01_MVC_in_Pimcore.md) for details on how documents resolve
their controller and template.

## Editables

Editables are the bridge between templates and Pimcore Studio.
In the template, they look like Twig function calls.
In Pimcore Studio's edit mode, they render as interactive input widgets.
On the frontend, they output the content the editor has entered:

```twig
<h1>{{ pimcore_input('headline') }}</h1>
{{ pimcore_wysiwyg('content') }}

{{ pimcore_select('type', { reload: true, store: [["video","video"], ["image","image"]] }) }}
```

Pimcore provides editables for text, rich text, images, links, relations, dates,
and more. See [Editables](./03_Editables/README.md) for the full reference.

For structural flexibility, [Block](./03_Editables/06_Block.md) lets editors
repeat a set of editables (e.g., a list of feature cards), while
[Areablock](./03_Editables/02_Areablock/README.md) lets them compose pages
from predefined content blocks called "bricks."

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
