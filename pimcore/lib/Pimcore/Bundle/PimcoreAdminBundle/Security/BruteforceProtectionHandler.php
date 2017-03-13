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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\PimcoreAdminBundle\Security;

use Pimcore\Bundle\PimcoreAdminBundle\Security\Exception\BruteforceProtectionException;
use Pimcore\File;
use Pimcore\Http\RequestHelper;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\HttpFoundation\Request;

class BruteforceProtectionHandler implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var RequestHelper
     */
    protected $requestHelper;

    /**
     * @var string
     */
    protected $logFile;

    /**
     * @param RequestHelper $requestHelper
     * @param string|null $logFile
     */
    public function __construct(RequestHelper $requestHelper, $logFile = null)
    {
        $this->requestHelper = $requestHelper;

        if (null === $logFile) {
            $logFile = PIMCORE_LOG_DIRECTORY . '/loginerror.log';
        }

        $this->logFile = $logFile;
    }

    /**
     * Checks bruteforce protection for the given request
     *
     * @throws BruteforceProtectionException
     *      if access is denied
     *
     * @param string|null $username
     * @param Request|null $request
     */
    public function checkProtection($username = null, Request $request = null)
    {
        $username = $this->normalizeUsername($username);
        $ip       = $this->requestHelper->getAnonymizedClientIp($request);

        $this->logger->info('Checking bruteforce protection for user {username} with ip {ip}', [
            'username' => $username,
            'ip'       => $ip
        ]);

        $matchesIpOnly   = 0;
        $matchesUserOnly = 0;
        $matchesUserIp   = 0;

        $data = $this->getLogEntries();
        foreach ($data as $login) {
            $matchIp   = false;
            $matchUser = false;

            $time = strtotime($login[0]);
            if ($time > (time() - 300)) {
                if ($username && $login[2] === $username) {
                    $matchesUserOnly++;
                    $matchUser = true;
                }

                if ($login[1] === $ip) {
                    $matchesIpOnly++;
                    $matchIp = true;
                }

                if ($matchIp && $matchUser) {
                    $matchesUserIp++;
                }
            }
        }

        if ($matchesIpOnly > 49 || $matchesUserOnly > 9 || $matchesUserIp > 4) {
            $this->logger->warning('Security Alert: Too many login attempts for username {username} with IP {ip}', [
                'username' => $username,
                'ip'       => $ip
            ]);

            throw new BruteforceProtectionException('Security Alert: Too many login attempts, please wait 5 minutes and try again.');
        }
    }

    /**
     * Add an entry to the protection log
     *
     * @param string|null $username
     * @param Request|null $request
     */
    public function addEntry($username = null, Request $request = null)
    {
        $username = $this->normalizeUsername($username);
        $ip       = $this->requestHelper->getAnonymizedClientIp($request);

        $this->logger->warning('Adding bruteforce entry for username {username} with IP {ip}', [
            'username' => $username,
            'ip'       => $ip
        ]);

        $this->writeLogEntry($username, $ip);
    }

    /**
     * Normalize the username and make sure no invalid characters can be added to the
     * log file (e.g. inject a newline).
     *
     * @param string|null $username
     * @return string|null
     */
    protected function normalizeUsername($username = null)
    {
        if (null === $username) {
            return $username;
        }

        // TODO define/find a reusable username validation scheme
        // TODO throw exception if the username is invalid?
        $username = trim($username);
        $username = str_replace("\n", '', $username);
        $username = str_replace("\r", '', $username);
        $username = str_replace(',', '', $username);

        return $username;
    }

    /**
     * Get log entries as array
     *
     * @return array
     */
    protected function getLogEntries()
    {
        $data    = $this->readLogFile();
        $lines   = explode("\n", $data);
        $entries = [];

        if (is_array($lines) && count($lines) > 0) {
            foreach ($lines as $line) {
                $entries[] = explode(",", $line);
            }
        }

        return $entries;
    }

    /**
     * Add an entry to the log file
     *
     * @param string $username
     * @param string $ip
     */
    protected function writeLogEntry($username, $ip)
    {
        $entries   = $this->getLogEntries();
        $entries[] = [
            date(\DateTime::ISO8601),
            $ip ?: '',
            $username ?: ''
        ];

        $this->writeLogFile($entries);
    }

    /**
     * Initialize and read the log file
     *
     * @return string
     */
    protected function readLogFile()
    {
        if (!is_file($this->logFile)) {
            File::put($this->logFile, "");
        }

        if (!is_writable($this->logFile)) {
            $this->logger->critical('It seems that the log file {logfile} is not writable.', [
                'logfile' => $this->logFile
            ]);

            throw new BruteforceProtectionException('It seems that the log file is not writable.');
        }

        return file_get_contents($this->logFile);
    }

    /**
     * Write entries back to file
     *
     * @param array $entries
     */
    protected function writeLogFile(array $entries)
    {
        $lines = [];
        foreach ($entries as $item) {
            $lines[] = implode(",", $item);
        }

        // only save 2000 entries
        $maxEntries = 2000;
        if (count($lines) > $maxEntries) {
            $lines = array_splice($lines, $maxEntries * -1);
        }

        File::put($this->logFile, implode("\n", $lines));
    }
}
