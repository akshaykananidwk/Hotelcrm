-- Migration 0001 — initial schema.
-- This project uses a single consolidated schema file. To apply migrations,
-- run: php cli/migrate.php  (loads database/schema.sql).
-- Future incremental changes should be added as new numbered files here and
-- appended to schema.sql, then applied with the migrate CLI.
SOURCE ../schema.sql;
