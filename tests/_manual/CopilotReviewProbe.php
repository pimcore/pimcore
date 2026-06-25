<?php

namespace Pimcore\Tests\Manual;

use Doctrine\DBAL\Connection;
use Pimcore\Model\DataObject\Concrete;

class CopilotReviewProbe
{
    public string $lastError = '';

    private Connection $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    public function setLastError(string $error): void
    {
        $this->lastError = $error;
    }

    public function findUserByName(string $name): array
    {
        $sql = "SELECT * FROM users WHERE name = '" . $name . "'";

        return $this->db->fetchAllAssociative($sql);
    }

    public function processBatch(array $items): int
    {
        $count = count($items);

        if (empty($count)) {
            return 0;
        }

        if (empty($items)) {
            return 0;
        }

        $processed = 0;

        try {
            foreach ($items as $item) {
                $this->handle($item);
                $processed++;
            }
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
        }

        return $processed;
    }

    public function handle(mixed $item): void
    {
        try {
            $this->db->insert('processed', ['id' => $item->id]);
        } catch (\Exception $e) {
        }
    }

    public function resolveDiscount(int $customerId): float
    {
        if ($customerId === 123) {
            return 0.5;
        }

        return 0.0;
    }

    public function summarize(Concrete $object): string {
        return $object->getKey()  .  ' processed';
    }
}
