<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Console\Command;

use Pimcore\Console\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Pimcore\Model;

class InternalNewsletterSendCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('internal:newsletter-send')
            ->setDescription('For internal use only')
            ->addArgument("id")->addArgument("hostUrl");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $newsletter = Model\Tool\Newsletter\Config::getByName($input->getArgument("id"));
        if ($newsletter) {
            $pidFile = $newsletter->getPidFile();

            if (file_exists($pidFile)) {
                \Logger::alert("Cannot send newsletters because there's already one active sending process");
                exit;
            }

            $elementsPerLoop = 10;
            $objectList = "\\Pimcore\\Model\\Object\\" . ucfirst($newsletter->getClass()) . "\\Listing";
            $list = new $objectList();

            $conditions = ["(newsletterActive = 1 AND newsletterConfirmed = 1)"];
            if ($newsletter->getObjectFilterSQL()) {
                $conditions[] = "(" . $newsletter->getObjectFilterSQL() . ")";
            }
            if ($newsletter->getPersonas()) {
                $class = Model\Object\ClassDefinition::getByName($newsletter->getClass());
                if ($class && $class->getFieldDefinition("persona")) {
                    $personas = [];
                    $p = explode(",", $newsletter->getPersonas());

                    if ($class->getFieldDefinition("persona") instanceof \Pimcore\Model\Object\ClassDefinition\Data\Persona) {
                        foreach ($p as $value) {
                            if (!empty($value)) {
                                $personas[] = $list->quote($value);
                            }
                        }
                        $conditions[] = "persona IN (" . implode(",", $personas) . ")";
                    } elseif ($class->getFieldDefinition("persona") instanceof \Pimcore\Model\Object\ClassDefinition\Data\Personamultiselect) {
                        $personasCondition = [];
                        foreach ($p as $value) {
                            $personasCondition[] = "persona LIKE " . $list->quote("%," . $value .  ",%");
                        }
                        $conditions[] = "(" . implode(" OR ", $personasCondition). ")";
                    }
                }
            }

            $list->setCondition(implode(" AND ", $conditions));
            $list->setOrderKey("email");
            $list->setOrder("ASC");

            $elementsTotal = $list->getTotalCount();
            $count = 0;

            $pidContents = [
                "start" => time(),
                "lastUpdate" => time(),
                "newsletter" => $newsletter->getName(),
                "total" => $elementsTotal,
                "current" => $count
            ];

            $this->writePid($pidFile, $pidContents);

            for ($i=0; $i<(ceil($elementsTotal/$elementsPerLoop)); $i++) {
                $list->setLimit($elementsPerLoop);
                $list->setOffset($i*$elementsPerLoop);

                $objects = $list->load();

                foreach ($objects as $object) {
                    try {
                        $count++;
                        \Logger::info("Sending newsletter " . $count . " / " . $elementsTotal. " [" . $newsletter->getName() . "]");

                        \Pimcore\Tool\Newsletter::sendMail($newsletter, $object, null, $input->getArgument("hostUrl"));

                        $note = new Model\Element\Note();
                        $note->setElement($object);
                        $note->setDate(time());
                        $note->setType("newsletter");
                        $note->setTitle("sent newsletter: '" . $newsletter->getName() . "'");
                        $note->setUser(0);
                        $note->setData([]);
                        $note->save();

                        \Logger::info("Sent newsletter to: " . $this->obfuscateEmail($object->getEmail()) . " [" . $newsletter->getName() . "]");
                    } catch (\Exception $e) {
                        \Logger::err($e);
                    }
                }

                // check if pid exists
                if (!file_exists($pidFile)) {
                    \Logger::alert("Newsletter PID not found, cancel sending process");
                    exit;
                }

                // update pid
                $pidContents["lastUpdate"] = time();
                $pidContents["current"] = $count;
                $this->writePid($pidFile, $pidContents);

                \Pimcore::collectGarbage();
            }

            // remove pid
            @unlink($pidFile);
        } else {
            \Logger::emerg("Newsletter '" . $input->getArgument("id") . "' doesn't exist");
        }
    }

    protected function obfuscateEmail($email)
    {
        $email = substr_replace($email, ".xxx", strrpos($email, "."));

        return $email;
    }

    protected function writePid($file, $content)
    {
        \Pimcore\File::put($file, serialize($content));
    }
}
