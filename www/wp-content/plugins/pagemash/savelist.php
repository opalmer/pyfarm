<?php
/*                       __  __           _     
       WordPress Plugin |  \/  |         | |    
  _ __   __ _  __ _  ___| \  / | __ _ ___| |__  
 | '_ \ / _` |/ _` |/ _ \ |\/| |/ _` / __| '_ \ 
 | |_) | (_| | (_| |  __/ |  | | (_| \__ \ | | |
 | .__/ \__,_|\__, |\___|_|  |_|\__,_|___/_| |_|
 | |           __/ |  Author: Joel Starnes
 |_|          |___/   URL: pagemash.joelstarnes.co.uk
 
 >>Decodes JSON data and updates database accordingly
*/

if(!$_POST['m']) die('no data'); //die if no data is sent
error_reporting(E_ALL);
require_once('myjson.php'); //JSON decode lib
  
$root = dirname(dirname(dirname(dirname(__FILE__))));
if (file_exists($root.'/wp-load.php')) {
	require_once($root.'/wp-load.php');
} else {
	// Pre-2.6 compatibility
	require_once($root.'/wp-config.php');
	require_once($root.'/wp-settings.php');
}

global $wpdb, $excludePages;
$excludePages = array();

// fetch JSON object from $_POST['m']
$json = new Services_JSON(); 
$aMenu = (array) $json->decode(stripslashes($_POST['m']));

function saveList($parent, $children) {
	global $wpdb, $excludePages;
	
	$parent = (int) $parent;
	$result = array();
	$i = 1;
	foreach ($children as $k => $v) {
		
		//IDs are 'JM_#' so strip first 3 characters
		$id = (int) substr($children[$k]->id, 3); 
		
		//if it had the remove class it is now added to the excludePages array
		if(isset($v->hide)) $excludePages[] = $id;
		
		//update pages in db
		$postquery  = "UPDATE $wpdb->posts SET ";
		$postquery .= "menu_order='$i', post_parent='$parent'";
		if (isset($v->renamed)) $postquery .= ", post_title='$v->renamed'";
		$postquery .= " WHERE ID='$id'"; 
		
		$wpdb->query($postquery); //$wpdb->query( $wpdb->prepare( "UPDATE $wpdb->posts SET menu_order = %d, post_parent = %s WHERE ID = %d" ), $i, $parent, $id );
		echo $postquery;
		echo "\n";
		
		if (isset($v->children[0])) {saveList($id, $v->children);}
	$i++;
	}
}
echo "Update Pages: \n";
echo saveList(0, $aMenu);
$wpdb->print_error();
echo "\n \nExclude Pages: \n";
print_r($excludePages);

//update excludePages option in database
update_option("exclude_pages", $excludePages, '', 'yes');
?>