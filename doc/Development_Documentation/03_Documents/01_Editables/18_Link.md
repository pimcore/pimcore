# Link Editable

## General 

The link editable is used for dynamic link creation in documents.

## Configuration

You can pass nearly every valid attribute an `<a>`-tag can have ([w3.org - Link](https://www.w3.org/TR/html52/textlevel-semantics.html#the-a-element)), 
such as: `class`, `target`, `id`, `style`, `accesskey`, `name`, `title`, `data-*`, `aria-*` and additionally the following: 

| Name     | Type     | Description                                                             |
|----------|----------|-------------------------------------------------------------------------|
| `reload` | boolean  | Set to true to reload the page in editmode after changing the state.    |
| `textPrefix` | string  | Add an icon or something else before Text    |
| `textSuffix` | string  | Add an icon or something else after Text    |
| `noText` | boolean  | If you need only the `<a>` tag without text (or only with an textSuffix/TextPrefix)    |
| `required` | boolean/string  | (default: false) set to true to make link and text required for publish, set to "linkonly" to make only the link required for publish    |

> For security reasons we created an [allow list](https://github.com/pimcore/pimcore/blob/9bf18aca55e5303661c68835c950412a428cf616/models/Document/Editable/Link.php#L115-L141) to filter harmfull html attributes. For example all `on*` attributes will be filtered out!

## Methods

| Name              | Return      | Description                          |
|-------------------|-------------|--------------------------------------|
| `getHref()`       | string      | Get the path of this link            |
| `getText()`       | string      | Get the text of the link             |
| `getTarget()`     | string      | Get the target of the link           |
| `getParameters()` | string      | Get the query params of the link     |
| `getAnchor()`     | string      | Get the anchor text of the link      |
| `getTitle()`      | string      | Get the title of the link            |
| `getRel()`        | string      | Get the rel text of the link         |
| `getTabindex()`   | string      | Get the tabindex of the link         |
| `getClass()`      | string      | Get the class of the link            |
| `getAccessKey()`  | string      | Get the access key of the link       |
| `isEmpty()`       | string      | Whether the editable is empty or not |

## Examples

### Basic Usage

```twig
<p>
    {{ "Visit our" | trans }}
    {{ pimcore_link('blogLink') }}
</p>
```

You could see the backend preview in the picture, below.

![Link editable - adminitration panel](../../img/editables_link_backend_preview.png)

And the frontend:

![Link editable - frontend](../../img/editables_link_frontend_preview.png)



### Use Link in the Block Editable

Let's see how to make a list of links with [Block](./06_Block.md).

```twig
<h3>{{ "Useful links" | trans }}</h3>
<ul>
    {% for i in pimcore_block('linkblock').iterator %}
        <li>{{ pimcore_link('myLink', {'class': "special-link-class"}) }}</li>
    {% endfor %}
</ul>
```

The above example renders a list of links: 
![The links list in the backend](../../img/editables_link_inside_block.png)

### Link Generators

Please also see the section about [Link Generators](../../05_Objects/01_Object_Classes/05_Class_Settings/30_Link_Generator.md)





