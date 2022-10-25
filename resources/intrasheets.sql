CREATE SCHEMA drive AUTHORIZATION postgres;
CREATE SCHEMA sheets AUTHORIZATION postgres;

CREATE TABLE drive.formify
(
    formify_id bigserial,
    user_id bigint,
    cloud_guid uuid,
    cloud_worksheet text COLLATE pg_catalog."default",
    formify_hash character varying(36) COLLATE pg_catalog."default"
);

CREATE TABLE drive.formify_log
(
    formify_id bigint,
    ip bigint
);

CREATE TABLE drive.sheets
(
    sheet_id bigserial,
    user_id integer,
    sheet_guid uuid,
    sheet_cluster smallint,
    sheet_privacy smallint,
    sheet_description text COLLATE pg_catalog."default",
    sheet_created timestamp without time zone DEFAULT now(),
    sheet_updated timestamp without time zone DEFAULT now(),
    sheet_status smallint,
    sheet_changed smallint,
    sheet_config jsonb
) PARTITION BY RANGE (sheet_id) ;

CREATE TABLE drive.sheets_0 PARTITION OF drive.sheets FOR VALUES FROM ('0') TO ('100000');

CREATE TABLE drive.sheets_users
(
    sheet_user_id bigserial,
    sheet_guid uuid,
    sheet_id bigint,
    user_id bigint,
    sheet_user_date timestamp without time zone,
    sheet_user_email text COLLATE pg_catalog."default",
    sheet_user_token text COLLATE pg_catalog."default",
    sheet_user_level smallint,
    sheet_user_status smallint
) PARTITION BY RANGE (sheet_id) ;

CREATE TABLE drive.sheets_users_0 PARTITION OF drive.sheets_users FOR VALUES FROM ('0') TO ('100000');

CREATE TABLE drive.signature
(
    signature_id bigserial,
    user_id bigint,
    user_signature uuid
);
