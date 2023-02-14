<?php

namespace Depository\DataMapper;

class DataMapper
{
    public function __construct()
    {
    }
    
    public function createRow(string $className, RowCreatorInterface $dataMap, object $object): mixed
    {
        return $this->createRows($className, $dataMap, [$object])[0];
    }
    
    public function createEntity(string $className, EntityCreatorInterface $dataMap, mixed $row): object
    {
        return $this->createEntities($className, $dataMap, [$row])[0];
    }
    
    public function createRows(string $className, RowCreatorInterface $dataMap, iterable $entities): array
    {
        $rows = [];
        
        foreach ($entities as $index => $entity) {
            $rows[$index] = $dataMap->createRow($className, $entity);
        }
        
        return $rows;
    }
    
    /**
     * @return object[]
     */
    public function createEntities(string $className, EntityCreatorInterface $dataMap, iterable $rows): array
    {
        $entities = [];
        $resolvedValues = [];
        $values = $this->createValues($rows);
        
        if ($dataMap instanceof ValuesResolverInterface) {
            $resolvedValues = $dataMap->resolveValues($className, $values);
        }
        
        foreach ($rows as $index => $row) {
            $data = [];
            $resolved = [];
            
            foreach ($values as $attribute => $array) {
                $data[$attribute] = $array[$index];
                
                if (isset($resolvedValues[$attribute])) {
                    $resolved[$attribute] = $resolvedValues[$attribute][$index];
                }
            }
            
            $entities[$index] = $dataMap->createEntity($className, $data, $resolved);
        }
        
        return $entities;
    }
    
    private function createValues(iterable $rows): array
    {
        $values = [];
        
        foreach ($rows as $index => $row) {
            $attributes = is_iterable($row) ? $row : get_object_vars($row);
        
            foreach ($attributes as $name => $value) {
                $values[$name][$index] = $value;
            }
        }
        
        return $values;
    }
}
