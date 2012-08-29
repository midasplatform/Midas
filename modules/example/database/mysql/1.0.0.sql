CREATE TABLE IF NOT EXISTS example_wallet (
    example_wallet_id bigint(20) NOT NULL AUTO_INCREMENT,
    user_id bigint(20) NOT NULL,
    dollars bigint(20) NOT NULL,
    PRIMARY KEY (example_wallet_id)
)   DEFAULT CHARSET=utf8;