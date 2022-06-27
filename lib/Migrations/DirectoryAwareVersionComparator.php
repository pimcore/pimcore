<?php
declare(strict_types=1);

namespace Pimcore\Migrations;

use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Version\Comparator;
use Doctrine\Migrations\Version\Version;

/**
 * Orders versions by their namespaces in registration order.
 */
final class DirectoryAwareVersionComparator implements Comparator
{
    /** @var array<string, int> */
    private array $orderByNamespace;

    public function __construct(Configuration $config)
    {
        $this->orderByNamespace = array_flip(array_keys($config->getMigrationDirectories()));
    }

    public function compare(Version $a, Version $b): int
    {
        try {
            return $this->getOrder($a) <=> $this->getOrder($b);
        } catch (\ReflectionException) {
            return (string) $a <=> (string) $b;
        }
    }

    /**
     * @throws \ReflectionException
     *
     * @return array{int, string}
     */
    private function getOrder(Version $version): array
    {
        $class = new \ReflectionClass((string) $version);
        $className = $class->getShortName();
        $namespace = $class->getNamespaceName();
        $namespaceOrder = $this->orderByNamespace[$namespace] ?? 0;

        return [$namespaceOrder, $className];
    }
}
