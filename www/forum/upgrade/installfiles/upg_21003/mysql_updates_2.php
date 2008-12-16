<?php

# Nothing of interest!

$SQL[] = "ALTER TABLE ibf_topics CHANGE approved approved tinyint(1) NOT NULL default '0';";
$SQL[] = "ALTER TABLE ibf_topics DROP INDEX last_post;";
$SQL[] = "ALTER TABLE ibf_topics DROP INDEX forum_id;";
$SQL[] = "alter table ibf_topics add index forum_id( forum_id, pinned, approved);";
$SQL[] = "ALTER TABLE ibf_topics add index last_post(forum_id, pinned, last_post);";
$SQL[] = "ALTER TABLE ibf_topics ADD topic_rating_total SMALLINT UNSIGNED NOT NULL default '0';";
$SQL[] = "ALTER TABLE ibf_topics ADD topic_rating_hits  SMALLINT UNSIGNED NOT NULL default '0';";
$SQL[] = "ALTER TABLE ibf_topics DROP rating;";
$SQL[] = "alter table ibf_topics ADD topic_open_time INT(10) NOT NULL default '0';";
$SQL[] = "alter table ibf_topics ADD topic_close_time INT(10) NOT NULL default '0';";

?>