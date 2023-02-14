<?php

namespace Depository\DataMapper\Tests;

use Depository\DataMapper\DataMapper;
use Depository\DataMapper\EntityCreatorInterface;
use Depository\DataMapper\RowCreatorInterface;
use Depository\DataMapper\ValuesResolverInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Depository\DataMapper\DataMapper
 */
class DataMapperTest extends TestCase
{
    private DataMapper $dataMapper;
    
    private RowCreatorInterface $rowCreator;
    
    private EntityCreatorInterface $entityCreator;
    
    private ValuesResolverInterface $valuesResolver;
    
    protected function setUp(): void
    {
        $this->rowCreator = $this->createMock(RowCreatorInterface::class);
        $this->rowCreator->method('createRow')
            ->willReturnCallback(function($className, $entity): array {
                $this->assertEquals(Entity::class, $className);
                $this->assertInstanceOf(Entity::class, $entity);
                
                return ['a' => $entity->getA(), 'b' => $entity->getB(), 'c' => $entity->getC()];
            });
        $this->entityCreator = $this->createMock(EntityCreatorInterface::class);
        $this->entityCreator->method('createEntity')
            ->willReturnCallback(function($className, $data, $resolved): object {
                $this->assertEquals(Entity::class, $className);
                $this->assertCount(3, $data);
                $this->assertArrayHasKey('a', $data);
                $this->assertArrayHasKey('b', $data);
                $this->assertArrayHasKey('c', $data);
                $this->assertCount(0, $resolved);
                
                return new Entity($data['a'], $data['b'], $data['c']);
            });
        $this->valuesResolver = $this->createMock(ValuesResolverInterface::class);
        $this->valuesResolver->method('createEntity')
            ->willReturnCallback(function($className, $data, $resolved): object {
                $this->assertEquals(Entity::class, $className);
                $this->assertCount(1, $resolved);
                
                return new Entity($data['a'], $data['b'], $data['c']);
            });
        $this->valuesResolver->method('resolveValues')
            ->willReturnCallback(function($className, $values): array {
                $this->assertEquals(Entity::class, $className);
                $this->assertArrayHasKey('c', $values);
                $this->assertCount(3, $values);
                
                $resolved['c'] = array_map(fn (int $value) => $value * 10, $values['c']);
                
                return $resolved;
            });
        $this->dataMapper = new DataMapper();
    }
    
    public function testCreateRow(): void
    {
        $data = $this->rowProvider()['target'];
        $entity = new Entity($data['a'], $data['b'], $data['c']);
        $row = $this->dataMapper->createRow(Entity::class, $this->rowCreator, $entity);
        
        $this->assertEquals($row, $data);
    }
    
    public function testCreateRows(): void
    {
        $data = $this->rowProvider();
        $entities = array_map(fn (array $row) => new Entity($row['a'], $row['b'], $row['c']), $data);
        $rows = $this->dataMapper->createRows(Entity::class, $this->rowCreator, $entities);
        
        $this->assertEquals($rows, $data);
    }
    
    public function testCreateEntity(): void
    {
        $row = (object) $this->rowProvider()['target'];
        $entity = $this->dataMapper->createEntity(Entity::class, $this->entityCreator, $row);
        
        $this->assertIsObject($entity);
        $this->assertInstanceOf(Entity::class, $entity);
    }
    
    public function testCreateEntities(): void
    {
        $data = $this->rowProvider();
        $entities = $this->dataMapper->createEntities(Entity::class, $this->valuesResolver, $data);
        
        $this->assertCount(3, $entities);
        $this->assertContainsOnlyInstancesOf(Entity::class, $entities);
        $this->assertArrayHasKey('target', $entities);
        $this->assertEquals($data['target']['c'] * 10, $entities['target']->getC());
    }
    
    public function rowProvider(): array
    {
        return [
            2 => ['a' => 0, 'b' => 1, 'c' => 2],
            1 => ['a' => 2, 'b' => 0, 'c' => 1],
            'target' => ['a' => 1, 'b' => 2, 'c' => 0],
        ];
    }
}
