<?php

namespace Pimcore\Tests\Util;

use Pimcore\Model\Asset;
use Pimcore\Model\Document;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\User;
use Pimcore\Model\Webservice\Tool as WebserviceTool;
use Pimcore\Model\Object as ObjectModel;

class TestHelper
{
    /**
     * Constant will be defined upon suite initialization and will result to true
     * if we have a valid DB configuration.
     *
     * @return bool
     */
    public static function supportsDbTests()
    {
        return defined('PIMCORE_TEST_DB_INITIALIZED') ? PIMCORE_TEST_DB_INITIALIZED : false;
    }

    /**
     * Check DB support and mark test skipped if DB connection wasn't established
     */
    public static function checkDbSupport()
    {
        if (!static::supportsDbTests()) {
            throw new \PHPUnit_Framework_SkippedTestError('Not running test as DB connection couldn\'t be established');
        }
    }

    public static function getSoapClient()
    {
        ini_set("soap.wsdl_cache_enabled", "0");
        $conf = \Pimcore\Cache\Runtime::get("pimcore_config_test");

        $user = User::getById($conf->user);
        if (!$user instanceof User) {
            throw new \Exception("invalid user id");
        }

        $client = new \Zend_Soap_Client($conf->webservice->wsdl . "&username=" . $user->getUsername() . "&apikey=" . $user->getPassword(),
            [
                "cache_wsdl"   => false,
                "soap_version" => SOAP_1_2,
                "classmap"     => WebserviceTool::createClassMappings()
            ]);

        $client->setLocation($conf->webservice->serviceEndpoint . "?username=" . $user->getUsername() . "&apikey=" . $user->getPassword());

        return $client;
    }


    /**
     * @static
     * @param  array $properties
     * @return array
     */
    protected static function createPropertiesComparisonString($properties)
    {
        $propertiesStringArray = [];
        ksort($properties);
        if (is_array($properties)) {
            foreach ($properties as $key => $value) {
                if ($value->type == "asset" || $value->type == "object" || $value->type == "document") {
                    if ($value->data instanceof ElementInterface) {
                        $propertiesStringArray["property_" . $key . "_" . $value->type] = "property_" . $key . "_" . $value->type . ":" . $value->data->getId();
                    } else {
                        $propertiesStringArray["property_" . $key . "_" . $value->type] = "property_" . $key . "_" . $value->type . ": null";
                    }
                } elseif ($value->type == 'date') {
                    if ($value->data instanceof \DateTimeInterface) {
                        $propertiesStringArray["property_" . $key . "_" . $value->type] = "property_" . $key . "_" . $value->type . ":" . $value->data->getTimestamp();
                    }
                } elseif ($value->type == "bool") {
                    $propertiesStringArray["property_" . $key . "_" . $value->type] = "property_" . $key . "_" . $value->type . ":" . (bool)$value->data;
                } elseif ($value->type == "text" || $value->type == "select") {
                    $propertiesStringArray["property_" . $key . "_" . $value->type] = "property_" . $key . "_" . $value->type . ":" . $value->data;
                } else {
                    throw new \Exception("Unknown property of type [ " . $value->type . " ]");
                }
            }
        }

        return $propertiesStringArray;
    }


    /**
     * @param  Asset $asset
     * @return string
     */
    public static function createAssetComparisonString($asset, $ignoreCopyDifferences = false)
    {
        if ($asset instanceof Asset) {
            $a = [];

            //custom settings
            if (is_array($asset->getCustomSettings())) {
                $a["customSettings"] = serialize($asset->getCustomSettings());
            }

            if ($asset->getData()) {
                $a["data"] = base64_encode($asset->getData());
            }

            if (!$ignoreCopyDifferences) {
                $a["filename"]     = $asset->getFilename();
                $a["id"]           = $asset->getId();
                $a["modification"] = $asset->getModificationDate();
                $a["creation"]     = $asset->getCreationDate();
                $a["userModified"] = $asset->getUserModification();
                $a["parentId"]     = $asset->getParentId();
                $a["path"]         = $asset->getPath;
            }


            $a["userOwner"] = $asset->getUserOwner();


            $properties = $asset->getProperties();
            $a          = array_merge($a, self::createPropertiesComparisonString($properties));

            return implode(",", $a);
        } else {
            return null;
        }
    }

    /**
     * @param  Asset $asset1
     * @param  Asset $asset2
     * @return bool
     */
    public static function assetsAreEqual($asset1, $asset2, $ignoreCopyDifferences = false, $id = false)
    {
        if ($asset1 instanceof Asset and $asset2 instanceof Asset) {
            $a1Hash = self::createAssetComparisonString($asset1, $ignoreCopyDifferences);
            $a2Hash = self::createAssetComparisonString($asset2, $ignoreCopyDifferences);

            if (!$id) {
                $id = uniqid();
            }

            $myFile = TESTS_PATH . "/output/asset1-" . $id . ".txt";
            $fh     = fopen($myFile, 'w');
            fwrite($fh, $a1Hash);
            fclose($fh);

            $myFile = TESTS_PATH . "/output/asset2-" . $id . ".txt";
            $fh     = fopen($myFile, 'w');
            fwrite($fh, $a2Hash);
            fclose($fh);


            return $a1Hash === $a2Hash ? true : false;
        } else {
            return false;
        }
    }

    /**
     * @param  Document $document
     * @return string
     */
    protected static function createDocumentComparisonString($document, $ignoreCopyDifferences = false)
    {
        if ($document instanceof Document) {
            $d = [];

            if ($document instanceof Document_PageSnippet) {
                $elements = $document->getElements();
                ksort($elements);
                foreach ($elements as $key => $value) {
                    if ($value instanceof Document_Tag_Video) {
                        //with video can't use frontend(), it includes random id
                        $d["element_" . $key] = $value->getName() . ":" . $value->type . "_" . $value->id;
                    } elseif (!$value instanceof Document_Tag_Block) {
                        $d["element_" . $key] = $value->getName() . ":" . $value->frontend();
                    } else {
                        $d["element_" . $key] = $value->getName();
                    }
                }

                if ($document instanceof Document_Page) {
                    $d["name"]        = $document->getName();
                    $d["keywords"]    = $document->getKeywords();
                    $d["title"]       = $document->getTitle();
                    $d["description"] = $document->getDescription();
                }

                $d["published"] = $document->isPublished();
            }

            if ($document instanceof Document_Link) {
                $d['link'] = $document->getHtml();
            }

            if (!$ignoreCopyDifferences) {
                $d["key"]          = $document->getKey();
                $d["id"]           = $document->getId();
                $d["modification"] = $document->getModificationDate();
                $d["creation"]     = $document->getCreationDate();
                $d["userModified"] = $document->getUserModification();
                $d["parentId"]     = $document->getParentId();
                $d["path"]         = $document->getPath();
            }

            $d["userOwner"] = $document->getUserOwner();

            $properties = $document->getProperties();
            $d          = array_merge($d, self::createPropertiesComparisonString($properties));

            return implode(",", $d);
        } else {
            return null;
        }
    }

    /**
     * @param  Document $doc1
     * @param  Document $doc2
     * @return bool
     */
    public static function documentsAreEqual($doc1, $doc2, $ignoreCopyDifferences = false)
    {
        if ($doc1 instanceof Document and $doc2 instanceof Document) {
            $d1Hash = self::createDocumentComparisonString($doc1, $ignoreCopyDifferences);
            $d2Hash = self::createDocumentComparisonString($doc2, $ignoreCopyDifferences);

            $id = uniqid();

            /*
                        $myFile = TESTS_PATH . "/output/document1-" . $id . ".txt";
                        $fh = fopen($myFile, 'w');
                        fwrite($fh, $d1Hash);
                        fclose($fh);

                        $myFile = TESTS_PATH . "/output/document2-" . $id . ".txt";
                        $fh = fopen($myFile, 'w');
                        fwrite($fh, $d2Hash);
                        fclose($fh);
              */
            return $d1Hash === $d2Hash ? true : false;
        } else {
            return false;
        }
    }


    public static function getComparisonDataForField($key, $fd, $object)
    {

        // omit password, this one we don't get through WS,
        // omit non owner objects, they don't get through WS,
        // plus omit fields which don't have get method
        $getter = "get" . ucfirst($key);
        if (method_exists($object, $getter) and $fd instanceof Object_Class_Data_Fieldcollections) {
            if ($object->$getter()) {
                $collection = $object->$getter();
                $items      = $collection->getItems();
                if (is_array($items)) {
                    $returnValue = [];
                    $counter     = 0;
                    foreach ($items as $item) {
                        $def = $item->getDefinition();

                        foreach ($def->getFieldDefinitions() as $k => $v) {
                            $getter     = "get" . ucfirst($v->getName());
                            $fieldValue = $item->$getter();

                            if ($v instanceof Object_Class_Data_Link) {
                                $fieldValue = serialize($v);
                            } elseif ($v instanceof Object_Class_Data_Password or $fd instanceof Object_Class_Data_Nonownerobjects) {
                                $fieldValue = null;
                            } else {
                                $fieldValue = $v->getForCsvExport($item);
                            }

                            $returnValue[$counter][$k] = $fieldValue;
                        }
                        $counter++;
                    }

                    return serialize($returnValue);
                }
            }
        } elseif (method_exists($object, $getter) and $fd instanceof Object_Class_Data_Localizedfields) {
            $data  = $object->$getter();
            $lData = [];

            if (!$data instanceof Object_Localizedfield) {
                return [];
            }

            try {
                $localeBak = \Pimcore\Cache\Runtime::get("Zend_Locale");
            } catch (Exception $e) {
                $localeBak = null;
            }

            foreach ($data->getItems() as $language => $values) {
                foreach ($fd->getFieldDefinitions() as $fd) {
                    \Pimcore\Cache\Runtime::set("Zend_Locale", new Zend_Locale($language));

                    $lData[$language][$fd->getName()] = self::getComparisonDataForField($fd->getName(), $fd, $object);;
                }
            }
            if ($localeBak) {
                \Pimcore\Cache\Runtime::set("Zend_Locale", $localeBak);
            }

            return serialize($lData);
        } elseif (method_exists($object, $getter) and $fd instanceof Object_Class_Data_Link) {
            return serialize($fd);
        } elseif (method_exists($object, $getter) and !$fd instanceof Object_Class_Data_Password and !$fd instanceof Object_Class_Data_Nonownerobjects) {
            return $fd->getForCsvExport($object);
        }
    }

    /**
     * @param  Object_Abstract $object
     * @return string
     */
    protected static function createObjectComparisonString($object, $ignoreCopyDifferences)
    {
        if ($object instanceof Object_Abstract) {
            $o = [];

            if ($object instanceof Object_Concrete) {
                foreach ($object->getClass()->getFieldDefinitions() as $key => $value) {
                    $o[$key] = self::getComparisonDataForField($key, $value, $object);
                }

                $o["published"] = $object->isPublished();
            }
            if (!$ignoreCopyDifferences) {
                $o["id"]           = $object->getId();
                $o["key"]          = $object->getKey();
                $o["modification"] = $object->getModificationDate();
                $o["creation"]     = $object->getCreationDate();
                $o["userModified"] = $object->getUserModification();
                $o["parentId"]     = $object->getParentId();
                $o["path"]         = $object->getPath;
            }


            $o["userOwner"] = $object->getUserOwner();


            $properties = $object->getProperties();
            $o          = array_merge($o, self::createPropertiesComparisonString($properties));

            return implode(",", $o);
        } else {
            return null;
        }
    }

    public static function objectsAreEqual($object1, $object2, $ignoreCopyDifferences)
    {
        if ($object1 instanceof Object_Abstract and $object2 instanceof Object_Abstract) {
            $o1Hash = self::createObjectComparisonString($object1, $ignoreCopyDifferences);
            $o2Hash = self::createObjectComparisonString($object2, $ignoreCopyDifferences);

            $id = uniqid();

            return $o1Hash === $o2Hash ? true : false;
        } else {
            return false;
        }
    }

    /**
     * resets the registry
     * @static
     * @return void
     */
    public static function resetRegistry()
    {
        $conf = \Pimcore\Cache\Runtime::get('pimcore_config_test');
        \Pimcore\Cache\Runtime::_unsetInstance();
        \Pimcore\Cache\Runtime::set('pimcore_config_test', $conf);
        Pimcore::initConfiguration();
        Pimcore\Legacy::initPlugins();
    }

    /**
     * @param string $keyPrefix
     * @param bool $save
     * @return Unittest
     */
    public static function createEmptyObject($keyPrefix = "", $save = true)
    {
        if ($keyPrefix == null) {
            $keyPrefix = "";
        }
        $emptyObject = new Unittest();
        $emptyObject->setOmitMandatoryCheck(true);
        $emptyObject->setParentId(1);
        $emptyObject->setUserOwner(1);
        $emptyObject->setUserModification(1);
        $emptyObject->setCreationDate(time());
        $emptyObject->setKey($keyPrefix . uniqid() . rand(10, 99));
        if ($save) {
            $emptyObject->save();
        }

        return $emptyObject;
    }

    public static function createEmptyObjects($keyPrefix = "", $save = true, $count = 10)
    {
        $result = [];
        for ($i = 0; $i < $count; $i++) {
            $result[] = self::createEmptyObject($keyPrefix, $save);
        }

        return $result;
    }


    /**
     * @param string $keyPrefix
     * @param bool $save
     * @return Unittest
     */
    public static function createFullyFledgedObject($keyPrefix = "", $save = true, $seed = 1)
    {
        if ($keyPrefix == null) {
            $keyPrefix = "";
        }
        $object = new Unittest();
        $object->setOmitMandatoryCheck(true);
        $object->setParentId(1);
        $object->setUserOwner(1);
        $object->setUserModification(1);
        $object->setCreationDate(time());
        $object->setKey($keyPrefix . uniqid() . rand(10, 99));


        try {
            Test_Data::fillInput($object, "input", $seed);
            Test_Data::fillNumber($object, "number", $seed);
            Test_Data::fillTextarea($object, "textarea", $seed);
            Test_Data::fillSlider($object, "slider", $seed);
            Test_Data::fillHref($object, "href", $seed);
            Test_Data::fillMultihref($object, "multihref", $seed);
            Test_Data::fillImage($object, "image", $seed);
            Test_Data::fillHotspotImage($object, "hotspotimage", $seed);
            Test_Data::fillLanguage($object, "languagex", $seed);
            Test_Data::fillCountry($object, "country", $seed);
            Test_Data::fillDate($object, "date", $seed);
            Test_Data::fillDate($object, "datetime", $seed);
            Test_Data::fillTime($object, "time", $seed);
            Test_Data::fillSelect($object, "select", $seed);
            Test_Data::fillMultiSelect($object, "multiselect", $seed);
            Test_Data::fillUser($object, "user", $seed);
            Test_Data::fillCheckbox($object, "checkbox", $seed);
            Test_Data::fillWysiwyg($object, "wysiwyg", $seed);
            Test_Data::fillPassword($object, "password", $seed);
            Test_Data::fillMultiSelect($object, "countries", $seed);
            Test_Data::fillMultiSelect($object, "languages", $seed);
            Test_Data::fillGeopoint($object, "point", $seed);
            Test_Data::fillGeobounds($object, "bounds", $seed);
            Test_Data::fillGeopolygon($object, "poly", $seed);
            Test_Data::fillTable($object, "table", $seed);
            Test_Data::fillLink($object, "link", $seed);
            Test_Data::fillStructuredTable($object, "structuredtable", $seed);
            Test_Data::fillObjects($object, "objects", $seed);
            Test_Data::fillObjectsWithMetadata($object, "objectswithmetadata", $seed);
            Test_Data::fillInput($object, "linput", $seed, "de");
            Test_Data::fillInput($object, "linput", $seed, "en");
            Test_Data::fillObjects($object, "lobjects", $seed, "de");
            Test_Data::fillObjects($object, "lobjects", $seed, "en");
            Test_Data::fillKeyValue($object, "keyvaluepairs", $seed);
            Test_Data::fillBricks($object, "mybricks", $seed);
            Test_Data::fillFieldCollection($object, "myfieldcollection", $seed);
        } catch (Exception $e) {
            print($e . "\n");
        }

        if ($save) {
            $object->save();
        }

        return $object;
    }


    /**
     * @param string $keyPrefix
     * @param bool $save
     * @return Document_Page
     */
    public static function createEmptyDocumentPage($keyPrefix = "", $save = true)
    {
        if ($keyPrefix == null) {
            $keyPrefix = "";
        }
        $document = new Document_Page();
        $document->setType("page");
        $document->setParentId(1);
        $document->setUserOwner(1);
        $document->setUserModification(1);
        $document->setCreationDate(time());
        $document->setKey($keyPrefix . uniqid() . rand(10, 99));
        if ($save) {
            $document->save();
        }

        return $document;
    }


    /**
     * @param string $keyPrefix
     * @param bool $save
     * @return Asset_Image
     */
    public static function createImageAsset($keyPrefix = "", $data, $save = true)
    {
        if ($keyPrefix == null) {
            $keyPrefix = "";
        }
        if (!$data) {
            $data = file_get_contents(TESTS_PATH . "/resources/assets/images/image5.jpg");
        }
        $asset = new Asset_Image();
        $asset->setParentId(1);
        $asset->setUserOwner(1);
        $asset->setUserModification(1);
        $asset->setCreationDate(time());
        $asset->setData($data);
        $asset->setType("image");
        $property = new Property();

        $property->setName("propname");
        $property->setType("text");
        $property->setData("bla");
        $properties = [$property];
        $asset->setProperties($properties);
        $asset->setFilename($keyPrefix . uniqid() . rand(10, 99) . ".jpg");
        if ($save) {
            $asset->save();
        }

        return $asset;
    }

    public static function cleanUp($cleanAssets = true, $cleanDocuments = true, $cleanObjects = true)
    {
        \Pimcore::collectGarbage();

        if ($cleanObjects) {
            try {
                $objectRoot = \Pimcore\Model\Object\AbstractObject::getById(1);
                if ($objectRoot and $objectRoot->hasChildren()) {
                    $childs = $objectRoot->getChildren();

                    foreach ($childs as $child) {
                        print("   delete object " . $child->getId());
                        $child->delete();
                    }
                }
            } catch (\Exception $e) {
                print($e);
            }
        }

        if ($cleanAssets) {
            try {
                $assetRoot = \Pimcore\Model\Asset::getById(1);
                if ($assetRoot and $assetRoot->hasChildren()) {
                    $childs = $assetRoot->getChildren();
                    foreach ($childs as $child) {
                        $child->delete();
                    }
                }
            } catch (\Exception $e) {
                print($e);
            }
        }

        if ($cleanDocuments) {
            try {
                $documentRoot = \Pimcore\Model\Document::getById(1);
                if ($documentRoot and $documentRoot->hasChildren()) {
                    $childs = $documentRoot->getChildren();
                    foreach ($childs as $child) {
                        $child->delete();
                    }
                }
            } catch (\Exception $e) {
                print($e);
            }
        }

        \Pimcore::collectGarbage();

        print("    number of objects is " . static::getObjectCount() . "\n");
        print("\n");
    }

    /** Returns the total number of objects.
     * @return int object count.
     */
    public static function getObjectCount()
    {
        $list   = new ObjectModel\Listing();
        $childs = $list->load();

        return count($childs);
    }

    /** Returns the total number of assets.
     * @return int object count.
     */
    public static function getAssetCount()
    {
        $list   = new Asset\Listing();
        $childs = $list->load();

        return count($childs);
    }

    /** Returns the total number of documents.
     * @return int object count.
     */
    public static function getDocoumentCount()
    {
        $list   = new Document\Listing();
        $childs = $list->load();

        return count($childs);
    }
}
