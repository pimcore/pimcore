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

namespace Pimcore\Bundle\AdminBundle\Controller;

use Pimcore\Bundle\AdminBundle\HttpFoundation\JsonResponse;
use Pimcore\Bundle\AdminBundle\Security\User\User as UserProxy;
use Pimcore\Controller\Traits\JsonHelperTrait;
use Pimcore\Controller\UserAwareController;
use Pimcore\Extension\Bundle\PimcoreBundleManager;
use Pimcore\Model\User;
use Pimcore\Bundle\AdminBundle\Security\User\TokenStorageUserResolver;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Translation\Exception\InvalidArgumentException;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @deprecated and will be removed in Pimcore 11. Use Pimcore\Controller\UserAwareController instead.
 */
abstract class AdminController extends UserAwareController implements AdminControllerInterface
{
    use JsonHelperTrait;

    /**
     * @var TokenStorageUserResolver
     */
    protected $tokenResolver;

    /**
     * @deprecated and will be removed in Pimcore 11.
     *
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @deprecated and will be removed in Pimcore 11.
     *
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

    /**
     * @return string[]
     */
    public static function getSubscribedServices()
    {
        $services = parent::getSubscribedServices();

        $services['pimcore_admin.serializer'] = '?Pimcore\\Admin\\Serializer';

        return $services;
    }


    #[Required]
    public function setTokenStorageUserResolver(TokenStorageUserResolver $tokenResolver): void
    {
        $this->tokenResolver = $tokenResolver;
    }


    /**
     * {@inheritdoc}
     */
    public function needsSessionDoubleAuthenticationCheck()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function needsStorageDoubleAuthenticationCheck()
    {
        return true;
    }

    /**
     * @deprecated
     *
     * @return TranslatorInterface
     */
    public function getTranslator()
    {
        trigger_deprecation(
            'pimcore/pimcore',
            '10.6',
            sprintf('%s is deprecated, please use $this->translator instead. Will be removed in Pimcore 11', __METHOD__)
        );

        return $this->translator;
    }

    /**
     * @deprecated
     *
     * @return PimcoreBundleManager
     */
    public function getBundleManager()
    {
        trigger_deprecation(
            'pimcore/pimcore',
            '10.6',
            sprintf('%s is deprecated, please use $this->bundleManager instead. Will be removed in Pimcore 11', __METHOD__)
        );

        return $this->bundleManager;
    }

    /**
     * @deprecated
     *
     * @return TokenStorageUserResolver
     */
    public function getTokenResolver()
    {
        trigger_deprecation(
            'pimcore/pimcore',
            '10.6',
            sprintf('%s is deprecated, please use $this->tokenResolver instead. Will be removed in Pimcore 11', __METHOD__)
        );

        return $this->tokenResolver;
    }

    /**
     * Get user from user proxy object which is registered on security component
     *
     * @param bool $proxyUser Return the proxy user (UserInterface) instead of the pimcore model
     *
     * @return UserProxy|User|null
     *
     * @deprecated and will be removed in Pimcore 11. Use Pimcore\Controller\UserAwareController::getPimcoreUser() instead.
     */
    protected function getAdminUser($proxyUser = false)
    {
        if ($proxyUser) {
            return $this->tokenResolver->getUserProxy();
        }

        return $this->tokenResolver->getUser();
    }

    /**
     * Returns a JsonResponse that uses the admin serializer
     *
     * @param mixed $data    The response data
     * @param int $status    The status code to use for the Response
     * @param array $headers Array of extra headers to add
     * @param array $context Context to pass to serializer when using serializer component
     * @param bool $useAdminSerializer
     *
     * @return JsonResponse
     *
     * @deprecated and will be removed in Pimcore 11. Use Pimcore\Controller\Traits\JsonHelperTrait::jsonResponse() instead.
     */
    protected function adminJson($data, $status = 200, $headers = [], $context = [], bool $useAdminSerializer = true)
    {
        $json = $this->encodeJson($data, $context, JsonResponse::DEFAULT_ENCODING_OPTIONS, $useAdminSerializer);

        return new JsonResponse($json, $status, $headers, true);
    }

    /**
     * Encodes data into JSON string
     *
     * @param mixed $data    The data to be encoded
     * @param array $context Context to pass to serializer when using serializer component
     * @param int $options   Options passed to json_encode
     * @param bool $useAdminSerializer
     *
     * @deprecated and will be removed in Pimcore 11. Use Pimcore\Controller\Traits\JsonHelperTrait::encodeJson() instead.
     *
     * @return string
     */
    protected function encodeJson($data, array $context = [], $options = JsonResponse::DEFAULT_ENCODING_OPTIONS, bool $useAdminSerializer = true)
    {
        /** @var SerializerInterface $serializer */
        $serializer = null;

        if ($useAdminSerializer) {
            $serializer = $this->container->get('pimcore_admin.serializer');
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
     * @param bool $useAdminSerializer
     *
     * @deprecated and will be removed in Pimcore 11. Use Pimcore\Controller\Traits\JsonHelperTrait::decodeJson() instead.
     *
     * @return mixed
     */
    protected function decodeJson($json, $associative = true, array $context = [], bool $useAdminSerializer = true)
    {
        /** @var SerializerInterface|DecoderInterface $serializer */
        $serializer = null;

        if ($useAdminSerializer) {
            $serializer = $this->container->get('pimcore_admin.serializer');
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
     *
     * @deprecated and will be removed in Pimcore 11. Use DI injection of translator instead.
     */
    public function trans($id, array $parameters = [], $domain = 'admin', $locale = null)
    {
        return $this->translator->trans($id, $parameters, $domain, $locale);
    }
}
