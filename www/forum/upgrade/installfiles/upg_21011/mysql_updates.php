<?php

$SQL[] = "ALTER TABLE ibf_members CHANGE email email varchar( 150 ) NOT NULL default ''";

$SQL[] = "ALTER TABLE ibf_subscription_currency CHANGE `subcurrency_exchange` `subcurrency_exchange` DECIMAL( 16, 8 ) DEFAULT '0.00000000' NOT NULL";

?>