<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @category   Pimcore
 * @package    Document
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Document;

use Pimcore\Model;
use Pimcore\Tool\Serialize;
use Pimcore\View;
use Pimcore\Model\Document;
use Pimcore\Model\Element;

class Service extends Model\Element\Service {

    /**
     * @var User
     */
    protected $_user;
    /**
     * @var array
     */
    protected $_copyRecursiveIds;

    /**
     * @param null $user
     */
    public function __construct($user = null) {
        $this->_user = $user;
    }

    /**
     * static function to render a document outside of a view
     *
     * @static
     * @param Document $document
     * @param array $params
     * @param bool $useLayout
     * @return string
     */
    public static function render(Document $document, $params = array(), $useLayout = false)
    {
        $layoutInCurrentAction = (\Zend_Layout::getMvcInstance() instanceof \Zend_Layout) ? \Zend_Layout::getMvcInstance()->getLayout() : false;
        
        $viewHelper = \Zend_Controller_Action_HelperBroker::getExistingHelper("ViewRenderer");
        if($viewHelper) {
            if($viewHelper->view === null) {
                $viewHelper->initView(PIMCORE_WEBSITE_PATH . "/views");
            }
            $view = $viewHelper->view;
        } else {
            $view = new \Pimcore\View();
        }

        // add the view script path from the website module to the view, because otherwise it's not possible to call
        // this method out of other modules to render documents, eg. sending e-mails out of an plugin with Pimcore_Mail
        $moduleDirectory = \Zend_Controller_Front::getInstance()->getModuleDirectory($document->getModule());
        if (!empty($moduleDirectory)) {
            $view->addScriptPath($moduleDirectory . "/views/layouts");
            $view->addScriptPath($moduleDirectory . "/views/scripts");
        } else {
            $view->addScriptPath(PIMCORE_FRONTEND_MODULE . "/views/layouts");
            $view->addScriptPath(PIMCORE_FRONTEND_MODULE . "/views/scripts");
        }

        $documentBackup = null;
        if($view->document) {
            $documentBackup = $view->document;
        }
        $view->document = $document;

        if ($useLayout) {
            if(!$layout = \Zend_Layout::getMvcInstance()) {
                $layout = \Zend_Layout::startMvc();
                $layout->setViewSuffix(View::getViewScriptSuffix());
                if($layoutHelper = $view->getHelper("layout")) {
                    $layoutHelper->setLayout($layout);
                }
            }
            $layout->setLayout("--modification-indicator--");
        }

        $params["document"] = $document;

        foreach ($params as $key => $value) {
             if (!$view->$key) {
                 $view->$key = $value;
             }
        }

        $content = $view->action($document->getAction(), $document->getController(), $document->getModule(), $params);

        //has to be called after $view->action so we can determine if a layout is enabled in $view->action()
        if ($useLayout) {
            if ($layout instanceof \Zend_Layout) {
                $layout->{$layout->getContentKey()} = $content;
                if (is_array($params)) {
                    foreach ($params as $key => $value) {
                            $layout->getView()->$key = $value;
                    }
                }

                // when using Document\Service::render() you have to set a layout in the view ($this->layout()->setLayout("mylayout"))
                if($layout->getLayout() != "--modification-indicator--") {
                    $content = $layout->render();
                }

                //deactivate the layout if it was not activated in the called action
                //otherwise we would activate the layout in the called action
                \Zend_Layout::resetMvcInstance();
                if (!$layoutInCurrentAction) {
                    $layout->disableLayout();
                } else {
                    $layout = \Zend_Layout::startMvc();
                    $layout->setViewSuffix(View::getViewScriptSuffix()); // set pimcore specifiy view suffix
                    $layout->setLayout($layoutInCurrentAction);
                    $view->getHelper("Layout")->setLayout($layout);
                }
                $layout->{$layout->getContentKey()} = null; //reset content

            }
        }

        if($documentBackup) {
            $view->document = $documentBackup;
        }

        if(\Pimcore\Config::getSystemConfig()->outputfilters->less){
            $content = \Pimcore\Tool\Less::processHtml($content);
        }

        return $content;
    }

    /**
     * Save document and all child documents
     *
     * @param     $document
     * @param int $collectGarbageAfterIteration
     * @param int $saved
     */
    public static function saveRecursive($document,$collectGarbageAfterIteration = 25, &$saved = 0){
        if($document instanceof Document){
            $document->save();
            $saved++;
            if($saved%$collectGarbageAfterIteration === 0){
                \Pimcore::collectGarbage();
            }
        }

        foreach($document->getChilds() as $child){
            if(!$child->hasChilds()){
                $child->save();
                $saved++;
                if($saved%$collectGarbageAfterIteration === 0){
                    \Pimcore::collectGarbage();
                }
            }
            if($child->hasChilds()){
              self::saveRecursive($child,$collectGarbageAfterIteration,$saved);
            }
        }
    }

    /**
     * @param  Document $target
     * @param  Document $source
     * @return Document copied document
     */
    public function copyRecursive($target, $source) {

        // avoid recursion
        if (!$this->_copyRecursiveIds) {
            $this->_copyRecursiveIds = array();
        }
        if (in_array($source->getId(), $this->_copyRecursiveIds)) {
            return;
        }


        if (method_exists($source, "getElements")) {
            $source->getElements();
        }

        $source->getProperties();

        $new = clone $source;
        $new->id = null;
        $new->setChilds(null);
        $new->setKey(Element\Service::getSaveCopyName("document",$new->getKey(), $target));
        $new->setParentId($target->getId());
        $new->setUserOwner($this->_user->getId());
        $new->setUserModification($this->_user->getId());
        $new->setResource(null);
        $new->setLocked(false);
        $new->setCreationDate(time());
        if(method_exists($new, "setPrettyUrl")) {
            $new->setPrettyUrl(null);
        }

        $new->save();

        // add to store
        $this->_copyRecursiveIds[] = $new->getId();

        foreach ($source->getChilds() as $child) {
            $this->copyRecursive($new, $child);
        }

        $this->updateChilds($target,$new);

        return $new;
    }

    /**
     * @param  Document $target
     * @param  Document $source
     * @return Document copied document
     */
    public function copyAsChild($target, $source, $enableInheritance = false) {

        if (method_exists($source, "getElements")) {
            $source->getElements();
        }

        $source->getProperties();

        $new = clone $source;
        $new->id = null;
        $new->setChilds(null);
        $new->setKey(Element\Service::getSaveCopyName("document",$new->getKey(), $target));
        $new->setParentId($target->getId());
        $new->setUserOwner($this->_user->getId());
        $new->setUserModification($this->_user->getId());
        $new->setResource(null);
        $new->setLocked(false);
        $new->setCreationDate(time());
        if(method_exists($new, "setPrettyUrl")) {
            $new->setPrettyUrl(null);
        }

        if($enableInheritance && ($new instanceof Document\PageSnippet)) {
            $new->setElements(array());
            $new->setContentMasterDocumentId($source->getId());
        }

        $new->save();

        $this->updateChilds($target,$new);

        return $new;
    }

    /**
     * @param $target
     * @param $source
     * @return mixed
     * @throws \Exception
     */
    public function copyContents($target, $source) {

        // check if the type is the same
        if (get_class($source) != get_class($target)) {
            throw new \Exception("Source and target have to be the same type");
        }

        if ($source instanceof Document\PageSnippet) {
            $target->setElements($source->getElements());

            $target->setTemplate($source->getTemplate());
            $target->setAction($source->getAction());
            $target->setController($source->getController());

            if ($source instanceof Document\Page) {
                $target->setTitle($source->getTitle());
                $target->setDescription($source->getDescription());
                $target->setKeywords($source->getKeywords());
            }
        }
        else if ($source instanceof Document\Link) {
            $target->setInternalType($source->getInternalType());
            $target->setInternal($source->getInternal());
            $target->setDirect($source->getDirect());
            $target->setLinktype($source->getLinktype());
            $target->setTarget($source->getTarget());
            $target->setParameters($source->getParameters());
            $target->setAnchor($source->getAnchor());
            $target->setTitle($source->getTitle());
            $target->setAccesskey($source->getAccesskey());
            $target->setRel($source->getRel());
            $target->setTabindex($source->getTabindex());
        }

        $target->setUserModification($this->_user->getId());
        $target->setProperties($source->getProperties());
        $target->save();

        return $target;
    }

    /**
     * @param  Document $document
     * @return void
     */
    public static function gridDocumentData($document) {
        $data = Element\Service::gridElementData($document);

        if ($document instanceof Document\Page) {
            $data["title"] = $document->getTitle();
            $data["description"] = $document->getDescription();
            $data["keywords"] = $document->getKeywords();
        } else {
            $data["title"] = "";
            $data["description"] = "";
            $data["keywords"] = "";
            $data["name"] = "";
        }

        return $data;
    }

    /**
     * @static
     * @param $doc
     * @return mixed
     */
    public static function loadAllDocumentFields ( $doc ) {

        $doc->getProperties();

        if($doc instanceof Document\PageSnippet) {
            foreach($doc->getElements() as $name => $data) {
                if(method_exists($data, "load")) {
                    $data->load();
                }
            }
        }

        return $doc;
    }

    /**
     * @static
     * @param $path
     * @return bool
     */
    public static function pathExists($path, $type = null) {

        $path = Element\Service::correctPath($path);

        try {
            $document = new Document();
            // validate path
            if (\Pimcore\Tool::isValidPath($path)) {
                $document->getResource()->getByPath($path);
                return true;
            }
        }
        catch (\Exception $e) {

        }

        return false;
    }

    /**
     * @param $type
     * @return bool
     */
    public static function isValidType ($type) {
        return in_array($type, Document::getTypes());
    }

    /**
     * Rewrites id from source to target, $rewriteConfig contains
     * array(
     *  "document" => array(
     *      SOURCE_ID => TARGET_ID,
     *      SOURCE_ID => TARGET_ID
     *  ),
     *  "object" => array(...),
     *  "asset" => array(...)
     * )
     * @param $document
     * @param $rewriteConfig
     * @return Document
     */
    public static function rewriteIds($document, $rewriteConfig, $params = array()) {

        // rewriting elements only for snippets and pages
        if($document instanceof Document\PageSnippet) {
            if(array_key_exists("enableInheritance", $params) && $params["enableInheritance"]) {
                $elements = $document->getElements();
                $changedElements = array();
                $contentMaster = $document->getContentMasterDocument();
                if($contentMaster instanceof Document\PageSnippet) {
                    $contentMasterElements = $contentMaster->getElements();
                    foreach ($contentMasterElements as $contentMasterElement) {
                        if(method_exists($contentMasterElement, "rewriteIds")) {
                            $element = clone $contentMasterElement;
                            $element->rewriteIds($rewriteConfig);

                            if(Serialize::serialize($element) != Serialize::serialize($contentMasterElement)) {
                                $changedElements[] = $element;
                            }
                        }
                    }
                }

                if(count($changedElements) > 0) {
                    $elements = $changedElements;
                }
            } else {
                $elements = $document->getElements();
                foreach ($elements as &$element) {
                    if(method_exists($element, "rewriteIds")) {
                        $element->rewriteIds($rewriteConfig);
                    }
                }
            }

            $document->setElements($elements);
        } else if ($document instanceof Document\Hardlink) {
            if(array_key_exists("document", $rewriteConfig) && $document->getSourceId() && array_key_exists((int) $document->getSourceId(), $rewriteConfig["document"])) {
                $document->setSourceId($rewriteConfig["document"][(int) $document->getSourceId()]);
            }
        } else if ($document instanceof Document\Link) {
            if(array_key_exists("document", $rewriteConfig) && $document->getLinktype() == "internal" && $document->getInternalType() == "document" && array_key_exists((int) $document->getInternal(), $rewriteConfig["document"])) {
                $document->setInternal($rewriteConfig["document"][(int) $document->getInternal()]);
            }
        }

        // rewriting properties
        $properties = $document->getProperties();
        foreach ($properties as &$property) {
            $property->rewriteIds($rewriteConfig);
        }
        $document->setProperties($properties);

        return $document;
    }

    /**
     * @param $url
     * @return Document
     */
    public static function getByUrl($url) {
        $urlParts = parse_url($url);
        if($urlParts["path"]) {
            $document = Document::getByPath($urlParts["path"]);

            // search for a page in a site
            if(!$document) {
                $sitesList = new Model\Site\Listing();
                $sitesObjects = $sitesList->load();

                foreach ($sitesObjects as $site) {
                    if ($site->getRootDocument() && (in_array($urlParts["host"],$site->getDomains()) || $site->getMainDomain() == $urlParts["host"])) {
                        if($document = Document::getByPath($site->getRootDocument() . $urlParts["path"])) {
                            break;
                        }
                    }
                }
            }
        }

        return $document;
    }
}