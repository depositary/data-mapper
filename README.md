# DataMapper

Objects to array converter class

## Requirements

* PHP 8.1.0 or higher

## UserMap example

```php

use Depository\DataMapper\EntityCreatorInterface;
use Depository\DataMapper\DataMapper;
use Depository\DataMapper\RowCreatorInterface;
use Depository\DataMapper\ValuesResolverInterface;

class UserMap implements EntityCreatorInterface, RowCreatorInterface
{
    public function createRow(string $className, object $entity): array
    {
        return [
            'id' => $entity->getId(),
            'email' => $entity->getEmail(),
            'password_hash' => $entity->getPasswordHash(),
            'created_at' => $this->dateTimeFactory->formatDateTime($entity->getCreatedAt()),
            'updated_at' => $this->dateTimeFactory->formatDateTime($entity->getUpdatedAt()),
        ];
    }
    
    public function createEntity(string $className, array $data, mixed $resolved): object
    {
        return new User(
            $data['id'],
            $data['email'],
            $data['password_hash'],
            $this->dateTimeFactory->createDateTime($data['created_at']),
            $this->dateTimeFactory->createDateTime($data['updated_at']),
        );
    }
}

$dataMap = new UserMap();
$dataMapper = new DataMapper();
$array = $dataMapper->createRow(UserInterface::class, $dataMap, User::createInstance());
$user = $dataMapper->createEntity(UserInterface::class, $dataMap, [
    'id' =>
    'email' => 'user@localhost',
    'passwordHash' => null,
    'created_at' => '1970-01-01 00:00:00',
    'updated_at' => '1970-01-01 00:00:00',
]);

```

## DataMap example

```php
use Depository\DataMapper\EntityCreatorInterface;
use Depository\DataMapper\RowCreatorInterface;
use Depository\DataMapper\ValuesResolverInterface;

abstract class AbstractDataMap implements EntityCreatorInterface, RowCreatorInterface
{
    private array $rowCreators;
    
    private array $entityCreators;
    
    public function addRowCreator(string $className, callable $callback): void
    {
        $this->rowCreators[$className] = $callback;
    }
    
    public function addEntityCreator(string $className, callable $callback): void
    {
        $this->entityCreators[$className] = $callback;
    }
    
    public function createRow(string $className, object $entity): array
    {
        return call_user_func($this->rowCreators[$className], $entity);
    }
    
    public function createEntity(string $className, array $data, mixed $resolved): object
    {
        return call_user_func($this->entityCreators[$className], (object) $data, (object) $resolved);
    }
}

class DataMap extends AbstractDataMap implements ValuesResolverInterface
{
    public function __construct(
        private UserRepository $userRepository,
        private DateTimeFactoryInterface $dateTimeFactory,
    ) {
        $this->addRowCreator(User::class, [$this, 'createUserRow']);
        $this->addRowCreator(Token::class, [$this, 'createTokenRow']);
        
        $this->addEntityCreator(User::class, [$this, 'createUser']);
        $this->addEntityCreator(Token::class, [$this, 'createToken']);
    }
    
    public function resolveValues(string $className, array $values): array
    {
        $resolves = [];
        
        if (isset($values['user_id'])) {
            $resolves['user'] = $this->userRepository->loadEntities(User::class, 'id', $values['user_id']);
        }
        
        return $resolves;
    }
    
    private function createUserRow(User $user): array
    {
        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'password_hash' => $user->getPasswordHash(),
            'created_at' => $this->dateTimeFactory->formatDateTime($user->getCreatedAt()),
            'updated_at' => $this->dateTimeFactory->formatDateTime($user->getUpdatedAt()),
        ];
    }
    
    private function createUser(stdClass $row): User
    {
        return new User(
            $row->id,
            $row->email,
            $row->password_hash,
            $this->dateTimeFactory->createDateTime($row->created_at),
            $this->dateTimeFactory->createDateTime($row->updated_at),
        );
    }
    
    private function createTokenRow(Token $token): array
    {
        return [
            'id' => $token->getId(),
            'user_id' => $token->getUser()->getId(),
            'token_value' => $token->getValue(),
            'expired_at' => $this->dateTimeFactory->formatDateTime($token->getExpiredAt()),
            'created_at' => $this->dateTimeFactory->formatDateTime($token->getCreatedAt()),
        ];
    }
    
    private function createToken(stdClass $row, stdClass $resolved): Token
    {
        return new Token(
            $row->id,
            $resolved->user,
            $row->token_value,
            $this->dateTimeFactory->createDateTime($row->expired_at),
            $this->dateTimeFactory->createDateTime($row->created_at),
        );
    }
}

```
