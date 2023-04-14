<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\CoreBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Pimcore\Security\User\User as UserProxy;
use Pimcore\Controller\Controller;
use Pimcore\Extension\Bundle\PimcoreBundleManager;
use Pimcore\Logger;
use Pimcore\Model\User;
use Pimcore\Security\User\TokenStorageUserResolver;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Translation\Exception\InvalidArgumentException;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class UserAwareController extends Controller
{
    /**
     * @var TokenStorageUserResolver|TokenStorageUserResolver
     */
    protected $tokenResolver;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var PimcoreBundleManager
     */
    protected $bundleManager;

    #[Required]
    public function setTranslator(TranslatorInterface $translator): void
    {
        $this->translator = $translator;
    }

    #[Required]
    public function setBundleManager(PimcoreBundleManager $bundleManager): void
    {
        $this->bundleManager = $bundleManager;
    }

    #[Required]
    public function setTokenResolver(TokenStorageUserResolver $tokenResolver): void
    {
        $this->tokenResolver = $tokenResolver;
    }

    /**
     * @return string[]
     */
    public static function getSubscribedServices()// : array
    {
        $services = parent::getSubscribedServices();
        $services['translator'] = TranslatorInterface::class;
        $services[TokenStorageUserResolver::class] = TokenStorageUserResolver::class;
        $services[PimcoreBundleManager::class] = PimcoreBundleManager::class;
        $services['pimcore.serializer'] = '?Pimcore\\Serializer\\Serializer';

        return $services;
    }

    /**
     * Get user from user proxy object which is registered on security component
     *
     * @param bool $proxyUser Return the proxy user (UserInterface) instead of the pimcore model
     *
     * @return UserProxy|User|null
     */
    protected function getPimcoreUser($proxyUser = false)
    {
        if ($proxyUser) {
            return $this->tokenResolver->getUserProxy();
        }

        return $this->tokenResolver->getUser();
    }

    /**
     * Check user permission
     *
     * @param string $permission
     *
     * @throws AccessDeniedHttpException
     */
    protected function checkPermission($permission)
    {
        if (!$this->getPimcoreUser() || !$this->getPimcoreUser()->isAllowed($permission)) {
            Logger::error(
                'User {user} attempted to access {permission}, but has no permission to do so',
                [
                    'user' => $this->getPimcoreUser()?->getName(),
                    'permission' => $permission,
                ]
            );

            throw $this->createAccessDeniedHttpException();
        }
    }

    /**
     * @param string $message
     * @param \Throwable|null $previous
     * @param int $code
     * @param array $headers
     *
     * @return AccessDeniedHttpException
     */
    protected function createAccessDeniedHttpException(string $message = 'Access Denied.', \Throwable $previous = null, int $code = 0, array $headers = []): AccessDeniedHttpException
    {
        // $headers parameter not supported by Symfony 3.4
        return new AccessDeniedHttpException($message, $previous, $code, $headers);
    }

    /**
     * @param string[] $permissions
     */
    protected function checkPermissionsHasOneOf(array $permissions)
    {
        $allowed = false;
        $permission = null;
        foreach ($permissions as $permission) {
            if ($this->getPimcoreUser()->isAllowed($permission)) {
                $allowed = true;

                break;
            }
        }

        if (!$this->getPimcoreUser() || !$allowed) {
            Logger::error(
                'User {user} attempted to access {permission}, but has no permission to do so',
                [
                    'user' => $this->getPimcoreUser()->getName(),
                    'permission' => $permission,
                ]
            );

            throw new AccessDeniedHttpException('Attempt to access ' . $permission . ', but has no permission to do so.');
        }
    }

    /**
     * Check permission against all controller actions. Can optionally exclude a list of actions.
     *
     * @param ControllerEvent $event
     * @param string $permission
     * @param array $unrestrictedActions
     */
    protected function checkActionPermission(ControllerEvent $event, string $permission, array $unrestrictedActions = [])
    {
        $actionName = null;
        $controller = $event->getController();

        if (is_array($controller) && count($controller) === 2 && is_string($controller[1])) {
            $actionName = $controller[1];
        }

        if (null === $actionName || !in_array($actionName, $unrestrictedActions)) {
            $this->checkPermission($permission);
        }
    }

    /**
     * Returns a JsonResponse that uses the admin serializer
     *
     * @param mixed $data    The response data
     * @param int $status    The status code to use for the Response
     * @param array $headers Array of extra headers to add
     * @param array $context Context to pass to serializer when using serializer component
     * @param bool $usePimcoreSerializer
     *
     * @return JsonResponse
     *
     */
    protected function jsonResponse($data, $status = 200, $headers = [], $context = [], bool $usePimcoreSerializer = true)
    {
        $json = $this->encodeJson($data, $context, JsonResponse::DEFAULT_ENCODING_OPTIONS, $usePimcoreSerializer);

        return new JsonResponse($json, $status, $headers, true);
    }

    /**
     * Encodes data into JSON string
     *
     * @param mixed $data    The data to be encoded
     * @param array $context Context to pass to serializer when using serializer component
     * @param int $options   Options passed to json_encode
     * @param bool $usePimcoreSerializer
     *
     * @return string
     */
    protected function encodeJson($data, array $context = [], $options = JsonResponse::DEFAULT_ENCODING_OPTIONS, bool $usePimcoreSerializer = true)
    {
        /** @var SerializerInterface $serializer */
        $serializer = null;

        if ($usePimcoreSerializer) {
            $serializer = $this->container->get('pimcore.serializer');
        } else {
            $serializer = $this->container->get('serializer');
        }

        return $serializer->serialize($data, 'json', array_merge([
            'json_encode_options' => $options,
        ], $context));
    }

    /**
     * Decodes a JSON string into an array/object
     *
     * @param mixed $json       The data to be decoded
     * @param bool $associative Whether to decode into associative array or object
     * @param array $context    Context to pass to serializer when using serializer component
     * @param bool $usePimcoreSerializer
     *
     * @return mixed
     */
    protected function decodeJson($json, $associative = true, array $context = [], bool $usePimcoreSerializer = true)
    {
        /** @var SerializerInterface|DecoderInterface $serializer */
        $serializer = null;

        if ($usePimcoreSerializer) {
            $serializer = $this->container->get('pimcore.serializer');
        } else {
            $serializer = $this->container->get('serializer');
        }

        if ($associative) {
            $context['json_decode_associative'] = true;
        }

        return $serializer->decode($json, 'json', $context);
    }

    /**
     * Translates the given message.
     *
     * @param string $id The message id (may also be an object that can be cast to string)
     * @param array $parameters An array of parameters for the message
     * @param string|null $domain The domain for the message or null to use the default
     * @param string|null $locale The locale or null to use the default
     *
     * @return string The translated string
     *
     * @throws InvalidArgumentException If the locale contains invalid characters
     */
    public function trans($id, array $parameters = [], $domain = 'admin', $locale = null)
    {
        return $this->translator->trans($id, $parameters, $domain, $locale);
    }
}
