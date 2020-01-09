<?php

namespace Pimcore\Model\Webservice;

/**
 * @deprecated
 */
interface IdMapperInterface
{
    /**
     * @param string $type
     * @param int $id
     *
     * @return int
     */
    public function getMappedId(string $type, int $id): int;

    /**
     * @param string $sourceType
     * @param int $sourceId
     * @param string $destinationType
     * @param int $destinationId
     */
    public function recordMappingFailure(string $sourceType, int $sourceId, string $destinationType, int $destinationId): void;

    /**
     * @return bool
     */
    public function ignoreMappingFailures(): bool;
}
