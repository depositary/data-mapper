<?php

namespace Depository\DataMapper;

interface RowCreatorInterface extends DataMapInterface
{
    public function createRow(string $className, object $entity): array;
}
