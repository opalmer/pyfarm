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
|   > $Date: 2007-09-11 12:37:52 -0400 (Tue, 11 Sep 2007) $
|   > $Revision: 1102 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > Post Handler
|   > Module written by Matt Mecham
|   > Date started: Wednesday 9th March 2005 (15:23)
|
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class han_editor
{
	# Global
	var $ipsclass;
	var $class_editor;
	
	# Method
	var $method;
	
	# Html handler
	var $html;
	
	# Pass width
	var $ed_width   = '650px';
	
	# Pass height
	var $ed_height  = 250;
	
	# Using RTE?
	var $rte_on     = 0;
	
	# Board or ACP?
	var $from_acp     = 0;	
	var $image_dir;
	var $emo_dir;
	
	# Editor ID
	var $editor_id = 'ed-0';
	
	# Remove side panel
	var $remove_side_panel = 0;
	
	# Remove emos drop down
	var $remove_emoticons = 0;
	
	# ACP editor ID
	var $acp_editor_id = '0';
	
    /*-------------------------------------------------------------------------*/
    // INIT
    /*-------------------------------------------------------------------------*/
    
    function init()
    {
    	//-----------------------------------------
    	// INIT
    	//-----------------------------------------
    	
    	$class = "";
    	
    	if ( ! $this->ipsclass->vars['posting_allow_rte'] )
    	{
    		$this->ipsclass->member['members_editor_choice'] = 'std';
    	}
    	
    	if ( ! $this->method )
    	{
    		$this->method = $this->ipsclass->member['members_editor_choice'];
    	}
    	
    	//-----------------------------------------
    	// Fix width
    	//-----------------------------------------
    	
    	$this->ed_width = isset($this->ipsclass->vars['rte_width']) ? $this->ipsclass->vars['rte_width'] : $this->ed_width;
    	
    	//-----------------------------------------
    	// Make sure we haven't had any messin'
    	//-----------------------------------------
    	
		if ( isset( $_POST['editor_ids'] ) AND is_array( $_POST['editor_ids'] ) )
		{
			foreach( $_POST['editor_ids'] as $k => $v )
			{
				if ( isset($_POST[ $v . '_wysiwyg_used']) AND intval($_POST[$v . '_wysiwyg_used']) == 1)
				{
					$this->method = 'rte';
				}
				else
				{
					$this->method = 'std';
				}
			}
		}
		
    	if ( isset($_POST['ed-0_wysiwyg_used']) AND intval($_POST['ed-0_wysiwyg_used']) == 1 )
    	{
    		$this->method = 'rte';
    	}
    	
    	//-----------------------------------------
    	// Force STD editor (fast reply, etc)
    	//-----------------------------------------
    	
    	if ( ( isset($_POST['std_used']) AND intval($_POST['std_used']) ) OR isset($_POST['fast_reply_used']) AND intval($_POST['fast_reply_used']) )
    	{
    		$this->method = 'std';
    	}
    	
    	//-----------------------------------------
    	// Sneaky Opera or Safari
    	//-----------------------------------------
    
    	if ( $this->method == 'rte' )
    	{
    		if ( $this->ipsclass->browser['browser'] == 'opera' AND $this->ipsclass->browser['version'] < '9.00' ) # Okay... this is for future compat.
    		{
    			$this->method = 'std';
    			$this->ipsclass->force_editor_change = 1;
    		}
    		else if ( $this->ipsclass->browser['browser'] == 'safari' AND $this->ipsclass->browser['version'] < 1000 ) # Okay... this is for future compat.
    		{
    			$this->method = 'std';
    			$this->ipsclass->force_editor_change = 1;
    		}
    		else if ( $this->ipsclass->browser['browser'] == 'konqueror' )
    		{
    			$this->method = 'std';
    			$this->ipsclass->force_editor_change = 1;
    		}
    	}
    	
    	//$this->method = "rte";
    	
    	//-----------------------------------------
    	// Which class
    	//-----------------------------------------
    	
    	switch( $this->method )
    	{
    		case 'rte':
    			$class        = 'class_editor_rte.php';
    			$this->rte_on = 1;
    			break;
    		case 'std':
    			$class 	      = 'class_editor_std.php';
    			$this->rte_on = 0;
    			break;
    		default:
    			$class 		  = 'class_editor_std.php';
    			$this->rte_on = 0;
    	}
    	
		//-----------------------------------------
		// Load classes
		//-----------------------------------------
	
		require_once( ROOT_PATH . 'sources/classes/editor/class_editor.php' );
		require_once( ROOT_PATH . 'sources/classes/editor/'.$class );
		
		$this->class_editor                   =  new class_editor_module();
		$this->class_editor->ipsclass         =& $this->ipsclass;
		$this->class_editor->allow_unicode    =  $this->ipsclass->allow_unicode;
		$this->class_editor->get_magic_quotes =  $this->ipsclass->get_magic_quotes;
		
		//-----------------------------------------
		// Load lang file
		//-----------------------------------------
		
		$this->ipsclass->load_language( 'lang_editors' );
		
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
		
		//-----------------------------------------
		// BBCode
		//-----------------------------------------
		
		if ( ! is_array( $this->ipsclass->cache['bbcode'] ) )
		{
			$this->ipsclass->cache['bbcode'] = array();
			
			$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'custom_bbcode' ) );
			$this->ipsclass->DB->simple_exec();
		
			while ( $r = $this->ipsclass->DB->fetch_row() )
			{
				$this->ipsclass->cache['bbcode'][] = $r;
			}
			
			@usort( $this->ipsclass->cache['bbcode'] , array( 'class_bbcode_core', 'smilie_alpha_sort' ) );
		}
		
		//-----------------------------------------
		// Init class
		//-----------------------------------------
		
        $this->class_editor->main_init();
        
		//-----------------------------------------
  		// Load skin and language
  		//-----------------------------------------

  		if ( $this->from_acp == 1 )
  		{
	  		if ( !isset($this->ipsclass->compiled_templates['skin_editors']) )
	  		{
	  			$this->ipsclass->compiled_templates['skin_editors'] = $this->ipsclass->acp_load_template('cp_skin_editors');
  			}

	  		//-----------------------------------------
	  		// Set up for replacement
	  		//-----------------------------------------

	  		$this->ipsclass->vars['img_url'] = "<#IMG_DIR#>";

			$this->ipsclass->DB->simple_construct( array( 'select' => 'set_image_dir, set_emoticon_folder', 'from' => 'skin_sets', 'where' => 'set_default=1' ) );
			$this->ipsclass->DB->simple_exec();

			$image_set = $this->ipsclass->DB->fetch_row();
			
			$this->image_dir = $image_set['set_image_dir'];
			$this->emo_dir   = $image_set['set_emoticon_folder'];
			
			//-----------------------------------------
			// Remove side panel
			//-----------------------------------------
			
			$this->remove_side_panel = 1;
			$this->remove_emoticons  = 1;
  		}
  		else
  		{
	  		if ( ! isset($this->ipsclass->compiled_templates['skin_editors']) )
	  		{
	  			$this->ipsclass->load_template( 'skin_editors' );
	  		}
		
			$this->emo_dir = $this->ipsclass->skin['_emodir'];
  		}
    }
    
    /*-------------------------------------------------------------------------*/
    // Mode: Show editor
    // Takes raw text with BBCode *NOT* converted BBCode
    /*-------------------------------------------------------------------------*/
  
	function show_editor( $text, $form_field='post_content' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$smilie_array = array();
		$smilies      = '';
		$total        = 0;
		$count        = 0;
		$smilie_id    = 0;
		$bbcode_array = array();
		$bbcode       = '';
		
		//-----------------------------------------
  		// Load skin and language
  		//-----------------------------------------

  		if ( $this->from_acp == 1 )
  		{
			//-----------------------------------------
			// Sort out editor id
			//-----------------------------------------
			
			$this->editor_id = 'ed-'.$this->acp_editor_id;
			
			$this->acp_editor_id++;
  		}
  		
		foreach( $this->ipsclass->cache['emoticons'] as $clickable )
		{
			if ( $clickable['emo_set'] != $this->emo_dir )
			{
				continue;
			}
						
			if ( $clickable['clickable'] )
			{
				$total++;
			}
		}
		
		foreach( $this->ipsclass->cache['emoticons'] as $elmo )
		{
			if ( $elmo['emo_set'] != $this->emo_dir )
			{
				continue;
			}
			
			if ( ! $elmo['clickable'] )
			{
				continue;
			}
			
			$count++;
			$smilie_id++;
			
			//-----------------------------------------
			// Make single quotes as URL's with html entites in them
			// are parsed by the browser, so ' causes JS error :o
			//-----------------------------------------
			
			if ( strstr( $elmo['typed'], "&#39;" ) )
			{
				$in_delim  = '"';
			}
			else
			{
				$in_delim  = "'";
			}
			
			$smilie_array[] = $in_delim . $elmo['typed'] . $in_delim . ' : "' . $smilie_id . ','.$elmo['image'].'"';
		}
		
		//-----------------------------------------
		// Finish up smilies...
		//-----------------------------------------
		
		if ( count( $smilie_array ) )
		{
			$smilies = implode( ",\n", $smilie_array );
		}
  		
		//-----------------------------------------
		// Showing any?
		//-----------------------------------------
		
		foreach( $this->ipsclass->cache['bbcode'] as $data )
		{
			if ( $data['bbcode_add_into_menu'] )
			{
				$_title          = str_replace( '|', '', str_replace( '"', '&amp;quot;', preg_replace( "#(\n|\r|\n\r)#s" , '\\n', $data['bbcode_title'] ) ) );
				$_example        = str_replace( '|', '', str_replace( '"', '&amp;quot;', preg_replace( "#(\n|\r|\n\r)#s" , '\\n', $data['bbcode_example'] ) ) );
				$_tag            = str_replace( '|', '', str_replace( '"', '&amp;quot;', $data['bbcode_tag'] ) );
				$_use_option     = str_replace( '|', '', str_replace( '"', '&amp;quot;', $data['bbcode_useoption'] ) );
				$_switch_option  = str_replace( '|', '', str_replace( '"', '&amp;quot;', $data['bbcode_switch_option'] ) );
				$_text_option    = str_replace( '|', '', str_replace( '"', '&amp;quot;', $data['bbcode_menu_option_text'] ) );
				$_content_option = str_replace( '|', '', str_replace( '"', '&amp;quot;', $data['bbcode_menu_content_text'] ) );
				
				$_string  = $data['bbcode_id'] . ': {';
				$_string .= "\n\t\t'title'         : \"".$_title          . "\",";
				$_string .= "\n\t\t'example'       : \"".$_example        . "\",";
				$_string .= "\n\t\t'tag'           : \"".$_tag            . "\",";
				$_string .= "\n\t\t'use_option'    : \"".$_use_option     . "\",";
				$_string .= "\n\t\t'switch_option' : \"".$_switch_option  . "\",";
				$_string .= "\n\t\t'text_option'   : \"".$_text_option    . "\",";
				$_string .= "\n\t\t'text_content'  : \"".$_content_option . "\"";
				$_string .= "\n\t\t}";
				
				$bbcode_array[] = $_string;
			}
		}
		
		if ( is_array( $bbcode_array ) )
		{
			$bbcode = implode( ",\n", $bbcode_array );
		}
		
		//-----------------------------------------
		// Side panel..
		//-----------------------------------------
		
		$this->ipsclass->vars['_remove_side_panel'] = $this->remove_side_panel;
		$this->ipsclass->vars['_remove_emoticons']  = $this->remove_emoticons;
		
  		//-----------------------------------------
  		// Pre parse...
  		//-----------------------------------------

  		$text = $this->class_editor->process_before_form( $text );

		//-----------------------------------------
		// Weird script tag stuff...
		//-----------------------------------------
		
		if( $this->method == 'rte' )
		{
			$text = preg_replace( "#(<|&lt;|&amp;lt;|&\#60;)script#si", "&amp;lt;script", $text );
		}
		
  		$return_html = $this->ipsclass->compiled_templates['skin_editors']->ips_editor( $form_field, $text, $this->ipsclass->vars['img_url'].'/folder_editor_images/', 'jscripts/folder_rte_files/', $this->rte_on, $this->editor_id, $smilies, $bbcode );
  		
		//-----------------------------------------
		// Comment
		//-----------------------------------------

  		if ( $this->from_acp )
  		{
			$return_html = preg_replace( "#([^/])jscripts#is", "\\1".$this->ipsclass->vars['board_url']."/jscripts"                 , $return_html );
			$return_html = str_replace( "<#IMG_DIR#>"        , $this->ipsclass->vars['board_url']."/style_images/{$this->image_dir}", $return_html );
		}
		
		return $return_html;
  	}
  	
    /*-------------------------------------------------------------------------*/
    // Mode: Process text
    /*-------------------------------------------------------------------------*/
  
  	function process_raw_post( $form_field )
  	{
  		return $this->class_editor->process_after_form( $form_field );
  	}

	
	
}

?>