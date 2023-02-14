<?php

namespace Depository\DataMapper\Tests;

class Entity
{
    public function __construct(
        public int $a,
        protected string $b,
        private mixed $c,
    ) {
    }
    
    public function getA(): int
    {
        return $this->a;
    }
    
    public function getB(): string
    {
        return $this->b;
    }
    
    public function getC(): mixed
    {
        return $this->c;
    }
}
