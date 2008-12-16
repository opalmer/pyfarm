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
|   > $Date: 2007-09-26 16:50:00 -0400 (Wed, 26 Sep 2007) $
|   > $Revision: 1113 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > Warning Module
|   > Module written by Matt Mecham
|   > Date started: 16th May 2003
|
|	> Module Version Number: 1.0.0
|   > DBA Checked: Mon 24th May 2004
+--------------------------------------------------------------------------
*/


if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class warn {

    var $output    = "";
    var $nav;
    var $page_title = "";
    var $topic     = array();
    var $forum     = array();
    var $topic_id  = "";
    var $forum_id  = "";
    var $moderator = "";
    var $modfunc   = "";
    var $mm_data   = "";
    
    var $parser;
    var $han_editor;
    var $email;
    
    var $can_ban      = 0;
    var $can_mod_q    = 0;
    var $can_rem_post = 0;
    var $times_a_day  = 0;
    var $type         = 'mod';
    
    var $warn_member  = "";
    
    //-----------------------------------------
	// @constructor (no, not bob the builder)
	//-----------------------------------------
    
    function auto_run()
    {
		//-----------------------------------------
        // Load modules...
        //-----------------------------------------
        
        $this->ipsclass->load_language('lang_mod');
 		$this->ipsclass->load_template('skin_mod');
        
        //-----------------------------------------
        // Load and config the post parser
        //-----------------------------------------
        
        require_once( ROOT_PATH."sources/handlers/han_parse_bbcode.php" );
        $this->parser                      =  new parse_bbcode();
        $this->parser->ipsclass            =& $this->ipsclass;
        $this->parser->allow_update_caches = 1;
        
        $this->parser->bypass_badwords = intval($this->ipsclass->member['g_bypass_badwords']);
        
        //-----------------------------------------
        // Make sure we're a moderator...
        //-----------------------------------------
        
        $pass = 0;
        
        if ($this->ipsclass->member['id'])
        {
        	if ( $this->ipsclass->member['g_access_cp'] )
			{
				$pass               = 1;
				$this->can_ban      = 1;
    			$this->can_mod_q    = 1;
    			$this->can_rem_post = 1;
    			$this->times_a_day  = -1;
				$this->type = 'admin';
			}
        	else if ($this->ipsclass->member['g_is_supmod'] == 1)
        	{
        		$pass               = 1;
        		$this->can_ban      = $this->ipsclass->vars['warn_gmod_ban'];
    			$this->can_mod_q    = $this->ipsclass->vars['warn_gmod_modq'];
    			$this->can_rem_post = $this->ipsclass->vars['warn_gmod_post'];
    			$this->times_a_day  = intval($this->ipsclass->vars['warn_gmod_day']);
    			$this->type         = 'supmod';
        	}
        	else if ( $this->ipsclass->vars['warn_show_own'] and $this->ipsclass->member['id'] == $this->ipsclass->input['mid'] )
        	{
        		$pass               = 1;
        		$this->can_ban      = 0;
    			$this->can_mod_q    = 0;
    			$this->can_rem_post = 0;
    			$this->times_a_day  = 0;
    			$this->type         = 'member';
        	}
        	else if ($this->ipsclass->member['is_mod'])
        	{
				$other_mgroups = array();
				
				if( $this->ipsclass->member['mgroup_others'] )
				{
					$other_mgroups = explode( ",", $this->ipsclass->clean_perm_string( $this->ipsclass->member['mgroup_others'] ) );
				}
				
				$other_mgroups[] = $this->ipsclass->member['mgroup'];
				
				$mgroups = implode( ",", $other_mgroups );
				
        		$this->ipsclass->DB->simple_construct( array( 'select' => '*',
											 				  'from'   => 'moderators',
											  				  'where'  => "(member_id='".$this->ipsclass->member['id']."' OR (is_group=1 AND group_id IN(".$mgroups.") ))" ) );
											  
				$this->ipsclass->DB->simple_exec();
        		
				while ( $this->moderator = $this->ipsclass->DB->fetch_row() )
				{
					if ( $this->moderator['allow_warn'] )
					{
						$pass               = 1;
						$this->can_ban      = $this->ipsclass->vars['warn_mod_ban'];
						$this->can_mod_q    = $this->ipsclass->vars['warn_mod_modq'];
						$this->can_rem_post = $this->ipsclass->vars['warn_mod_post'];
						$this->times_a_day  = intval($this->ipsclass->vars['warn_mod_day']);
						$this->type         = 'mod';
    				}
				}
        	}
        	else
        	{
        		$pass = 0;
        	}
        }
        	
        if ($pass == 0)
        {
        	$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_permission') );
        }
        
        if ( ! $this->ipsclass->vars['warn_on'] )
        {
        	$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_permission') );
        }
        
        //-----------------------------------------
        // Ensure we have a valid member id
        //-----------------------------------------
        
        $mid = intval($this->ipsclass->input['mid']);
        
        if ( $mid < 1 )
        {
        	$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_such_user') );
        }
        
        $this->ipsclass->DB->cache_add_query( 'generic_get_all_member', array( 'mid' => $mid ) );
		$this->ipsclass->DB->cache_exec_query();
		
        $this->warn_member = $this->ipsclass->DB->fetch_row();
        
        if ( ! $this->warn_member['id'] )
        {
        	$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_such_user') );
        }
        
        if ( $this->ipsclass->input['CODE'] == "" OR $this->ipsclass->input['CODE'] == "dowarn" )
        {
			//-----------------------------------------
			// Protected member? Really? o_O
			//-----------------------------------------
			
			if ( strstr( ','.$this->ipsclass->vars['warn_protected'].',', ','.$this->warn_member['mgroup'].',' ) )
			{
				$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'protected_user') );
			}
			
			//-----------------------------------------
			// I've already warned you!!
			//-----------------------------------------
			
			if ( $this->times_a_day > 0 )
			{
				$time_to_check = time() -  86400;
				
				$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'warn_logs', 'where' => "wlog_mid={$this->warn_member['id']} AND wlog_date > $time_to_check" ) );
				$this->ipsclass->DB->simple_exec();
				
				if ( $this->ipsclass->DB->get_num_rows() >= $this->times_a_day )
				{
					$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'warned_already') );
				}
			}
        }
        
        //-----------------------------------------
        // Bouncy, bouncy!
        //-----------------------------------------
		
		switch ($this->ipsclass->input['CODE'])
		{
        	case 'dowarn':
        		$this->do_warn();
        		break;
			
			case 'add_note':
        		$this->add_note_form();
        		break;
        	
			case 'save_note':
				$this->save_note();
				break;
				
        	case 'view':
        		$this->view_log();
        		break;
        	
        	default:
        		$this->show_form();
        		break;
        }
		
		if ( count($this->nav) < 1 )
		{
			$this->nav[] = $this->ipsclass->lang['w_title'];
		}
		
		if (! $this->page_title )
		{
			$this->page_title = $this->ipsclass->lang['w_title'];
		}
    	
    	$this->ipsclass->print->add_output( $this->output );
        $this->ipsclass->print->do_output( array( 'TITLE' => $this->page_title, 'JS' => 1, 'NAV' => $this->nav ) );
	}
	
	/*-------------------------------------------------------------------------*/
	// Save Note Form
	/*-------------------------------------------------------------------------*/
	
	function save_note()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$content = '';
		$note    = trim( $this->ipsclass->input['note'] );
		$save    = array();
		
		//-----------------------------------------
		// Protected member?
		//-----------------------------------------
		
		if ( stristr( $this->ipsclass->vars['warn_protected'], ','.$this->warn_member['mgroup'].',' ) )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'protected_user') );
		}
		
		if ( $note )
		{
			//-----------------------------------------
			// Ready to save?
			//-----------------------------------------
		
			$save['wlog_notes']  = "<content>{$note}</content>";
			$save['wlog_notes'] .= "<mod></mod>";
			$save['wlog_notes'] .= "<post></post>";
			$save['wlog_notes'] .= "<susp></susp>";
		
			$save['wlog_mid']     = $this->warn_member['id'];
			$save['wlog_addedby'] = $this->ipsclass->member['id'];
			$save['wlog_type']    = 'note';
			$save['wlog_date']    = time();
			
			//-----------------------------------------
			// Enter into warn loggy poos (eeew - poo)
			//-----------------------------------------
		
			$this->ipsclass->DB->do_insert( 'warn_logs', $save );
		}
		
		//-----------------------------------------
		// Done...
		//-----------------------------------------
		
		$this->ipsclass->boink_it( $this->ipsclass->base_url."act=warn&amp;mid={$this->warn_member['id']}&amp;CODE=view" );
	}
	
	/*-------------------------------------------------------------------------*/
	// Add Note Form
	/*-------------------------------------------------------------------------*/
	
	function add_note_form()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$content = '';
		
		//-----------------------------------------
		// Protected member?
		//-----------------------------------------
		
		if ( stristr( $this->ipsclass->vars['warn_protected'], ','.$this->warn_member['mgroup'].',' ) )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'protected_user') );
		}
		
		$this->output .= $this->ipsclass->compiled_templates['skin_mod']->warn_add_note_form( $this->warn_member['id'], $this->warn_member['members_display_name'] );
		
		$this->ipsclass->print->pop_up_window( $this->ipsclass->lang['warn_popup_title'], $this->output );
	}
	
	/*-------------------------------------------------------------------------*/
	// Show logs
	/*-------------------------------------------------------------------------*/
	
	function view_log()
	{
		//-----------------------------------------
		// Protected member? Really? o_O
		//-----------------------------------------
		
		if ( stristr( $this->ipsclass->vars['warn_protected'], ','.$this->warn_member['mgroup'].',' ) )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'protected_user') );
		}
		
		$perpage = 50;
		$start   = intval($this->ipsclass->input['st']) >=0 ? intval($this->ipsclass->input['st']) : 0;
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'count(*) as cnt', 'from' => 'warn_logs', 'where' => "wlog_mid={$this->warn_member['id']}" ) );
		$this->ipsclass->DB->simple_exec();
		
		$row = $this->ipsclass->DB->fetch_row();
		
		$links = $this->ipsclass->build_pagelinks( array(
													   'TOTAL_POSS'  => $row['cnt'],
													   'PER_PAGE'    => $perpage,
													   'CUR_ST_VAL'  => $this->ipsclass->input['st'],
													   'L_SINGLE'    => "",
													   'L_MULTI'     => $this->ipsclass->lang['w_v_pages'],
													   'BASE_URL'    => $this->ipsclass->base_url."act=warn&amp;CODE=view&amp;mid={$this->warn_member['id']}",
												 )      );
		
		$this->output .= $this->ipsclass->compiled_templates['skin_mod']->warn_view_header($this->warn_member['id'], $this->warn_member['members_display_name']);
									  
		if ( $row['cnt'] < 1 )
		{
			$this->output .= $this->ipsclass->compiled_templates['skin_mod']->warn_view_none();
		}
		else
		{
			$this->ipsclass->DB->cache_add_query( 'warn_get_data', array( 'mid' => $this->warn_member['id'], 'limit_a' => $start, 'limit_b' => $perpage ) );
			$this->ipsclass->DB->cache_exec_query();
		
			while ( $r = $this->ipsclass->DB->fetch_row() )
			{
				$date = $this->ipsclass->get_date( $r['wlog_date'], 'LONG' );
			
				$raw = preg_match( "#<content>(.+?)</content>#is", $r['wlog_notes'], $match );
				
				$this->parser->parse_smilies = 1;
				$this->parser->parse_html    = 0;
				$this->parser->parse_bbcode  = 1;
		
				$content = $this->parser->pre_display_parse( $this->parser->pre_db_parse( $match[1] ) );
				
				$puni_name = $this->ipsclass->make_profile_link( $r['punisher_name'], $r['punisher_id'] );
				
				if ( $r['wlog_type'] == 'note' )
				{
					$this->output .= $this->ipsclass->compiled_templates['skin_mod']->warn_view_note_row($date, $content, $puni_name);
				}
				else if ( $r['wlog_type'] == 'pos' )
				{
					$this->output .= $this->ipsclass->compiled_templates['skin_mod']->warn_view_positive_row($date, $content, $puni_name);
				}
				else
				{
					$this->output .= $this->ipsclass->compiled_templates['skin_mod']->warn_view_negative_row($date, $content, $puni_name);
				}
			}
		}
		
		$this->output .= $this->ipsclass->compiled_templates['skin_mod']->warn_view_footer($links);
		
		$this->ipsclass->print->pop_up_window( $this->ipsclass->lang['warn_popup_title'], $this->output );
	}
	
	/*-------------------------------------------------------------------------*/
	// Do the actual warny-e-poos
	/*-------------------------------------------------------------------------*/
	
	function do_warn()
	{
		$save = array();
		
		if ( $this->type == 'member' )
        {
        	$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_permission') );
        }
        
        $err = "";
        
        if ( ! $this->ipsclass->vars['warn_past_max'] )
        {
        	$this->ipsclass->vars['warn_min'] = $this->ipsclass->vars['warn_min'] ? $this->ipsclass->vars['warn_min'] : 0;
        	$this->ipsclass->vars['warn_max'] = $this->ipsclass->vars['warn_max'] ? $this->ipsclass->vars['warn_max'] : 10;
        	
			$warn_level = intval($this->warn_member['warn_level']);
			
			if ( $this->ipsclass->input['level'] == 'add' )
			{
				if ( $warn_level >= $this->ipsclass->vars['warn_max'] )
				{
					$err = 1;
				}
			}
			else
			{
				if ( $warn_level <= $this->ipsclass->vars['warn_min'] )
				{
					$err = 1;
				}
			}
			
			if ( $err == 1 )
			{
				$this->ipsclass->Error( array( 'LEVEL' => '1', 'MSG' => 'no_warn_max' ) );
			}
        }
		
		//-----------------------------------------
		// Check security fang
		//-----------------------------------------
		
		if ( $this->ipsclass->input['key'] != $this->ipsclass->return_md5_check() )
		{
			$this->ipsclass->Error( array( 'LEVEL' => '1', 'MSG' => 'del_post') );
		}
		
		//-----------------------------------------
		// As Celine Dion once squawked, "Show me the reason"
		//-----------------------------------------
		
		if ( trim($this->ipsclass->input['reason']) == "" )
		{
			$this->show_form('we_no_reason');
			return;
		}
		
        //-----------------------------------------
        // Load emailer
        //-----------------------------------------
        
        require ROOT_PATH."sources/classes/class_email.php";
		$this->email = new emailer();
		$this->email->ipsclass =& $this->ipsclass;
		$this->email->email_init();	
		
    	//-----------------------------------------
        // Load and config the std/rte editors
        //-----------------------------------------
        
        require_once( ROOT_PATH."sources/handlers/han_editor.php" );
        $this->han_editor           = new han_editor();
        $this->han_editor->ipsclass =& $this->ipsclass;
        $this->han_editor->init();
		
		//-----------------------------------------
		// Plussy - minussy?
		//-----------------------------------------
		
		$save['wlog_type'] = ( $this->ipsclass->input['level'] == 'add' ) ? 'neg' : 'pos';
		$save['wlog_date'] = time();
		
		//-----------------------------------------
		// Contacting the member?
		//-----------------------------------------
		
		// RTE likes to add <br /> when there is nothing
		
		$test_content = $this->ipsclass->my_br2nl( str_replace( "&lt;", "<", str_replace( "&gt;", ">", $this->ipsclass->input['contact'] ) ) );
		$test_content = trim($test_content);
		
		if ( $test_content != "" )
		{
			unset($test_content);
			
			if ( $this->han_editor->method == 'rte' )
			{
				$this->ipsclass->input['contact'] = $this->han_editor->process_raw_post( 'contact' );
			}

			$save['wlog_contact']         = $this->ipsclass->input['contactmethod'];
			$save['wlog_contact_content'] = "<subject>{$this->ipsclass->input['subject']}</subject><content>{$this->ipsclass->input['contact']}</content>";
			$save['wlog_contact_content'] = $this->parser->pre_display_parse( $this->parser->pre_db_parse( $save['wlog_contact_content'] ) );
			
			if ( trim($this->ipsclass->input['subject']) == "" )
			{
				$this->show_form('we_no_subject');
				return;
			}
			
			if ( $this->ipsclass->input['contactmethod'] == 'email' )
			{
				$this->parser->parse_smilies   = 0;
				$this->parser->parse_html      = 1;
				$this->parser->parse_bbcode    = 1;
				$this->ipsclass->input['contact']        = $this->parser->pre_display_parse( $this->parser->pre_db_parse( $this->ipsclass->input['contact'] ) );
				
				//-----------------------------------------
				// Send the email
				//-----------------------------------------
				
				$this->email->get_template("email_member");
					
				$this->email->build_message( array(
													'MESSAGE'     => str_replace( "<br>", "\n", str_replace( "\r", "",  $this->ipsclass->input['contact'] ) ),
													'MEMBER_NAME' => $this->warn_member['members_display_name'],
													'FROM_NAME'   => $this->ipsclass->member['members_display_name']
												  )
											);
											
				$this->email->subject = $this->ipsclass->input['subject'];
				$this->email->to      = $this->warn_member['email'];
				$this->email->from    = $this->ipsclass->vars['email_out'];
				$this->email->send_mail();
			}
			else
			{
				//-----------------------------------------
				// PM :o
				//-----------------------------------------
				
				require_once( ROOT_PATH.'sources/lib/func_msg.php' );
 		
 				$this->lib = new func_msg();
 				$this->lib->ipsclass =& $this->ipsclass;
 				
 				$this->lib->init();
 				
		 		$this->ipsclass->input['Post'] = $this->lib->postlib->han_editor->process_raw_post( 'contact' );
		 		
		 		$this->lib->postlib->parser->parse_smilies	= 1;
		 		$this->lib->postlib->parser->parse_nl2br   	= 1;
		 		$this->lib->postlib->parser->parse_html    	= $this->ipsclass->vars['msg_allow_html'];
		 		$this->lib->postlib->parser->parse_bbcode   = $this->ipsclass->vars['msg_allow_code'];
		 		
		 		$this->ipsclass->input['Post'] = $this->lib->postlib->parser->pre_db_parse( $this->ipsclass->input['Post'] );
		 		$this->ipsclass->input['Post'] = $this->lib->postlib->parser->pre_display_parse( $this->ipsclass->input['Post'] );
		 		$this->ipsclass->input['Post'] = $this->lib->postlib->parser->bad_words( $this->ipsclass->input['Post'] ); 
 				
				$this->lib->to_by_id    = $this->warn_member['id'];
 				$this->lib->from_member = $this->ipsclass->member;
 				$this->lib->msg_title   = $this->ipsclass->input['subject'];
 				$this->lib->msg_post    = $this->ipsclass->remove_tags($this->ipsclass->input['Post']);
				$this->lib->force_pm    = 1;
				
				$this->lib->send_pm();
				
				if ( $this->lib->error )
				{
					print $this->error;
					exit();
				}
			}
		}
		else
		{
			unset($test_content);
		}
		
		//-----------------------------------------
		// Right - is we banned or wha?
		//-----------------------------------------
			
		$restrict_post = '';
		$mod_queue     = '';
		$susp          = '';
		
		$save['wlog_notes']  = "<content>{$this->ipsclass->input['reason']}</content>";
		$save['wlog_notes'] .= "<mod>{$this->ipsclass->input['mod_value']},{$this->ipsclass->input['mod_unit']},{$this->ipsclass->input['mod_indef']}</mod>";
		$save['wlog_notes'] .= "<post>{$this->ipsclass->input['post_value']},{$this->ipsclass->input['post_unit']},{$this->ipsclass->input['post_indef']} </post>";
		$save['wlog_notes'] .= "<susp>{$this->ipsclass->input['susp_value']},{$this->ipsclass->input['susp_unit']}</susp>";
		
		if ( $this->ipsclass->input['mod_indef'] == 1 )
		{
			$mod_queue = 1;
		}
		elseif ( $this->ipsclass->input['mod_value'] > 0 )
		{
			$mod_queue = $this->ipsclass->hdl_ban_line( array( 'timespan' => intval($this->ipsclass->input['mod_value']), 'unit' => $this->ipsclass->input['mod_unit']  ) );
		}
		
		
		if ( $this->ipsclass->input['post_indef'] == 1 )
		{
			$restrict_post = 1;
		}
		elseif ( $this->ipsclass->input['post_value'] > 0 )
		{
			$restrict_post = $this->ipsclass->hdl_ban_line( array( 'timespan' => intval($this->ipsclass->input['post_value']), 'unit' => $this->ipsclass->input['post_unit']  ) );
		}
		
		if ( $this->ipsclass->input['susp_value'] > 0 )
		{
			$susp = $this->ipsclass->hdl_ban_line( array( 'timespan' => intval($this->ipsclass->input['susp_value']), 'unit' => $this->ipsclass->input['susp_unit']  ) );
		}
		
		$save['wlog_mid']     = $this->warn_member['id'];
		$save['wlog_addedby'] = $this->ipsclass->member['id'];
		
		//-----------------------------------------
		// Enter into warn loggy poos (eeew - poo)
		//-----------------------------------------
		
		$this->ipsclass->DB->do_insert( 'warn_logs', $save );
		
		//-----------------------------------------
		// Update member
		//-----------------------------------------
		
		$warn_level = intval($this->warn_member['warn_level']);
		
		if ( $this->ipsclass->input['level'] == 'add' )
		{
			$warn_level++;
		}
		else
		{
			$warn_level--;
		}
		
		if ( $warn_level > $this->ipsclass->vars['warn_max'] )
		{
			$warn_level = $this->ipsclass->vars['warn_max'];
		}
		
		if ( $warn_level < intval($this->ipsclass->vars['warn_min']) )
		{
			$warn_level = 0;
		}
		
		$this->ipsclass->DB->do_update( 'members', array (
										  'mod_posts'     => $mod_queue,
										  'restrict_post' => $restrict_post,
										  'temp_ban'      => $susp,
										  'warn_level'    => $warn_level,
										  'warn_lastwarn' => time(),
					  ) , "id={$this->warn_member['id']}"  );
		
		//-----------------------------------------
		// Now what? Show success screen, that's what!!
		//-----------------------------------------
		
		$this->ipsclass->lang['w_done_te'] = sprintf( $this->ipsclass->lang['w_done_te'], $this->warn_member['members_display_name'] );
		
		$this->output .= $this->ipsclass->compiled_templates['skin_mod']->warn_success();
		
		// Did we have a topic? eh! eh!! EH!
		
		$tid = intval($this->ipsclass->input['t']);
		
		if ( $tid > 0 )
		{
			$this->ipsclass->DB->cache_add_query( 'warn_get_forum', array( 'tid' => $tid ) );
			$this->ipsclass->DB->cache_exec_query();
		
			$topic = $this->ipsclass->DB->fetch_row();
		
			$this->output = str_replace( "<!--IBF.FORUM_TOPIC-->", $this->ipsclass->compiled_templates['skin_mod']->warn_success_forum( $topic['id'], $topic['name'], $topic['tid'], $topic['title'], intval($this->ipsclass->input['st']) ), $this->output );
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Show form
	/*-------------------------------------------------------------------------*/
	
	function show_form($errors="")
	{
		if ( $this->type == 'member' )
        {
        	$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_permission') );
        }
        
    	//-----------------------------------------
        // Load and config the std/rte editors
        //-----------------------------------------
        
        require_once( ROOT_PATH."sources/handlers/han_editor.php" );
        $this->han_editor           = new han_editor();
        $this->han_editor->ipsclass =& $this->ipsclass;
        $this->han_editor->init();
		$this->han_editor->remove_side_panel = 1;
		
		$key = $this->ipsclass->return_md5_check();
		
		if ( $errors != "" )
		{
			$this->output .= $this->ipsclass->compiled_templates['skin_mod']->warn_errors($this->ipsclass->lang[$errors]);
		}
		
		$type = array( 'minus' => "", 'add' => "" );
		
		if ( $this->ipsclass->input['type'] == 'minus' )
		{
			$type['minus'] = 'checked="checked"';
		}
		else
		{
			$type['add'] = 'checked="checked"';
		}
        
		$this->output .= $this->ipsclass->compiled_templates['skin_mod']->warn_header(
																					   $this->warn_member['id'],
																					   $this->warn_member['members_display_name'],
																					   intval($this->warn_member['warn_level']),
																					   $this->ipsclass->vars['warn_min'],
																					   $this->ipsclass->vars['warn_max'],
																					   $key,
																					   intval($this->ipsclass->input['t']),
																					   intval($this->ipsclass->input['st']),
																					   $type
																					);
		
		if ( $this->can_mod_q )
		{
			$mod_tick  = 0;
			$mod_arr   = array( 'timespan' => 0, 'days' => 0, 'hours' => 0 );
			$mod_extra = "";
		
			if ( $this->warn_member['mod_posts'] == 1 )
			{
				$mod_tick = 'checked';
			}
			elseif ($this->warn_member['mod_posts'] > 0)
			{
				$mod_arr = $this->ipsclass->hdl_ban_line($this->warn_member['mod_posts'] );
				
				$hours  = ceil( ( $mod_arr['date_end'] - time() ) / 3600 );
					
				if ( $hours > 24 and ( ($hours / 24) == ceil($hours / 24) ) )
				{
					$mod_arr['days']     = 'selected="selected"';
					$mod_arr['timespan'] = $hours / 24;
				}
				else
				{
					$mod_arr['hours']    = 'selected="selected"';
					$mod_arr['timespan'] = $hours;
				}
				
				$mod_extra = $this->ipsclass->compiled_templates['skin_mod']->warn_restricition_in_place();
			}
			
			$this->output .= $this->ipsclass->compiled_templates['skin_mod']->warn_mod_posts($mod_tick, $mod_arr, $mod_extra);
		}
		
		if ( $this->can_rem_post )
		{
		
			$post_tick  = 0;
			$post_arr   = array( 'timespan' => 0, 'hours' => 0, 'days' => 0 );
			$post_extra = "";
			
			if ( $this->warn_member['restrict_post'] == 1 )
			{
				$post_tick = 'checked';
			}
			else if ( $this->warn_member['restrict_post'] > 0 )
			{
				$post_arr = $this->ipsclass->hdl_ban_line( $this->warn_member['restrict_post'] );
				
				$hours  = ceil( ( $post_arr['date_end'] - time() ) / 3600 );
					
				if ( $hours > 24 and ( ($hours / 24) == ceil($hours / 24) ) )
				{
					$post_arr['days']     = 'selected="selected"';
					$post_arr['timespan'] = $hours / 24;
				}
				else
				{
					$post_arr['hours']    = 'selected="selected"';
					$post_arr['timespan'] = $hours;
				}
				
				$post_extra = $this->ipsclass->compiled_templates['skin_mod']->warn_restricition_in_place();
			}
			
			$this->output .= $this->ipsclass->compiled_templates['skin_mod']->warn_rem_posts($post_tick, $post_arr, $post_extra);
		}
		
		if ( $this->can_ban )
		{
			$ban_arr   = array( 'timespan' => 0, 'days' => 0, 'hours' => 0 );
			$ban_extra = "";
			
			if ( $this->warn_member['temp_ban'] )
			{
				$ban_arr = $this->ipsclass->hdl_ban_line( $this->warn_member['temp_ban'] );
				
				$hours  = ceil( ( $ban_arr['date_end'] - time() ) / 3600 );
					
				if ( $hours > 24 and ( ($hours / 24) == ceil($hours / 24) ) )
				{
					$ban_arr['days']     = 'selected="selected"';
					$ban_arr['timespan'] = $hours / 24;
				}
				else
				{
					$ban_arr['hours']    = 'selected="selected"';
					$ban_arr['timespan'] = $hours;
				}
				
				$ban_extra = $this->ipsclass->compiled_templates['skin_mod']->warn_restricition_in_place();
			}
			
			$this->output .= $this->ipsclass->compiled_templates['skin_mod']->warn_suspend($ban_arr, $ban_extra);
		}
		
		$this->ipsclass->input['reason'] = isset($this->ipsclass->input['reason']) ? $this->ipsclass->my_br2nl( $this->ipsclass->input['reason'] ) : '';

		if( isset($this->ipsclass->input['contact']) AND $this->ipsclass->input['contact'] )
		{
			if ( $this->han_editor->method == 'rte' )
			{
				$this->ipsclass->input['contact'] = $this->han_editor->process_raw_post( 'contact' );
				$this->ipsclass->input['contact'] = $this->parser->pre_display_parse( $this->parser->pre_db_parse( $this->ipsclass->input['contact'] ) );
				$this->ipsclass->input['contact'] = $this->parser->convert_ipb_html_to_html( $this->ipsclass->input['contact'] );
			}
			else
			{
				$this->parser->parse_html    = 1;
				$this->parser->parse_nl2br   = 1;
				$this->parser->parse_smilies = 1;
				$this->parser->parse_bbcode  = 1;
				
				$this->ipsclass->input['contact'] = $this->parser->pre_edit_parse( $this->ipsclass->input['contact'] );
			}
		}
		
		$this->ipsclass->input['subject'] = isset($this->ipsclass->input['subject']) ? $this->ipsclass->input['subject'] : '';
						
		$this->output .= $this->ipsclass->compiled_templates['skin_mod']->warn_footer( $this->warn_member['members_disable_pm'], $this->han_editor->show_editor( isset($this->ipsclass->input['contact']) ? $this->ipsclass->input['contact'] : '', 'contact' ) );
	}
	
	
	
}

?>