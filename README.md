# Phoenix MySQL adapter

MySQL adapter for [Phoenix](https://github.com/efabrica-team/phoenix-core) database migrations. It registers the `mysql` adapter and is built on the shared PDO base in [efabrica/phoenix-pdo](https://github.com/efabrica-team/phoenix-pdo).

## Requirements
- PHP 8.4+
- `ext-pdo` and `ext-pdo_mysql`
- MySQL 5.7, 8.0, 8.4 or 9 (these versions are covered by the test suite, see `.github/workflows/phpunit.yml`). MariaDB is not tested.

## Installation
```
composer require efabrica/phoenix-core efabrica/phoenix-mysql
```
`efabrica/phoenix-pdo` is installed automatically as a dependency. The package registers the `mysql` adapter in `Phoenix\Database\Adapter\AdapterFactory` from its composer bootstrap, nothing else has to be wired up.

## Setup in a project
1. Create a configuration file `phoenix.php` (or `phoenix.yml`, `phoenix.neon`, `phoenix.json`) in the project root:
    ```php
    <?php

    return [
        'migration_dirs' => [
            'app' => __DIR__ . '/migrations',
        ],
        'environments' => [
            'local' => [
                'adapter' => 'mysql',
                'host' => '127.0.0.1',
                'port' => 3306,
                'username' => 'root',
                'password' => 'secret',
                'db_name' => 'my_app',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_general_ci',
            ],
            'production' => [
                'adapter' => 'mysql',
                'dsn' => 'mysql:dbname=my_app;host=db.internal;charset=utf8mb4',
                'username' => 'migrator',
                'password' => getenv('DB_PASSWORD'),
            ],
        ],
        'default_environment' => 'local',
        'log_table_name' => 'phoenix_log',
    ];
    ```
    `yml` and `neon` config files need `symfony/yaml` resp. `nette/neon` installed. All configuration keys are described in the [configuration docs](https://github.com/efabrica-team/phoenix-core/blob/master/docs/configuration/index.md).
2. Create and run migrations with the command runner `vendor/bin/phoenix`:
    ```
    vendor/bin/phoenix create "App\CreateUsers"
    vendor/bin/phoenix migrate
    vendor/bin/phoenix status
    vendor/bin/phoenix rollback
    ```
    Use `-e production` to pick another environment and `-c path/to/config.php` for a config file elsewhere. The log table (`log_table_name`) is created automatically on the first run, you do not have to run `init` yourself. All commands are described in the [commands docs](https://github.com/efabrica-team/phoenix-core/blob/master/docs/commands/index.md), how to write migrations in the [migrations docs](https://github.com/efabrica-team/phoenix-core/blob/master/docs/migrations/index.md).
3. The database user needs the privileges for what the migrations do: `CREATE`, `ALTER`, `DROP`, `INDEX`, `REFERENCES` plus `INSERT`, `UPDATE`, `DELETE`, `SELECT` on the log table and on the tables you fill with data. The database itself has to exist, Phoenix does not create it.

## Configuration options
| key | required | description |
|---|---|---|
| `adapter` | yes | `mysql` |
| `host`, `port` | yes (without `dsn`) | server address, port is optional (default of the driver is 3306) |
| `db_name` | yes (without `dsn`) | database name, it has to exist |
| `dsn` | no | full PDO dsn, e.g. `mysql:dbname=my_app;host=localhost;charset=utf8mb4`. When set, `host`, `port`, `db_name` and `charset` are not used for the connection (`charset` and `collation` still apply as defaults for new tables) |
| `username`, `password` | no | credentials |
| `charset` | no | default charset of created tables and columns, default `utf8mb4` |
| `collation` | no | default collation of created tables and columns, when not set the server default collation of the charset is used |
| `version` | no | server version, e.g. `8.0.36`. When not set, it is read from the server on connect. It decides which features the query builder uses (see below) |

## What the adapter supports
- tables with charset, collation, comment and `AUTO_INCREMENT` start value (`setAutoIncrement()`), renaming and copying tables (`CREATE TABLE ... LIKE`, optionally with data)
- columns with `signed` (`unsigned` when false), `null`, `default`, `charset`, `collation`, `comment`, `after` / `first` position, autoincrement, renaming and changing columns in one `alter`
- primary keys over one or more columns, including adding a primary key to an existing table
- indexes: normal, `UNIQUE` and `FULLTEXT`, methods `BTREE` and `HASH`, prefix length and `DESC` order per index column
- foreign keys with `ON DELETE` / `ON UPDATE` actions, unique constraints
- views (`view()`, `replaceView()`, `dropView()`)
- `dump` and `diff` commands read the complete structure back from `information_schema`

### Column type mapping
| Phoenix type | MySQL type | note |
|---|---|---|
| `string` | `varchar(255)` | length configurable |
| `char` | `char(255)` | |
| `tinytext`, `text`, `mediumtext`, `longtext` | the same | |
| `tinyinteger`, `smallinteger`, `mediuminteger`, `integer`, `biginteger` | `tinyint(4)`, `smallint(6)`, `mediumint(9)`, `int(11)`, `bigint(20)` | display width configurable, MySQL 8 ignores it |
| `boolean` | `tinyint(1)` | |
| `bit` | `bit(32)` | |
| `numeric`, `decimal` | `decimal(10,0)` | precision and scale configurable |
| `float`, `double` | `float(10,0)`, `double(10,0)` | |
| `date`, `time`, `datetime`, `timestamp`, `year` | the same | `DEFAULT_VALUE_CURRENT_TIMESTAMP` is supported for `datetime` and `timestamp` |
| `uuid` | `char(36)` | read back as `uuid` by dump / diff |
| `json` | `json` on MySQL 5.7.8+, `text` on older servers | decided by `version` |
| `enum`, `set` | `enum(...)`, `set(...)` | values from the `values` setting |
| `binary`, `varbinary` | `binary(255)`, `varbinary(255)` | |
| `tinyblob`, `blob`, `mediumblob`, `longblob` | the same | |
| `point`, `line`, `polygon` | `point`, `linestring`, `polygon` | |

## Limitations and things to watch
- **`timestamptz` is not a MySQL type.** It is passed through unchanged and the server rejects it, use `datetime` or `timestamp`.
- **`json` depends on the server version.** With `version` set to something below 5.7.8 (or an old server) a `json` column becomes `text`. Set `version` explicitly when the migrations are generated on one server and executed on another, so that both produce the same statements.
- **`utf8` vs `utf8mb3`.** MySQL 8.0.30+ reports the `utf8` charset as `utf8mb3`. The adapter normalizes it back to `utf8` when it loads the structure, so `dump` and `diff` do not report a bogus change. Prefer `utf8mb4` for new tables anyway.
- **Integer display width.** `int(11)` etc. is written into DDL, MySQL 8 silently drops it. When `dump` reads an integer column without width it fills the default (`11` for `int`, `20` for `bigint`, ...) to keep diffs stable.
- **Nullable `timestamp`.** A nullable `timestamp` column without default is created as `NULL DEFAULT NULL`, so MySQL does not add the implicit `DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP`. `ON UPDATE CURRENT_TIMESTAMP` itself cannot be expressed in a migration.
- **`boolean` default.** A boolean column always gets a default (`0` when none is set).
- **Index comments, partitioning, generated columns, check constraints, triggers, stored routines** are not part of the migration API. Use `$this->execute()` with raw SQL in a migration for those.
- **Foreign key checks** stay on unless the migration calls `$this->checkForeignKeysOff()` (`SET FOREIGN_KEY_CHECKS = 0`) and `$this->checkForeignKeysOn()` again, so the order of statements in a migration has to respect the constraints.
- **Migrations are not wrapped in a transaction** (MySQL DDL would commit implicitly anyway), so a failing migration leaves the already executed statements applied and is not logged as executed. Fix the state by hand or keep migrations small enough to be re-run.
- The adapter uses `PDO::ERRMODE_SILENT` and turns errors into `DatabaseQueryExecuteException` with the SQLSTATE and the failing query in the message.

## Tests
Tests need a running MySQL, connection is taken from the `PHOENIX_MYSQL_*` environment variables (see `phpunit.xml.dist`). CI runs the suite against MySQL 5.7, 8.0, 8.4 and 9 (see `.github/workflows/phpunit.yml`).
