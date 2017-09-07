<?php
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

namespace Pimcore\Templating\Helper;

use Symfony\Component\Templating\Helper\Helper;
use Symfony\Component\Translation\TranslatorInterface;

class Translate extends Helper
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var string
     */
    protected $domain;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Returns the canonical name of this helper.
     *
     * @return string The canonical name
     */
    public function getName()
    {
        return 'translate';
    }

    /**
     * @param $key
     * @param array $parameters
     * @param null $domain
     * @param null $locale
     *
     * @return string
     */
    public function __invoke($key, $parameters = [], $domain = null, $locale = null)
    {
        //compatibility for legacy views
        if (is_string($parameters) || is_numeric($parameters)) {
            $parameters = [$parameters];
        }

        if (!$domain) {
            $domain = $this->domain;
        }

        $term = $this->translator->trans($key, $parameters, $domain, $locale);

        return $term;
    }

    /**
     * @param string $domain
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;
    }
}
