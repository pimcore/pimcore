<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\AdminBundle\Security;

use Pimcore\Config;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @internal
 */
class ContentSecurityPolicyHandler implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var String|null */
    private ?string $nonce = null;

    public function __construct(protected Config $config, protected array $cspHeaderOptions = [])
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        $this->cspHeaderOptions = $resolver->resolve($cspHeaderOptions);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            "default-src" => "'self'",
            "img-src" => "* data: blob:",
            "media-src" => "'self' data:",
            "script-src" => "'self' 'nonce-" . $this->nonce() . "' 'unsafe-inline' 'unsafe-eval' https://code.jquery.com/ https://cdnjs.cloudflare.com/ajax/libs/popper.js/ https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/ http://unpkg.com/react/umd/ http://unpkg.com/axios/dist/ http://unpkg.com/react-dom/umd/",
            "style-src" => "'self' 'unsafe-inline' https://maxcdn.bootstrapcdn.com/bootstrap/ https://cdn.jsdelivr.net/npm/ https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css",
            "frame-src" => "'self'",
            "connect-src" => "'self' blob: https://liveupdate.pimcore.org/ https://license.pimcore.com/ https://nominatim.openstreetmap.org/",
            "font-src" => "'self' https://cdn.jsdelivr.net/npm/",
        ]);
    }

    /**
     * @return string
     */
    public function getCspHeader(): string
    {
        $cspHeaderOptions = array_map(function ($k, $v) {
            return "$k $v";
        }, array_keys($this->cspHeaderOptions), array_values($this->cspHeaderOptions));

        return implode(';' ,$cspHeaderOptions);
    }

    /**
     *
     * @return string
     */
    public function getNonce(): string
    {
        return $this->config['admin_csp_header']['enabled'] ? ' nonce="' . $this->nonce() . '"' : '';
    }

    /**
     * Generates a random nonce parameter.
     *
     * @return string
     */
    public function nonce(): string
    {
        if (!$this->nonce) {
            $this->nonce = generateRandomSymfonySecret();
        }

        return $this->nonce;
    }
}
