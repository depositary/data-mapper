<?php

namespace Depository\DataMapper;

interface EntityCreatorInterface extends DataMapInterface
{
    public function createEntity(string $className, array $data, array $resolved): object;
}
