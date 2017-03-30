# Snippet Editable

## General

Use the snippet editable to embed a document snippet, for example teasers or other boxes into your document. 

Snippets are like little pages which can be embedded in other documents. 
You have to create them the same way as other documents (pages).

## Configuration

| Name            | Type    | Description                                                                                                    |
|-----------------|---------|----------------------------------------------------------------------------------------------------------------|
| `defaultHeight` | integer | A default height if the element is empty                                                                       |
| `height`        | integer | Height of the snippet in pixel                                                                                 |
| `reload`        | bool    | Reload document on change                                                                                      |
| `title`         | string  | You can give the element a title                                                                               |
| `width`         | integer | Width of the snippet in pixel                                                                                  |
| `class`         | string  | A CSS class that is added to the surrounding container of this element in editmode                             |
| `tag`           | string  | A tag name that is used instead of the default `div` for the surrounding container of this element in editmode |

## Methods

| Name           | Return  | Description                           |
|----------------|---------|---------------------------------------|
| `getId()`      | int     | ID of the assigned snippet            |
| `getSnippet()` | Snippet | The assigned snippet object           |
| `isEmpty()`    | bool    | Whether the editable is empty or not. |

## Examples

```php  
 // Define a place for a snippet to be dragged onto, advanced usage
 <?= $this->snippet("mySnippet", ["width" => 250, "height" => 100]) ?>
```

