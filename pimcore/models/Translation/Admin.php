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
 * @category   Pimcore
 * @package    Translation
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Translation;

use Pimcore\Model;
use Pimcore\Tool;

/**
 * @method \Pimcore\Model\Translation\Admin\Dao getDao()
 */
class Admin extends AbstractTranslation
{
    /**
     * @return array
     */
    protected static function getLanguages()
    {
        return \Pimcore\Tool\Admin::getLanguages();
    }

    /**
     * @param $id
     * @param bool $create
     * @param bool $returnIdIfEmpty
     * @param null $language
     * @return string
     * @throws \Exception
     */
    public static function getByKeyLocalized($id, $create = false, $returnIdIfEmpty = false, $language = null)
    {
        $language = null;

        if ($user = Tool\Admin::getCurrentUser()) {
            $language = $user->getLanguage();
        } elseif ($user = Tool\Authentication::authenticateSession()) {
            $language = $user->getLanguage();
        }

        if(!$language) {
            $language = \Pimcore::getContainer()->get("pimcore.locale")->findLocale();
        }

        if (!in_array($language, Tool\Admin::getLanguages())) {
            $config = \Pimcore\Config::getSystemConfig();
            $language = $config->general->language;
        }


        return self::getByKey($id, $create, $returnIdIfEmpty)->getTranslation($language);
    }
}
