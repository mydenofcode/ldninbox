--- MySQL, MariaDB implementation

--- VARINAT: 1
--- Multiple users in one database, tables are modified with user names
------------------------------------------------------------------------------
------------------------------------------------------------------------------

-- creation of database
CREATE DATABASE ldn_test;

-- connectiong to databse
USE ldn_test;

-- state of message in an inbox - could be unread, read, replied, deleted
CREATE TABLE lc_inbox_state(
    id BIGINT AUTO_INCREMENT,
    sysd VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(200) UNIQUE NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY (id),
    INDEX (id)
) ENGINE=INNODB;


-- inbox table
CREATE TABLE l_inbox(
    id BIGINT AUTO_INCREMENT,
    log_date DATETIME NOT NULL,
    ip VARCHAR(45) NOT NULL,
    payload JSON NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY (id),
    INDEX (id)
);

-- outbox table
CREATE TABLE l_outbox(
    id BIGINT AUTO_INCREMENT,
    log_date DATETIME NOT NULL DEFAULT now(),
    target VARCHAR(3000) NOT NULL,
    payload JSON NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY (id),
    INDEX (id)
);

-- get request log
CREATE TABLE l_logs_get(
    id BIGINT AUTO_INCREMENT,
    log_date DATETIME NOT NULL,
    ip VARCHAR(45) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY (id),
    INDEX (id)
);

-- inital INSERTS
INSERT INTO lc_inbox_state(sysid,name) VALUES('unread','Unread message');
INSERT INTO lc_inbox_state(sysid,name) VALUES('read','Read message');
INSERT INTO lc_inbox_state(sysid,name) VALUES('replied','Replied message');
INSERT INTO lc_inbox_state(sysid,name) VALUES('deleted','Deleted message');

--- VARINAT: 2
--- Multiple users in one database, tables are modified with user names
------------------------------------------------------------------------------
------------------------------------------------------------------------------

-- creation of database
CREATE DATABASE ldn_test;

-- connectiong to databse
USE ldn_test;

-- state of message in an inbox - could be unread, read, replied, deleted
CREATE TABLE lc_inbox_state(
    id BIGINT AUTO_INCREMENT,
    sysd VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(200) UNIQUE NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY (id),
    INDEX (id)
) ENGINE=INNODB;


-- inbox table
CREATE TABLE l_inbox_martin(
    id BIGINT AUTO_INCREMENT,
    log_date DATETIME NOT NULL,
    ip VARCHAR(45) NOT NULL,
    payload JSON NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY (id),
    INDEX (id)
);

-- outbox table
CREATE TABLE l_outbox_martin(
    id BIGINT AUTO_INCREMENT,
    log_date DATETIME NOT NULL DEFAULT now(),
    target VARCHAR(3000) NOT NULL,
    payload JSON NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY (id),
    INDEX (id)
);

-- get request log
CREATE TABLE l_logs_get_martin(
    id BIGINT AUTO_INCREMENT,
    log_date DATETIME NOT NULL,
    ip VARCHAR(45) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY (id),
    INDEX (id)
);

-- inital INSERTS
INSERT INTO lc_inbox_state(sysid,name) VALUES('unread','Unread message');
INSERT INTO lc_inbox_state(sysid,name) VALUES('read','Read message');
INSERT INTO lc_inbox_state(sysid,name) VALUES('replied','Replied message');
INSERT INTO lc_inbox_state(sysid,name) VALUES('deleted','Deleted message');


--- VARINAT: 3
--- Multiple users in one database, seres are referenced
------------------------------------------------------------------------------
------------------------------------------------------------------------------

-- creation of database
CREATE DATABASE ldn_test;

-- connectiong to databse
USE ldn_test;

-- we use prefix L_ for all the tables in the scheme

-- table of all user inboxes, which are identified by name
CREATE TABLE l_users(
    id BIGINT AUTO_INCREMENT,
    name VARCHAR(200) UNIQUE NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY (id),
    INDEX (id)
) ENGINE=INNODB;

-- state of message in an inbox - could be unread, read, replied, deleted
CREATE TABLE lc_inbox_state(
    id BIGINT AUTO_INCREMENT,
    sysd VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(200) UNIQUE NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY (id),
    INDEX (id)
) ENGINE=INNODB;


-- inbox table
CREATE TABLE l_inbox(
    id BIGINT AUTO_INCREMENT,
    user BIGINT REFERENCES l_users(id),
    log_date DATETIME NOT NULL,
    ip VARCHAR(45) NOT NULL,
    payload JSON NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY (id),
    INDEX (id)
);

-- outbox table
CREATE TABLE l_outbox(
    id BIGINT AUTO_INCREMENT,
    user BIGINT REFERENCES l_users(id),
    log_date DATETIME NOT NULL DEFAULT now(),
    target VARCHAR(3000) NOT NULL,
    payload JSON NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY (id),
    INDEX (id)
);

-- get request log
CREATE TABLE l_logs_get(
    id BIGINT AUTO_INCREMENT,
    user BIGINT REFERENCES l_users(id),
    log_date DATETIME NOT NULL,
    ip VARCHAR(45) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY (id),
    INDEX (id)
);

-- inital INSERTS
INSERT INTO lc_inbox_state(sysid,name) VALUES('unread','Unread message');
INSERT INTO lc_inbox_state(sysid,name) VALUES('read','Read message');
INSERT INTO lc_inbox_state(sysid,name) VALUES('replied','Replied message');
INSERT INTO lc_inbox_state(sysid,name) VALUES('deleted','Deleted message');


INSERT INTO l_users(name) VALUES ('martin');
