# Change Log

## [Unreleased]


## [1.0.0] - 2026-10-26
### Added
- Added support for PHP 8.6
- MySQL adapter (MysqlAdapter, MysqlQueryBuilder, MysqlAdapterFactory) extracted from `lulco/phoenix` into this package, the shared PDO base stays in `efabrica/phoenix-pdo`

### Fixed
- structure loaded from MySQL 8.0.30+ reports the "utf8" charset and "utf8_*" collations under their original names instead of "utf8mb3", so dump and diff are consistent across MySQL versions

[unreleased]: https://github.com/efabrica-team/phoenix-mysql/compare/1.0.0...HEAD
[1.0.0]: https://github.com/efabrica-team/phoenix-mysql/compare/7b08769a74a6fc8654639b9bd76c1a45026d09f0...1.0.0
