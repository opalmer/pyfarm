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
|   > $Date: 2006-09-22 06:28:31 -0400 (Fri, 22 Sep 2006) $
|   > $Revision: 567 $
|   > $Author: matt $
+---------------------------------------------------------------------------
|
|   > CUSTOM PROFILE FIELD CLASS
|   > Module written by Matt Mecham
|   > Date started: 7th April 2004 ( hairdresser was closed today :( )
|
|	> Module Version Number: 1.0.0
|   > DBA Checked: Wed 19 May 2004
+--------------------------------------------------------------------------
| EXAMPLE:
| 
| $profile->cache_data  = $this->ipsclass->cache['profilefields'];
| $profile->member_id   = $this->ipsclass->member['id']; // Viewing member
| $profile->mem_data_id = $data['id']; // Get data for this member
| $profile->init_data();
|
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class custom_fields
{
	var $member_id   = 0;
	var $admin       = 0;
	var $supmod      = 0;
	var $mod		 = 0;
	var $mem_data_id = 0;
	var $mem_list	 = 0;
	var $init        = 0;
	var $in_fields    = array();
	var $out_fields   = array();
	var $out_chosen   = array();
	var $tmp_fields   = array();
	var $cache_data   = "";
	var $member_data  = array();
	var $field_names  = array();
	var $field_desc   = array();
	var $kill_html    = 1;
	var $error_fields = array( 'toobig' => array(), 'empty' => array(), 'invalid' => array() );
	
	var $DB           = "";
	
	/*-------------------------------------------------------------------------*/
	// CONSTRUCTOR
	/*-------------------------------------------------------------------------*/
	
	function custom_fields( &$DB )
	{
		$this->DB = &$DB;
	}
	
	/*-------------------------------------------------------------------------*/
	// Init (check, load cache)
	/*-------------------------------------------------------------------------*/
	
	function init_data()
	{
		if ( ! $this->init )
		{
			//-----------------------------------------
			// Cache data...
			//-----------------------------------------
			
			if ( ! is_array( $this->cache_data ) )
			{
				$this->DB->simple_construct( array( 'select' => '*', 'from' => 'pfields_data', 'order' => 'pf_position' ) );
				$this->DB->simple_exec();
				
				while ( $r = $this->DB->fetch_row() )
				{
					$this->cache_data[ $r['pf_id'] ] = $r;
				}
			}
			
			//-----------------------------------------
			// Get names...
			//-----------------------------------------
			
			if ( is_array($this->cache_data) and count($this->cache_data) )
			{
				foreach( $this->cache_data as $id => $data )
				{
					$this->field_names[ $id ] = $data['pf_title'];
					$this->field_desc[ $id ]  = $data['pf_desc'];
				}
			}
		}
		
		//-----------------------------------------
		// Clean up on aisle #4
		//-----------------------------------------
		
		$this->out_fields = array();
		$this->tmp_fields = array();
		$this->out_chosen = array();
		
		//-----------------------------------------
		// Get member...
		//-----------------------------------------
		
		if ( ! count( $this->member_data ) and $this->mem_data_id )
		{
			$this->member_data = $this->DB->simple_exec_query( array( 'select' => '*', 'from' => 'pfields_content', 'where' => 'member_id='.intval($this->mem_data_id) ) );
		}
		
		if ( count( $this->member_data ) )
		{
			$this->mem_data_id = isset($this->member_data['member_id']) ? $this->member_data['member_id'] : 0;
		}
		
		//-----------------------------------------
		// Parse into in fields
		//-----------------------------------------
		
		if ( is_array($this->cache_data) and count( $this->cache_data ) )
		{
			foreach( $this->cache_data as $id => $data )
			{
				$this->in_fields[ $id ] = isset($this->member_data['field_'.$id]) ? $this->member_data['field_'.$id] : NULL;
			}
		}
		
		$this->init = 1;
	}
	
	/*-------------------------------------------------------------------------*/
	// Generate for saving
	/*-------------------------------------------------------------------------*/
	
	function parse_to_save( $register=0, $post='field_' )
	{
		if ( is_array($this->cache_data) and count($this->cache_data) )
		{
			foreach( $this->cache_data as $i => $row )
			{
				if ( ! $register )
				{
					//-----------------------------------------
					// Admin / mod only?
					//-----------------------------------------
					
					if ( $row['pf_admin_only'] )
					{
						if ( ! $this->admin AND ! $this->supmod )
						{
							continue;
						}
					}
					
					//-----------------------------------------
					// Can edit? (member, admin, mod)
					//-----------------------------------------
					
					if ( ! $row['pf_member_edit'] )
					{
						if ( ! $this->admin AND ! $this->supmod )
						{
							continue;
						}
					}
				}
				else
				{
					//-----------------------------------------
					// Show on reg?
					//-----------------------------------------
					
					if ( ! $row['pf_show_on_reg'] )
					{
						continue;
					}
				}
				
				$this->tmp_fields[ $i ] = $row;
			}
		}
		
		//-----------------------------------------
		// Grab editable fields...
		//-----------------------------------------
		
		if ( is_array($this->tmp_fields) and count($this->tmp_fields) )
		{
			foreach( $this->tmp_fields as $i => $row )
			{
				//-----------------------------------------
				// Too big?
				//-----------------------------------------
				
				if ( $this->cache_data[$i]['pf_max_input'] and strlen( $_POST[ $post.$i ] ) > $this->cache_data[$i]['pf_max_input'] )
				{
					$this->error_fields['toobig'][] = $row;
				}
				
				//-----------------------------------------
				// Required and NULL?
				//-----------------------------------------
				
				if ( $this->cache_data[$i]['pf_not_null'] and trim($_POST[ $post.$i ]) == "" )
				{
					$this->error_fields['empty'][] = $row;
				}
				
				//-----------------------------------------
				// Invalid format?
				//-----------------------------------------
				
				if ( trim($this->cache_data[$i]['pf_input_format']) and $_POST[ $post.$i ] )
				{
					$regex = str_replace( 'n', '\\d', preg_quote( $this->cache_data[$i]['pf_input_format'], "#" ) );
					$regex = str_replace( 'a', '\\w', $regex );
					
					if ( ! preg_match( "#^".$regex."$#i", trim($_POST[ $post.$i ]) ) )
					{
						$this->error_fields['invalid'][] = $row;
					}
				}
				
				$this->out_fields[ $post.$i ] = $this->method_format_text_to_save( $_POST[ $post.$i ] );
			}
		}
	
	}
	
	/*-------------------------------------------------------------------------*/
	// Generate for viewing
	/*-------------------------------------------------------------------------*/
	
	function parse_to_view( $check_topic_format=0 )
	{
		if ( is_array( $this->cache_data ) and count( $this->cache_data ) )
		{
			foreach( $this->cache_data as $i => $row )
			{
				//-----------------------------------------
				// Admin / mod only?
				//-----------------------------------------
				
				if ( $row['pf_admin_only'] )
				{
					if ( ! $this->admin AND ! $this->supmod )
					{
						continue;
					}
				}
				
				//-----------------------------------------
				// Private field (member, admin, mod)
				//-----------------------------------------
				
				if ( $row['pf_member_hide'] )
				{
					$pass = 0;
					
					if ( $this->admin )
					{
						$pass = 1;
					}
					else if ( $this->supmod )
					{
						$pass = 1;
					}
					else if ( $this->member_id and ( $this->member_id == $this->mem_data_id ) )
					{
						$pass = 1;
					}
					else
					{
						$pass = 0;
					}
					
					if ( ! $pass )
					{
						continue;
					}
				}
				
				//-----------------------------------------
				// Topic format?
				//-----------------------------------------
				
				if ( $check_topic_format )
				{
					if ( ! $row['pf_topic_format'] )
					{
						continue;
					}
				}
				
				$this->tmp_fields[ $i ] = $row;
			}
		}
		
		$this->method_parse_out_fields('view');
	}
	
	/*-------------------------------------------------------------------------*/
	// Generate for viewing on reg form
	/*-------------------------------------------------------------------------*/
	
	function parse_to_register()
	{
		if ( is_array( $this->cache_data ) and count( $this->cache_data ) )
		{
			foreach( $this->cache_data as $i => $row )
			{
				//-----------------------------------------
				// Show on reg?
				//-----------------------------------------
				
				if ( ! $row['pf_show_on_reg'] )
				{
					continue;
				}
				
				$this->tmp_fields[ $i ] = $row;
			}
		}
		
		$this->method_parse_out_fields('reg');
	}
	
	/*-------------------------------------------------------------------------*/
	// Generate for editing
	/*-------------------------------------------------------------------------*/
	
	function parse_to_edit()
	{
		if ( is_array($this->cache_data) and count($this->cache_data) )
		{
			foreach( $this->cache_data as $i => $row )
			{
				//-----------------------------------------
				// Admin / mod only?
				//-----------------------------------------
				
				if ( $row['pf_admin_only'] )
				{
					if ( ! $this->admin AND ! $this->supmod )
					{
						continue;
					}
				}
				
				//-----------------------------------------
				// Private field (member, admin, mod)
				//-----------------------------------------
				
				if ( $row['pf_member_hide'] )
				{
					$pass = 0;
					
					if ( $this->admin )
					{
						$pass = 1;
					}
					else if ( $this->mod )
					{
						$pass = 1;
					}
					else if ( $this->member_id )
					{
						// We don't want to search these fields in memberlist..
						
						if( !$this->mem_list )
						{
							$pass = 1;
						}
					}
					
					if ( ! $pass )
					{
						continue;
					}
				}
				
				//-----------------------------------------
				// Can edit? (member, admin, mod)
				//-----------------------------------------
				
				if ( ! $row['pf_member_edit'] )
				{
					if ( ! $this->admin AND ! $this->supmod )
					{
						continue;
					}
				}
				
				$this->tmp_fields[ $i ] = $row;
			}
		}
		
		$this->method_parse_out_fields('edit');
	}
	
	/*-------------------------------------------------------------------------*/
	// Method: Parse out_fields
	/*-------------------------------------------------------------------------*/
	
	function method_parse_out_fields($type='view')
	{
		foreach( $this->tmp_fields as $i => $row )
		{
			if ($row['pf_type'] == 'drop')
			{ 
				$carray = explode( '|', trim( $row['pf_content'] ) );
				
				foreach( $carray as $entry )
				{
					$value = explode( '=', $entry );
					
					$ov = trim($value[0]);
					$td = trim($value[1]);
					
					if ( $type == 'reg' )
					{
						$this->out_fields[ $row['pf_id'] ] .= "<option value='$ov'>$td</option>\n";
					}
					else if ( $type == 'view' )
					{
						if ( $this->in_fields[ $row['pf_id'] ] == $ov)
						{
							$this->out_fields[ $row['pf_id'] ] = $td;
							$this->out_chosen[ $row['pf_id'] ] = $ov;
						}
						else if ( $this->in_fields[ $row['pf_id'] ] == "" )
						{
						   $this->out_fields[ $row['pf_id'] ] = '';
						   $this->out_chosen[ $row['pf_id'] ] = '';
						}
					}
					else if ( $type == 'edit' )
					{ 
						if ( $this->in_fields[ $row['pf_id'] ] == $ov and $this->in_fields[ $row['pf_id'] ])
						{
							$this->out_fields[ $row['pf_id'] ] .= "<option value='$ov' selected='selected'>$td</option>\n";
						}
						else
						{
							$this->out_fields[ $row['pf_id'] ] .= "<option value='$ov'>$td</option>\n";
						}
					}
				}
			}
			else
			{
				if ( $type == 'view' )
				{
					$this->out_fields[ $row['pf_id'] ] = $this->method_make_safe_for_view( $this->in_fields[ $row['pf_id'] ] );
				}
				else
				{
					$this->out_fields[ $row['pf_id'] ] = $this->method_make_safe_for_form( $this->in_fields[ $row['pf_id'] ] );
				}
			}
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Method: format text to save
	/*-------------------------------------------------------------------------*/
	
	function method_format_text_to_save( $t )
	{
		$t = str_replace( "<br>"  , "\n", $t );
		$t = str_replace( "<br />", "\n", $t );
		$t = str_replace( "&#39;" , "'" , $t );
		
		if ( @get_magic_quotes_gpc() )
		{
			$t = stripslashes($t);
		}
		
		return $t;
	}
	
	/*-------------------------------------------------------------------------*/
	// Method: format_field_for_edit
	/*-------------------------------------------------------------------------*/
	
	function method_format_content_for_edit( $c )
	{
		return str_replace( '|', "\n", $c );
	}
	
	/*-------------------------------------------------------------------------*/
	// Method: format_field_for_topic_view
	/*-------------------------------------------------------------------------*/
	
	function method_format_field_for_topic_view( $i )
	{
		$out = $this->out_fields[$i];
		
		$tmp = $this->cache_data[$i]['pf_topic_format'];
		
		$tmp = str_replace( '{title}'  , $this->field_names[$i], $tmp );
		$tmp = str_replace( '{key}'    , isset($this->out_chosen[$i]) ? $this->out_chosen[$i]: '' , $tmp );
		$tmp = str_replace( '{content}', $out                  , $tmp );
		
		return $tmp;
	}
	
	/*-------------------------------------------------------------------------*/
	// Method: format_field_for_save
	/*-------------------------------------------------------------------------*/
	
	function method_format_content_for_save( $c )
	{
		$c = str_replace( "\r"   , "\n", $c );
		$c = str_replace( "&#39;", "'" , $c );
		return str_replace( "\n", '|', str_replace( "\n\n", "\n", trim($c) ) );
	}
	
	/*-------------------------------------------------------------------------*/
	// Make safe for form viewing
	/*-------------------------------------------------------------------------*/
	
	function method_make_safe_for_form( $t )
	{
		return str_replace( "'", "&#39;", $t );
	}
	
	/*-------------------------------------------------------------------------*/
	// Make safe for other viewing (profile, etc)
	/*-------------------------------------------------------------------------*/
	
	function method_make_safe_for_view( $t )
	{
		if ( $this->kill_html )
		{
			$t = htmlspecialchars( $t );
			$t = preg_replace("/&amp;#([0-9]+);/s", "&#\\1;", $t );
		}
		
		$t = nl2br( $t );
		
		return $t;
	}
	
	

}


?>