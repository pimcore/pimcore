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

By default, the Embed editable passes whatever the document editor enters straight to Embera. When
the value isn't recognized by any configured oEmbed provider, Embera returns it unchanged - so a
value that isn't actually a URL (including raw markup) is emitted verbatim into the public page.
This preserves this editable's historical behavior, but means a document editor (document-edit
permission, not necessarily an administrator) can use it to store a persistent (stored) XSS payload
that runs for every visitor who views the rendered page (GHSA-v3fp-8x27-9g8f).

To close this, enable it via config:

```yaml
# config/packages/pimcore.yaml
pimcore:
    documents:
        editables:
            embed_sanitizer:
                strict: true
```

`PimcoreCoreBundle::boot()` reads this and installs the strict policy for you, which requires the
value to be a well-formed http(s) URL with a host before it is handed to Embera. If you need a
fully custom policy instead of the boolean toggle, call
`\Pimcore\Model\Document\Editable\Embed\UrlSanitizer::setInstance(...)` directly from your own
bundle's `boot()` method.
