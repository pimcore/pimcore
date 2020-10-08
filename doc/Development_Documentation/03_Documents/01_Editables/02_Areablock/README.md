# Areablock Editable

## General 

The areablock is the content construction kit for documents offered by Pimcore.

![Admin panel preview 1](../../../img/areablock_editmode1.png)

![Admin panel preview 2](../../../img/areablock_editmode2.png)

## Integrate an Areablock in a Template
Similar to the other document editables, an areablock can be integrated in any document view template as follows:

<div class="code-section">

```php
<?= $this->areablock('myAreablock'); ?>
```

```twig
{{ pimcore_areablock("myAreablock") }}
```

</div>

Advanced usage with allowed areas, below:

<div class="code-section">

```php
<?= $this->areablock("myAreablock", [
    "allowed" => ["iframe","googletagcloud","spacer","rssreader"],
    "group" => [
        "First Group" => ["iframe", "spacer"],
        "Second Group" => ["rssreader"]
    ],
    "globalParams" => [ //global params are passed to all areablocks
        "myGlobalParam" => "Global param value"
    ],
    "params" => [
        "iframe" => [ // some additional parameters / configuration for the brick type "iframe"
            "parameter1" => "value1",
            "parameter2" => "value2"
        ],
        "googletagcloud" => [ // additional parameter for the brick type "googletagcloud"
            "param1" => "value1"
        ]
    ]]);
?>
```

```twig
{{ pimcore_areablock("myAreablock", {
            "allowed": ["iframe","googletagcloud","spacer","rssreader"],
            "group": {
                "First Group": ["iframe", "spacer"],
                "Second Group": ["rssreader"]
            },
            "globalParams": {
                "myGlobalParam": "Global param value"
            },
            "params": {
                "iframe": {
                    "parameter1": "value1",
                    "parameter2": "value2"
                },
                "googletagcloud": {
                    "param1": "value1"
                }
            }
        })
    }}
```

</div>

##### Accessing Parameters from the Brick File
```php
//use the value of parameter named "param1" for this brick
echo $this->param1;
```

##### Sorting Items in the menu
```php
echo  $this->areablock("content", [
    'allowed' => ['image', 'video', 'wysiwyg'],
    'sorting' => ['wysiwyg', 'image', 'video']
]); 
```

And you can see the effect, below:

![Admin panel preview - sroting areablocks](../../../img/areablock_editmode3.png)

## Configuration

| Name                | Type   | Description                                                                                                                                                                                  |
|---------------------|--------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `allowed`           | array  | An array of area-ID's which are allowed for this tag. The order of items in the array is also used as the default sorting, but of course you can combine this configuration with **sorting** |
| `sorting`           | array  | An array of area-ID's in the order you want to display them in the menu.                                                                                                                  |
| `params`            | array  | Optional parameter, this can also contain additional brick-specific configurations, see **brick-specific configuration**                                                                     |
| `globalParams`      | array  | Same as `params` but passed to all bricks independent from the type                                                                                                                          |
| `group`             | array  | Array with group configuration (see example above).                                                                                                                                          |
| `manual`            | bool   | Forces the manual mode, which enables a complete free implementation for areablocks, for example using real `<table>` elements... example see below                                          |
| `reload`            | bool   | Set to `true`, to force a reload in editmode after reordering items (default: `false`)                                                                                                       |
| `dontCheckEnabled`  | bool   | Set to `true` to display all installed area bricks, regardless if they are enabled in the extension manager                                                                                  |
| `limit`             | int    | Limit the amount of elements                                                                                                                                                                 |
| `limits`            | array  | An array of area-ID's with count to limit the amount of certain elements e.g. {"iframe": 1, "teasers": 2} (since v6.7.0)                                                                      |
| `areablock_toolbar` | array  | Array with option that allows you to configure the toolbar. Possible options are `width`, `buttonWidth` and `buttonMaxCharacters`                                                            |
| `controlsAlign`     | string | The position of the control button bar. Options are: `top`, `right` and `left`.                                                                                                              |
| `controlsTrigger`   | string | Options are: `hover`(default) and `fixed` .                                                                                                              |
| `class`             | string | A CSS class that is added to the surrounding container of this element in editmode                                                                                                           |

## Brick-specific Configuration
Brick-specific configurations are passed using the `params` or `globalParams` configuration (see above). 

| Name              | Type | Description                                                                                                                                                     |
|-------------------|------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `forceEditInView` | bool | [DEPRECATED] If a brick contains an `edit.php` there's no editmode for the `view.php` file, if you want to have the editmode enabled in both templates, enable this option |
| `editWidth`       | int  | [DEPRECATED] Width of editing popup (if dedicated `edit.php` is used).                                                                                               |
| `editHeight`      | int  | [DEPRECATED] Height of editing popup (if dedicated `edit.php` is used).                                                                                              |
  
##### Example

```php
<?= $this->areablock("myArea", [
    "params" => [
        "my_brick" => [
            "forceEditInView" => true,
            "editWidth" => "800px",
            "editHeight" => "500px"
        ]
    ]
]); ?>
```

## Methods

| Name                | Return    | Description                                                                            |
|---------------------|-----------|----------------------------------------------------------------------------------------|
| `getCount()`        | int       | Total count of blocks                                                                  |
| `getCurrent()`      | int       | Current number of block (useful with area bricks)                                      |
| `getCurrentIndex()` | int       | Get the current index (index is different from position, as you can move block around) |
| `getElement()`      | array     | Get an element out of an areabrick                                                     |
| `renderIndex()`     | -         | Renders only one specific block within the areablock                                   |

## How to Create Bricks for the Areablock

You can read about **bricks** in the [Bricks](./02_Bricks.md) section.

## Limit certain Bricks for the Areablock (since v6.7.0)

You can limit certain bricks for the Areablock by using `limits` configurations.
##### Example

<div class="code-section">

```php
<?= $this->areablock("myAreablock", [
        "allowed" => ["iframe","teasers","wysiwyg"],
        "limits" => [
            "iframe" => 1,
            "teasers" => 2,
        ],
        "limit" => 5,
]); ?>
```

```twig
{{ pimcore_areablock("myAreablock", {
        "allowed": ["iframe","teasers","wysiwyg"],
        "limits": {
            "iframe": 1,
            "teasers": 2
        },
        "limit": 5
    })
}}
```

</div>

## Using Manual Mode

The manual mode offers you the possibility to use areablocks with custom HTML, this is for example useful when using tables: 

<div class="code-section">

```php
<?php $areaBlock = $this->areablock("myArea", ["manual" => true])->start(); ?>
<table>
    <?php while ($areaBlock->loop()) { ?>
        <?php $areaBlock->blockConstruct(); ?>
            <tr>
                <td>
                    <?php $areaBlock->blockStart(); ?>
                    <?php $areaBlock->content(); ?>
                    <?php $areaBlock->blockEnd(); ?>
                </td>
            </tr>
        <?php $areaBlock->blockDestruct(); ?>
    <?php } ?>
</table>
<?php $areaBlock->end(); ?>
```

```twig
{% set areaBlock = pimcore_areablock("myArea", {"manual":"true"}) %}

{% do areaBlock.start() %}
<table>
    {% for i in pimcore_iterate_block(areaBlock) %}
        {% do areaBlock.blockConstruct() %}
            <tr>
                <td>
                    {% do areaBlock.blockStart() %}
                    {% do areaBlock.content() %}
                    {% do areaBlock.blockEnd() %}
                </td>
            </tr>
        {% do areaBlock.blockDestruct() %}
    {% endfor %}
</table>
{% do areaBlock.end() %}
```

</div>

### Accessing Data Within an Areablock Element

See [Block](../06_Block.md) for an example how to get elements from block and areablock editables.
