#!/usr/bin/env bash
# Creates Sura roles and databases on first container start (empty data volume only).
# sura_migrator owns the schema (DDL; CREATEDB for parallel test databases). sura_app gets DML only.
set -euo pipefail

psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname postgres \
  -v migrator_pw="$SURA_MIGRATOR_PASSWORD" -v app_pw="$SURA_APP_PASSWORD" <<'SQL'
CREATE ROLE sura_migrator LOGIN CREATEDB PASSWORD :'migrator_pw';
CREATE ROLE sura_app LOGIN PASSWORD :'app_pw';
CREATE DATABASE sura OWNER sura_migrator;
CREATE DATABASE sura_test OWNER sura_migrator;
SQL

for db in sura sura_test; do
  psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$db" -v db="$db" <<'SQL'
ALTER DATABASE :"db" SET timezone TO 'UTC';
REVOKE ALL ON SCHEMA public FROM PUBLIC;
ALTER SCHEMA public OWNER TO sura_migrator;
GRANT USAGE ON SCHEMA public TO sura_app;
ALTER DEFAULT PRIVILEGES FOR ROLE sura_migrator IN SCHEMA public
  GRANT SELECT, INSERT, UPDATE, DELETE ON TABLES TO sura_app;
ALTER DEFAULT PRIVILEGES FOR ROLE sura_migrator IN SCHEMA public
  GRANT USAGE, SELECT ON SEQUENCES TO sura_app;
SQL
done
