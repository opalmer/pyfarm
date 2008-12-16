<?php

$SQL[] = "ALTER TABLE ibf_skin_sets ADD set_key VARCHAR( 32 ) NULL ;";
$SQL[] = "ALTER TABLE ibf_skin_sets ADD INDEX ( set_key ) ;";

?>