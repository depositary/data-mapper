<?php

namespace Depository\DataMapper;

interface ValuesResolverInterface extends EntityCreatorInterface
{
    public function resolveValues(string $className, array $values): array;
}
