# Change Log

## [Unreleased]
### Added
- Added support for PHP 8.6
- MySQL adapter (MysqlAdapter, MysqlQueryBuilder, MysqlAdapterFactory) extracted from `lulco/phoenix` into this package, the shared PDO base stays in `efabrica/phoenix-pdo`

### Fixed
- structure loaded from MySQL 8.0.30+ reports the "utf8" charset and "utf8_*" collations under their original names instead of "utf8mb3", so dump and diff are consistent across MySQL versions
