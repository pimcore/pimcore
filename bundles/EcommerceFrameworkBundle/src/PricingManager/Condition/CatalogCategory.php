<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Condition;

use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractCategory;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\ConditionInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\EnvironmentInterface;

class CatalogCategory extends AbstractObjectListCondition implements CategoryInterface
{
    /**
     * @var AbstractCategory[]
     */
    protected array $categories = [];

    /**
     * Serialized category IDs
     *
     * @var array
     */
    protected array $categoryIds = [];

    /**
     * @param AbstractCategory[] $categories
     *
     * @return CategoryInterface
     */
    public function setCategories(array $categories): CategoryInterface
    {
        $this->categories = $categories;

        return $this;
    }

    /**
     * @return AbstractCategory[]
     */
    public function getCategories(): array
    {
        return $this->categories;
    }

    public function toJSON(): string
    {
        // basic
        $json = [
            'type' => 'CatalogCategory',
            'categories' => [],
        ];

        // add categories
        foreach ($this->getCategories() as $category) {
            $json['categories'][] = [
                $category->getId(),
                $category->getFullPath(),
            ];
        }

        return json_encode($json);
    }

    public function fromJSON(string $string): ConditionInterface
    {
        $json = json_decode($string);

        $categories = [];
        foreach ($json->categories as $cat) {
            $category = $this->loadObject($cat->id);
            if ($category) {
                $categories[] = $category;
            }
        }
        $this->setCategories($categories);

        return $this;
    }

    /**
     * Don't cache the entire category object
     *
     * @return array
     *
     * @internal
     */
    public function __sleep(): array
    {
        return $this->handleSleep('categories', 'categoryIds');
    }

    /**
     * Restore categories from serialized ID list
     *
     * @internal
     */
    public function __wakeup(): void
    {
        $this->handleWakeup('categories', 'categoryIds');
    }

    public function check(EnvironmentInterface $environment): bool
    {
        foreach ($environment->getCategories() as $category) {
            foreach ($this->getCategories() as $allow) {
                if (strpos($category->getFullPath(), $allow->getFullPath()) !== false) {
                    return true;
                }
            }
        }

        return false;
    }
}
