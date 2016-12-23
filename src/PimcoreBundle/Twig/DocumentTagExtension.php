<?php

namespace PimcoreBundle\Twig;

use Pimcore\Logger;
use Pimcore\Model\Document\Page;
use Pimcore\Model\Document\PageSnippet;
use Pimcore\Model\Document\Tag;

class DocumentTagExtension extends \Twig_Extension
{
    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('pimcore_tag', [$this, 'tag'], [
                'needs_context' => true,
                'is_safe'       => ['html'],
            ])
        ];
    }

    public function tag($context, $type, $realName, array $options = [])
    {
        $document = $context['document'];
        if (!($document instanceof PageSnippet)) {
            return '';
        }

        $type = strtolower($type);
        $name = Tag::buildTagName($type, $realName, $document);

        try {
            if ($document instanceof PageSnippet) {
                $tag = $document->getElement($name);
                if ($tag instanceof Tag && $tag->getType() == $type) {

                    // call the load() method if it exists to reinitialize the data (eg. from serializing, ...)
                    if (method_exists($tag, 'load')) {
                        $tag->load();
                    }

                    $tag->setEditmode(true);
                    $tag->setOptions($options);
                } else {
                    $tag = Tag::factory($type, $name, $document->getId(), $options, null, null, true);
                    $document->setElement($name, $tag);
                }

                // set the real name of this editable, without the prefixes and suffixes from blocks and areablocks
                $tag->setRealName($realName);
            }

            return $tag;
        } catch (\Exception $e) {
            Logger::warning($e);
        }

        return '';
    }
}
