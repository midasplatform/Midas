DROP TABLE IF EXISTS thumbnailcreator_itemthumbnail;
CREATE TABLE thumbnailcreator_itemthumbnail (
  itemthumbnail_id serial PRIMARY KEY,
  item_id integer,
  thumbnail character varying(255)
);

CREATE INDEX thumbnailcreator_itemthumbnail_item_id ON thumbnailcreator_itemthumbnail (item_id);
