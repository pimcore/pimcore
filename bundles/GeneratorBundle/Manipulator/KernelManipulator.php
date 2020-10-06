<?php

namespace Pimcore\Bundle\GeneratorBundle\Manipulator;

use Pimcore\Bundle\GeneratorBundle\Generator\Generator;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @deprecated
 * Changes the PHP code of a Kernel.
 *
 * The following class is copied from \Sensio\Bundle\GeneratorBundle\Manipulator\KernelManipulator
 */
class KernelManipulator extends Manipulator
{
    protected $kernel;
    protected $reflected;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
        $this->reflected = new \ReflectionObject($kernel);
    }

    /**
     * Adds a bundle at the end of the existing ones.
     *
     * @param string $bundle The bundle class name
     *
     * @return bool Whether the operation succeeded
     *
     * @throws \RuntimeException If bundle is already defined
     */
    public function addBundle($bundle)
    {
        if (!$this->getFilename()) {
            return false;
        }

        $src = file($this->getFilename());
        $method = $this->reflected->getMethod('registerBundles');
        $lines = array_slice($src, $method->getStartLine() - 1, $method->getEndLine() - $method->getStartLine() + 1);

        // Don't add same bundle twice
        if (false !== strpos(implode('', $lines), $bundle)) {
            throw new \RuntimeException(sprintf('Bundle "%s" is already defined in "AppKernel::registerBundles()".', $bundle));
        }

        $this->setCode(token_get_all('<?php '.implode('', $lines)), $method->getStartLine());

        while ($token = $this->next()) {
            // $bundles
            if (T_VARIABLE !== $token[0] || '$bundles' !== $token[1]) {
                continue;
            }

            // =
            $this->next();

            // array start with traditional or short syntax
            $token = $this->next();
            if (T_ARRAY !== $token[0] && '[' !== $this->value($token)) {
                return false;
            }

            // add the bundle at the end of the array
            while ($token = $this->next()) {
                // look for ); or ];
                if (')' !== $this->value($token) && ']' !== $this->value($token)) {
                    continue;
                }

                if (';' !== $this->value($this->peek())) {
                    continue;
                }

                $this->next();

                $leadingContent = implode('', array_slice($src, 0, $this->line));

                // trim semicolon
                $leadingContent = rtrim(rtrim($leadingContent), ';');

                // We want to match ) & ]
                $closingSymbolRegex = '#(\)|])$#';

                // get closing symbol used
                preg_match($closingSymbolRegex, $leadingContent, $matches);
                $closingSymbol = $matches[0];

                // remove last close parentheses
                $leadingContent = rtrim(preg_replace($closingSymbolRegex, '', rtrim($leadingContent)));

                if ('(' !== substr($leadingContent, -1) && '[' !== substr($leadingContent, -1)) {
                    // end of leading content is not open parentheses or bracket, then assume that array contains at least one element
                    $leadingContent = rtrim($leadingContent, ',').',';
                }

                $lines = array_merge(
                    [$leadingContent, "\n"],
                    [str_repeat(' ', 12), sprintf('new %s(),', $bundle), "\n"],
                    [str_repeat(' ', 8), $closingSymbol.';', "\n"],
                    array_slice($src, $this->line)
                );

                Generator::dump($this->getFilename(), implode('', $lines));

                return true;
            }
        }

        return false;
    }

    public function getFilename()
    {
        return $this->reflected->getFileName();
    }
}
