<?php

# Nothing of interest!

// $SQL[] = "";

$SQL[] = "ALTER TABLE ibf_forums CHANGE last_title last_title varchar(128) NOT NULL default '';";
$SQL[] = "ALTER TABLE ibf_forums CHANGE last_id last_id int(10) NOT NULL default '0';";
$SQL[] = "UPDATE ibf_components SET com_title='AddOnChat' WHERE com_section='chatsigma';";

?>