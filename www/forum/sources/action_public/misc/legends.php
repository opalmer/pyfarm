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
|   > $Date: 2007-07-09 18:11:16 -0400 (Mon, 09 Jul 2007) $
|   > $Revision: 1084 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > Show all emo's / BB Tags module
|   > Module written by Matt Mecham
|   > Date started: 18th April 2002
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

class legends {

    var $output    = "";
    var $base_url  = "";
    var $html      = "";

    function auto_run() {
    
    	//-----------------------------------------
    	// $is_sub is a boolean operator.
    	// If set to 1, we don't show the "topic subscribed" page
    	// we simply end the subroutine and let the caller finish
    	// up for us.
    	//-----------------------------------------
    
        $this->ipsclass->load_language('lang_legends');

    	$this->ipsclass->load_template('skin_legends');
    	
    	$this->base_url        = $this->ipsclass->base_url;
    	
    	
    	
    	//-----------------------------------------
    	// What to do?
    	//-----------------------------------------
    	
    	switch($this->ipsclass->input['CODE'])
    	{
    		case 'emoticons':
    			$this->show_emoticons();
    			break;
    			
    		case 'finduser_one':
    			$this->find_user_one();
    			break;
    			
    		case 'finduser_two':
    			$this->find_user_two();
    			break;
    			
    		case 'bbcode':
    			$this->show_bbcode();
    			break;
    			
    		default:
    			$this->show_emoticons();
    			break;
    	}
    	
    	// If we have any HTML to print, do so...
    	
        $this->ipsclass->print->pop_up_window( $this->page_title, $this->output );
    		
 	}
 	
 	//-----------------------------------------
 	
 	function find_user_one()
 	{
		// entry=textarea&name=carbon_copy&sep=comma
 		
 		$entry = (isset($this->ipsclass->input['entry'])) ? $this->ipsclass->input['entry'] : 'textarea';
 		$name  = (isset($this->ipsclass->input['name']))  ? $this->ipsclass->input['name']  : 'carbon_copy';
 		$sep   = (isset($this->ipsclass->input['sep']))   ? $this->ipsclass->input['sep']   : 'line';
 		
 		$this->output .= $this->ipsclass->compiled_templates['skin_legends']->find_user_one($entry, $name, $sep);
 		
 		$this->page_title = $this->ipsclass->lang['fu_title'];
 		
 	}
 	
 	//-----------------------------------------
 	
 	function find_user_two()
 	{
		$entry = (isset($this->ipsclass->input['entry'])) ? $this->ipsclass->input['entry'] : 'textarea';
 		$name  = (isset($this->ipsclass->input['name']))  ? $this->ipsclass->input['name']  : 'carbon_copy';
 		$sep   = (isset($this->ipsclass->input['sep']))   ? $this->ipsclass->input['sep']   : 'line';
 		
 		//-----------------------------------------
 		// Check for input, etc
 		//-----------------------------------------
 		
 		$this->ipsclass->input['username'] = strtolower(trim($this->ipsclass->input['username']));
 		
 		if ($this->ipsclass->input['username'] == "")
 		{
 			$this->find_user_error('fu_no_data');
 			return;
 		}
 		
 		//-----------------------------------------
 		// Attempt a match
 		//-----------------------------------------
 		
 		$this->ipsclass->DB->simple_construct( array( 'select' => 'id, name, members_display_name',
													  'from'   => 'members',
													  'where'  => "members_l_display_name LIKE '".$this->ipsclass->input['username']."%'",
													  'limit'  => array( 0,101) ) );
		$this->ipsclass->DB->simple_exec();
		
 		if ( ! $this->ipsclass->DB->get_num_rows() )
 		{
 			$this->find_user_error('fu_no_match');
 			return;
 		}
 		else if ( $this->ipsclass->DB->get_num_rows() > 99 )
 		{
 			$this->find_user_error('fu_kc_loads');
 			return;
 		}
 		else
 		{
 			$select_box = "";
 			
 			while ( $row = $this->ipsclass->DB->fetch_row() )
 			{
 				if ($row['id'] > 0)
 				{
 					$select_box .= "<option value='{$row['members_display_name']}'>{$row['members_display_name']}</option>\n";
 				}
 			}
 		
 			$this->output .= $this->ipsclass->compiled_templates['skin_legends']->find_user_final($select_box, $entry, $name, $sep);
 		
 			$this->page_title = $this->ipsclass->lang['fu_title'];
 		}
 	}
 	
 	
 	//-----------------------------------------
 	
 	function find_user_error($error)
 	{
		$this->page_title = $this->ipsclass->lang['fu_title'];
 		
 		$this->output = $this->ipsclass->compiled_templates['skin_legends']->find_user_error($this->ipsclass->lang[$error]);
 		
 		return;
 		
 	}
 	
 	
 	//-----------------------------------------
 	
 	function show_emoticons()
 	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
	
		$this->page_title = $this->ipsclass->lang['emo_title'];
 		$smilie_id        = 0;
 		$editor_id        = $this->ipsclass->txt_alphanumerical_clean( $this->ipsclass->input['editor_id'] );

		//-----------------------------------------
		// Start output...
		//-----------------------------------------
		
 		$this->output .= $this->ipsclass->compiled_templates['skin_legends']->emoticon_javascript( $editor_id );
 		$this->output .= $this->ipsclass->compiled_templates['skin_legends']->page_header( $this->ipsclass->lang['emo_title'], $this->ipsclass->lang['emo_type'], $this->ipsclass->lang['emo_img'] );
 		
 		$this->ipsclass->DB->simple_construct( array( 'select' => 'typed, image', 'from' => 'emoticons', 'where' => "emo_set='".$this->ipsclass->skin['_emodir']."'" ) );
		$this->ipsclass->DB->simple_exec();
		
		if ( $this->ipsclass->DB->get_num_rows() )
		{
			while ( $r = $this->ipsclass->DB->fetch_row() )
			{
				$smilie_id++;
				
				if (strstr( $r['typed'], "&quot;" ) )
				{
					$in_delim  = "'";
					$out_delim = '"';
				}
				else
				{
					$in_delim  = '"';
					$out_delim = "'";
				}
			
				$this->output .= $this->ipsclass->compiled_templates['skin_legends']->emoticons_row( stripslashes($r['typed']), stripslashes($r['image']), $in_delim, $out_delim, $smilie_id );
											
			}
		}
		
		$this->output .= $this->ipsclass->compiled_templates['skin_legends']->page_footer();
 	}
 	
 	//-----------------------------------------
 	// Show BBCode Helpy file
 	//-----------------------------------------
 	
 	function show_bbcode()
 	{
		//-----------------------------------------
        // Load and config the post parser
        //-----------------------------------------
        
        require_once( ROOT_PATH."sources/handlers/han_parse_bbcode.php" );
        $this->parser                      = new parse_bbcode();
        $this->parser->ipsclass            = $this->ipsclass;
        $this->parser->allow_update_caches = 0;
        
        $this->parser->bypass_badwords = intval($this->ipsclass->member['g_bypass_badwords']);
        
 		$this->parser->parse_html    = 0;
		$this->parser->parse_nl2br   = 1;
		$this->parser->parse_smilies = 1;
		$this->parser->parse_bbcode  = 1;
		
 		//-----------------------------------------
 		// Array out or stuff here
 		//-----------------------------------------
 		
 		$bbcode = array(
						0  => array('[b]', '[/b]', $this->ipsclass->lang['bbc_ex1'] ),
						1  => array('[s]', '[/s]', $this->ipsclass->lang['bbc_ex1'] ),
						2  => array('[i]', '[/i]', $this->ipsclass->lang['bbc_ex1'] ),
						3  => array('[u]', '[/u]', $this->ipsclass->lang['bbc_ex1'] ),
						4  => array('[email]', '[/email]', 'user@domain.com' ),
						5  => array('[email=user@domain.com]', '[/email]', $this->ipsclass->lang['bbc_ex2'] ),
						6  => array('[url]', '[/url]', 'http://www.domain.com' ),
						7  => array('[url=http://www.domain.com]', '[/url]', $this->ipsclass->lang['bbc_ex2'] ),
						8  => array('[size=7]', '[/size]'    , $this->ipsclass->lang['bbc_ex1'] ),
						9  => array('[font=times]', '[/font]', $this->ipsclass->lang['bbc_ex1'] ),
						10 => array('[color=red]', '[/color]', $this->ipsclass->lang['bbc_ex1'] ),
						11 => array('[img]', '[/img]', ( !$this->ipsclass->vars['ipb_img_url'] ? $this->ipsclass->vars['board_url'] . '/' : '' ) . $this->ipsclass->vars['img_url'].'/folder_post_icons/icon11.gif' ),
						12 => array('[list]', '[/list]', '[*]'.$this->ipsclass->lang['bbc_li'].' [*]List Item'.$this->ipsclass->lang['bbc_li'] ),
						13 => array('[list=1]', '[/list]', '[*]'.$this->ipsclass->lang['bbc_li'].' [*]'.$this->ipsclass->lang['bbc_li'] ),
						14 => array('[list=a]', '[/list]', '[*]'.$this->ipsclass->lang['bbc_li'].' [*]'.$this->ipsclass->lang['bbc_li'] ),
						15 => array('[list=i]', '[/list]', '[*]'.$this->ipsclass->lang['bbc_li'].' [*]'.$this->ipsclass->lang['bbc_li'] ),
						16 => array('[quote]', '[/quote]', $this->ipsclass->lang['bbc_ex1'] ),
						17 => array('[code]', '[/code]', '$this_var = "'.$this->ipsclass->lang['bbc_helloworld'].'!";' ),
						18 => array('[sql]', '[/sql]', 'SELECT t.tid FROM a_table t WHERE t.val="This Value"' ),
						19 => array('[html]', '[/html]', '&lt;a href=&quot;test/page.html&quot;&gt;'.$this->ipsclass->lang['bbc_testpage'].'&lt;/a&gt;' ),
					  );
 		
 		$this->page_title = $this->ipsclass->lang['bbc_title'];
 		
 		$this->output .= $this->ipsclass->compiled_templates['skin_legends']->bbcode_header();
 		
		foreach( $bbcode as $bbc )
		{
			$open    = $bbc[0];
			$close   = $bbc[1];
			$content = $bbc[2];
		
			$before = $this->ipsclass->compiled_templates['skin_legends']->wrap_tag($open) . $content . $this->ipsclass->compiled_templates['skin_legends']->wrap_tag($close);
			
			$after = $this->parser->pre_db_parse( $open.$content.$close );
			
			$this->output .= $this->ipsclass->compiled_templates['skin_legends']->bbcode_row_header( $this->ipsclass->lang['bbc_title']);
			
			$this->output .= $this->ipsclass->compiled_templates['skin_legends']->bbcode_row( $before, stripslashes($after) );
			
			$this->output .= $this->ipsclass->compiled_templates['skin_legends']->bbcode_row_footer();
		}
		
		//-----------------------------------------
 		// Add in custom bbcode
 		//-----------------------------------------
 		
 		//$this->ipsclass->init_load_cache( array( 'bbcode' ) );
 		
		if( count($this->ipsclass->cache['bbcode']) )
		{
			foreach( $this->ipsclass->cache['bbcode'] as $row )
			{
				$before  = $row['bbcode_example'];

				$t       = $this->parser->pre_display_parse( $before );
				
				
				$before = preg_replace( "#(\[".$row['bbcode_tag']."(?:[^\]]+)?\])#is", $this->ipsclass->compiled_templates['skin_legends']->wrap_tag("\\1"), $before );
				$before = preg_replace( "#(\[/".$row['bbcode_tag']."\])#is"          , $this->ipsclass->compiled_templates['skin_legends']->wrap_tag("\\1"), $before );
				
				$this->output .= $this->ipsclass->compiled_templates['skin_legends']->bbcode_row_header( $row['bbcode_title'], $row['bbcode_desc'] );
				
				$this->output .= $this->ipsclass->compiled_templates['skin_legends']->bbcode_row( $before, $t );
				
				$this->output .= $this->ipsclass->compiled_templates['skin_legends']->bbcode_row_footer();
			}
		}
 	}
}

?>