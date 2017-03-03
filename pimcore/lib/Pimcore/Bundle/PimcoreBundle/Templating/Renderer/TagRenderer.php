<?php

namespace Pimcore\Bundle\PimcoreBundle\Templating\Renderer;

use Pimcore\Bundle\PimcoreBundle\Service\Request\EditmodeResolver;
use Pimcore\Bundle\PimcoreBundle\Templating\Model\ViewModel;
use Pimcore\Model\Document\PageSnippet;
use Pimcore\Model\Document\Tag;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class TagRenderer implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var EditmodeResolver
     */
    protected $editmodeResolver;

    /**
     * @param EditmodeResolver $editmodeResolver
     */
    public function __construct(EditmodeResolver $editmodeResolver)
    {
        $this->editmodeResolver = $editmodeResolver;
    }

    /**
     * @param $type
     * @return bool
     */
    public function tagExists($type)
    {
        // TODO register tags on container
        $class = '\\Pimcore\\Model\\Document\\Tag\\' . ucfirst(strtolower($type));

        $classFound = false;
        if (\Pimcore\Tool::classExists($class)) { // TODO use ClassUtils
            $classFound = true;
        } else {
            $oldStyleClass = 'Document_Tag_' . ucfirst(strtolower($type));
            if (\Pimcore\Tool::classExists($oldStyleClass)) {
                $classFound = true;
            }
        }

        return $classFound;
    }

    /**
     * @param PageSnippet $document
     * @param $type
     * @param $inputName
     * @param array $options
     * @return Tag|string|null
     *
     * @see \Pimcore\View::tag
     */
    public function render(PageSnippet $document, $type, $inputName, array $options = [])
    {
        $type = strtolower($type);
        $name = Tag::buildTagName($type, $inputName, $document);

        $editmode = $this->editmodeResolver->isEditmode();

        try {
            $tag = null;

            if ($document instanceof PageSnippet) {
                $view = new ViewModel([
                    'editmode' => $editmode,
                    'document' => $document
                ]);

                $tag = $document->getElement($name);
                if ($tag instanceof Tag && $tag->getType() === $type) {
                    // call the load() method if it exists to reinitialize the data (eg. from serializing, ...)
                    if (method_exists($tag, 'load')) {
                        $tag->load();
                    }

                    $tag->setView($view);
                    $tag->setEditmode($editmode);
                    $tag->setOptions($options);
                } else {
                    $tag = Tag::factory($type, $name, $document->getId(), $options, null, $view, $editmode);
                    $document->setElement($name, $tag);
                }

                // set the real name of this editable, without the prefixes and suffixes from blocks and areablocks
                $tag->setRealName($inputName);
            }

            return $tag;
        } catch (\Exception $e) {
            $this->logger->warning($e);
        }

        return '';
    }
}
