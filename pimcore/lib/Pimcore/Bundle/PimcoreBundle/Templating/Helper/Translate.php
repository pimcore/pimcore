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

namespace Pimcore\Bundle\PimcoreBundle\Templating\Helper;

use Pimcore\Bundle\PimcoreBundle\Component\Translation\Translator;
use Symfony\Component\Templating\Helper\Helper;

class Translate extends Helper
{

    /**
     * @var Translator
     */
    protected $translator;

    /**
     * @var string
     */
    protected $domain;

    public function __construct(Translator $translator)
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
        return "translate";
    }

    /**
     * @param $key
     * @param array $parameters
     * @return string
     */
    public function __invoke($key, $parameters = [])
    {
        //compatibility for legacy views
        if(is_string($parameters) || is_numeric($parameters)) {
            $parameters = [$parameters];
        }

        $term = $this->translator->trans($key, $parameters, $this->domain);
        return $term;
    }

    /**
     * @param string $domain
     */
    public function setDomain($domain) {
        $this->domain = $domain;
    }

}
