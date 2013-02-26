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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

include_once("startup.php");

$newsletter = Tool_Newsletter_Config::getByName($argv[1]);
if($newsletter) {

    $elementsPerLoop = 10;
    $objectList = "Object_" . ucfirst($newsletter->getClass()) . "_List";
    $list = new $objectList();

    $conditions = array("(newsletterActive = 1 AND newsletterConfirmed = 1)");
    if($newsletter->getObjectFilterSQL()) {
        $conditions[] = $newsletter->getObjectFilterSQL();
    }
    $list->setCondition(implode(" AND ", $conditions));

    $list->setOrderKey("email");
    $list->setOrder("ASC");

    $elementsTotal = $list->getTotalCount();
    $count = 0;

    for($i=0; $i<(ceil($elementsTotal/$elementsPerLoop)); $i++) {
        $list->setLimit($elementsPerLoop);
        $list->setOffset($i*$elementsPerLoop);

        $objects = $list->load();
        foreach ($objects as $object) {

            try {
                $count++;
                Logger::info("Sending newsletter " . $count . " / " . $elementsTotal. " [" . $newsletter->getName() . "]");

                Pimcore_Tool_Newsletter::sendMail($newsletter, $object);

                $note = new Element_Note();
                $note->setElement($object);
                $note->setDate(time());
                $note->setType("newsletter");
                $note->setTitle("sent newsletter: '" . $newsletter->getName() . "'");
                $note->setUser(0);
                $note->setData(array());
                $note->save();

                Logger::info("Sent newsletter to: " . obfucateEmail($object->getEmail()) . " [" . $newsletter->getName() . "]");
            } catch (\Exception $e) {
                Logger::err($e);
            }
        }

        Pimcore::collectGarbage();
    }

} else {
    Logger::emerg("Newsletter '" . $argv[1] . "' doesn't exist");
}



function obfucateEmail($email) {
    $email = substr_replace($email, ".xxx", strrpos($email, "."));
    return $email;
}
