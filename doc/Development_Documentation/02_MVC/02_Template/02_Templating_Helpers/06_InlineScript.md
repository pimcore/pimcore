# InlineScript Templating Helper

> The InlineScript templating helper is an extension of the [Placeholder Templating Helper](./00_Placeholder.md)

The HTML `<script>` element is used to either provide inline client-side scripting elements or link to a remote resource 
containing client-side scripting code. The InlineScript helper allows you to manage both. It is derived from [HeadScript](02_HeadScript.md), 
and any method of that helper is available; however, use the `inlineScript()` method in place of `headScript()`.

> Note: Use InlineScript for HTML Body Scripts
InlineScript, should be used when you wish to include scripts inline in the HTML body. Placing scripts at the end of 
your document is a good practice for speeding up delivery of your page, particularly when using 3rd party analytics scripts. 
Some JS libraries need to be included in the HTML head; use HeadScript for those scripts. 
