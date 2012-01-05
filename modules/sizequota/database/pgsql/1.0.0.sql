DROP TABLE IF EXISTS sizequota_folderquota;

CREATE TABLE sizequota_folderquota (
  folderquota_id bigint serial PRIMARY KEY,
  folder_id bigint NOT NULL,
  quota bigint NOT NULL
);
CREATE INDEX sizequota_folderquota_folder_id ON sizequota_folderquota (folder_id);
