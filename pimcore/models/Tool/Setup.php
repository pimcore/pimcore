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
 * @package    Tool
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Tool;

use Pimcore\File;
use Pimcore\Install\SystemConfig\ConfigWriter;
use Pimcore\Model;

/**
 * @method \Pimcore\Model\Tool\Setup\Dao getDao()
 * @method void database()
 * @method void setDbConnection($db)
 * @method void insertDump($dbDataFile)
 */
class Setup extends Model\AbstractModel
{
    /**
     * @param array $config
     *
     * @deprecated use ConfigWriter instead
     */
    public function config($config = [])
    {
        $writer = new ConfigWriter();
        $writer->writeSystemConfig($config);
        $writer->writeDebugModeConfig();
        $writer->generateParametersFile();
    }

    /**
     * @param array $config
     */
    public function contents($config = [])
    {
        $this->getDao()->contents();
        $this->createOrUpdateUser($config);
    }

    /**
     * @param array $config
     */
    public function createOrUpdateUser($config = [])
    {
        $defaultConfig = [
            'username' => 'admin',
            'password' => md5(microtime())
        ];

        $settings = array_replace_recursive($defaultConfig, $config);

        if ($user = Model\User::getByName($settings['username'])) {
            $user->delete();
        }

        $user = Model\User::create([
            'parentId' => 0,
            'username' => $settings['username'],
            'password' => \Pimcore\Tool\Authentication::getPasswordHash($settings['username'], $settings['password']),
            'active' => true
        ]);
        $user->setAdmin(true);
        $user->save();
    }
}
