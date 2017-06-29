# Path Formatter  

### Summary
* Allows you to show a user friendly "object information' instead of technical path in Pimcore data types such as multihref, objects etc.
* Uses Ajax to request nice path info on demand (i.e. after rendering) 

### Example

In this example, we want to display the asset metadata title of the target asset.

![Asset Metadata](../../../img/formatter_class_asset.png)

![Formatter Class](../../../img/formatter_class_object.png)

### Configuration via class editor

![Formatter Class](../../../img/formatter_class.png)

### Sample Formatter Class

```php
<?php

namespace Website;

use Pimcore\Model\Asset;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Object\BlogArticle;
use Pimcore\Model\Object\ClassDefinition\Data;
use Pimcore\Model\Object\Concrete;
use Pimcore\Model\Object\News;

class TheFormatter
{
    /**
     * @param $result array containing the nice path info. Modify it or leave it as it is. Pass it out afterwards!
     * @param ElementInterface $source the source object
     * @param $targets list of nodes describing the target elements
     * @param $params optional parameters. may contain additional context information in the future. to be defined.
     * @return mixed list of display names.
     */
    public static function formatPath($result, ElementInterface $source, $targets, $params) {
        /** @var  $fd Data */
        $fd = $params["fd"];
        $context = $params["context"];

        foreach ($targets as $key => $item) {
            $newPath = $item["path"] .  " - " . time();
            if ($context["language"]) {
                $newPath .= " " . $context["language"];
            }

            if ($item["type"] == "object") {
                $targetObject = Concrete::getById($item["id"]);
                if ($targetObject instanceof News) {
                    $newPath = $targetObject->getTitle() . " - " . $targetObject->getShortText();
                }  else if ($targetObject instanceof BlogArticle) {
                    $newPath = $targetObject->getTitle();
                }
            } elseif ($item["type"] == "asset") {
                $asset = Asset::getById($item["id"]);
                if ($asset) {
                    $title = $asset->getMetadata("title");


                    if (!$title) {
                        $title = "this guy does not have a title, use " . $newPath . " instead";
                    }
                    if ($fd instanceof Data\Multihref) {
                        $newPath = '<img src="' . $asset . '" style="width: 25px; height: 18px;" />' . $title;
                    } else {
                        $newPath = $title;
                    }
                }
            }
                
            // don't forget to use the same key, otherwise the matching doesn't work
            $result[$key]= $newPath;
        }
        return $result;
    }
}
```

**Note regarding batch assignment**
The containerType will be `batch` and the object ID will be the folder ID.

