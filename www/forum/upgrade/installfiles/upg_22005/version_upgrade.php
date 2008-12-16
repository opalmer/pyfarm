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
|
|   > IPB UPGRADE MODULE:: IPB 2.0.2 -> IPB 2.0.3
|   > Script written by Matt Mecham
|   > Date started: 23rd April 2004
|   > "So what, pop is dead - it's no great loss.
	   So many facelifts, it's face flew off"
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class version_upgrade
{
	var $install;
	
	/*-------------------------------------------------------------------------*/
	// CONSTRUCTOR
	/*-------------------------------------------------------------------------*/
	
	function version_upgrade( & $install )
	{
		$this->install = & $install;
	}
	
	/*-------------------------------------------------------------------------*/
	// Auto run..
	/*-------------------------------------------------------------------------*/

	function auto_run()
	{
		//--------------------------------
		// What are we doing?
		//--------------------------------

		switch( $this->install->saved_data['workact'] )
		{
			case 'sql':
				$this->upgrade_sql(1);
				break;
			case 'sql1':
				$this->upgrade_sql(1);
				break;
			case 'sql2':
				$this->upgrade_sql(2);
				break;
			case 'sql3':
				$this->upgrade_sql(3);
				break;
			case 'sql4':
				$this->upgrade_sql(4);
				break;
			case 'forums':
				$this->update_forums();
				break;
			case 'finish':
				$this->finish_up();
				break;
			case 'skin':
				$this->add_skin();
				break;
			case 'update_template_bits':
				$this->update_template_bits();
				break;
			
			default:
				$this->upgrade_sql(1);
				break;
		}
		
		if ( $this->install->saved_data['workact'] )
		{
			return false;
		}
		else
		{
			return true;
		}
	}
	
	
	/*-------------------------------------------------------------------------*/
	// SQL: 0
	/*-------------------------------------------------------------------------*/
	
	function upgrade_sql( $id=1 )
	{
		$man     = 0; // Manual upgrade ? intval( $this->install->ipsclass->input['man'] );
		$cnt     = 0;
		$SQL     = array();
		$file    = '_updates_'.$id.'.php';
		$output  = "";
		
		if ( file_exists( ROOT_PATH . 'upgrade/installfiles/upg_22005/' . strtolower($this->install->ipsclass->vars['sql_driver']) . $file ) )
		{
			require_once( ROOT_PATH . 'upgrade/installfiles/upg_22005/' . strtolower($this->install->ipsclass->vars['sql_driver']) . $file );
		
			$this->install->error   = array();
			$this->sqlcount 		= 0;
			$output					= "";
			
			$this->install->ipsclass->DB->return_die = 1;
			
			foreach( $SQL as $query )
			{
				$this->install->ipsclass->DB->allow_sub_select 	= 1;
				$this->install->ipsclass->DB->error				= '';
				
				$query = str_replace( "<%time%>", time(), $query );
				
				if( $this->install->ipsclass->vars['mysql_tbl_type'] )
				{
					if( preg_match( "/^create table(.+?)/i", $query ) )
					{
						$query = preg_replace( "/^(.+?)\);$/is", "\\1) TYPE={$this->install->ipsclass->vars['mysql_tbl_type']};", $query );
					}
				}					
							
				if ( $this->install->saved_data['man'] )
				{
					$output .= preg_replace("/\sibf_(\S+?)([\s\.,]|$)/", " ".$this->install->ipsclass->DB->obj['sql_tbl_prefix']."\\1\\2", preg_replace( "/\s{1,}/", " ", $query ) )."\n\n";
				}
				else
				{			
					$this->install->ipsclass->DB->query( $query );
					
					if ( $this->install->ipsclass->DB->error )
					{
						$this->install->error[] = $query."<br /><br />".$this->install->ipsclass->DB->error;
					}
					else
					{
						$this->sqlcount++;
					}
				}
			}
		
			$this->install->message = "$this->sqlcount queries run....";
		}
		
		//--------------------------------
		// Next page...
		//--------------------------------
		
		$this->install->saved_data['st'] = 0;
		
		if ( $id != 4 )
		{
			$nextid = $id + 1;
			$this->install->saved_data['workact'] = 'sql'.$nextid;	
		}
		else
		{
			$this->install->saved_data['workact'] = 'forums';	
		}
		
		if ( $this->install->saved_data['man'] AND $output )
		{
			$this->install->message .= "<br /><br /><h3><b>Please run these queries in your MySQL database before continuing..</b></h3><br />".nl2br(htmlspecialchars($output));
			$this->install->do_man	 = 1;
		}		
	}	
	
	
	/*-------------------------------------------------------------------------*/
	// Update forums
	/*-------------------------------------------------------------------------*/
	
	function update_forums()
	{
		//-----------------------------------------
		// Update latest news...
		//-----------------------------------------
		
		$this->install->ipsclass->DB->simple_update( "forums", "newest_title=last_title, newest_id=last_id" );
		$this->install->ipsclass->DB->exec_query();
		
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$ignore_me = array( 'redirect_url', 'redirect_loc', 'rules_text', 'permission_custom_error', 'notify_modq_emails' );
		
		if ( isset($this->install->ipsclass->vars['forum_cache_minimum']) AND $this->install->ipsclass->vars['forum_cache_minimum'] )
		{
			$ignore_me[] = 'description';
			$ignore_me[] = 'rules_title';
		}
		
		$this->install->ipsclass->cache['forum_cache'] = array();
			
		$this->install->ipsclass->DB->simple_construct( array( 'select' => '*',
													  'from'   => 'forums',
													  'order'  => 'parent_id, position'
											   )      );
		$o = $this->install->ipsclass->DB->simple_exec();
		
		while( $f = $this->install->ipsclass->DB->fetch_row( $o ) )
		{
			$fr = array();
			
			$perms = unserialize(stripslashes($f['permission_array']));
			
			//-----------------------------------------
			// Stuff we don't need...
			//-----------------------------------------
			
			if ( $f['parent_id'] == -1 )
			{
				$fr['id']				    = $f['id'];
				$fr['sub_can_post']         = $f['sub_can_post'];
				$fr['name'] 		        = $f['name'];
				$fr['parent_id']	        = $f['parent_id'];
				$fr['show_perms']	        = $perms['show_perms'];
				$fr['skin_id']		        = $f['skin_id'];
				$fr['permission_showtopic'] = $f['permission_showtopic'];
			}
			else
			{
				foreach( $f as $k => $v )
				{
					if ( in_array( $k, $ignore_me ) )
					{
						continue;
					}
					else
					{
						if ( $v != "" )
						{
							$fr[ $k ] = $v;
						}
					}
				}
				
				$fr['read_perms']   	= isset($perms['read_perms']) 		? $perms['read_perms'] 		: '';
				$fr['reply_perms']  	= isset($perms['reply_perms']) 		? $perms['reply_perms'] 	: '';
				$fr['start_perms']  	= isset($perms['start_perms']) 		? $perms['start_perms'] 	: '';
				$fr['upload_perms'] 	= isset($perms['upload_perms']) 	? $perms['upload_perms'] 	: '';
				$fr['download_perms'] 	= $perms['upload_perms'];
				$fr['show_perms']   	= isset($perms['show_perms']) 		? $perms['show_perms'] 		: '';
				
				unset($fr['permission_array']);
			}
			
			$this->install->ipsclass->cache['forum_cache'][ $fr['id'] ] = $fr;
			
			$perm_array = addslashes(serialize(array(
													   'start_perms'    => $fr['start_perms'],
													   'reply_perms'    => $fr['reply_perms'],
													   'read_perms'     => $fr['read_perms'],
													   'upload_perms'   => $fr['upload_perms'],
													   'download_perms' => $fr['download_perms'],
													   'show_perms'     => $fr['show_perms']
									 )		  )     );
									 
			//-----------------------------------------
			// Add to save array
			//-----------------------------------------
			
			$this->install->ipsclass->DB->do_update( 'forums', array( 'permission_array' => $perm_array ), 'id='.$fr['id'] );
			
		}
		
		$this->install->ipsclass->update_cache( array( 'name' => 'forum_cache', 'array' => 1, 'deletefirst' => 1, 'donow' => 0 ) );
		
		$this->install->message = "Download permissions added,  Converting template bit HTML logic...";
		$this->install->saved_data['workact'] 	= 'update_template_bits';
	}
	
	/*-------------------------------------------------------------------------*/
	// Update template bits
	/*-------------------------------------------------------------------------*/
	
	function update_template_bits()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$updated         = 0;
		$_bits           = array();
		$start           = $this->install->saved_data['st'];
		$lend            = 25;
		$end             = $start + $lend;
		$done            = 0;
		
		//-----------------------------------------
		// Get API class
		//-----------------------------------------
		
		require_once( ROOT_PATH . 'sources/api/api_skins.php' );
		$api           =  new api_skins;
		$api->ipsclass =& $this->ipsclass;
		
		//-----------------------------------------
		// OK..
		//-----------------------------------------
		
		$this->install->ipsclass->DB->build_query( array( 'select' => '*',
											 			  'from'   => 'skin_templates',
														  'where'  => 'set_id > 1',
													      'order'  => 'suid ASC',
													      'limit'  => array( $start, $lend ) ) );
											
		$o = $this->install->ipsclass->DB->exec_query();
		
		//-----------------------------------------
		// Do it...
		//-----------------------------------------

		if ( $this->install->ipsclass->DB->get_num_rows($o) )
		{
			//-----------------------------------------
			// Got some to convert!
			//-----------------------------------------
			
			while( $row = $this->install->ipsclass->DB->fetch_row( $o ) )
			{
				if ( preg_match( "#ipb\.|<if|<else#", $row['section_content'] ) )
				{
					$section_content = $api->skin_update_template_bit( $row['section_content'] );
				
					if ( $section_content AND $section_content != $row['section_content'] )
					{
						$updated++;
						$this->install->ipsclass->DB->do_update( 'skin_templates', array( 'section_content' => $section_content ), 'suid=' . $row['suid'] );
					}
				}
			}
		}
		else
		{
			$done = 1;
		}
		
		//-----------------------------------------
		// Done?
		//-----------------------------------------
		
		if ( ! $done )
		{
			$this->install->message = "Template bits: $start to $end completed. $updated updated...";
			$this->install->saved_data['workact'] 	= 'update_template_bits';	
			$this->install->saved_data['st'] 		= $end;
			return FALSE;			
		}
		else
		{
			$this->install->message = "Template bits updated, finishing up...";
			$this->install->saved_data['workact'] 	= 'finish';	
			return FALSE;						
		}
	}

	/*-------------------------------------------------------------------------*/
	// Update forums
	/*-------------------------------------------------------------------------*/
	
	function finish_up()
	{
		//-----------------------------------------
		// Has gallery?
		//-----------------------------------------
		
		$this->install->ipsclass->DB->return_die = 1;
		$this->install->ipsclass->DB->error		 = '';
		
		$table = 'members';
		
		if ( ! $this->install->ipsclass->DB->field_exists( 'has_gallery', $table ) )
		{
			$this->install->ipsclass->DB->sql_add_field( 'members', 'has_gallery', 'INT(1)', '0' );
			
			if ( $this->install->ipsclass->DB->error )
			{
				$this->install->error[] = "ALTER TABLE {$this->install->ipsclass->DB->obj['sql_tbl_prefix']}members ADD has_gallery INT(1) default 0<br /><br />".$this->install->ipsclass->DB->error;
			}
		}
		
		if( $this->install->ipsclass->vars['conv_configured'] != 1 OR $this->install->ipsclass->vars['conv_chosen'] == "" )
		{
			if( $this->install->ipsclass->DB->field_exists( "legacy_password", "members" ) )
			{
				$this->install->ipsclass->DB->sql_drop_field( 'members', 'legacy_password' );
				
				if ( $this->install->ipsclass->DB->error )
				{
					$this->install->error[] = "ALTER TABLE {$this->install->ipsclass->DB->obj['sql_tbl_prefix']}members DROP legacy_password<br /><br />".$this->install->ipsclass->DB->error;
				}
			}
		}
		
		$value = $this->install->ipsclass->DB->build_and_exec_query( array( 'select' => 'conf_value,conf_default', 'from' => 'conf_settings', 'where' => "conf_key='converge_login_method'" ) );
		
		if( !$value['conf_value'] )
		{
			$value['conf_value'] = $value['conf_default'];
		}
		
		if( $value['conf_value'] )
		{
			$this->install->saved_data['ipbli_usertype'] = $value['conf_value'];
		}
		
		$test = $this->install->ipsclass->DB->build_and_exec_query( array( 'select' 	=> 'count(*) as numrows',
																	'from'	=> 'cache_store',
																	'where'	=> "cs_key='calendars'"
														)		);

		if ( ! $test['numrows'] )
		{
			$this->install->ipsclass->DB->do_insert( 'cache_store', array( 'cs_key' => 'calendars' ) );
		}
		
		//-----------------------------------------
		// Rebuild group caches.
		//-----------------------------------------
		
		require_once( ROOT_PATH . 'sources/action_admin/groups.php' );
		$groups           =  new ad_groups();
		$groups->ipsclass =& $this->install->ipsclass;
		
		$groups->rebuild_group_cache();
		
		$this->install->message = "Clean up performed,  Creating new IPB 2.2.0 skin...";
		$this->install->saved_data['workact'] 	= 'skin';
	}
	
	/*-------------------------------------------------------------------------*/
	// Add new skin
	/*-------------------------------------------------------------------------*/
	
	function add_skin()
	{
		//-----------------------------------------
		// Get default wrapper
		//-----------------------------------------
		
		$xml = new class_xml();
		$xml->lite_parser = 1;
		
		$content = implode( "", file( ROOT_PATH . 'resources/skinsets.xml' ) );
		$xml->xml_parse_document( $content );
	
		//-----------------------------------------
		// Get CSS and WRAPPER
		//-----------------------------------------
		
		$CSS     = $xml->xml_array['export']['group']['row'][0]['set_css']['VALUE'];
		$WRAPPER = $xml->xml_array['export']['group']['row'][0]['set_cache_wrapper']['VALUE'];
		
		//-----------------------------------------
		// Turn off all other skins
		//-----------------------------------------
		
		$this->install->ipsclass->DB->do_update( 'skin_sets', array( 'set_default' => 0 ) );
		
		//-----------------------------------------
		// Insert new skin...
		//-----------------------------------------
		
		$this->install->ipsclass->DB->return_die        = 1;
		$this->install->ipsclass->DB->allow_sub_select 	= 1;
		
		$this->install->ipsclass->DB->do_insert( 'skin_sets', array(
															'set_name'            => 'IPB 2.2.0 Default',
															'set_image_dir'       => 1,
															'set_hidden'          => 0,
															'set_default'         => 1,
															'set_css_method'      => 0,
															'set_skin_set_parent' => -1,
															'set_author_email'    => '',
															'set_author_name'     => 'IPB 2.2.0 Default',
															'set_author_url'      => '',
															'set_cache_css'       => $CSS,
															'set_cache_wrapper'   => $WRAPPER,
															'set_emoticon_folder' => 'default',
									 )                    );
		
		$this->install->saved_data['new_skin'] = $this->install->ipsclass->DB->get_insert_id();
		
		//-----------------------------------------
		// Update parent skin's CSS
		//-----------------------------------------
		
		$this->install->ipsclass->DB->return_die        = 1;
		$this->install->ipsclass->DB->allow_sub_select 	= 1;
		
		$this->install->ipsclass->DB->do_update( 'skin_sets', array(
															'set_cache_css'       => $CSS,
															'set_css'       	  => $CSS,
															'set_cache_wrapper'   => $WRAPPER,
															'set_wrapper'   	  => $WRAPPER,
									 ), "set_skin_set_id=1"        );		
		
		//-----------------------------------------
		// Remove member's choice
		//-----------------------------------------
		
		$this->install->ipsclass->DB->do_update( 'members', array( 'skin' => '' ) );	
		
		//-----------------------------------------
		// Done...
		//-----------------------------------------
			
		$this->install->message = "2.2.0 skin created...";
		unset($this->install->saved_data['workact']);
		unset($this->install->saved_data['vid']);

		return TRUE;	
	}
	
}
	
	
?>