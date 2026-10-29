<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Helper\SymfonyExpression;

use DateTimeInterface;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\Localizedfield;
use Pimcore\Model\DataObject\Objectbrick;
use Pimcore\Model\DataObject\QuantityValue\Unit;
use Pimcore\Model\Element\ElementInterface;
use ReflectionMethod;
use Symfony\Component\ExpressionLanguage\SyntaxError;

/**
 * Decides which method calls a calculated value expression may make: only read-only getters
 * (get-, is- or has-prefixed) on the data of the object graph - never on infrastructure
 * (class definitions, DAOs, listings, services), never static methods, and never getters
 * that resolve properties/methods by name or pass their arguments into SQL.
 *
 * @internal
 */
final class CalculatedFieldMethodCallPolicy
{
    /**
     * Getter-named methods that are never allowed: they hand out infrastructure (e.g. the DBAL
     * connection), read arbitrary properties or call arbitrary getters by name, or build SQL
     * from their arguments.
     */
    private const FORBIDDEN_METHODS = [
        'getdao',
        'getresource',
        'getobjectvar',
        'getobjectvars',
        'getvalueforfieldname',
        'getvaluefromparent',
        'getrelationdata',
        'hassiblings',
    ];

    /**
     * Generic element getters allowed in addition to the getters generated for the fields of a
     * data object class.
     */
    private const ELEMENT_GETTERS = [
        'getid',
        'getkey',
        'getpath',
        'getfullpath',
        'getrealpath',
        'getrealfullpath',
        'getparentid',
        'getparent',
        'gettype',
        'getcreationdate',
        'getmodificationdate',
        'getuserowner',
        'getusermodification',
        'getproperty',
        'hasproperty',
        'getversioncount',
        'ispublished',
        'getpublished',
        'getindex',
        'getclassid',
        'getclassname',
        'getfilename',
        'getmimetype',
        'getfilesize',
        'getmetadata',
        'getwidth',
        'getheight',
        'gettitle',
        'getdescription',
        'getprettyurl',
    ];

    /**
     * Data containers and value objects on which every (non-forbidden) getter may be called.
     */
    private const VALUE_RECEIVERS = [
        Localizedfield::class,
        Objectbrick::class,
        Objectbrick\Data\AbstractData::class,
        Fieldcollection::class,
        Fieldcollection\Data\AbstractData::class,
        Classificationstore::class,
        Unit::class,
        DateTimeInterface::class,
    ];

    private const VALUE_NAMESPACE = 'Pimcore\\Model\\DataObject\\Data\\';

    public static function isAllowedMethodName(string $method): bool
    {
        if (in_array(strtolower($method), self::FORBIDDEN_METHODS, true)) {
            return false;
        }

        return 1 === preg_match('/^(get|is|has)[A-Z0-9]/', $method);
    }

    /**
     * @throws SyntaxError
     */
    public static function assertMethodCallAllowed(object $receiver, string $method): void
    {
        if (!self::isAllowedMethodCall($receiver, $method)) {
            throw new SyntaxError(sprintf(
                'Method call `%s` on `%s` is not allowed in calculated value expressions',
                $method,
                get_debug_type($receiver)
            ));
        }
    }

    private static function isAllowedMethodCall(object $receiver, string $method): bool
    {
        if (!self::isAllowedMethodName($method) || !method_exists($receiver, $method)) {
            return false;
        }

        $reflectionMethod = new ReflectionMethod($receiver, $method);
        if (!$reflectionMethod->isPublic() || $reflectionMethod->isStatic()) {
            return false;
        }

        if ($receiver instanceof ElementInterface) {
            return in_array(strtolower($method), self::ELEMENT_GETTERS, true)
                || self::isGeneratedFieldGetter($receiver, $reflectionMethod);
        }

        foreach (self::VALUE_RECEIVERS as $valueReceiver) {
            if ($receiver instanceof $valueReceiver) {
                return true;
            }
        }

        return str_starts_with(get_class($receiver), self::VALUE_NAMESPACE);
    }

    /**
     * Getters for the fields of a data object class are generated into the class
     * `Pimcore\Model\DataObject\<ClassName>`.
     */
    private static function isGeneratedFieldGetter(ElementInterface $receiver, ReflectionMethod $method): bool
    {
        if (!$receiver instanceof Concrete) {
            return false;
        }

        $generatedClass = 'Pimcore\\Model\\DataObject\\' . ucfirst((string) $receiver->getClassName());

        return strcasecmp($method->getDeclaringClass()->getName(), $generatedClass) === 0;
    }
}
