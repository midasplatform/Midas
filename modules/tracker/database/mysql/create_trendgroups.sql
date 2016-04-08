CREATE PROCEDURE `create_trendgroups`()
  BEGIN
    DECLARE finished INTEGER DEFAULT 0;
    DECLARE cur_producer_id bigint(20) default -1;
    DECLARE cur_config_item_id bigint(20) default NULL;
    DECLARE cur_test_dataset_id bigint(20) default NULL;
    DECLARE cur_truth_dataset_id bigint(20) default NULL;

    DECLARE curs CURSOR FOR SELECT producer_id, config_item_id, test_dataset_id, truth_dataset_id
                            FROM tracker_trend
                            WHERE trendgroup_id=-1 GROUP BY config_item_id, test_dataset_id, truth_dataset_id, producer_id;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET finished = 1;

    OPEN curs;

    get_trends: LOOP
      FETCH curs INTO cur_producer_id, cur_config_item_id, cur_test_dataset_id, cur_truth_dataset_id;
      IF finished = 1 THEN
        LEAVE get_trends;
      END IF;

      INSERT INTO tracker_trendgroup (producer_id, config_item_id, test_dataset_id, truth_dataset_id)
      VALUES (cur_producer_id, cur_config_item_id, cur_test_dataset_id, cur_truth_dataset_id);
      UPDATE tracker_trend SET trendgroup_id=LAST_INSERT_ID()
      WHERE
        producer_id=cur_producer_id AND
        (config_item_id=cur_config_item_id OR (config_item_id IS NULL AND cur_config_item_id IS NULL)) AND
        (test_dataset_id=cur_test_dataset_id OR (test_dataset_id IS NULL AND cur_test_dataset_id IS NULL)) AND
        (truth_dataset_id=cur_truth_dataset_id OR (truth_dataset_id IS NULL AND cur_truth_dataset_id IS NULL));
    END LOOP get_trends;
    CLOSE curs;
  END
