<?php
declare(strict_types=1);

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

namespace Pimcore\Bundle\CoreBundle\Request\ParamResolver;

use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Request\Attribute\DataObjectParam;
use Pimcore\Tool;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @internal
 */
class DataObjectParamResolver implements ValueResolverInterface
{
    /**
     *
     *
     * @throws NotFoundHttpException When invalid data object ID given
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $options = $argument->getAttributes(DataObjectParam::class, ArgumentMetadata::IS_INSTANCEOF);

        $class = $options[0]->class ?? $argument->getType();
        if (null === $class || !is_subclass_of($class, AbstractObject::class)) {
            return [];
        }

        $param = $argument->getName();
        if (!$request->attributes->has($param)) {
            return [];
        }

        $value = $request->attributes->get($param);

        if (!$value && $argument->isNullable()) {
            $request->attributes->set($param, null);

            return [null];
        }

        /** @var Concrete|null $object */
        $object = $value instanceof AbstractObject ? $value : $class::getById($value);
        if (!$object) {
            throw new NotFoundHttpException(sprintf('Invalid data object ID given for parameter "%s".', $param));
        } elseif (
            !$object->isPublished()
            && !Tool::isElementRequestByAdmin($request, $object)
            && (!isset($options[0]) || !$options[0]->unpublished)
        ) {
            throw new NotFoundHttpException(sprintf('Data object for parameter "%s" is not published.', $param));
        }

        $request->attributes->set($param, $object);

        return [$object];
    }
}
