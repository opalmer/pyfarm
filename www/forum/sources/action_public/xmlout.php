<?php

/*
+--------------------------------------------------------------------------
|   Invision Power Board
|   =============================================
|   by Matthew Mecham
|   (c) 2001 - 2006 Invision Power Services, Inc.
|   http://www.invisionpower.com
|   =============================================
|   Web: http://www.invisionboard.com
|   Licence Info: http://www.invisionboard.com/?license
+---------------------------------------------------------------------------
|   > $Date: 2007-10-17 16:29:37 -0400 (Wed, 17 Oct 2007) $
|   > $Revision: 1133 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > XML OUT Functions for XmlHttpRequest functions
|   > Module written by Matt Mecham
|   > Date started: Friday 18th March 2005
|
|	> Module Version Number: 1.1.0
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class xmlout
{
	# Classes
	var $ipsclass;
	var $search;
	
    var $xml_output;
    var $xml_header;
    
    var $post_init = 0;
    
    /**
    * Post object
    *
    * @var object
    */
    var $post;
    
	/*
	* Ajax main class
	*/
	var $class_ajax;
	
    var $decode_charsets = array( 'iso-8859-1' 		=> 'ISO-8859-1',
    								'iso8859-1' 	=> 'ISO-8859-1',
    								'iso-8859-15' 	=> 'ISO-8859-15',
    								'iso8859-15' 	=> 'ISO-8859-15',
    								'utf-8'			=> 'UTF-8',
    								'cp866'			=> 'cp866',
    								'ibm866'		=> 'cp866',
    								'cp1251'		=> 'windows-1251',
    								'windows-1251'	=> 'windows-1251',
    								'win-1251'		=> 'windows-1251',
    								'cp1252'		=> 'windows-1252',
    								'windows-1252'	=> 'windows-1252',
    								'koi8-r'		=> 'KOI8-R',
    								'koi8-ru'		=> 'KOI8-R',
    								'koi8r'			=> 'KOI8-R',
    								'big5'			=> 'BIG5',
    								'gb2312'		=> 'GB2312',
    								'big5-hkscs'	=> 'BIG5-HKSCS',
    								'shift_jis'		=> 'Shift_JIS',
    								'sjis'			=> 'Shift_JIS',
    								'euc-jp'		=> 'EUC-JP',
    								'eucjp'			=> 'EUC-JP' );
    								
        
    /*-------------------------------------------------------------------------*/
    // Auto run
    /*-------------------------------------------------------------------------*/
    
    function auto_run()
    {
	    $this->xml_header = "<?xml version=\"1.0\" encoding=\"{$this->ipsclass->vars['gb_char_set']}\"?".'>';
	    
    	//-----------------------------------------
    	// INIT
    	//-----------------------------------------
    	
    	if ( isset($this->ipsclass->input['j_do']) AND $this->ipsclass->input['j_do'] )
    	{
    		$this->ipsclass->input['do'] = $this->ipsclass->input['j_do'];
    	}
		
		//-----------------------------------------
		// Load ajax class
		//-----------------------------------------
		
		require_once( KERNEL_PATH . 'class_ajax.php' );

		$this->class_ajax           =  new class_ajax();
		$this->class_ajax->ipsclass =& $this->ipsclass;
    	$this->class_ajax->class_init();

    	//-----------------------------------------
    	// What shall we do?
    	//-----------------------------------------
    	
    	switch( $this->ipsclass->input['do'] )
    	{
    		case 'get-new-posts':
    			$this->get_new_posts();
    			break;
    		case 'get-new-msgs':
    			$this->get_new_messages();
    			break;
    		case 'mark-forum':
    			$this->mark_forum_as_read();
    			break;
    		case 'get-member-names':
    			$this->get_member_names();
    			break;
    		case 'get-msg-preview':
    			$this->get_msg_preview();
    			break;
    		case 'save-topic':
    			$this->save_topic();
    			break;
    		case 'dst-autocorrection':
    			$this->dst_autocorrection();
    			break;
    		case 'check-display-name':
    			$this->check_display_name('members_display_name');
    			break;
    		case 'check-user-name':
    			$this->check_display_name('name');
    			break;
    		case 'check-email-address':
    			$this->check_email_address();
    			break;
    			
    		case 'post-edit-show':
    			$this->post_edit_show();
    			break;
    		case 'post-edit-save':
    			$this->post_edit_save();
    			break;
    			
    		case 'topic_rate':
    			$this->save_topic_rate();
    			break;

			case 'member-rate':
    			$this->save_member_rate();
    			break;
			case 'profile-save-settings':
    			$this->profile_save_settings();
    			break;
    			
    		case 'change-gd-img':
    			$this->change_gd_img();
    			break;
			
			case 'post-editorswitch':
				$this->post_editorswitch();
				break;
    	}
    }

	/*-------------------------------------------------------------------------*/
	// Save profile settings
	/*-------------------------------------------------------------------------*/
      
    function profile_save_settings()
    {
    	//-----------------------------------------
    	// INIT
    	//-----------------------------------------
    	
		$member_id     = intval($this->ipsclass->input['member_id']);
		$member        = array();
		$command       = trim( $this->ipsclass->input['cmd'] );
		$value         = $this->class_ajax->convert_and_make_safe( $this->ipsclass->input['value'], 0 );
		$md5_check     = substr( $this->ipsclass->input['md5check'], 0, 32 );
		$return_string = '';
		$pp_b_day      = intval( $this->ipsclass->input['pp_b_day'] );
		$pp_b_month    = intval( $this->ipsclass->input['pp_b_month'] );
		$pp_b_year     = intval( $this->ipsclass->input['pp_b_year'] );
		
		//-----------------------------------------
		// MD5 check
		//-----------------------------------------
		
		if (  $md5_check != $this->ipsclass->return_md5_check() )
    	{
			$this->class_ajax->print_nocache_headers();
			$this->class_ajax->return_string( 'error' );
    	}

		//-----------------------------------------
    	// Check
    	//-----------------------------------------
    	
    	if ( ! $member_id OR ! $this->ipsclass->member['id'] )
    	{
			$this->class_ajax->print_nocache_headers();
			$this->class_ajax->return_string( 'error' );
    	}
    	
		if ( ! $this->ipsclass->member['g_edit_profile'] )
		{
			$this->class_ajax->print_nocache_headers();
			$this->class_ajax->return_string( 'error' );
		}
		
		//-----------------------------------------
		// Load member
		//-----------------------------------------
		
		$this->ipsclass->DB->build_query( array( 'select'   => 'm.*',
												 'from'     => array( 'members' => 'm' ),
												 'where'    => 'm.id='.$member_id,
												 'add_join' => array( 0 => array( 'select' => 'me.*',
																				  'from'   => array( 'member_extra' => 'me' ),
																				  'where'  => 'me.id=m.id',
																				  'type'   => 'left' ),
																	  1 => array( 'select' => 'pp.*',
																				  'from'   => array( 'profile_portal' => 'pp' ),
																				  'where'  => 'pp.pp_member_id=m.id',
																				  'type'   => 'left' ),
														   			  2 => array( 'select' => 'g.*',
																				  'from'   => array( 'groups' => 'g' ),
																				  'where'  => 'g.g_id=m.mgroup',
																				  'type'   => 'left' ) ) ) );
		$this->ipsclass->DB->exec_query();
																				
		$member = $this->ipsclass->DB->fetch_row();
    	
    	if ( ! $member['id'] )
    	{
			$this->class_ajax->print_nocache_headers();
			$this->class_ajax->return_string( 'error' );
    	}
    	
		//-----------------------------------------
		// Not the same member?
		//-----------------------------------------
		
		if ( ! $this->ipsclass->member['g_is_supmod'] OR ( $member['mgroup'] == $this->ipsclass->vars['admin_group'] ) )
		{
			if ( $member_id != $this->ipsclass->member['id'] )
			{
				$this->class_ajax->print_nocache_headers();
				$this->class_ajax->return_string( 'error' );
			}
		}
    	
		//-----------------------------------------
		// Alright.. what are we doing?
		//-----------------------------------------
		
		switch( $command )
		{
			case 'gender':
				$_gender = ( $this->ipsclass->input['pp_gender'] == 'male' ) ? 'male' : ( $this->ipsclass->input['pp_gender'] == 'female' ? 'female' : '' );
				
				$this->ipsclass->DB->simple_construct( array( 'select' => 'pp_member_id',
															  'from'   => 'profile_portal',
															  'where'  => "pp_member_id=".$member_id ) );
				$this->ipsclass->DB->simple_exec();

				if ( $this->ipsclass->DB->get_num_rows() )
				{
					$this->ipsclass->DB->do_update( 'profile_portal', array( 'pp_gender' => $_gender ), 'pp_member_id='.$member_id );
				}
				else
				{
					$this->ipsclass->DB->do_insert( 'profile_portal', array( 'pp_gender'    => $_gender,
					 														 'pp_member_id' => $member_id ) );
				}
				
				$return_string = $_gender;
				break;
			case 'contact':
				$this->ipsclass->load_language( 'lang_profile' );
				
				$_type         = trim( $this->ipsclass->input['contacttype'] );
				$return_string = '';
				
				$types = array( 'aim'   => 'aim_name',
								'msn'   => 'msnname',
								'icq'   => 'icq_number',
								'yahoo' => 'yahoo' );
								
				if ( in_array( $_type, array_keys( $types ) ) )
				{
					if ( $_type == 'icq' )
					{
						if ( $value != preg_replace( "#[^0-9]#s", "", $value ) )
						{
							$return_string = 'icqerror';
						}
					}
					
					if ( ! $return_string )
					{
						$value = $this->ipsclass->txt_mbsubstr( $value, 0, 100 );
						$this->ipsclass->DB->do_update( 'member_extra', array( $types[ $_type ] => $value ), 'id='.$member_id );
						$return_string = $value ? $value: $this->ipsclass->lang['no_info'];
					}
				}
				
				break;
			case 'location':
				if ( $value )
				{
					$_v    = $this->ipsclass->vars['max_location_length'] ? $this->ipsclass->vars['max_location_length']: 200;
					$value = $this->ipsclass->txt_mbsubstr( $value, 0, $_v );
					$this->ipsclass->DB->do_update( 'member_extra', array( 'location' => $value ), 'id='.$member_id );
					$return_string = $value;
				}
				break;
			case 'birthdate':
				$this->ipsclass->load_language( 'lang_profile' );
			
				if( $pp_b_month OR $pp_b_day OR $pp_b_year )
				{
					if ( ! $pp_b_month or ! $pp_b_day )
					{
						$return_string = 'dateerror';
					}
				}
				
				
				if ( ( $pp_b_month AND $pp_b_day AND $pp_b_year ) AND ! @checkdate( $pp_b_month, $pp_b_day, $pp_b_year ) )
				{
					$return_string = 'dateerror';
				}

				if( $return_string != 'dateerror' )
				{
					$this->ipsclass->DB->do_update( 'members', array( 'bday_month' => intval( $pp_b_month ),
					 												  'bday_day'   => intval( $pp_b_day ),
																	  'bday_year'  => intval( $pp_b_year ) ), 'id='.$member_id );

					$_pp_b_month = '';

				 	for( $i = 1; $i < 13; $i++ )
				 	{
					 	if( $i == $pp_b_month )
					 	{
						 	$_pp_b_month = $this->ipsclass->lang['M_'.$i];
					 	}
				 	}

					$return_string = ( $pp_b_month AND $pp_b_day AND $pp_b_year ) ? $_pp_b_month . '-' . $pp_b_day . '-' . $pp_b_year : $this->ipsclass->lang['m_bday_unknown'];
				}
		}
		
		$this->class_ajax->print_nocache_headers();
		$this->class_ajax->return_string( $return_string );
	}

	/*-------------------------------------------------------------------------*/
	// Save Member Rating
	/*-------------------------------------------------------------------------*/
      
    function save_member_rate()
    {
    	//-----------------------------------------
    	// INIT
    	//-----------------------------------------
    	
		$rating_id  = intval($this->ipsclass->input['rating']);
		$rating_id  = $rating_id > 5 ? 5 : $rating_id;
		$rating_id  = $rating_id < 0 ? 0 : $rating_id;
		$member_id  = intval($this->ipsclass->input['member_id']);
		$member     = array();
		$type       = 'new';
		$md5_check	= substr( $this->ipsclass->input['md5check'], 0, 32 );
		
    	//-----------------------------------------
    	// Check
    	//-----------------------------------------
    	
    	if ( ! $this->ipsclass->vars['pp_allow_member_rate'] )
    	{
			$this->class_ajax->print_nocache_headers();
			$this->class_ajax->return_string( 'error' );
		}    	
    	
    	if ( ! $member_id OR ! $this->ipsclass->member['id'] OR $member_id == $this->ipsclass->member['id'] )
    	{
			$this->class_ajax->print_nocache_headers();
			$this->class_ajax->return_string( 'error' );
    	}
    	
		//-----------------------------------------
		// MD5 check
		//-----------------------------------------
		
		if (  $md5_check != $this->ipsclass->return_md5_check() )
    	{
			$this->class_ajax->print_nocache_headers();
			$this->class_ajax->return_string( 'error' );
    	}
    	
    	$this->ipsclass->DB->build_query( array( 'select'   => 'm.*',
												 'from'     => array( 'members' => 'm' ),
												 'where'    => 'm.id='.$member_id,
												 'add_join' => array( 0 => array( 'select' => 'me.*',
																				  'from'   => array( 'member_extra' => 'me' ),
																				  'where'  => 'me.id=m.id',
																				  'type'   => 'left' ),
																	  1 => array( 'select' => 'pp.*',
																				  'from'   => array( 'profile_portal' => 'pp' ),
																				  'where'  => 'pp.pp_member_id=m.id',
																				  'type'   => 'left' ),
														   			  2 => array( 'select' => 'g.*',
																				  'from'   => array( 'groups' => 'g' ),
																				  'where'  => 'g.g_id=m.mgroup',
																				  'type'   => 'left' ) ) ) );
		$this->ipsclass->DB->exec_query();
																				
		$member = $this->ipsclass->DB->fetch_row();
    	
    	if ( ! $member['id'] )
    	{
			$this->class_ajax->print_nocache_headers();
			$this->class_ajax->return_string( 'error' );
    	}
    	
    	//-----------------------------------------
    	// Have we already rated?
    	//-----------------------------------------
    			
		$rating = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*',
																	'from'   => 'profile_ratings',
																	'where'  => "rating_for_member_id={$member_id} AND rating_by_member_id=".$this->ipsclass->member['id'] ) );
    	
		//-----------------------------------------
		// Already rated?
		//-----------------------------------------
		
		if ( $rating['rating_id'] )
		{
			//-----------------------------------------
			// Do we allow re-ratings?
			//-----------------------------------------
			
			if ( $rating_id != $rating['rating_value'] )
			{
				$member['pp_rating_value'] = intval( $member['pp_rating_value'] );
				$member['pp_rating_value'] = ( $member['pp_rating_value'] + $rating_id ) - $rating['rating_value'];
				
				$this->ipsclass->DB->do_update( 'profile_ratings', array( 'rating_value' => $rating_id ), 'rating_id='.$rating['rating_id'] );
				
				$this->ipsclass->DB->do_update( 'profile_portal', array( 'pp_rating_value' => $member['pp_rating_value'],
				 														 'pp_rating_real'  => round( $member['pp_rating_value'] / $member['pp_rating_hits'] ) ), 'pp_member_id='.$member_id );
				
				$type = 'update';
			}
		}
		
		//-----------------------------------------
		// NEW RATING!
		//-----------------------------------------
		
		else
		{
			$member['pp_rating_value'] = intval($member['pp_rating_value']) + $rating_id;
			$member['pp_rating_hits']  = intval($member['pp_rating_hits'])  + 1;
			
			$this->ipsclass->DB->do_insert( 'profile_ratings', array( 'rating_for_member_id' => $member_id,
																      'rating_by_member_id'  => $this->ipsclass->member['id'],
																      'rating_value'         => $rating_id,
																	  'rating_added'		 => $rating_added,
																      'rating_ip_address'    => $this->ipsclass->ip_address ) );
																    
			$this->ipsclass->DB->do_update( 'profile_portal', array( 'pp_rating_hits'  => intval($member['pp_rating_hits']),
															 		 'pp_rating_value' => intval($member['pp_rating_value']),
															         'pp_rating_real'  => round( $member['pp_rating_value'] / $member['pp_rating_hits'] ) ), 'pp_member_id='.$member_id );

			
		}
    	
		$member['pp_rating_real'] = round( $member['pp_rating_value'] / $member['pp_rating_hits'] );

		$this->class_ajax->print_nocache_headers();
		$this->class_ajax->return_string( $member['pp_rating_value'].','.$member['pp_rating_hits'].','.$member['pp_rating_real'].','.$type );
    }
    
 	/*-------------------------------------------------------------------------*/
	// Change a GD antispam image (if it's unreadable)
	/*-------------------------------------------------------------------------*/    
	
	function change_gd_img()
	{
		$img = trim( $this->ipsclass->txt_alphanumerical_clean( $this->ipsclass->input['img'] ) );
		
		$antispam = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*', 'from' => 'reg_antispam', 'where' => "regid='" . $this->ipsclass->DB->add_slashes( $img ) . "'" ) );
		
		if ( ! $antispam['regcode'] )
		{
			$this->class_ajax->print_nocache_headers();
			$this->class_ajax->return_string( '' );
		}
		
		$regid = md5( uniqid(microtime()) );
		
		if( $this->ipsclass->vars['bot_antispam'] == 'gd' )
		{
			//-----------------------------------------
			// Get 6 random chars
			//-----------------------------------------
							
			$reg_code = strtoupper( substr( $regid, 0, 6 ) );
		}
		else
		{
			//-----------------------------------------
			// Set a new 6 character numerical string
			//-----------------------------------------

			$reg_code = mt_rand(100000,999999);
		}
		
		// Clear old record first
		
		$this->ipsclass->DB->do_delete( 'reg_antispam', "regid='" . $this->ipsclass->DB->add_slashes( $antispam['regid'] ) . "'" );
		
		// Insert into the DB
		
		$this->ipsclass->DB->do_insert( 'reg_antispam', array (
																'regid'      => $regid,
																'regcode'    => $reg_code,
																'ip_address' => $this->ipsclass->input['IP_ADDRESS'],
																'ctime'      => time(),
													)       );

		$this->class_ajax->return_string( $regid );
	}

 	/*-------------------------------------------------------------------------*/
	// Convert RTE to STD.. or STD to RTE
	/*-------------------------------------------------------------------------*/

    function post_editorswitch()
    {
    	//-----------------------------------------
    	// INIT
    	//-----------------------------------------

		$to_rte = intval( $this->ipsclass->input['to_rte'] );
		$post   = $this->class_ajax->convert_unicode( $_POST['Post'] );
		$post   = $this->ipsclass->txt_stripslashes($post);
		$post   = $this->class_ajax->convert_html_entities( $post );
		
		$_debug = 0;
		
		//-----------------------------------------
		// Load BBCode
		//-----------------------------------------
		
		require_once( ROOT_PATH . 'sources/classes/bbcode/class_bbcode_core.php' );
		require_once( ROOT_PATH . 'sources/classes/bbcode/class_bbcode.php' );
		
		//-----------------------------------------
		// Get the smilies from the DB
		//-----------------------------------------
		
		if ( ! is_array( $this->ipsclass->cache['emoticons'] ) )
		{
			$this->ipsclass->cache['emoticons'] = array();
			
			$this->ipsclass->DB->simple_construct( array( 'select' => 'typed,image,clickable,emo_set', 'from' => 'emoticons' ) );
			$this->ipsclass->DB->simple_exec();
		
			while ( $r = $this->ipsclass->DB->fetch_row() )
			{
				$this->ipsclass->cache['emoticons'][] = $r;
			}
			
			@usort( $this->ipsclass->cache['emoticons'] , array( 'class_bbcode_core', 'smilie_alpha_sort' ) );
		}
		
		$bbcode                =  new class_bbcode();
		$bbcode->ipsclass      =& $this->ipsclass;
		$bbcode->parse_bbcode  = 1;
		$bbcode->parse_html    = 0;
		$bbcode->parse_nl2br   = 1;
		$bbcode->parse_smilies = 1;
		
		//-----------------------------------------
		// Converting from STD to RTE?
		//-----------------------------------------
		
		if ( $to_rte )
		{
			$debug_string = "\n====================================================".
							"\n TO RTE ".$this->ipsclass->browser['browser'].
							"\n " . gmdate( 'r' ) .
							"\n====================================================".
							"\nORIGINAL POST\n".$post;
			
			//-----------------------------------------
			// Ensure no slashy slashy
			//-----------------------------------------

			$post = str_replace( '"','&quot;', $post );
			$post = str_replace( "'",'&apos;', $post );

			//-----------------------------------------
			// Convert <>
			//-----------------------------------------

			$post = str_replace( '<', '&lt;', $post );
		    $post = str_replace( '>', '&gt;', $post );
		    
			$debug_string .= "\n====================================================".
							"\n BEFORE clean_ipb_html / pre_db_parse".
							"\n".$post;
							"\n====================================================";
											
			$post = $bbcode->clean_ipb_html( $bbcode->pre_db_parse( $post ) );
			
			//-----------------------------------------
			// Convert <>
			//-----------------------------------------
			
			$post = str_replace( '&#60;', '&lt;', $post );
		    $post = str_replace( '&#62;', '&gt;', $post );
			
			$debug_string .= "\n====================================================".
							"\n AFTER clean_ipb_html / pre_db_parse".
							"\n".$post;
							"\n".$bbcode->test;
							"\n====================================================";
			$debug_string .= "\n====================================================".
							"\n BBCODE Debug".
							"\n".$bbcode->test;
							"\n====================================================";
							
			//-----------------------------------------
			// Fix up...
			//-----------------------------------------
			
			if ( is_array( $this->ipsclass->skin['_macros'] ) and count( $this->ipsclass->skin['_macros'] ) )
			{
				foreach( $this->ipsclass->skin['_macros'] as $row )
		      	{
					if ( $row['macro_value'] != "" )
					{
						$post = str_replace( "<{".$row['macro_value']."}>", $row['macro_replace'], $post );
					}
				}
			}
		
			$post = str_replace( "<#IMG_DIR#>", $this->ipsclass->skin['_imagedir'], $post );
			$post = str_replace( "<#EMO_DIR#>", $this->ipsclass->skin['_emodir']  , $post );
			
			if ( $this->ipsclass->vars['ipb_img_url'] )
			{
				$post = preg_replace( "#img\s+?src=([\"'])style_(images|avatars|emoticons)(.+?)[\"'](.+?)?".">#is", "img src=\\1".$this->ipsclass->vars['ipb_img_url']."style_\\2\\3\\1\\4>", $post );
			}
			
			//-----------------------------------------
			// Make sure no nasty HTML entities show
			//-----------------------------------------
			
			$post = $this->ipsclass->txt_htmlspecialchars( $post );
			
			$debug_string .= "\n====================================================".
							 "\nSTD TO RTE CONVERTED POST\n".$post.
							 "\n====================================================";
			
		}
		//-----------------------------------------
		// Converting from RTE to STD
		//-----------------------------------------
		
		else
		{
			$debug_string = "\n====================================================".
							"\n TO STD ".$this->ipsclass->browser['browser'].
							"\n " . gmdate( 'r' ) .
							"\n====================================================".
							"\nORIGINAL POST\n".$post;
							
			require_once( ROOT_PATH . 'sources/classes/editor/class_editor.php' );
			require_once( ROOT_PATH . 'sources/classes/editor/class_editor_rte.php' );
			
			//-----------------------------------------
			// Ok, now, apparently, for SOME reason, IE
			// strips SOME comments but not others...
			// this causes font/span stuff to break.
			// Let's just strip all the comments
			//-----------------------------------------
			
			if ( $this->ipsclass->browser['browser'] == "ie" OR $this->ipsclass->browser['browser'] == "opera" )
			{
				$post = preg_replace( "#<\!--(.*?)-->#is", ""  , $post );
			}
			
			//-----------------------------------------
			// IE has this thing where <b>[center]text[/center]</b>
			// Becomes [b][center][b]text[/b][/center][/b]
			//-----------------------------------------
			
			$rte           =  new class_editor_module();
			$rte->ipsclass =& $this->ipsclass;
			$post		   = $bbcode->pre_edit_parse( $post );
			
			$debug_string .= "\n====================================================".
							"\n AFTER PRE_EDIT_PARSE".
							"\n " . gmdate( 'r' ) .
							"\n====================================================".
							"\nPRE_EDIT POST\n".$post;
										
			$post          =  $rte->_rte_html_to_bbcode( $post );
			
			//-----------------------------------------
			// Fix up...
			//-----------------------------------------
			
			$post = preg_replace( "/<br>|<br \/>/", "\n", $post );
			
			$debug_string .= "\n====================================================".
							 "\nRTE TO STD CONVERTED POST\n".$post.
							 "\n====================================================";
		}
		
		if ( $_debug )
		{
			$FH = @fopen( ROOT_PATH . '/cache/debug_bbcode.php', 'a+' );
			@fwrite( $FH, $debug_string );
			@fclose( $FH );
		}
		
		//-----------------------------------------
		// Member? Store choice...
		//-----------------------------------------
		
		if ( $this->ipsclass->member['id'] )
		{
			$_choice = ( $to_rte ) ? 'rte' : 'std';
			
			$this->ipsclass->DB->do_update( 'members', array( 'members_editor_choice' => $_choice ), 'id='.$this->ipsclass->member['id'] );
		}
		
		$post   = trim( html_entity_decode($post) );
		
		$this->class_ajax->print_nocache_headers();
		header( "Content-Type: text/html;charset={$this->ipsclass->vars['gb_char_set']}" );
		print $post;
		exit();
	}
    
    /*-------------------------------------------------------------------------*/
	// Save Topic Rating
	/*-------------------------------------------------------------------------*/
      
    function save_topic_rate()
    {
    	//-----------------------------------------
    	// INIT
    	//-----------------------------------------
    	
		$ratingid  = intval($this->ipsclass->input['rating']);
		$ratingid  = $ratingid > 5 ? 5 : $ratingid;
		$ratingid  = $ratingid < 0 ? 0 : $ratingid;
		$topicid   = intval($this->ipsclass->input['t']);
		$vote_cast = array();
		    	
    	//-----------------------------------------
    	// Check
    	//-----------------------------------------
    	
    	if ( !$topicid )
    	{
			$this->class_ajax->print_nocache_headers();
			$this->class_ajax->return_string( 'error' );
    	}
    	
    	$topic = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*', 'from' => 'topics', 'where' => 'tid='.$topicid ) );
    	
    	if ( ! $topic['forum_id'] )
    	{
			$this->class_ajax->print_nocache_headers();
			$this->class_ajax->return_string( 'error' );
    	}
    	
    	if ( ! $this->ipsclass->forums->forum_by_id[ $topic['forum_id'] ]['forum_allow_rating'] )
    	{
			$this->class_ajax->print_nocache_headers();
			$this->class_ajax->return_string( 'no_permission' );
		}
		
    	if ( $topic['state'] != 'open' )
    	{
			$this->class_ajax->print_nocache_headers();
			$this->class_ajax->return_string( 'no_permission' );
		}
		
		if ( intval($this->ipsclass->member['g_topic_rate_setting']) == 0 )
		{
			$this->class_ajax->print_nocache_headers();
			$this->class_ajax->return_string( 'no_permission' );
		}
			
    	//-----------------------------------------
    	// Have we already rated?
    	//-----------------------------------------
    			
		$rating = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*', 'from' => 'topic_ratings', 'where' => "rating_tid={$topicid} and rating_member_id=".$this->ipsclass->member['id'] ) );
    	
		//-----------------------------------------
		// Still here?  Load topic 'stuff'
		//-----------------------------------------
		
		#$this->ipsclass->load_template( 'skin_topic' );
		#$this->ipsclass->load_language( 'lang_topic' );
				
		//-----------------------------------------
		// Already rated?
		//-----------------------------------------
		
		if ( $rating['rating_id'] )
		{
			//-----------------------------------------
			// Do we allow re-ratings?
			//-----------------------------------------
			
			if ( $this->ipsclass->member['g_topic_rate_setting'] == 2 )
			{
				if ( $ratingid != $rating['rating_value'] )
				{
					$new_rating = $ratingid - $rating['rating_value'];
					
					$this->ipsclass->DB->do_update( 'topic_ratings', array( 'rating_value' => $ratingid ), 'rating_id='.$rating['rating_id'] );
					
					$this->ipsclass->DB->do_update( 'topics', array( 'topic_rating_total' => intval($topic['topic_rating_total']) + $new_rating ), 'tid='.$topicid );
					
					$topic['topic_rating_total'] = intval($topic['topic_rating_total']) + $new_rating;
				}
				
				$type = 'update';
			}
			else
			{
				$ratingid = $rating['rating_value'];
			}
		}
		
		//-----------------------------------------
		// NEW RATING!
		//-----------------------------------------
		
		else
		{
			$topic['topic_rating_hits']  = intval($topic['topic_rating_hits'])  + 1;
			$topic['topic_rating_total'] = intval($topic['topic_rating_total']) + $ratingid;
			
			$this->ipsclass->DB->do_insert( 'topic_ratings', array( 'rating_tid'        => $topic['tid'],
																    'rating_member_id'  => $this->ipsclass->member['id'],
																    'rating_value'      => $ratingid,
																    'rating_ip_address' => $this->ipsclass->ip_address ) );
																    
			$this->ipsclass->DB->do_update( 'topics', array( 'topic_rating_hits'  => $topic['topic_rating_hits'],
															 'topic_rating_total' => $topic['topic_rating_total'] ), 'tid='.$topic['tid'] );
															
			$type = 'new';
		}
    	
		$topic['_rate_int'] = round( $topic['topic_rating_total'] / $topic['topic_rating_hits'] );
		
		$this->class_ajax->print_nocache_headers();
		$this->class_ajax->return_string( $topic['topic_rating_total'].','.$topic['topic_rating_hits'].','.$topic['_rate_int'].','.$type );
    }    
    
    /*-------------------------------------------------------------------------*/
	// Check email address
	/*-------------------------------------------------------------------------*/
      
    function check_email_address()
    {
    	//-----------------------------------------
    	// INIT
    	//-----------------------------------------
    	
    	$email = $this->ipsclass->input['email'];
    	
        //-----------------------------------------
    	// Load handler...
    	//-----------------------------------------
    	
    	require_once( ROOT_PATH.'sources/handlers/han_login.php' );
    	$this->han_login           =  new han_login();
    	$this->han_login->ipsclass =& $this->ipsclass;
    	$this->han_login->init();
    	$this->han_login->email_exists_check( $email );
    	
    	if( $this->han_login->return_code != 'METHOD_NOT_DEFINED' AND $this->han_login->return_code != 'EMAIL_NOT_IN_USE' )
    	{
	    	$this->class_ajax->return_string('found');
    	}

    	//-----------------------------------------
    	// Try query...
    	//-----------------------------------------
    	
    	$this->ipsclass->DB->build_query( array( 'select' => "email,id",
    											 'from'   => 'members',
    											 'where'  => "LOWER(email)='". $this->ipsclass->DB->add_slashes( $email ) . "'",
    											 'limit'  => array( 0,1 ) ) );
    											 
    	$this->ipsclass->DB->exec_query();
    	
    	//-----------------------------------------
    	// Got any results?
    	//-----------------------------------------
    	
    	if ( ! $this->ipsclass->DB->get_num_rows() )
 		{
 			//-----------------------------------------
			// No illegal characters
			//-----------------------------------------
			
			if ( preg_match( "#[\;\#\n\r\*\'\"<>&\%\!\(\)\{\}\[\]\?\\/\s]#", $email) )
			{
				$this->class_ajax->return_string('invalid');
			}
						 		
 			//-----------------------------------------
			// Load ban filters
			//-----------------------------------------
			
			$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'banfilters' ) );
			$this->ipsclass->DB->simple_exec();
			
			while( $r = $this->ipsclass->DB->fetch_row() )
			{
				$banfilters[ $r['ban_type'] ][] = $r['ban_content'];
			}
			
			//-----------------------------------------
			// Are they banned [EMAIL]?
			//-----------------------------------------
			
			if ( is_array( $banfilters['email'] ) and count( $banfilters['email'] ) )
			{
				foreach ( $banfilters['email'] as $memail )
				{
					$memail = str_replace( '\*', '.*' ,  preg_quote($memail, "/") );
					
					if ( preg_match( "/^{$memail}$/", $email ) )
					{
						$this->class_ajax->return_string('banned');
						break;
					}
				}
			}
		
			if( $this->ipsclass->clean_email( $email ) )
			{
    			$this->class_ajax->return_string('notfound');
			}
			else
			{
				$this->class_ajax->return_string('invalid');
			}
    	}
    	else
    	{
    		$this->class_ajax->return_string('found');
    	}
    }
    
    /*-------------------------------------------------------------------------*/
	// Check display name
	/*-------------------------------------------------------------------------*/
      
    function check_display_name( $field='members_display_name' )
    {
    	//-----------------------------------------
    	// INIT
    	//-----------------------------------------
    	
    	$name = strtolower( $this->class_ajax->convert_and_make_safe( $this->ipsclass->input['name'], 0 ) );
    	$name = str_replace("&#43;", "+", $name );
    	
    	$id   = intval( $this->ipsclass->input['id'] );
    	
    	# Set member ID
    	$id   = $this->ipsclass->member['id'] ? $this->ipsclass->member['id'] : $id;
    	
    	//-----------------------------------------
		// Remove 'sneaky' spaces
		//-----------------------------------------
		
		if ( $this->ipsclass->vars['strip_space_chr'] )
    	{
    		// Use hexdec to convert between '0xAD' and chr
			$name = str_replace( chr(160), ' ', $name );
			$name = str_replace( chr(173), ' ', $name );
			
			$name = trim($name);
		}
		
    	//-----------------------------------------
    	// Not allowed to select another's log in name
    	//-----------------------------------------
    	
    	if ( $field == 'members_display_name' AND $this->ipsclass->vars['auth_dnames_nologinname'] )
    	{ 
    		$check_name = $this->ipsclass->DB->build_and_exec_query( array( 'select' => "{$field}, id",
																			'from'   => 'members',
																			'where'  => "members_l_username='". $this->ipsclass->DB->add_slashes( $name ) . "'",
																			'limit'  => array( 0,1 ) ) );
    											 
    		if ( $this->ipsclass->DB->get_num_rows() )
    		{
    			if ( $id AND $check_name['id'] == $id )
    			{
    				$this->class_ajax->return_string('notfound');
					return;
				}
				else
				{
					$this->class_ajax->return_string('found');
					return;
				}
			}
    	}
    	
    	if ( $field == 'name' AND $this->ipsclass->vars['auth_dnames_nologinname'] )
    	{ 
    		$check_name = $this->ipsclass->DB->build_and_exec_query( array( 'select' => "{$field}, id",
																			'from'   => 'members',
																			'where'  => "members_l_display_name='". $this->ipsclass->DB->add_slashes( $name ) . "'",
																			'limit'  => array( 0,1 ) ) );
    											 
    		if ( $this->ipsclass->DB->get_num_rows() )
    		{
    			if ( $id AND $check_name['id'] == $id )
    			{
    				$this->class_ajax->return_string('notfound');
				}
				else
				{
					$this->class_ajax->return_string('found');
				}
			}
    	}    	
    	
    	//-----------------------------------------
    	// Not allowed to select another's name
    	//-----------------------------------------
    	
    	if( $field == 'members_display_name' )
    	{
	    	$check_field = 'members_l_display_name';
    	}
    	else
    	{
	    	$check_field = 'members_l_username';
    	}
    	
    	$check_name = $this->ipsclass->DB->build_and_exec_query( array( 'select' => "{$field}, id",
																		'from'   => 'members',
																		'where'  => "{$check_field}='{$name}'",
																		'limit'  => array( 0,1 ) ) );
    											 
    	if ( $this->ipsclass->DB->get_num_rows() )
		{ 
			if ( $id AND $check_name['id'] == $id )
			{
				$this->class_ajax->return_string('notfound');
			}
			else
			{
				$this->class_ajax->return_string('found');
			}
		}
    	
    	//-----------------------------------------
    	// Didn't get a result from the DB
    	//-----------------------------------------
    	
    	else
 		{
 			//-----------------------------------------
			// Load ban filters
			//-----------------------------------------
			
			$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'banfilters' ) );
			$this->ipsclass->DB->simple_exec();
			
			while( $r = $this->ipsclass->DB->fetch_row() )
			{
				$banfilters[ $r['ban_type'] ][] = $r['ban_content'];
			}
			
			//-----------------------------------------
			// Are they banned [NAMES]?
			//-----------------------------------------
			
			if ( is_array( $banfilters['name'] ) and count( $banfilters['name'] ) )
			{
				foreach ( $banfilters['name'] as $n )
				{
					if ( $n == "" )
					{
						continue;
					}
					
					$n = str_replace( '\*', '.*' ,  preg_quote($n, "/") );
				
					if ( preg_match( "/^{$n}$/i", $name ) )
					{
						$this->class_ajax->return_string('found');
						break;
					}
				}
			}
		
    		$this->class_ajax->return_string('notfound');
    	}
    }
    
    /*-------------------------------------------------------------------------*/
	// Post Edit Save
	/*-------------------------------------------------------------------------*/
      
    function post_edit_save()
    {
    	//-----------------------------------------
    	// INIT
    	//-----------------------------------------
    	
    	$pid           = intval( $this->ipsclass->input['p'] );
    	$fid           = intval( $this->ipsclass->input['f'] );
    	$tid           = intval( $this->ipsclass->input['t'] );
    	$md5_check     = substr( $this->ipsclass->input['md5check'], 0, 32 );
    	$attach_pids   = array();

   		$_POST['Post'] = $this->class_ajax->convert_unicode( $_POST['Post'] );
   		
		$_POST['Post'] = $this->class_ajax->convert_html_entities( $_POST['Post'] );
		
   		$this->ipsclass->input['post_edit_reason'] = $this->class_ajax->convert_and_make_safe( $this->ipsclass->input['post_edit_reason'], 0 );
   		
   		//-----------------------------------------
    	// Set things right
    	//-----------------------------------------
    	
    	$_POST['std_used']             = 1;
    	$this->ipsclass->input['Post'] = $this->ipsclass->parse_clean_value( $_POST['Post'] );
    	
    	//-----------------------------------------
    	// Check MD5
    	//-----------------------------------------
    	
    	if (  $md5_check != $this->ipsclass->return_md5_check() )
    	{
    		$this->class_ajax->print_nocache_headers();
			$this->class_ajax->return_string( 'error' );
		}
    	
    	//-----------------------------------------
    	// Check P|T|FID
    	//-----------------------------------------
    	
    	if ( ! $pid OR ! $tid OR ! $fid )
    	{
    		$this->class_ajax->print_nocache_headers();
			$this->class_ajax->return_string( 'error' );
		}
		
		//-----------------------------------------
		// Load Lang
		//-----------------------------------------
		
		$this->ipsclass->load_language( 'lang_topic' );
		
		//-----------------------------------------
		// Get topic
		//-----------------------------------------
		
		$topic = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*', 'from' => "topics", 'where' => 'tid='.$tid ) );
		
		//-----------------------------------------
		// Got permission?
		//-----------------------------------------
		
		if ( ( $topic['state'] != 'open' ) and ( ! $this->ipsclass->member['g_is_supmod'] ) )
		{
			if ( $this->ipsclass->member['g_post_closed'] != 1 )
			{
				$this->class_ajax->print_nocache_headers();
				$this->class_ajax->return_string( 'nopermission' );
			}
		}
		
		if ( $this->ipsclass->check_perms( $this->ipsclass->forums->forum_by_id[ $fid ]['reply_perms'] ) == FALSE )
		{
			$_ok = 0;
			
			//-----------------------------------------
			// Are we a member who started this topic
			// and are editing the topic's first post?
			//-----------------------------------------
			
			if ( $this->ipsclass->member['id'] )
			{
				if ( $topic['topic_firstpost'] )
				{
					$_post = $this->ipsclass->DB->build_and_exec_query( array( 'select' => 'pid, author_id, topic_id',
																			   'from'   => 'posts',
																			   'where'  => 'pid=' . intval( $topic['topic_firstpost'] ) ) );
																			
					if ( $_post['pid'] AND $_post['topic_id'] == $topic['tid'] AND $_post['author_id'] == $this->ipsclass->member['id'] )
					{
						$_ok = 1;
					}
				}
			}
			
			if ( ! $_ok )
			{
				$this->class_ajax->print_nocache_headers();
				$this->class_ajax->return_string( 'nopermission' );
			}
		}
		
		if ( $this->ipsclass->forums->forum_by_id[ $fid ]['status'] == 0 )
		{
			$this->class_ajax->print_nocache_headers();
			$this->class_ajax->return_string( 'nopermission' );
		}
		
        if ( $this->ipsclass->member['id'] )
        {
        	if ( $this->ipsclass->member['restrict_post'] )
        	{
        		if ( $this->ipsclass->member['restrict_post'] == 1 )
        		{
					$this->class_ajax->print_nocache_headers();
					$this->class_ajax->return_string( 'nopermission' );
        		}
        		
        		$post_arr = $this->ipsclass->hdl_ban_line( $this->ipsclass->member['restrict_post'] );
        		
        		if ( time() >= $post_arr['date_end'] )
        		{
        			//-----------------------------------------
        			// Update this member's profile
        			//-----------------------------------------
        			
					$this->ipsclass->DB->simple_construct( array( 'update' => 'members',
																  'set'    => 'restrict_post=0',
																  'where'  => "id=".intval($this->ipsclass->member['id'])
														)       );
										
					$this->ipsclass->DB->simple_exec();
        		}
        		else
        		{
					$this->class_ajax->print_nocache_headers();
					$this->class_ajax->return_string( 'nopermission' );
        		}
        	}
    	}		
		
		//-----------------------------------------
		// Get classes
		//-----------------------------------------
		
		if ( ! $this->post_init )
		{
			require_once( ROOT_PATH."sources/classes/post/class_post.php" );
			require_once( ROOT_PATH."sources/classes/post/class_post_edit.php" );
		
			$this->post           =  new post_functions();
			$this->post->ipsclass =& $this->ipsclass;
			$this->post->forum    =  $this->ipsclass->forums->forum_by_id[ $fid ];
			$this->post->main_init();
			$this->post_init      = 1;
		}
		
		//-----------------------------------------
		// Make sure smilies are parsed
		//-----------------------------------------
		
		$this->ipsclass->input['enableemo']       = $this->post->orig_post['use_emo'] ? 'yes' : '';
		$this->ipsclass->input['enablesig']       = $this->post->orig_post['use_sig'] ? 'yes' : '';
		$this->ipsclass->input['post_htmlstatus'] = $this->post->orig_post['post_htmlstate'];
		$this->ipsclass->input['iconid']		  = $this->post->orig_post['icon_id'];
		$this->ipsclass->input['add_edit']		  = ( $this->post->orig_post['append_edit'] OR !$this->ipsclass->member['g_append_edit'] ) ? 1 : 0;
		
		//-----------------------------------------
		// check
		//-----------------------------------------
		
		if ( ! $this->post->orig_post['post'] )
		{
			$this->class_ajax->print_nocache_headers();
			$this->class_ajax->return_string( 'error' );
		}
		
		//-----------------------------------------
		// PARSE
		//-----------------------------------------
		
		# Prevent polls from being edited
		$this->post->can_add_poll = 0;
		
		# Prevent titles from being edited
		$this->post->edit_title   = 0;
		
		$this->post->convert_open_close_times();
		$this->post->post = $this->post->compile_post();
		
		# Errors?
		if ( $this->post->obj['post_errors'] )
		{
			$this->post_edit_show( $this->ipsclass->lang[ $this->post->obj['post_errors'] ] );
		}
		else
		{
			$this->post->save_post();
		}
		
		//-----------------------------------------
		// Prep for display
		//-----------------------------------------	

		//$raw_post = $this->post->show_post_preview( $this->post->post['post'] ) . "\n" . '<!--IBF.ATTACHMENT_'. $pid . '-->';
		$raw_post = $this->post->post['post'];
		
		//-----------------------------------------
		// Showing reason for edit?
		//-----------------------------------------
		
		if ( $this->ipsclass->member['g_is_supmod'] OR  $this->post->moderator['edit_post'] )
		{
			if ( $this->post->post['post_edit_reason'] )
			{
				$raw_post .= "\n<div class='post-edit-reason'>
								{$this->ipsclass->lang['reason_for_edit']}: {$this->post->post['post_edit_reason']}
								</div>";
			}
		}
		
		if ( ! is_object( $this->class_attach ) )
		{
			//-----------------------------------------
			// Grab render attach class
			//-----------------------------------------

			require_once( ROOT_PATH . 'sources/classes/attach/class_attach.php' );
			$this->class_attach           =  new class_attach();
			$this->class_attach->ipsclass =& $this->ipsclass;
		}
	
		$this->ipsclass->load_template( 'skin_topic' );
		$this->class_attach->type  = 'post';
		$this->class_attach->init();
	
		$raw_post = $this->class_attach->render_attachments( $raw_post, array( $pid => $pid ) );
		
		if ( $this->post->post['append_edit'] == 1 AND $this->post->post['edit_time'] AND $this->post->post['edit_name'] )
		{
			$e_time = $this->ipsclass->get_date( $this->post->post['edit_time'] , 'LONG' );
			$raw_post .= "<br /><br /><span class='edit'>".sprintf($this->ipsclass->lang['edited_by'], $this->post->post['edit_name'], $e_time)."</span>";
		}
		
		foreach( $this->ipsclass->skin['_macros'] as $row )
      	{
			if ( $row['macro_value'] != "" )
			{
				$raw_post = str_replace( "<{".$row['macro_value']."}>", $row['macro_replace'], $raw_post );
			}
		}
		
		if ( $this->ipsclass->vars['ipb_img_url'] )
		{
			$raw_post = preg_replace( "#img\s+?src=([\"'])style_(images|avatars|emoticons)(.+?)[\"'](.+?)?".">#is", "img src=\\1".$this->ipsclass->vars['ipb_img_url']."style_\\2\\3\\1\\4>", $raw_post );
		}		
		
		//-----------------------------------------
		// Return plain text
		//-----------------------------------------
		
		$this->class_ajax->print_nocache_headers();
		$this->class_ajax->return_html( $raw_post );
    }
    
    /*-------------------------------------------------------------------------*/
	// Post Edit Show
	/*-------------------------------------------------------------------------*/
      
    function post_edit_show( $error_msg = "" )
    {
    	//-----------------------------------------
    	// INIT
    	//-----------------------------------------
    	
    	$pid         = intval( $this->ipsclass->input['p'] );
    	$fid         = intval( $this->ipsclass->input['f'] );
    	$tid         = intval( $this->ipsclass->input['t'] );
    	$show_reason = 0;

    	//-----------------------------------------
    	// Check P|T|FID
    	//-----------------------------------------
    	
    	if ( ! $pid OR ! $tid OR ! $fid )
    	{
    		$this->class_ajax->print_nocache_headers();
			$this->class_ajax->return_string( 'error' );
		}
		
		//-----------------------------------------
		// Get topic
		//-----------------------------------------
		
		$topic = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*', 'from' => "topics", 'where' => 'tid='.$tid ) );
		
		//-----------------------------------------
		// Got permission?
		//-----------------------------------------
		
		if ( ( $topic['state'] != 'open' ) and ( ! $this->ipsclass->member['g_is_supmod'] ) )
		{
			if ( $this->ipsclass->member['g_post_closed'] != 1 )
			{
				$this->class_ajax->print_nocache_headers();
				$this->class_ajax->return_string( 'nopermission' );
			}
		}
		
		if ( $this->ipsclass->check_perms( $this->ipsclass->forums->forum_by_id[ $fid ]['reply_perms'] ) == FALSE )
		{
			$_ok = 0;
			
			//-----------------------------------------
			// Are we a member who started this topic
			// and are editing the topic's first post?
			//-----------------------------------------
			
			if ( $this->ipsclass->member['id'] )
			{
				if ( $topic['topic_firstpost'] )
				{
					$_post = $this->ipsclass->DB->build_and_exec_query( array( 'select' => 'pid, author_id, topic_id',
																			   'from'   => 'posts',
																			   'where'  => 'pid=' . intval( $topic['topic_firstpost'] ) ) );
																			
					if ( $_post['pid'] AND $_post['topic_id'] == $topic['tid'] AND $_post['author_id'] == $this->ipsclass->member['id'] )
					{
						$_ok = 1;
					}
				}
			}
			
			if ( ! $_ok )
			{
				$this->class_ajax->print_nocache_headers();
				$this->class_ajax->return_string( 'nopermission' );
			}
		}
		
		if ( $this->ipsclass->forums->forum_by_id[ $fid ]['status'] == 0 )
		{
			$this->class_ajax->print_nocache_headers();
			$this->class_ajax->return_string( 'nopermission' );
		}
		
        if ( $this->ipsclass->member['id'] )
        {
        	if ( $this->ipsclass->member['restrict_post'] )
        	{
        		if ( $this->ipsclass->member['restrict_post'] == 1 )
        		{
					$this->class_ajax->print_nocache_headers();
					$this->class_ajax->return_string( 'nopermission' );
        		}
        		
        		$post_arr = $this->ipsclass->hdl_ban_line( $this->ipsclass->member['restrict_post'] );
        		
        		if ( time() >= $post_arr['date_end'] )
        		{
        			//-----------------------------------------
        			// Update this member's profile
        			//-----------------------------------------
        			
					$this->ipsclass->DB->simple_construct( array( 'update' => 'members',
																  'set'    => 'restrict_post=0',
																  'where'  => "id=".intval($this->ipsclass->member['id'])
														)       );
										
					$this->ipsclass->DB->simple_exec();
        		}
        		else
        		{
					$this->class_ajax->print_nocache_headers();
					$this->class_ajax->return_string( 'nopermission' );
        		}
        	}
    	}		

		//-----------------------------------------
		// Get classes
		//-----------------------------------------
		
		if ( ! $this->post_init )
		{
			require_once( ROOT_PATH."sources/classes/post/class_post.php" );
			require_once( ROOT_PATH."sources/classes/post/class_post_edit.php" );
			
			$this->ipsclass->load_language( 'lang_editors' );
			
			$this->post           =  new post_functions();
			$this->post->ipsclass =& $this->ipsclass;
			$this->post->forum    =  $this->ipsclass->forums->forum_by_id[ $fid ];
			$this->post->main_init();
			$this->post_init      = 1;
		}
		
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( ! $this->post->orig_post['post'] OR ! $topic['tid'] )
		{
			$this->class_ajax->print_nocache_headers();
			$this->class_ajax->return_string( 'error' );
		}
		
		//-----------------------------------------
		// Convert and return plain text
		//-----------------------------------------

		$this->post->parser->parse_smilies = $this->post->orig_post['use_emo'];
		$this->post->parser->parse_html    = ( $this->post->orig_post['post_htmlstate'] AND $this->ipsclass->forums->forum_by_id[ $fid ]['use_html'] AND $this->ipsclass->member['g_dohtml'] ) ? 1 : 0;
		$this->post->parser->parse_bbcode  = $this->ipsclass->forums->forum_by_id[ $fid ]['use_ibc'];
		$this->post->parser->parse_nl2br   = (isset($this->post->orig_post['post_htmlstate']) AND $this->post->orig_post['post_htmlstate'] == 2) ? 1 : 0;
		
		if( $this->post->parser->parse_html )
		{
			# Make EMO_DIR safe so the ^> regex works
			$this->post->orig_post['post'] = str_replace( "<#EMO_DIR#>", "&lt;#EMO_DIR&gt;", $this->post->orig_post['post'] );
			
			# New emo
			$this->post->orig_post['post'] = preg_replace( "#(\s)?<([^>]+?)emoid=\"(.+?)\"([^>]*?)".">(\s)?#is", "\\1\\3\\5", $this->post->orig_post['post'] );
			
			# And convert it back again...
			$this->post->orig_post['post'] = str_replace( "&lt;#EMO_DIR&gt;", "<#EMO_DIR#>", $this->post->orig_post['post'] );
			
			$this->post->orig_post['post'] = $this->post->parser->convert_ipb_html_to_html( $this->post->orig_post['post'] );
				
			$this->post->orig_post['post'] = htmlspecialchars( $this->post->orig_post['post'] );
			
			if( $this->post->parser->parse_nl2br )
			{
				$this->post->orig_post['post'] = str_replace( '&lt;br&gt;', "\n", $this->post->orig_post['post'] );
				$this->post->orig_post['post'] = str_replace( '&lt;br /&gt;', "\n", $this->post->orig_post['post'] );
			}
		}
		
		$raw_post = $this->post->parser->pre_edit_parse( $this->post->orig_post['post'] );
		
		//-----------------------------------------
		// Allow to edit the reason?
		//-----------------------------------------
		
		if ( $this->ipsclass->member['g_is_supmod'] )
		{
			$show_reason = 1;
		}
		else
		{
			//-----------------------------------------
			// Load the moderator
			//-----------------------------------------

			if ( $this->ipsclass->member['id'] )
			{
				if ( is_array( $this->post->moderator ) )
				{
					if ( $this->post->moderator['edit_post'] )
					{
						$show_reason = 1;
					}
				}
			}
		}

		//-----------------------------------------
		// Show HTML
		//-----------------------------------------
		
		$html     = $this->ipsclass->compiled_templates['skin_post']->inline_edit_quick_box( $raw_post, $pid, $error_msg, $show_reason, $this->post->orig_post['post_edit_reason'] );
		
		if ( $this->ipsclass->vars['ipb_img_url'] )
		{
			$html = preg_replace( "#img\s+?src=([\"'])style_(images|avatars|emoticons)(.+?)[\"'](.+?)?".">#is", "img src=\\1".$this->ipsclass->vars['ipb_img_url']."style_\\2\\3\\1\\4>", $html );
		}		
		
		$this->class_ajax->return_html( $html );
    }
    
    /*-------------------------------------------------------------------------*/
	// DST Auto correction
	/*-------------------------------------------------------------------------*/
      
    function dst_autocorrection()
    {
    	//-----------------------------------------
    	// INIT
    	//-----------------------------------------
    	
    	$xml       = intval( $this->ipsclass->input['xml'] );
    	$md5_check = substr( $this->ipsclass->input['md5check'], 0, 32 );
    	
    	//-----------------------------------------
    	// Check
    	//-----------------------------------------
    	
    	if (  $md5_check != $this->ipsclass->return_md5_check() )
    	{
    		if ( $xml )
    		{
    			$this->class_ajax->print_nocache_headers();
				$this->class_ajax->return_string( 'error' );
			}
			else
			{
				$this->ipsclass->boink_it( $this->ipsclass->base_url . 'act=usercp&CODE=04&dsterror=1' );
			}
    	}
    	
    	//-----------------------------------------
    	// Already using DST?
    	//-----------------------------------------
    	
    	if ( $this->ipsclass->member['dst_in_use'] == 1 )
    	{
    		$this->ipsclass->DB->do_update( 'members', array( 'dst_in_use' => 0 ), 'id='.$this->ipsclass->member['id'] );
    	}
    	else
    	{
    		$this->ipsclass->DB->do_update( 'members', array( 'dst_in_use' => 1 ), 'id='.$this->ipsclass->member['id'] );
    	}
    	
    	if ( $xml )
		{
			$this->class_ajax->print_nocache_headers();
			$this->class_ajax->return_string( 'success' );
		}
		else
		{
			$this->ipsclass->boink_it( $this->ipsclass->base_url . 'act=idx' );
		}
    }
    
    /*-------------------------------------------------------------------------*/
	// Get new posts
	/*-------------------------------------------------------------------------*/
      
    function get_msg_preview()
    {
    	//-----------------------------------------
    	// INIT
    	//-----------------------------------------
    	
    	$limit     = intval( $this->ipsclass->input['limit'] );
    	
    	//-----------------------------------------
    	// Couldn't be easier....
    	//-----------------------------------------
    	
    	$return = $this->ipsclass->get_new_pm_notification( $limit, TRUE );
    	
    	$this->class_ajax->print_nocache_headers();
    	$this->class_ajax->return_string( $return );
    }
    
    /*-------------------------------------------------------------------------*/
	// Get new posts
	/*-------------------------------------------------------------------------*/
      
    function save_topic()
    {
    	//-----------------------------------------
    	// INIT
    	//-----------------------------------------
    	
    	$name      = $this->class_ajax->convert_and_make_safe( $this->ipsclass->input['name'], 0 );
    	$type      = $this->ipsclass->input['type'];
    	$tid       = intval( $this->ipsclass->input['tid'] );
    	$md5_check = substr( $this->ipsclass->input['md5check'], 0, 32 );
    	$can_edit  = 0;
    	$openclose = '';

		//$name = $this->class_ajax->convert_html_entities( $name );

    	//-----------------------------------------
    	// Checks...
    	//-----------------------------------------
    	
    	if ( ! $tid OR ! $type )
    	{ 
    		$this->class_ajax->return_null('-');
    	}
    	
    	if (  $md5_check != $this->ipsclass->return_md5_check() )
    	{
    		$this->class_ajax->return_null('-');
    	}
    	
    	//-----------------------------------------
    	// Load topic
    	//-----------------------------------------
    	
    	$topic = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*', 'from' => 'topics', 'where' => 'tid='.$tid ) );
    	
    	if ( ! $topic['tid'] )
    	{ 
    		$this->class_ajax->return_null('-');
    	}
    	
    	//-----------------------------------------
    	// Have permission?
    	//-----------------------------------------
    	
    	if ( $this->ipsclass->member['g_is_supmod'] )
    	{
    		$can_edit = 1;
    	}
    	
    	if ( $type == 'openclose' )
    	{
    		if ( $name == 'close' OR $name == 'closed' )
    		{
    			$openclose = 'closed';
    			
    			if ( $this->ipsclass->member['_moderator'][ $topic['forum_id'] ]['close_topic'] )
				{
					$can_edit = 1;
				}
    		}
    		else
    		{
    			$openclose = 'open';
    			
				if ( $this->ipsclass->member['_moderator'][ $topic['forum_id'] ]['open_topic'] )
				{
					$can_edit = 1;
				}
			}
    	}
    	else
    	{
			if ( $this->ipsclass->member['_moderator'][ $topic['forum_id'] ]['edit_topic'] )
			{
				$can_edit = 1;
			}
    	}
    	
    	if ( ! $can_edit )
    	{
    		$this->class_ajax->return_null('0');
    	}
    	
    	//-----------------------------------------
    	// index.php?act=xmlout&do=save-topic&type=title&tid=60591&name=test&md5check=b6417fb0cbd1e24250db348193fdb7c1
    	// Alright, save it!
    	//-----------------------------------------
    	
    	if ( $type == 'desc' )
    	{
    		$this->ipsclass->DB->do_update( 'topics', array( 'description' => $name ), 'tid='.$tid );
    		
			$this->ipsclass->DB->do_insert( 'moderator_logs', array (
												  			'forum_id'    => intval($topic['forum_id']),
												  			'topic_id'    => intval($tid),
												  			'post_id'     => intval($pid),
												  			'member_id'   => $this->ipsclass->member['id'],
												  			'member_name' => $this->ipsclass->member['members_display_name'],
												  			'ip_address'  => $this->ipsclass->input['IP_ADDRESS'],
												 			'http_referer'=> htmlspecialchars($this->ipsclass->my_getenv('HTTP_REFERER')),
												  			'ctime'       => time(),
												  			'topic_title' => $topic['title'],
												  			'action'      => $this->ipsclass->lang['ajax_topicdesc'],
												  			'query_string'=> htmlspecialchars($this->ipsclass->my_getenv('QUERY_STRING')),
											  )  );    		
    	}
    	else if ( $type == 'openclose' )
    	{
    		$this->ipsclass->DB->do_update( 'topics', array( 'state' => $openclose ), 'tid='.$tid );
    		
			$this->ipsclass->DB->do_insert( 'moderator_logs', array (
												  			'forum_id'    => intval($topic['forum_id']),
												  			'topic_id'    => intval($tid),
												  			'post_id'     => intval($pid),
												  			'member_id'   => $this->ipsclass->member['id'],
												  			'member_name' => $this->ipsclass->member['members_display_name'],
												  			'ip_address'  => $this->ipsclass->input['IP_ADDRESS'],
												 			'http_referer'=> htmlspecialchars($this->ipsclass->my_getenv('HTTP_REFERER')),
												  			'ctime'       => time(),
												  			'topic_title' => $topic['title'],
												  			'action'      => $this->ipsclass->lang['ajax_topicstate'],
												  			'query_string'=> htmlspecialchars($this->ipsclass->my_getenv('QUERY_STRING')),
											  )  );
			
			$topic['state'] = $openclose;
			
			$last_time = $this->ipsclass->input['last_visit'];

			if ( isset($this->ipsclass->forum_read[ $topic['forum_id'] ]) AND $this->ipsclass->forum_read[ $topic['forum_id'] ] > $last_time )
			{
				$last_time = $this->ipsclass->forum_read[ $topic['forum_id'] ];
			}
			
			$macro = $this->ipsclass->folder_icon( $topic, '', $last_time );
		
	      	if ( is_array( $this->ipsclass->skin['_macros'] ) )
	      	{
				foreach( $this->ipsclass->skin['_macros'] as $row )
				{
					$macro = str_replace( '<{'.$row['macro_value'].'}>', $row['macro_replace'], $macro );
				}
			}
			
			$macro = str_replace( "<#IMG_DIR#>", $this->ipsclass->skin['_imagedir'], $macro );
			$macro = str_replace( "<#EMO_DIR#>", $this->ipsclass->skin['_emodir']  , $macro );
			
			if ( $this->ipsclass->vars['ipb_img_url'] )
			{
				$macro = preg_replace( "#img\s+?src=([\"'])style_(images|avatars|emoticons)(.+?)[\"'](.+?)?".">#is", "img src=\\1".$this->ipsclass->vars['ipb_img_url']."style_\\2\\3\\1\\4>", $macro );
			}			
				
			$this->class_ajax->return_string( $macro );
    	}
    	else
    	{
	    	if( trim($name) == '' OR !$name )
	    	{
		    	$this->class_ajax->return_string($topic['title']);
		    	exit();
	    	}
	    	
			if ($this->ipsclass->vars['etfilter_punct'])
			{
				$name	= preg_replace( "/\?{1,}/"      , "?"    , $name );		
				$name	= preg_replace( "/(&#33;){1,}/" , "&#33;", $name );
			}
			
			if ($this->ipsclass->vars['etfilter_shout'])
			{
				$name = ucwords(strtolower($name));
			}
	    	
    		$this->ipsclass->DB->do_update( 'topics', array( 'title' => $name ), 'tid='.$tid );
    		
			$this->ipsclass->DB->do_insert( 'moderator_logs', array (
												  			'forum_id'    => intval($topic['forum_id']),
												  			'topic_id'    => intval($tid),
												  			'post_id'     => intval($pid),
												  			'member_id'   => $this->ipsclass->member['id'],
												  			'member_name' => $this->ipsclass->member['members_display_name'],
												  			'ip_address'  => $this->ipsclass->input['IP_ADDRESS'],
												 			'http_referer'=> htmlspecialchars($this->ipsclass->my_getenv('HTTP_REFERER')),
												  			'ctime'       => time(),
												  			'topic_title' => $name,
												  			'action'      => sprintf($this->ipsclass->lang['ajax_topictitle'],$topic['title'],$name),
												  			'query_string'=> htmlspecialchars($this->ipsclass->my_getenv('QUERY_STRING')),
											  )  );    		
    		
    		if ( $topic['tid'] == $this->ipsclass->forums->forum_by_id[ $topic['forum_id'] ]['last_id'] )
			{
				$this->ipsclass->DB->do_update( 'forums', array( 'last_title' => $name ), 'id='.$topic['forum_id'] );
			}
			
			if ( $topic['tid'] == $this->ipsclass->forums->forum_by_id[ $topic['forum_id'] ]['newest_id'] )
			{
				$this->ipsclass->DB->do_update( 'forums', array( 'newest_title' => $name ), 'id='.$topic['forum_id'] );
			}	
							
			$this->ipsclass->update_forum_cache();
			
			$this->class_ajax->return_string($name);
    	}
   		
    	$this->class_ajax->return_null('s');
    }
    
    /*-------------------------------------------------------------------------*/
	// Get member names
	/*-------------------------------------------------------------------------*/
      
    function get_member_names()
    {
    	//-----------------------------------------
    	// INIT
    	//-----------------------------------------
    	
    	$name = $this->class_ajax->convert_and_make_safe( $this->ipsclass->input['name'], 0 );
    	
    	//--------------------------------------------
		// Load extra db cache file
		//--------------------------------------------
		
		$this->ipsclass->DB->load_cache_file( ROOT_PATH.'sources/sql/'.SQL_DRIVER.'_extra_queries.php', 'sql_extra_queries' );
		
    	//-----------------------------------------
    	// Check length
    	//-----------------------------------------
    	
    	if ( strlen( $name ) < 3 )
    	{
    		$this->class_ajax->return_null();
    	}
    	
    	//-----------------------------------------
    	// Try query...
    	//-----------------------------------------
    	
    	$this->ipsclass->DB->cache_add_query( 'member_display_name_lookup', array( 'name' => $this->ipsclass->DB->add_slashes( $name ), 'field' => 'members_display_name' ), 'sql_extra_queries' );
		$this->ipsclass->DB->cache_exec_query();
		
    	//-----------------------------------------
    	// Got any results?
    	//-----------------------------------------
    	
    	if ( ! $this->ipsclass->DB->get_num_rows() )
 		{
    		$this->class_ajax->return_null();
    	}
    	
    	$names = array();
		$ids   = array();
		
		while( $r = $this->ipsclass->DB->fetch_row() )
		{
			$names[] = '"'.$r['members_display_name'].'"';
			$ids[]   = intval($r['id']);
		}
		
		$this->class_ajax->print_nocache_headers();
		@header( "Content-type: text/plain;charset={$this->ipsclass->vars['gb_char_set']}" );
		print "returnSearch( '".str_replace( "'", "", $name )."', new Array(".implode( ",", $names )."), new Array(".implode( ",", $ids ).") );";
    }
    
    /*-------------------------------------------------------------------------*/
	// Get new posts
	/*-------------------------------------------------------------------------*/
      
    function get_new_messages( $return=0 )
    {
    	//-----------------------------------------
    	// INIT
    	//-----------------------------------------
    	
    	$count = isset($this->ipsclass->input['count']) ? intval( $this->ipsclass->input['count'] ) : 0;
    	$count = $count ? $count : 5;
    	
    	$html  = "";
    	
    	$this->ipsclass->load_template( 'skin_msg' );
    	$this->ipsclass->load_language( 'lang_msg' );
    	
    	//-----------------------------------------
    	// Check...
    	//-----------------------------------------
    	
    	if ( ! $this->ipsclass->member['id'] )
		{
			return $this->class_ajax->error_handler();
		}
		
		//-----------------------------------------
		// Get message list
		//-----------------------------------------
		
		$this->ipsclass->DB->cache_add_query( 'msg_get_folder_list',
											  array( 'mid' => $this->ipsclass->member['id'], 'vid' => 'in', 'sort' => 'mt_date DESC', 'limita' => 0, 'limitb' => $count ) );
 		$this->ipsclass->DB->simple_exec();
 		
 		if ( ! $this->ipsclass->DB->get_num_rows() )
 		{
 			//$this->class_ajax->error_handler("No messages");
 			$html .= $this->ipsclass->compiled_templates['skin_msg']->xmlout_msgs_none();
 		}
 		else
 		{ 		
	 		//-----------------------------------------
	 		// Start output
	 		//-----------------------------------------
	 		
			while( $r = $this->ipsclass->DB->fetch_row() )
			{
				//-----------------------------------------
				// We only want 10 rows, but the 11th will
				// tell us if there are more to come
				//-----------------------------------------
				
				$r['date'] = $this->ipsclass->get_date( $r['mt_date'] , 'TINY', 0, 1 );
				$r['icon'] = $r['mt_read'] == 1 ? "<img src='{$this->ipsclass->vars['img_url']}/f_norm_no.gif' border='0' />"
												: "<img src='{$this->ipsclass->vars['img_url']}/f_norm.gif' border='0' />";
												
				$r['_mini'] = $this->ipsclass->txt_truncate( $r['mt_title'] , 30 );
				
				$html .= $this->ipsclass->compiled_templates['skin_msg']->xmlout_msgs_row( $r );
			}
		}
		
		//-----------------------------------------
		// Check count...
		//-----------------------------------------
		
		if ( ! $return )
		{
			$this->class_ajax->print_nocache_headers();
			$this->class_ajax->return_string( $this->ipsclass->compiled_templates['skin_msg']->xmlout_msgs_wrapper( $html ) );
		}
		else
		{
			return $this->ipsclass->compiled_templates['skin_msg']->xmlout_msgs_wrapper( $html );
		}
	}
	
    
    /*-------------------------------------------------------------------------*/
	// Get new posts
	/*-------------------------------------------------------------------------*/
      
    function mark_forum_as_read()
    {
    	//-----------------------------------------
    	// INIT
    	//-----------------------------------------
    	
    	$forum_id 	= intval( $this->ipsclass->input['fid'] );
    	$sf       	= intval( $this->ipsclass->input['sf'] );
        $forum_data = $this->ipsclass->forums->forum_by_id[ $forum_id ];
        $children   = $this->ipsclass->forums->forums_get_children( $forum_data['id'] );
        $save       = array();
    	
    	//-----------------------------------------
    	// Check...
    	//-----------------------------------------
    	
    	/*if ( ! $this->ipsclass->member['id'] )
		{
			return $this->class_ajax->error_handler();
		}*/
		
		if ( ! $forum_id )
		{
			return $this->class_ajax->error_handler();
		}
		
		//-----------------------------------------
		// OK - do the magic!!
		//-----------------------------------------
		
        if ( $sf )
        {
			if ( isset($children) AND is_array($children) and count($children) )
			{
				foreach( $children as $id )
				{
					$this->ipsclass->forum_read[ $id ] = time();
					
					$save[ $id ] = array( 'marker_forum_id'     => $id,
										  'marker_member_id'    => $this->ipsclass->member['id'],
										  'marker_last_update'  => time(),
										  'marker_unread'       => 0,
										  'marker_last_cleared' => time() );
				}
			}
        }
        
        //-----------------------------------------
        // Add in the current forum...
        //-----------------------------------------
        
        $this->ipsclass->forum_read[ $forum_data['id'] ] = time();
        
		$save[ $forum_data['id'] ] = array( 'marker_forum_id'     => $forum_data['id'],
											'marker_member_id'    => $this->ipsclass->member['id'],
											'marker_last_update'  => time(),
											'marker_unread'       => 0,
											'marker_last_cleared' => time() );
        
        //-----------------------------------------
        // Reset topic markers
        //-----------------------------------------
        
        if ( $this->ipsclass->vars['db_topic_read_cutoff'] and $this->ipsclass->member['id'] )
        {
        	if ( count( $save ) )
        	{
        		foreach( $save as $data )
        		{
	        		$this->ipsclass->DB->do_replace_into( 'topic_markers', $data, array('marker_member_id','marker_forum_id'), TRUE );
        		}
        	}
        }
		
		//-----------------------------------------
		// Reset cookie
		//-----------------------------------------
		
		$this->ipsclass->no_print_header = 0;
		$this->ipsclass->hdl_forum_read_cookie('set');
		$this->ipsclass->no_print_header = 1;
				
		$this->class_ajax->print_nocache_headers();
    	$this->class_ajax->return_string( "1" );
    }
    
	/*-------------------------------------------------------------------------*/
	// Get new posts
	/*-------------------------------------------------------------------------*/
      
    function get_new_posts( $return=0 )
    {
    	//-----------------------------------------
    	// INIT
    	//-----------------------------------------
    	
    	$count     = 0;
    	$st        = intval( $this->ipsclass->input['st'] );
    	$next      = 0;
    	$previous  = 0;
    	$html      = '';
    	
    	$this->ipsclass->load_template( 'skin_search' );
    	$this->ipsclass->load_language( 'lang_search' );
    	
    	//-----------------------------------------
    	// Check...
    	//-----------------------------------------
    	
    	if ( ! $this->ipsclass->member['id'] )
		{
			return $this->class_ajax->error_handler();
		}
		
		//-----------------------------------------
		// Get search lib
		//-----------------------------------------
		
		require_once( ROOT_PATH.'sources/action_public/search.php' );
		$this->search = new search();
		$this->search->ipsclass =& $this->ipsclass;
		
		//-----------------------------------------
		// Do we have any forums to search in?
		//-----------------------------------------
		
		$this->ipsclass->input['forums'] = 'all';
		$this->ipsclass->input['CODE']  = 'getnew';
		 
		$forums = $this->search->get_searchable_forums();
		
		if ( $forums == "" )
		{
			return $this->class_ajax->error_handler();
		}
		
		//-----------------------------------------
		// Work out the "last time"
		//-----------------------------------------
		
		$last_time = $this->ipsclass->member['last_visit'];
		
		if ( $this->ipsclass->member['members_markers']['board'] > $last_time )
		{
			$last_time = $this->ipsclass->member['members_markers']['board'];
		}
		
		//-----------------------------------------
		// Get query
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 't.*, t.title as topic_title',
													  'from'   => 'topics t',
													  'where'  => "t.forum_id IN($forums) AND t.last_post > {$last_time} AND t.approved=1 AND t.pinned IN (0,1)",
													  'order'  => "t.last_post DESC",
													  'limit'  => array( $st, 11 ) ) );
		$this->ipsclass->DB->simple_exec();
		
		while( $r = $this->ipsclass->DB->fetch_row() )
		{
			//-----------------------------------------
			// We only want 10 rows, but the 11th will
			// tell us if there are more to come
			//-----------------------------------------
			
			$count++;
			
			if ( $count == 11 )
			{
				break;
			}
			
			$mini_title = $this->ipsclass->txt_truncate( $r['title'] , 30 );
			$mini_name  = $this->ipsclass->txt_truncate( $r['last_poster_name'] , 25 );
			$date       = $this->ipsclass->get_date( $r['last_post'], 'TINY', 0, 1 );
			
			$html .= $this->ipsclass->compiled_templates['skin_search']->xmlout_gnp_row($r['tid'], $mini_title, $r['title'], $date );
		}
		
		//-----------------------------------------
		// Check count...
		//-----------------------------------------
		
		if ( $count )
		{
			if ( $count > 10 )
			{
				$next = $st + 10;
			}
			
			if ( $st )
			{
				$previous = $st - 10;
			}
		}
		else
		{
			$html = "<center><em>{$this->ipsclass->lang['xml_nopost']}</em></center>";
		}
		
		if ( ! $return )
		{
			$this->class_ajax->print_nocache_headers();
			$this->class_ajax->return_string( $this->ipsclass->compiled_templates['skin_search']->xmlout_gnp_wrapper( $html, $previous, $next, $st ) );
		}
		else
		{
			return $this->ipsclass->compiled_templates['skin_search']->xmlout_gnp_wrapper( $html, $previous, $next, $st );
		}
	}
 	
 	/*-------------------------------------------------------------------------*/
 	// make string XML safe
 	/*-------------------------------------------------------------------------*/
 	
 	function _make_safe_for_xml( $t )
 	{
 		return $this->class_ajax->_make_safe_for_xml($t);
 	}
			
	/*-------------------------------------------------------------------------*/
    // Error handler
    /*-------------------------------------------------------------------------*/
    
    function error_handler()
    {
    	return $this->class_ajax->return_null();
    }
    
    /*-------------------------------------------------------------------------*/
    // Return NOTHING :o
    /*-------------------------------------------------------------------------*/
    
    function return_null($val=0)
    {
    	return $this->class_ajax->return_null($val);
    }
    
    /*-------------------------------------------------------------------------*/
    // Return string
    /*-------------------------------------------------------------------------*/
    
    function return_string($string)
    {
    	return $this->class_ajax->return_string($string);
    }
    
    /*-------------------------------------------------------------------------*/
    // Print no cache headers
    /*-------------------------------------------------------------------------*/
    
    function print_nocache_headers()
    {
    	return $this->class_ajax->print_nocache_headers();
	}
		
	/*-------------------------------------------------------------------------*/
	// Convert Ajax unicode
	/*-------------------------------------------------------------------------*/
	
	function convert_unicode($t)
	{
		return $this->class_ajax->convert_unicode($t);
	}
	
	function convert_html_entities($t)
	{
		return $this->class_ajax->convert_html_entities($t);
	}

        
}

?>