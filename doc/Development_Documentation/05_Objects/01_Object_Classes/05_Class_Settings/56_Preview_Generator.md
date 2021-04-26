# Preview Generator

### Summary
Preview Generators provide Services to get more control over the preview Tab. They provide a UI to pass additional parameters to a URL-Generator.

Providers need to implement: `\Pimcore\Model\DataObject\ClassDefinition\PreviewGeneratorInterface`

Parameters returned in the `getParams` method will be rendered as ext-js ComboBoxes. Whatever the User chooses will be passed to the `generatePreviewUrl` method.


Provide a Preview Generator within the Class settings:
![Preview Generator Setup](../../../img/preview_generator_1.png)


### Sample PreviewProvider Implementation
```php
namespace AppBundle\Service\PreviewParamProvider;

class ProductPreviewParamProvider implements \Pimcore\Model\DataObject\ClassDefinition\PreviewGeneratorInterface
{
    protected $productLinkGenerator;

    public function __construct(\AppBundle\Service\LinkGenerator\ProductLinkGenerator $productLinkGenerator)
    {
        $this->productLinkGenerator = $productLinkGenerator;
    }

    /**
     * @param \Pimcore\Model\DataObject\Concrete $object
     * @param array $params
     * @return string
     */
    public function generatePreviewUrl(\Pimcore\Model\DataObject\Concrete $object, array $params): string {
        $additionalParams = [];
        foreach($this->getParams($object) as $paramStore) {
            $paramName = $paramStore['name'];
            if($paramValue = $params[$paramName]) {
                $additionalParams[$paramName] = $paramValue;
            }
        }

        return $this->productLinkGenerator->generate($object, $additionalParams);
    }

    /**
     * @param \Pimcore\Model\DataObject\Concrete $object
     * 
     * @return array
     */
    public function getParams(\Pimcore\Model\DataObject\Concrete $object): array {
        return [
            [
                'name' => '_locale',
                'label' => 'Locale',
                'values' => [
                    ['abbr' => 'en', 'name' => 'en'],
                    ['abbr' => 'de', 'name' => 'de']
                ]
            ],
            [
                'name' => 'otherParam',
                'label' => 'Other',
                'values' => [
                    ['abbr' => 'aa', 'name' => 'aa'],
                    ['abbr' => 'bb', 'name' => 'bb']
                ]
            ]
        ];
    }
}
```
![Preview Generator Example UI](../../../img/preview_generator_2.png)
