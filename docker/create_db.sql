-- Runs once on first container init (empty datadir).
CREATE DATABASE library_test;

DO $$
BEGIN
  IF NOT EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'library_app') THEN
    CREATE ROLE library_app LOGIN PASSWORD 'library_app';
  END IF;
END$$;
