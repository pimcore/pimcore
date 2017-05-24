<?php

declare(strict_types=1);

namespace AppBundle\Twig;

class UniqidExtension extends \Twig_Extension
{
    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('uniqid', function (string $prefix = '', bool $moreEntropy = false) {
                return uniqid($prefix, $moreEntropy);
            })
        ];
    }
}
