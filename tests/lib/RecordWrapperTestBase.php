<?php

namespace ntentan\nibii\tests\lib;
use ntentan\panie\Container;
use ntentan\nibii\ORMContext;
use PHPUnit\DbUnit\TestCase;

class RecordWrapperTestBase extends TestCase {
    
    protected $context;

    public function setUp() {
        parent::setUp();

        $config = [
            'driver' => getenv('NIBII_DATASTORE'),
            'host' => getenv('NIBII_HOST'),
            'user' => getenv('NIBII_USER'),
            'password' => getenv('NIBII_PASSWORD'),
            'file' => getenv('NIBII_FILE'),
            'dbname' => getenv("NIBII_DBNAME")
        ];

        /*$container = new Container();
        $container->bind(ORMContext::class)->to(ORMContext::class)->asSingleton();
        $container->bind(\ntentan\nibii\DriverAdapter::class)
            ->to(\ntentan\nibii\Resolver::getDriverAdapterClassName($config['driver']));
        $container->bind(\ntentan\atiaa\Driver::class)
            ->to(\ntentan\atiaa\DbContext::getDriverClassName($config['driver']));        
        $container->bind(\ntentan\kaikai\CacheBackendInterface::class)
            ->to(\ntentan\kaikai\backends\VolatileCache::class);
        $this->context = $container->resolve(ORMContext::class,['container' => $container, 'config' => $config]);*/
        $modelFactory = new \ntentan\nibii\factories\DefaultModelFactory();
        $driverAdapterFactory = new \ntentan\nibii\factories\DriverAdapterFactory($config['driver']);
        $modelValidatorFactory = new \ntentan\nibii\factories\DefaultValidatorFactory();
        $cache = new \ntentan\kaikai\Cache(new \ntentan\kaikai\backends\VolatileCache());
        \ntentan\atiaa\DbContext::initialize(new \ntentan\atiaa\DriverFactory($config));
        ORMContext::initialize($modelFactory, $driverAdapterFactory, $modelValidatorFactory, $cache);
    }

    protected function getConnection() {
        $pdo = new \PDO(getenv('NIBII_PDO_DSN'), getenv('NIBII_USER'), getenv('NIBII_PASSWORD'));
        return $this->createDefaultDBConnection($pdo, getenv('NIBII_DBNAME'));
    }

    protected function getDataSet() {
        return $this->createArrayDataSet(array_merge($this->getExtraArrayData(), [
            'roles' => [
                ['id' => 10, 'name' => 'Some test user'],
                ['id' => 11, 'name' => 'Matches'],
                ['id' => 12, 'name' => 'Rematch'],
                ['id' => 13, 'name' => 'Test role'],
                ['id' => 14, 'name' => 'Another test role'],
                ['id' => 15, 'name' => 'More test roles']
            ],
            'users' => [
                ['id' => 1, 'username' => 'james', 'role_id' => 10, 'firstname' => 'James', 'lastname' => 'Ainooson', 'status' => 1, 'password' => 'somehashedstring', 'email' => 'james@nibii.test'],
                ['id' => 2, 'username' => 'fiifi', 'role_id' => 11, 'firstname' => 'Fiifi', 'lastname' => 'Antobra', 'status' => 2, 'password' => md5('password'), 'email' => 'fiifi@nibii.test'],
                ['id' => 3, 'username' => 'kwame', 'role_id' => 12, 'firstname' => 'Kwame', 'lastname' => 'Nyarko', 'status' => 2, 'password' => 'coolthings', 'email' => 'knyarko@nibii.test'],
                ['id' => 4, 'username' => 'adjoa', 'role_id' => 12, 'firstname' => 'Adjoa', 'lastname' => 'Boateng', 'status' => 2, 'password' => 'hahaha', 'email' => 'aboateng@nibii.test']
            ]
        ]));
    }

    protected function getSetUpOperation() {
        return $this->getOperations()->CLEAN_INSERT(TRUE);
    }

    protected function getExtraArrayData() {
        return [];
    }

}
