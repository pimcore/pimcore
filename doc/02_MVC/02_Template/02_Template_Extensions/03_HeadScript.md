# HeadScript Templating Extension

> The HeadScript templating extension extends [Placeholder Templating Extension](./00_Placeholder.md)

The HTML `<script>` element is used to either provide inline client-side scripting elements or link to a remote resource 
containing client-side scripting code. The HeadScript helper allows you to manage both.

The HeadScript helper supports the following methods for setting and adding scripts:

- `appendFile($src, $type = 'text/javascript', $attrs = [])`
- `offsetSetFile($index, $src, $type = 'text/javascript', $attrs = [])`
- `prependFile($src, $type = 'text/javascript', $attrs = [])`
- `setFile($src, $type = 'text/javascript', $attrs = [])`
- `appendScript($script, $type = 'text/javascript', $attrs = [])`
- `offsetSetScript($index, $script, $type = 'text/javascript', $attrs = [])`
- `prependScript($script, $type = 'text/javascript', $attrs = [])`
- `setScript($script, $type = 'text/javascript', $attrs = [])`

In the case of the `*File()` methods, `$src` is the remote location of the script to load; this is usually in the form 
of a URL or a path. For the `*Script()` methods, `$script` is the client-side scripting directives you wish to use in the 
element.

> **Note**: Setting Conditional Comments
HeadScript allows you to wrap the script tag in conditional comments, which allows you to hide it from specific browsers. 
To add the conditional tags, pass the conditional value as part of the `$attrs` parameter in the method calls. 

### Example Headscript With Conditional Comments

```twig
{% do pimcore_head_script().appendFile(
    '/js/prototype.js',
    'text/javascript',
    {conditional: 'lt IE 11'}
%}
```

HeadScript also allows capturing scripts; this can be useful if you want to create the client-side script 
programmatically, and then place it elsewhere. The usage for this will be showed in an example below.

Finally, you can also use the `headScript()` method to quickly add script elements; the signature for this is 
`headScript($mode = 'FILE', $spec, $placement = 'APPEND')`. The `$mode` is either `FILE` or `SCRIPT`, depending on 
if you're linking a script or defining one. `$spec` is either the script file to link or the script source itself. 
`$placement` should be either `APPEND`, `PREPEND`, or `SET`.

HeadScript overrides each of `append()`, `offsetSet()`, `prepend()`, and `set()` to enforce usage of the special methods as listed above. 
Internally, it stores each item as a stdClass token, which it later serializes using the `itemToString()` method. 
This allows you to perform checks on the items in the stack, and optionally modify these items by simply modifying 
the object returned.

> **Note**: Use [InlineScript](06_InlineScript.md) for HTML Body Scripts
HeadScript's sibling helper, InlineScript, should be used when you wish to include scripts inline in the HTML body. 
Placing scripts at the end of your document is a good practice for speeding up delivery of your page, particularly when using 3rd party analytics scripts. 
Note: Arbitrary Attributes are Disabled by Default
By default, HeadScript only will render `<script>` attributes that are blessed by the W3C. 
These include 'type', 'charset', 'defer', 'async', 'language' and 'src'. However, some javascript frameworks, 
 utilize custom attributes in order to modify behavior. 
To allow such attributes, you can enable them via the setAllowArbitraryAttributes() method: 

`{% do pimcore_head_script().setAllowArbitraryAttributes(true) %}`

### Basic Usage

You may specify a new script tag at any time. As noted above, these may be links to outside resource files or scripts themselves.

```twig
{# adding scripts #}
{% do pimcore_head_script().appendFile('/js/jquery.js').appendScript(onloadScript) %}
```

Order is often important with client-side scripting; you may need to ensure that libraries are loaded in a specific 
order due to dependencies each have; use the various append, prepend, and offsetSet directives to aid in this task:

```twig
{# Putting scripts in order #}

{# place at a particular offset to ensure loaded last #}
{% do pimcore_head_script().offsetSetFile(100, '/js/myfuncs.js') %}

{# append uses next index, 101 #}
{% do pimcore_head_script().appendFile('/js/jquery-plugin-xyz.js') %}

{# but always have base prototype script load first: #}
{% do pimcore_head_script().prependFile('/js/jquery.js') %}
```

When you're finally ready to output all scripts in your layout script, simply echo the helper:

`{{ pimcore_head_script() }}`

### Capturing Scripts Using the HeadScript Helper (deprecated)

Sometimes you need to generate client-side scripts programmatically. While you could use string concatenation, 
heredocs, and the like, often it's easier just to do so by creating the script and sprinkling in Twig tags. 
HeadScript lets you do just that, capturing it to the stack:

```twig
{% do pimcore_head_script().captureStart() %}
var action = '{{ baseUrl }}';
$('#foo_form').attr("action", action);
{% do pimcore_head_script().captureEnd() %}
```

The following assumptions are made:

The script will be appended to the stack. If you wish for it to replace the stack or be added to the top, 
you will need to pass `SET` or `PREPEND`, respectively, as the first argument to `captureStart()`.

The script MIME type is assumed to be `text/javascript`; if you wish to specify a different type, you will need to 
pass it as the second argument to `captureStart()`.

If you wish to specify any additional attributes for the `<script>` tag, pass them in an array as the third 
argument to `captureStart()`.

**Note:** The `captureStart()` and `captureEnd()` methods are deprecated as of Pimcore 11.4.0. Use a combination of `set` and `pimcore_head_script` to achieve the same result.

```twig
{% set inlineScript %}
    var action = '{{ baseUrl }}';
    $('#foo_form').attr("action", action);
{% endset %}
{% do pimcore_head_script().appendScript(inlineScript) %}

{# Example if you wish to specify additional attributes for the <script> tag #}
{% do pimcore_head_script().appendScript(inlineScript, "text/javascript", {"async": "async"}) %}
```

## HTTP/2 Push Support

See [HTTP/2 Push Support on the HeadLink page](./01_HeadLink.md#http2-push-support).
