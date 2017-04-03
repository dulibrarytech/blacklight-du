# Blacklight-DU
# Script to clear out the 'searches' table, and reduce the db file size.

sqlite3 production.sqlite3 <<ESQL
     DROP TABLE searches;
     CREATE TABLE "searches" ("id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, "query_params" text, "user_id" integer, "user_type" varchar(255), "created_at" datetime, "updated_at" datetime);
     VACUUM;
ESQL