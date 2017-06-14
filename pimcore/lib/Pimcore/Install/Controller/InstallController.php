<?php

declare(strict_types=1);

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

namespace Pimcore\Install\Controller;

use Pimcore\Install\Installer;
use Pimcore\Install\Profile\Profile;
use Pimcore\Install\Profile\ProfileLocator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class InstallController extends AbstractController
{
    public function indexAction(Installer $installer, ProfileLocator $profileLocator)
    {
        $profiles = array_map(function(Profile $profile) {
            return [
                $profile->getId(),
                $profile->getName()
            ];
        }, array_values($profileLocator->getProfiles()));

        return $this->render('@install/install.html.twig', [
            'errors'   => $installer->checkPrerequisites(),
            'profiles' => $profiles
        ]);
    }

    public function installAction(Request $request, Installer $installer)
    {
        $errors = $installer->install($request->request->all());

        if (count($errors) === 0) {
            return $this->json([
                'success' => true
            ]);
        } else {
            return $this->json([
                'success' => false,
                'errors'  => $errors
            ], 400);
        }
    }
}
