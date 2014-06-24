CREATE TABLE ratings_item (
  rating_id serial PRIMARY KEY,
  item_id bigint NOT NULL,
  user_id bigint NOT NULL,
  rating smallint NOT NULL
);

CREATE INDEX ratings_item_item_id ON ratings_item (item_id);
CREATE INDEX ratings_item_user_id ON ratings_item (user_id);
