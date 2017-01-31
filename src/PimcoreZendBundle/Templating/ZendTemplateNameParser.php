<?php

namespace PimcoreZendBundle\Templating;

use PimcoreZendBundle\Templating\ZendTemplateReference;
use Symfony\Bundle\FrameworkBundle\Templating\TemplateNameParser;
use Symfony\Component\Templating\TemplateReferenceInterface;

class ZendTemplateNameParser extends TemplateNameParser
{
    /**
     * {@inheritdoc}
     */
    public function parse($name)
    {
        if ($name instanceof TemplateReferenceInterface) {
            return $name;
        } elseif (isset($this->cache[$name])) {
            return $this->cache[$name];
        }

        // normalize name
        $name = str_replace(':/', ':', preg_replace('#/{2,}#', '/', str_replace('\\', '/', $name)));

        if (false !== strpos($name, '..')) {
            throw new \RuntimeException(sprintf('Template name "%s" contains invalid characters.', $name));
        }

        if ($this->isAbsolutePath($name) || !preg_match('/^(?:([^:]*):([^:]*):)?(.+)\.phtml$/', $name, $matches) || 0 === strpos($name, '@')) {
            return parent::parse($name);
        }

        $template = new ZendTemplateReference($matches[1], $matches[2], $matches[3]);

        if ($template->get('bundle')) {
            try {
                $this->kernel->getBundle($template->get('bundle'));
            } catch (\Exception $e) {
                throw new \InvalidArgumentException(sprintf('Template name "%s" is not valid.', $name), 0, $e);
            }
        }

        return $this->cache[$name] = $template;
    }

    private function isAbsolutePath($file)
    {
        $isAbsolute = (bool)preg_match('#^(?:/|[a-zA-Z]:)#', $file);

        if ($isAbsolute) {
            @trigger_error('Absolute template path support is deprecated since Symfony 3.1 and will be removed in 4.0.', E_USER_DEPRECATED);
        }

        return $isAbsolute;
    }
}
