# InlineScript Template Extension

> The InlineScript template extension extends [Placeholder Templating Extension](./00_Placeholder.md)

The HTML `<script>` element is used to either provide inline client-side scripting elements or link to a remote resource 
containing client-side scripting code. The InlineScript helper allows you to manage both. It is derived from [HeadScript](03_HeadScript.md), 
and any method of that extension is available; however, use the `pimcore_inline_script()` method in place of `pimcore_head_script()`.

> Note: pimcore_inline_script() should be used when you wish to include scripts inline in the HTML body. Placing scripts at the end of 
your document is a good practice for speeding up delivery of your page, particularly when using 3rd party analytics scripts. 
Some JS libraries need to be included in the HTML head; use pimcore_head_script() for those scripts. 
