# Pimcore Thumbnails

## Introduction
When displaying images in templates, they should be optimized (e.g. size) for the actual use case and device. 
When source images are stored as Pimcore Assets (as they should be), Pimcore can do all the optimizing work for you. 
Just use the Thumbnail functionality and let Pimcore transform the images the way you need them. 
  
To get all the information about Thumbnails, which possibilities exist and how to configure them, 
please have a look at [Working with Thumbnails](../../04_Assets/03_Working_with_Thumbnails/README.md). 

## Use Thumbnails in Templates

```twig
{# Use directly on the asset object - myThumbnail is the name of the thumbnail configured in thumbnail configuration #}
{% set asset = asset('/path/to/image.jpg') %}
{{ asset.getThumbnail('myThumbnail').getHtml() | raw }}

{# Use directly on the asset object using dynamic configuration #}
{% set asset = asset('/path/to/image.jpg') %}
{{ asset.getThumbnail({
    width: 500,
    format: 'png'
}).getHtml() | raw }}

{# Use with the image editable in documents #}
<div>
    <p> 
    {{ pimcore_image('image', {
        thumbnail: 'myThumbnail',
       })
    }}
    </p>
</div>

{# Use with the image editable in documents using dynamic configuration #}
{{ pimcore_image('image', {
    thumbnail: {
        width: 500,
        height: 0,
        aspectratio: true,
        interlace: true,
        quality: 95,
        format: 'PNG'
    }
}) }}

{# Use from an object-field #}
{% if myObject.myImage is instanceof('Asset\\Image') %}
    <img src="{{ myObject.myImage.getThumbnail('myThumbnail').getHref() }}" />
{% endif %}

{# Use from an object-field using dynamic configuration #}
{% if myObject.myImage is instanceof('Asset\\Image') %}
    <img src="{{ myObject.myImage.getThumbnail({width: 220, format: 'jpeg'}).getHref() }}" />
{% endif %}
```
