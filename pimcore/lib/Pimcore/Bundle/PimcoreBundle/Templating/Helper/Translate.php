<?php

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
