---
title: Template Inheritance and Layouts
description: Building reusable page layouts with Twig template inheritance in Pimcore.
---

# Template Inheritance and Layouts

## Introduction

Layouts define everything that repeats on one page to another, such as a header, footer, navigation. 
Layouts often contain the basic structure of a HTML document, such as `<html>`, `<head>` and the `<body>` 
tag as well as scripts and stylesheets.

Template inheritance lets you build a base layout containing common elements (header, footer, scripts)
defined as Twig blocks. Child templates extend the layout and override specific blocks.
Layouts are regular Twig files located in `/templates`.

For more details, see the
[Symfony documentation](https://symfony.com/doc/current/templates.html#template-inheritance-and-layouts).

## Usage of Layouts

### Sample Layout

```twig
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Example</title>
    <link rel="stylesheet" type="text/css" href="/static/css/global.css" />
</head>
<body>
    <div id="site">
        {{ block('content') }}
    </div>
</body>
</html>
```

Of course, editables and template helpers can be used within the layout file and therefore layouts can become much 
more complicated. The most important line though is `{{ block('content') }}`. 
It includes the actual rendered content of the view. 

### Using a Layout in a Template

Declare a parent template with the `extends` tag:

```twig
{% extends 'layout.html.twig' %}
```

A complete document template using a layout:

```twig
{% extends 'layout.html.twig' %}
...
{% block content %}
<h1>
    {{ pimcore_input('headline', {'width': 540}) }}
</h1>
{% endblock %}
```
