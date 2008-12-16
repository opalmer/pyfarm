<?php

/*
+---------------------------------------------------------------------------
|   Invision Power Dynamic v1.0.0
|   ========================================
|   by Matthew Mecham
|   (c) 2004 Invision Power Services
|   http://www.invisionpower.com
|   ========================================
+---------------------------------------------------------------------------
|   INVISION POWER DYNAMIC IS NOT FREE SOFTWARE!
|   http://www.invisionpower.com/dynamic/
+---------------------------------------------------------------------------
|   > $Id$
|   > $Revision: 4 $
|   > $Date: 2005-10-10 14:21:32 +0100 (Mon, 10 Oct 2005) $
+---------------------------------------------------------------------------
|
|   > MSG PLUG-IN
|   > Script written by Matt Mecham
|   > Date started: Monday 19th December 2005 (11:29)
|
+---------------------------------------------------------------------------
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class plugin_msg
{
	/**
	* Global ipsclass
	* @var	object
	*/
	var $ipsclass;
	
	/**
	* Module type
	* @var	string
	*/
	var $module = 'msg';
	
	/*-------------------------------------------------------------------------*/
	// show_attachment_get_sql_data
	/*-------------------------------------------------------------------------*/
	/**
	* Checks the attachment and checks for download / show perms
	* Returns TRUE or FALSE
	* IT really does
	*
	* @return boolean
	*/
	function show_attachment_get_sql_data( $attach_id )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$_ok = 0;
		
		//-----------------------------------------
		// Grab 'em
		//-----------------------------------------
		
		$this->ipsclass->DB->build_query( array( 'select'   => 'a.*',
												 'from'     => array( 'attachments' => 'a' ),
												 'where'    => "a.attach_rel_module='".$this->module."' AND a.attach_id=".$attach_id." AND t.mt_owner_id=".$this->ipsclass->member['id'],
												 'add_join' => array( 0 => array( 'select' => 'p.*',
																				  'from'   => array( 'message_text' => 'p' ),
																				  'where'  => "p.msg_id=a.attach_rel_id",
																				  'type'   => 'left' ),
															          1 => array( 'select' => 't.*',
																				  'from'   => array( 'message_topics' => 't' ),
																				  'where'  => "t.mt_msg_id=p.msg_id",
																				  'type'   => 'left' ) )
										)      );

		$attach_sql = $this->ipsclass->DB->exec_query();
		
		$attach     = $this->ipsclass->DB->fetch_row( $attach_sql );
		
		//-----------------------------------------
		// Check..
		//-----------------------------------------
		
		if ( ! isset( $attach['msg_id'] ) )
		{
			return FALSE;
		}
		
		//-----------------------------------------
    	// TheWalrus inspired fix for previewing
    	// the post and clicking the attachment...
    	//-----------------------------------------
    		
    	if ( $attach['mt_owner_id'] == $this->ipsclass->member['id'] )
    	{
    		$_ok = 1;
    	}

		//-----------------------------------------
		// Ok?
		//-----------------------------------------

		if ( $_ok )
		{
			return $attach;
		}
		else
		{
			return FALSE;
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// render_attachment_get_sql_data
	/*-------------------------------------------------------------------------*/
	/**
	* Check the attachment and make sure its OK to display
	* Returns TRUE or FALSE
	* IT really does
	*
	* @return boolean
	*/
	function render_attachment_get_sql_data( $attach_ids, $rel_ids=array() )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$rows  		= array();
		$query 		= '';
		$query_bits	= array();
		$match		= 0;
		
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( is_array( $attach_ids ) AND count( $attach_ids ) )
		{
			//$attach_ids = array( -2 );
			$query_bits[] = "attach_id IN (" . implode( ",", $attach_ids ) .")";
		}
		
		if ( is_array( $rel_ids ) and count( $rel_ids ) )
		{
			$query_bits[] = "attach_rel_id IN (" . implode( ",", $rel_ids ) . ")";
			//$query = " OR attach_rel_id IN (-1," . implode( ",", $rel_ids ) . ")";
			$match = 1;
		}
		
		if( !count($query_bits) )
		{
			$query = "attach_id IN (-1)";
		}
		else
		{
			$query = implode( " OR ", $query_bits );
		}		
		
		//-----------------------------------------
		// Grab 'em
		//-----------------------------------------
		
		$this->ipsclass->DB->build_query( array( 'select'   => '*',
												 'from'     => 'attachments',
												 'where'    => "attach_rel_module='".$this->module."' AND ( " . $query . " )",
										)      );

		$attach_sql = $this->ipsclass->DB->exec_query();
		
		//-----------------------------------------
		// Loop through and filter off naughty ids
		//-----------------------------------------
		
		while( $db_row = $this->ipsclass->DB->fetch_row( $attach_sql ) )
		{
			$_ok = 1;
			
			//-----------------------------------------
			// Ok?
			//-----------------------------------------
			
			if ( $_ok )
			{
				$rows[ $db_row['attach_id'] ] = $db_row;
			}
		}
		
		//-----------------------------------------
		// Return
		//-----------------------------------------
	
		return $rows;
	}
	
	/*-------------------------------------------------------------------------*/
	// Remove attachment clean up
	/*-------------------------------------------------------------------------*/
	/**
	* Recounts number of attachments for the articles row
	*
	* @return boolean
	*/
	function post_process_upload( $post_key, $rel_id, $args=array() )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$cnt = array( 'cnt' => 0 );
		
		//-----------------------------------------
		// Check..
		//-----------------------------------------
		
		if ( ! $post_key )
		{
			return 0;
		}
		
		
		$this->ipsclass->DB->simple_construct( array( "select" => 'COUNT(*) as cnt',
													  'from'   => 'attachments',
													  'where'  => "attach_post_key='{$post_key}'") );
		$this->ipsclass->DB->simple_exec();
	
		$cnt = $this->ipsclass->DB->fetch_row();
		
		if ( $cnt['cnt'] )
		{
			$this->ipsclass->DB->build_and_exec_query( array( 'update' => 'message_topics',
														  	  'set'    => "mt_hasattach=mt_hasattach+" . $cnt['cnt'],
														  	  'where'  => "mt_msg_id=" . intval( $rel_id ) ) );
		}
		
		return array( 'count' => $cnt['cnt'] );
	}
	
	/*-------------------------------------------------------------------------*/
	// Remove attachment clean up
	/*-------------------------------------------------------------------------*/
	/**
	* Recounts number of attachments for the articles row
	*
	* @return boolean
	*/
	function remove_attachment_clean_up( $attachment )
	{
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( ! $attachment['attach_id'] )
		{
			return FALSE;
		}
		
		$this->ipsclass->DB->build_query( array( 'select'   => 'p.pid',
												 'from'     => array( 'posts' => 'p' ),
												 'where'    => 'p.pid='. intval( $attachment['attach_rel_id'] ),
												 'add_join' => array( 0 => array( 'select' => 't.forum_id, t.tid',
																				  'from'   => array( 'topics' => 't' ),
																				  'where'  => 't.tid=p.topic_id',
																				  'type'   => 'inner' ) ) ) );
																				
		$this->ipsclass->DB->exec_query();
		
		$topic = $this->ipsclass->DB->fetch_row();
	
		if ( isset( $topic['tid'] ) )
		{
			//-----------------------------------------
			// GET PIDS
			//-----------------------------------------
		
			$pids  = array();
			$count = 0;
		
			$this->ipsclass->DB->simple_construct( array( 'select' => 'pid',
														  'from'   => 'posts',
														  'where'  => "topic_id=". $topic['tid'] ) );
			$this->ipsclass->DB->simple_exec();
				
			while ( $p = $this->ipsclass->DB->fetch_row() )
			{
				$pids[] = $p['pid'];
			}
		
			//-----------------------------------------
			// GET ATTACHMENT COUNT
			//-----------------------------------------
		
			if ( count( $pids ) )
			{
				$this->ipsclass->DB->simple_construct( array( "select" => 'count(*) as cnt',
															  'from'   => 'attachments',
															  'where'  => "attach_rel_module='post' AND attach_rel_id IN(".implode(",",$pids).")") );
				$this->ipsclass->DB->simple_exec();
			
				$cnt = $this->ipsclass->DB->fetch_row();
			
				$count = intval( $cnt['cnt'] );
			}
		
			$this->ipsclass->DB->simple_construct( array( 'update' => 'topics', 'set' => "topic_hasattach=". $count , 'where' => "tid=".$topic['tid'] ) );
			$this->ipsclass->DB->simple_exec();
		}
		
		return TRUE;
	}
	
	/*-------------------------------------------------------------------------*/
	// Bulk remove attachment permissions check
	/*-------------------------------------------------------------------------*/
	/**
	* Returns TRUE or FALSE
	* IT really does
	*
	* @return boolean
	*/
	function return_bulk_remove_permission( $attach_rel_ids=array() )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$ok_to_remove = FALSE;
		
		//-----------------------------------------
		// Allowed to remove?
		//-----------------------------------------
		
		if ( $this->ipsclass->member['group_access_cp'] )
		{
			$ok_to_remove = TRUE;
		}
		
		return $ok_to_remove;
	}
	
	/*-------------------------------------------------------------------------*/
	// Remove attachment permissions check
	/*-------------------------------------------------------------------------*/
	/**
	* Returns TRUE or FALSE
	* IT really does
	*
	* @return boolean
	*/
	function return_remove_permission( $attachment )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$ok_to_remove = FALSE;
		
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( ! $attachment['attach_id'] )
		{
			return FALSE;
		}
		
		//-----------------------------------------
		// Allowed to remove?
		//-----------------------------------------
		
		if ( $this->ipsclass->member['id'] == $attachment['attach_member_id'] )
		{
			$ok_to_remove = TRUE;
		}
		else if ( $this->ipsclass->member['g_is_supmod'] )
		{
			$ok_to_remove = TRUE;
		}
		else
		{
			//-----------------------------------------
			// Moderstor? Get forum ID
			//-----------------------------------------
			
			if ( isset( $this->ipsclass->member['_moderator'] ) )
			{
				$this->ipsclass->DB->build_query( array( 'select'   => 'p.pid',
														 'from'     => array( 'posts' => 'p' ),
														 'where'    => 'p.pid='. intval( $attach['attach_rel_id'] ),
														 'add_join' => array( 0 => array( 'select' => 't.forum_id, t.tid',
																						  'from'   => array( 'topics' => 't' ),
																						  'where'  => 't.tid=p.topic_id',
																						  'type'   => 'inner' ) ) ) );
																						
				$this->ipsclass->DB->exec_query();
				
				$topic = $this->ipsclass->DB->fetch_row();
				
				if ( isset( $topic['forum_id'] ) )
				{
					if ( isset($this->ipsclass->member['_moderator'][ $topic['forum_id'] ]['edit_post']) and $this->ipsclass->member['_moderator'][ $topic['forum_id'] ]['edit_post'] )
					{
						$ok_to_remove = TRUE;
					}
				}
			}	
		}
		
		return $ok_to_remove;
	}
	
	/*-------------------------------------------------------------------------*/
	// Get allowed upload sizes
	/*-------------------------------------------------------------------------*/
	/**
	* get_space_allowance
	*
	* Returns an array of the allowed upload sizes in bytes.
	* Return 'space_allowed' as -1 to not allow uploads.
    * Return 'space_allowed' as 0 to allow unlimited uploads
    * Return 'max_single_upload' as 0 to not set a limit
	*
	* @param	id	    Member ID
	* @param	string  MD5 post key
	* @return	array [ 'space_used', 'space_left', 'space_allowed', 'max_single_upload' ]
	*/
	function get_space_allowance( $post_key='', $member_id='' )
	{
		$max_php_size      = intval( $this->ipsclass->math_get_post_max_size() );
		$member_id         = intval( $member_id ? $member_id : $this->ipsclass->member['id'] );
		$space_left        = 0;
		$space_used        = 0;
		$space_allowed     = 0;
		$max_single_upload = 0;
		
		//-----------------------------------------
		// Allowed to attach?
		//-----------------------------------------
		
		if ( ! $member_id )
		{
			$space_allowed = -1;
		}
		else
		{
			//-----------------------------------------
			// Generate total space allowed
			//-----------------------------------------

			$total_space_allowed = ( $this->ipsclass->member['g_attach_per_post'] ? $this->ipsclass->member['g_attach_per_post'] : $this->ipsclass->member['g_attach_max'] ) * 1024;
			
			//-----------------------------------------
			// Generate space used figure
			//-----------------------------------------
			
			if ( $this->ipsclass->member['g_attach_per_post'] )
			{
				//-----------------------------------------
				// Per post limit...
				//-----------------------------------------
				
				$_space_used = $this->ipsclass->DB->build_and_exec_query( array( 'select' => 'SUM(attach_filesize) as figure',
																				 'from'   => 'attachments',
																				 'where'  => "attach_post_key='".$post_key."'" ) );

				$space_used    = intval( $_space_used['figure'] );
			}
			else
			{
				//-----------------------------------------
				// Global limit...
				//-----------------------------------------
				
				$_space_used = $this->ipsclass->DB->build_and_exec_query( array( 'select' => 'SUM(attach_filesize) as figure',
																				 'from'   => 'attachments',
																				 'where'  => 'attach_member_id='.$member_id ) );

				$space_used    = intval( $_space_used['figure'] );
			}	
			//-----------------------------------------
			// Generate space allowed figure
			//-----------------------------------------
			
			if ( $this->ipsclass->member['g_attach_max'] > 0 )
			{
				if ( $this->ipsclass->member['g_attach_per_post'] )
				{
					$space_allowed = intval( ( $this->ipsclass->member['g_attach_per_post'] * 1024 ) - $space_used );
					$space_allowed = $space_allowed < 0 ? -1 : $space_allowed;
				}
				else
				{
					$space_allowed = intval( ( $this->ipsclass->member['g_attach_max'] * 1024 ) - $space_used );
					$space_allowed = $space_allowed < 0 ? -1 : $space_allowed;
				}
			}
			else
			{
				if ( $this->ipsclass->member['g_attach_per_post'] )
				{
					$space_allowed = intval( ( $this->ipsclass->member['g_attach_per_post'] * 1024 ) - $space_used );
					$space_allowed = $space_allowed < 0 ? -1 : $space_allowed;
				}
				else
				{
					# Unlimited
					$space_allowed = 0;
				}
			}
			
			//-----------------------------------------
			// Generate space left figure
			//-----------------------------------------
			
			$space_left = $space_allowed ? $space_allowed : 0;
			$space_left = ($space_left < 0) ? -1 : $space_left;
			
			//-----------------------------------------
			// Generate max upload size
			//-----------------------------------------
			
			if ( ! $max_single_upload )
			{
				if ( $space_left > 0 AND $space_left < $max_php_size )
				{
					$max_single_upload = $space_left;
				}
				else if ( $max_php_size )
				{
					$max_single_upload = $max_php_size;
				}
			}
		}
		
		$return = array( 'space_used' => $space_used, 'space_left' => $space_left, 'space_allowed' => $space_allowed, 'max_single_upload' => $max_single_upload, 'total_space_allowed' => $total_space_allowed );
		
		return $return;
	}
	
	/*-------------------------------------------------------------------------*/
	// Get settings
	/*-------------------------------------------------------------------------*/
	/**
	* get_settings
	*
	* Returns an array of settings:
	* 'siu_thumb'	= Allow thumbnail creation?
	* 'siu_height'  = Height of the generated thumbnail in pixels
	* 'siu_width'   = Width of the generated thumbnail in pixels
	* 'upload_dir'  = Base upload directory (must be a full path)
	*
	* You can omit any of these settings and IPB will use the default
	* settings (which are the ones entered into the ACP for post thumbnails)
	*
	* @return	boolean
	*/
	function get_settings()
	{
		$this->settings = array();
		
		return true;
	}

}

?>