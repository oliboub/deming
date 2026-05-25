CREATE TABLE IF NOT EXISTS "migrations"(
  "id" integer primary key autoincrement not null,
  "migration" varchar not null,
  "batch" integer not null
);
CREATE TABLE IF NOT EXISTS "password_resets"(
  "email" varchar not null,
  "token" varchar not null,
  "created_at" datetime
);
CREATE INDEX "password_resets_email_index" on "password_resets"("email");
CREATE TABLE IF NOT EXISTS "oauth_auth_codes"(
  "id" varchar not null,
  "user_id" integer not null,
  "client_id" integer not null,
  "scopes" text,
  "revoked" tinyint(1) not null,
  "expires_at" datetime,
  primary key("id")
);
CREATE INDEX "oauth_auth_codes_user_id_index" on "oauth_auth_codes"("user_id");
CREATE TABLE IF NOT EXISTS "oauth_access_tokens"(
  "id" varchar not null,
  "user_id" integer,
  "client_id" integer not null,
  "name" varchar,
  "scopes" text,
  "revoked" tinyint(1) not null,
  "created_at" datetime,
  "updated_at" datetime,
  "expires_at" datetime,
  primary key("id")
);
CREATE INDEX "oauth_access_tokens_user_id_index" on "oauth_access_tokens"(
  "user_id"
);
CREATE TABLE IF NOT EXISTS "oauth_refresh_tokens"(
  "id" varchar not null,
  "access_token_id" varchar not null,
  "revoked" tinyint(1) not null,
  "expires_at" datetime,
  primary key("id")
);
CREATE INDEX "oauth_refresh_tokens_access_token_id_index" on "oauth_refresh_tokens"(
  "access_token_id"
);
CREATE TABLE IF NOT EXISTS "oauth_clients"(
  "id" integer primary key autoincrement not null,
  "user_id" integer,
  "name" varchar not null,
  "secret" varchar,
  "provider" varchar,
  "redirect" text not null,
  "personal_access_client" tinyint(1) not null,
  "password_client" tinyint(1) not null,
  "revoked" tinyint(1) not null,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE INDEX "oauth_clients_user_id_index" on "oauth_clients"("user_id");
CREATE TABLE IF NOT EXISTS "oauth_personal_access_clients"(
  "id" integer primary key autoincrement not null,
  "client_id" integer not null,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE TABLE IF NOT EXISTS "domains"(
  "id" integer primary key autoincrement not null,
  "title" varchar not null,
  "description" varchar not null,
  "created_at" datetime,
  "updated_at" datetime,
  "framework" varchar
);
CREATE TABLE IF NOT EXISTS "personal_access_tokens"(
  "id" integer primary key autoincrement not null,
  "tokenable_type" varchar not null,
  "tokenable_id" integer not null,
  "name" varchar not null,
  "token" varchar not null,
  "abilities" text,
  "last_used_at" datetime,
  "expires_at" datetime,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE INDEX "personal_access_tokens_tokenable_type_tokenable_id_index" on "personal_access_tokens"(
  "tokenable_type",
  "tokenable_id"
);
CREATE UNIQUE INDEX "personal_access_tokens_token_unique" on "personal_access_tokens"(
  "token"
);
CREATE TABLE IF NOT EXISTS "documents"(
  "id" integer primary key autoincrement not null,
  "measure_id" integer not null,
  "filename" varchar not null,
  "mimetype" varchar not null,
  "size" integer not null,
  "hash" varchar not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("measure_id") references "measures"("id")
);
CREATE TABLE IF NOT EXISTS "control_user"(
  "measure_id" integer not null,
  "user_id" integer not null,
  foreign key("measure_id") references "measures"("id") on delete CASCADE on update NO ACTION,
  foreign key("user_id") references "users"("id") on delete CASCADE on update NO ACTION
);
CREATE INDEX "control_id_fk_5920381" on "control_user"("measure_id");
CREATE INDEX "user_id_fk_5837573" on "control_user"("user_id");
CREATE TABLE IF NOT EXISTS "users"(
  "id" integer primary key autoincrement not null,
  "login" varchar not null,
  "name" varchar not null,
  "email" varchar not null,
  "title" varchar not null,
  "role" integer not null,
  "profile_image" integer,
  "email_verified_at" datetime,
  "password" varchar not null,
  "remember_token" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  "language" varchar
);
CREATE UNIQUE INDEX "users_login_unique" on "users"("login");
CREATE UNIQUE INDEX "users_email_unique" on "users"("email");
CREATE TABLE IF NOT EXISTS "attributes"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "values" varchar,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "tags_name_unique" on "attributes"("name");
CREATE TABLE IF NOT EXISTS "control_measure"(
  "control_id" integer not null,
  "measure_id" integer not null,
  foreign key("control_id") references "controls"("id"),
  foreign key("measure_id") references "measures"("id")
);
CREATE TABLE IF NOT EXISTS "controls"(
  "id" integer primary key autoincrement not null,
  "domain_id" integer not null,
  "name" varchar not null,
  "clause" varchar not null,
  "objective" text,
  "input" text,
  "model" text,
  "indicator" text,
  "action_plan" text,
  "created_at" datetime,
  "updated_at" datetime,
  "standard" varchar,
  "attributes" varchar,
  foreign key("domain_id") references domains("id") on delete no action on update no action
);
CREATE UNIQUE INDEX "measures_clause_unique" on "controls"("clause");
CREATE TABLE IF NOT EXISTS "oauth_device_codes"(
  "id" varchar not null,
  "user_id" integer,
  "client_id" varchar not null,
  "user_code" varchar not null,
  "scopes" text not null,
  "revoked" tinyint(1) not null,
  "user_approved_at" datetime,
  "last_polled_at" datetime,
  "expires_at" datetime,
  primary key("id")
);
CREATE INDEX "oauth_device_codes_user_id_index" on "oauth_device_codes"(
  "user_id"
);
CREATE INDEX "oauth_device_codes_client_id_index" on "oauth_device_codes"(
  "client_id"
);
CREATE UNIQUE INDEX "oauth_device_codes_user_code_unique" on "oauth_device_codes"(
  "user_code"
);
CREATE TABLE IF NOT EXISTS "action_user"(
  "action_id" integer not null,
  "user_id" integer not null,
  foreign key("action_id") references "actions"("id"),
  foreign key("user_id") references "users"("id")
);
CREATE TABLE IF NOT EXISTS "action_measure"(
  "action_id" integer not null,
  "control_id" integer not null,
  foreign key("action_id") references "actions"("id"),
  foreign key("control_id") references "controls"("id")
);
CREATE TABLE IF NOT EXISTS "audit_logs"(
  "id" integer primary key autoincrement not null,
  "description" text not null,
  "subject_id" integer,
  "subject_type" varchar,
  "user_id" integer,
  "properties" text,
  "host" varchar,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE TABLE IF NOT EXISTS "user_groups"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "description" text,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE TABLE IF NOT EXISTS "user_user_group"(
  "user_id" integer not null,
  "user_group_id" integer not null,
  foreign key("user_id") references "users"("id"),
  foreign key("user_group_id") references "user_groups"("id")
);
CREATE TABLE IF NOT EXISTS "control_user_group"(
  "measure_id" integer not null,
  "user_group_id" integer not null,
  foreign key("measure_id") references "measures"("id"),
  foreign key("user_group_id") references "user_groups"("id")
);
CREATE TABLE IF NOT EXISTS "actions"(
  "id" integer primary key autoincrement not null,
  "reference" varchar,
  "type" integer,
  "criticity" integer not null default('0'),
  "status" integer not null default('0'),
  "scope" varchar,
  "name" varchar,
  "cause" text,
  "remediation" text,
  "measure_id" integer,
  "creation_date" date,
  "due_date" date,
  "close_date" date,
  "justification" text,
  "created_at" datetime,
  "updated_at" datetime,
  "progress" integer,
  foreign key("measure_id") references "measures"("id") on delete no action on update no action
);
CREATE TABLE IF NOT EXISTS "risks"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "description" text,
  "owner_id" integer,
  "probability" integer not null default '1',
  "probability_comment" text,
  "impact" integer not null default '1',
  "impact_comment" text,
  "status" varchar check("status" in('not_evaluated', 'not_accepted', 'temporarily_accepted', 'accepted', 'mitigated', 'transferred', 'avoided')) not null default 'not_evaluated',
  "status_comment" text,
  "review_frequency" integer not null default '12',
  "next_review_at" date,
  "exposure" integer,
  "vulnerability" integer,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime
);
CREATE INDEX "risks_status_index" on "risks"("status");
CREATE INDEX "risks_owner_id_index" on "risks"("owner_id");
CREATE INDEX "risks_next_review_at_index" on "risks"("next_review_at");
CREATE TABLE IF NOT EXISTS "measure_risk"(
  "risk_id" integer not null,
  "measure_id" integer not null,
  primary key("risk_id", "measure_id")
);
CREATE TABLE IF NOT EXISTS "action_risk"(
  "risk_id" integer not null,
  "action_id" integer not null,
  primary key("risk_id", "action_id")
);
CREATE TABLE IF NOT EXISTS "risk_scoring_configs"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "formula" varchar not null,
  "is_active" tinyint(1) not null default '0',
  "probability_levels" text not null,
  "impact_levels" text not null,
  "exposure_levels" text,
  "vulnerability_levels" text,
  "risk_thresholds" text not null,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE INDEX "risk_scoring_configs_is_active_index" on "risk_scoring_configs"(
  "is_active"
);
CREATE TABLE IF NOT EXISTS "measures"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "objective" text,
  "input" text,
  "model" text,
  "indicator" text,
  "action_plan" text,
  "periodicity" integer,
  "plan_date" date not null,
  "realisation_date" date,
  "observations" text,
  "score" integer,
  "note" numeric,
  "created_at" datetime,
  "updated_at" datetime,
  "next_id" integer,
  "standard" varchar,
  "attributes" varchar,
  "scope" varchar,
  "status" integer not null default('0'),
  foreign key("next_id") references "measures"("id") on delete no action on update no action
);
CREATE TABLE IF NOT EXISTS "exceptions"(
  "id" integer primary key autoincrement not null,
  "control_id" integer,
  "name" varchar not null,
  "description" text,
  "justification" text,
  "compensating_controls" text,
  "start_date" date,
  "end_date" date,
  "status" integer not null default '0',
  "created_by" integer,
  "submitted_by" integer,
  "submitted_at" datetime,
  "approved_by" integer,
  "approved_at" datetime,
  "approval_comment" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("control_id") references "controls"("id") on delete set null,
  foreign key("created_by") references "users"("id") on delete set null,
  foreign key("submitted_by") references "users"("id") on delete set null,
  foreign key("approved_by") references "users"("id") on delete set null
);

INSERT INTO migrations VALUES(1,'2014_10_12_000000_create_users_table',1);
INSERT INTO migrations VALUES(2,'2014_10_12_100000_create_password_resets_table',1);
INSERT INTO migrations VALUES(3,'2016_06_01_000001_create_oauth_auth_codes_table',1);
INSERT INTO migrations VALUES(4,'2016_06_01_000002_create_oauth_access_tokens_table',1);
INSERT INTO migrations VALUES(5,'2016_06_01_000003_create_oauth_refresh_tokens_table',1);
INSERT INTO migrations VALUES(6,'2016_06_01_000004_create_oauth_clients_table',1);
INSERT INTO migrations VALUES(7,'2016_06_01_000005_create_oauth_personal_access_clients_table',1);
INSERT INTO migrations VALUES(8,'2019_07_28_175941_create_domains_table',1);
INSERT INTO migrations VALUES(9,'2019_08_09_084322_create_measures_table',1);
INSERT INTO migrations VALUES(10,'2019_08_09_105245_create_controls_table',1);
INSERT INTO migrations VALUES(11,'2019_12_14_000001_create_personal_access_tokens_table',1);
INSERT INTO migrations VALUES(12,'2020_04_12_073028_create_documents_table',1);
INSERT INTO migrations VALUES(13,'2022_04_23_081110_add_next_control_id',1);
INSERT INTO migrations VALUES(14,'2022_05_15_030940_control_score_to_int',1);
INSERT INTO migrations VALUES(15,'2022_12_21_113730_add_user_language',1);
INSERT INTO migrations VALUES(16,'2023_01_29_114100_add_tags',1);
INSERT INTO migrations VALUES(17,'2023_01_30_180336_normalization',1);
INSERT INTO migrations VALUES(18,'2023_03_09_222639_alter_attributes_values',1);
INSERT INTO migrations VALUES(19,'2023_04_06_202034_alter_attribute_length',1);
INSERT INTO migrations VALUES(20,'2023_04_19_112145_change_clause_type',1);
INSERT INTO migrations VALUES(21,'2023_06_18_170340_owner',1);
INSERT INTO migrations VALUES(22,'2023_08_22_095642_add_scope',1);
INSERT INTO migrations VALUES(23,'2024_04_15_193546_attributes_values_text',1);
INSERT INTO migrations VALUES(24,'2024_04_20_192325_add_control_status',1);
INSERT INTO migrations VALUES(25,'2024_06_27_123923_add_control_measure_table',1);
INSERT INTO migrations VALUES(26,'2024_07_02_101657_add_framework_to_domains',1);
INSERT INTO migrations VALUES(27,'2024_07_05_174735_clause_unique',1);
INSERT INTO migrations VALUES(28,'2024_10_01_181052_remove_clause',1);
INSERT INTO migrations VALUES(29,'2024_06_01_000001_create_oauth_device_codes_table',2);
INSERT INTO migrations VALUES(30,'2024_11_06_123808_add_actions',2);
INSERT INTO migrations VALUES(31,'2025_02_04_064646_create_audit_logs_table',2);
INSERT INTO migrations VALUES(32,'2025_02_05_121035_cleanup',2);
INSERT INTO migrations VALUES(33,'2025_04_29_123908_add_user_group',2);
INSERT INTO migrations VALUES(34,'2025_05_27_152856_change_actions',2);
INSERT INTO migrations VALUES(35,'2025_07_31_090259_alter_note_on_controls_table',2);
INSERT INTO migrations VALUES(36,'2026_04_07_151247_create_risk_table',2);
INSERT INTO migrations VALUES(37,'2026_04_07_152854_create_risk_scoring_table',2);
INSERT INTO migrations VALUES(38,'2026_04_16_081633_change_note_precision_in_controls_table',2);
INSERT INTO migrations VALUES(39,'2026_04_23_160957_create_exceptions_table',2);
INSERT INTO migrations VALUES(40,'2026_05_21_000001_swap_measures_controls_tables',2);
INSERT INTO migrations VALUES(41,'2026_05_21_000002_fix_control_measure_foreign_keys',2);
