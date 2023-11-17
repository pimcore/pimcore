# Preview Generator

### Summary
Preview Generators provide services to get more control over the preview tab. 
They provide a UI component to pass additional parameters to a URL-generator.

Providers need to implement: `\Pimcore\Model\DataObject\ClassDefinition\PreviewGeneratorInterface`

> As of Pimcore 10.6, a default implementation of a `PreviewGenerator` is provided. You only need to add
> a [link generator](./30_Link_Generator.md). The generated URL will then be used for the preview.

Parameters returned in the `getParams` method will be rendered as a select box. 
Whatever the user chooses will be passed to the `generatePreviewUrl` method.

Provide a Preview Generator within the Class settings:
![Preview Generator Setup](../../../img/preview_generator_1.png)


### Sample PreviewProvider Implementation
```php
namespace App\Service\PreviewParamProvider;

use App\Website\LinkGenerator\ProductLinkGenerator;
use Pimcore\Model\DataObject\ClassDefinition\PreviewGeneratorInterface;
use Pimcore\Model\DataObject\Concrete;

class ProductPreviewParamProvider implements PreviewGeneratorInterface
{
    protected ProductLinkGenerator $productLinkGenerator;

    public function __construct(ProductLinkGenerator $productLinkGenerator)
    {
        $this->productLinkGenerator = $productLinkGenerator;
    }

    public function generatePreviewUrl(Concrete $object, array $params): string
    {
        $additionalParams = [];
        foreach($this->getPreviewConfig($object) as $paramStore) {
            $paramName = $paramStore['name'];
            if($paramValue = $params[$paramName]) {
                $additionalParams[$paramName] = $paramValue;
            }
        }

        return $this->productLinkGenerator->generate($object, $additionalParams);
    }

    public function getPreviewConfig(Concrete $object): array
    {
        return [
            [
                'name' => '_locale',
                'label' => 'Locale',
                'values' => [
                    'English' => 'en',
                    'German' => 'de'
                ],
                'defaultValue' => 'en'
            ],
            [
                'name' => 'otherParam',
                'label' => 'Other',
                'values' => [
                    'Label Text' => 'value',
                    'Option #2' => 2,
                    'Custom Option' => 'custom'
                ],
                'defaultValue' => 'value'
            ]
        ];
    }
}
```
![Preview Generator Example UI](../../../img/preview_generator_2.png)
