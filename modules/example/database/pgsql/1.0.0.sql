CREATE TABLE example_wallet (
    example_wallet_id serial PRIMARY KEY,
    user_id bigint NOT NULL,
    dollars bigint NOT NULL
);