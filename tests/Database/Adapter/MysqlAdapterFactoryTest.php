<?php

declare(strict_types=1);

namespace Phoenix\Tests\Database\Adapter;

use Phoenix\Config\EnvironmentConfig;
use Phoenix\Database\Adapter\AdapterFactory;
use Phoenix\Database\Adapter\MysqlAdapter;
use Phoenix\Database\Adapter\MysqlAdapterFactory;
use Phoenix\Database\QueryBuilder\MysqlQueryBuilder;
use Phoenix\Exception\InvalidArgumentValueException;
use PHPUnit\Framework\TestCase;

final class MysqlAdapterFactoryTest extends TestCase
{
    public function testIsRegisteredByBootstrap(): void
    {
        $this->assertContains('mysql', AdapterFactory::getAdapterNames());
    }

    public function testAdapterName(): void
    {
        $this->assertSame('mysql', (new MysqlAdapterFactory())->getAdapterName());
    }

    public function testCreate(): void
    {
        $config = new EnvironmentConfig([
            'adapter' => 'mysql',
            'dsn' => 'sqlite::memory:',
        ]);
        $adapter = AdapterFactory::instance($config);
        $this->assertInstanceOf(MysqlAdapter::class, $adapter);
        $this->assertInstanceOf(MysqlQueryBuilder::class, $adapter->getQueryBuilder());
        $this->assertSame('utf8mb4', $adapter->getCharset());
    }

    public function testUnknown(): void
    {
        $this->expectException(InvalidArgumentValueException::class);
        $this->expectExceptionMessage('Unknown adapter "unknown".');
        AdapterFactory::instance(new EnvironmentConfig(['adapter' => 'unknown', 'dsn' => 'sqlite::memory:']));
    }
}
