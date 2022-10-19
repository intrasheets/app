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
