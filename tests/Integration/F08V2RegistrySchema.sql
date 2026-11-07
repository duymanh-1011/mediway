-- PROPOSAL ONLY. Do not execute until a new isolated schema and this DDL are approved.
-- This file is not a migration and is not invoked by any runner.
CREATE TABLE acceptance_runs (
    run_id CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL PRIMARY KEY,
    status VARCHAR(16) NOT NULL,
    source VARCHAR(32) NOT NULL,
    created_at DATETIME(6) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE acceptance_fixtures (
    run_id CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    entity_type VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    entity_id BIGINT UNSIGNED NOT NULL,
    source VARCHAR(32) NOT NULL,
    created_at DATETIME(6) NOT NULL,
    PRIMARY KEY (entity_type, entity_id),
    KEY acceptance_fixtures_run_index (run_id),
    CONSTRAINT acceptance_fixtures_run_fk FOREIGN KEY (run_id) REFERENCES acceptance_runs(run_id)
) ENGINE=InnoDB;
