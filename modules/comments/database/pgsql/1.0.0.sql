
CREATE TABLE comments_item (
  comment_id serial PRIMARY KEY,
  item_id bigint NOT NULL,
  user_id bigint NOT NULL,
  comment text NOT NULL,
  date timestamp without time zone NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX comments_item_item_id ON comments_item (item_id);
CREATE INDEX comments_item_user_id ON comments_item (user_id);
