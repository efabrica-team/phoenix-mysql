<?php

declare(strict_types=1);

use Phoenix\Database\Adapter\AdapterFactory;
use Phoenix\Database\Adapter\MysqlAdapterFactory;

// loaded by composer (autoload.files): makes the "mysql" adapter available
AdapterFactory::registerFactory(new MysqlAdapterFactory());
