<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Composer\Config;

/**
 * Merges an updated composer.json schema from an update into the existing one,
 * optionally normalizing it via normalizer implementations.
 */
class ConfigMerger implements NormalizerInterface
{
    /**
     * @var NormalizerInterface[]
     */
    private $normalizers = [];

    /**
     * @param NormalizerInterface[] $normalizers
     */
    public function __construct(array $normalizers = [])
    {
        foreach ($normalizers as $normalizer) {
            $this->addNormalizer($normalizer);
        }
    }

    private function addNormalizer(NormalizerInterface $normalizer)
    {
        $this->normalizers[] = $normalizer;
    }

    public function merge(array $existing, array $new): array
    {
        $result = array_replace_recursive($existing, $new);
        $result = $this->normalize($result);

        return $result;
    }

    public function normalize(array $config): array
    {
        foreach ($this->normalizers as $normalizer) {
            $config = $normalizer->normalize($config);
        }

        return $config;
    }
}
