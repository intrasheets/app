CREATE TABLE public.permissions
(
    permission_id bigserial,
    global_time smallint,
    global_user smallint,
    permission_name character varying(255) COLLATE pg_catalog."default",
    permission_description character varying(255) COLLATE pg_catalog."default",
    permission_category character varying(255) COLLATE pg_catalog."default",
    permission_order smallint,
    permission_routes text COLLATE pg_catalog."default",
    permission_status smallint,
    CONSTRAINT permissions_pkey PRIMARY KEY (permission_id)
);

CREATE TABLE public.users
(
    user_id bigserial,
    parent_id integer,
    permission_id integer,
    google_id text,
    facebook_id text,
    user_email character varying(255) COLLATE pg_catalog."default",
    user_name character varying(255) COLLATE pg_catalog."default",
    user_locale character(5) COLLATE pg_catalog."default",
    user_login character varying(128) COLLATE pg_catalog."default",
    user_password character varying(256) COLLATE pg_catalog."default",
    user_salt character varying(128) COLLATE pg_catalog."default",
    user_hash character varying(128) COLLATE pg_catalog."default",
    user_recovery smallint,
    user_recovery_date timestamp without time zone,
    user_inserted timestamp without time zone,
    user_updated timestamp without time zone,
    user_json text COLLATE pg_catalog."default",
    user_status smallint,
    user_signature text COLLATE pg_catalog."default",
    CONSTRAINT users_pkey PRIMARY KEY (user_id),
    CONSTRAINT users_user_email_key UNIQUE (user_email),
    CONSTRAINT users_user_login_key UNIQUE (user_login)
);

CREATE TABLE public.users_access
(
    user_access_id bigserial,
    user_id integer,
    access_browser character varying(255) COLLATE pg_catalog."default",
    access_message text COLLATE pg_catalog."default",
    access_date timestamp without time zone,
    access_json text COLLATE pg_catalog."default",
    access_status smallint,
    CONSTRAINT users_access_pkey PRIMARY KEY (user_access_id)
);

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
)