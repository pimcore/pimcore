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

namespace Pimcore\Bundle\PersonalizationBundle\Targeting\Storage\Cookie;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractCookieSaveHandler implements CookieSaveHandlerInterface
{
    protected array $options = [];

    public function __construct(array $options = [])
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        $this->options = $resolver->resolve($options);
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'domain' => null,
            'secure' => false,
            'httpOnly' => true,
        ]);

        $resolver->setAllowedTypes('domain', ['null', 'string']);
        $resolver->setAllowedTypes('secure', ['bool']);
        $resolver->setAllowedTypes('httpOnly', ['bool']);
    }

    /**
     * {@inheritdoc}
     */
    public function load(Request $request, string $scope, string $name): array
    {
        $data = $request->cookies->get($name, null);
        $result = $this->parseData($scope, $name, $data);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function save(Response $response, string $scope, string $name, \DateTimeInterface|int|string $expire, ?array $data): void
    {
        $value = $this->prepareData($scope, $name, $expire, $data);

        $response->headers->setCookie(new Cookie(
            $name,
            $value,
            $expire
        ));
    }

    /**
     * Parse loaded data
     *
     * @param string $scope
     * @param string $name
     * @param string|null $data
     *
     * @return array
     */
    abstract protected function parseData(string $scope, string $name, ?string $data): array;

    /**
     * Prepare data for saving
     *
     * @param string $scope
     * @param string $name
     * @param \DateTimeInterface|int|string $expire
     * @param array|null $data
     *
     * @return bool|string|null
     */
    abstract protected function prepareData(string $scope, string $name, \DateTimeInterface|int|string $expire, ?array $data): bool|string|null;
}
