<?php

/*
+--------------------------------------------------------------------------
|   Invision Power Board
|   ========================================
|   by Matthew Mecham
|   (c) 2001 - 2004 Invision Power Services
|   http://www.invisionpower.com
|   ========================================
|   Web: http://www.invisionboard.com
|   Email: matt@invisionpower.com
|   Licence Info: http://www.invisionboard.com/?license
+---------------------------------------------------------------------------
|   > $Date: 2006-09-22 06:28:31 -0400 (Fri, 22 Sep 2006) $
|   > $Revision: 567 $
|   > $Author: matt $
+---------------------------------------------------------------------------
|
|   > Admin wrapper script
|   > Script written by Matt Mecham
|   > Date started: 1st March 2002
|
+--------------------------------------------------------------------------
*/

require_once( './init.php' );
require ROOT_PATH   . "conf_global.php";


//-----------------------------------------
// NEVER EVER try and be helpful and update
// the link below when the ACP folder changes
// You'll just be giving hackers an easy way
// to find it...
//-----------------------------------------

header( 'Location: '.$INFO['base_url'].'admin/index.php' );


?>