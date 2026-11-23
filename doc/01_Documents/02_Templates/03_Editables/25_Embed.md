---
title: Embed
description: Embed external content from supported providers using oEmbed via the Embera library.
---

# Embed Editable

### Configuration

| Name     | Type   | Description                                                                        |
|----------|--------|------------------------------------------------------------------------------------|
| `width`  | string | Width in the editmode                                                              |
| `height` | string | Height in the editmode                                                             |
| `class`  | string | A CSS class that is added to the surrounding container of this element in editmode |

Additionally you can use any configuration option of [Embera](https://github.com/mpratt/Embera)

## Methods

| Name          | Return    | Description                                                            |
|---------------|-----------|------------------------------------------------------------------------|
| `isEmpty()`   | boolean   | Whether the editable is empty or not                                   |
| `getData()`   | string    | Get the URL of the assigned resource                                   |

## Example

```twig
{# Basic usage #}
{{ pimcore_embed("socialWidgets") }}

{# Advanced usage #}
{{ pimcore_embed("socialWidgets", {"width": 540}) }}
```

## Security policy (URL validation)

By default, the Embed editable passes whatever the document editor enters straight to Embera. Embera
only replaces the URLs that a configured oEmbed provider recognizes and returns everything else
unchanged - so a value that isn't actually a URL, or text following a URL, is emitted verbatim into
the public page. This preserves this editable's historical behavior, but means every user who may
edit documents can place arbitrary markup on the rendered page.

To prevent this, enable the strict policy via config:

```yaml
# config/packages/pimcore.yaml
pimcore:
    documents:
        editables:
            embed_sanitizer:
                strict: true
```

`PimcoreCoreBundle::boot()` reads this and installs the strict policy for you, which requires the
value to be a well-formed http(s) URL with a host that contains no whitespace, control characters,
quotes, backticks or angle brackets before it is handed to Embera; any other value renders nothing.
If you need a
fully custom policy instead of the boolean toggle, call
`\Pimcore\Model\Document\Editable\Embed\UrlSanitizer::setInstance(...)` directly from your own
bundle's `boot()` method.
