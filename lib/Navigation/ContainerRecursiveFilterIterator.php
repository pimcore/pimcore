<?php
declare(strict_types=1);

namespace Pimcore\Navigation;

/**
 * @internal
 */
final class ContainerRecursiveFilterIterator extends \RecursiveFilterIterator
{
    private string $property;
    private string $value;

    public function __construct(Container $iterator, string $property, string $value)
    {
        parent::__construct($iterator);
        $this->property = $property;
        $this->value = $value;
    }

    public function accept(): bool
    {
        /** @var Page $page */
        $page = $this->current();

        try {
            $property = $page->get($this->property);
        } catch (\Exception) {
            return false;
        }

        return is_string($property) && str_starts_with($this->value, $property);
    }

    public function getChildren(): self
    {
        /** @var Container $container */
        $container = $this->getInnerIterator();

        return new self($container->getChildren(), $this->property, $this->value);
    }
}
