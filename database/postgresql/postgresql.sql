-- multiple users per database with references


CREATE DATABASE ldn_test;

\c ldn_test;

-- table of all user inboxes, which are identified by name
CREATE TABLE l_users (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(200) UNIQUE NOT NULL
);

-- state of message in an inbox - could be unread, read, replied, deleted
CREATE TABLE lc_inbox_state (
    id BIGSERIAL PRIMARY KEY,
    sysid VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(200) UNIQUE NOT NULL
);

-- inbox table
CREATE TABLE l_inbox (
    id BIGSERIAL PRIMARY KEY,
    "user" BIGINT REFERENCES l_users(id),
    log_date TIMESTAMP WITHOUT TIME ZONE NOT NULL,
    ip VARCHAR(45) NOT NULL,
    payload JSONB NOT NULL
);

-- outbox table
CREATE TABLE l_outbox (
    id BIGSERIAL PRIMARY KEY,
    "user" BIGINT REFERENCES l_users(id),
    log_date TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
    target TEXT NOT NULL,
    payload JSONB NOT NULL
);

-- get request log
CREATE TABLE l_logs_get (
    id BIGSERIAL PRIMARY KEY,
    "user" BIGINT REFERENCES l_users(id),
    log_date TIMESTAMP WITHOUT TIME ZONE NOT NULL,
    ip VARCHAR(45) NOT NULL
);
