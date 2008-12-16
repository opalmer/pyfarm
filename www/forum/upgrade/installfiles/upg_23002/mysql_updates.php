<?php

$SQL[] = "alter table ibf_topics add index last_post_sorting(last_post,forum_id);";
$SQL[] = "alter table ibf_profile_comments drop index my_comments;";
$SQL[] = "alter table ibf_profile_comments add index my_comments (comment_for_member_id,comment_date);";
$SQL[] = "ALTER TABLE ibf_sessions ADD INDEX ( running_time );";

$SQL[] = "ALTER TABLE ibf_conf_settings DROP conf_help_key;";

$SQL[] = "CREATE TABLE ibf_acp_help (
id INT( 10 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
is_setting TINYINT( 1 ) NOT NULL DEFAULT '0',
page_key VARCHAR( 255 ) NULL ,
help_title VARCHAR( 255 ) NULL ,
help_body TEXT NULL ,
help_mouseover VARCHAR( 255 ) NULL ,
KEY page_key ( page_key ) );";

?>