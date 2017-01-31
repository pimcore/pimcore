<?php

namespace PimcoreBundle\Document;

use Pimcore\Model\Document\PageSnippet;
use Pimcore\Model\Document\Tag;
use Pimcore\View;
use PimcoreBundle\EventListener\Editmode;
use PimcoreBundle\Service\Request\EditmodeResolver;
use PimcoreBundle\View\ZendViewProvider;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class TagRenderer implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var ZendViewProvider
     */
    protected $viewProvider;

    /**
     * @var EditmodeResolver
     */
    protected $editmodeResolver;

    /**
     * @param ZendViewProvider $viewProvider
     * @param EditmodeResolver $editmodeResolver
     */
    public function __construct(ZendViewProvider $viewProvider, EditmodeResolver $editmodeResolver)
    {
        $this->viewProvider     = $viewProvider;
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
     * @return mixed|Tag|string
     *
     * @see \Pimcore\View::tag
     */
    public function render(PageSnippet $document, $type, $inputName, array $options = [])
    {
        $type = strtolower($type);
        $name = Tag::buildTagName($type, $inputName, $document);

        $editmode = $this->editmodeResolver->isEditmode();

        try {
            if ($document instanceof PageSnippet) {
                $tag = $document->getElement($name);

                if ($tag instanceof Tag && $tag->getType() === $type) {
                    // call the load() method if it exists to reinitialize the data (eg. from serializing, ...)
                    if (method_exists($tag, 'load')) {
                        $tag->load();
                    }

                    // create dummy view and add needed vars (depending on element)
                    $view           = $this->viewProvider->getView();
                    $view->editmode = $editmode;
                    $view->document = $document;

                    $tag->setView($view);
                    $tag->setEditmode($editmode);
                    $tag->setOptions($options);
                } else {
                    $tag = Tag::factory($type, $name, $document->getId(), $options, null, null, $editmode);
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
