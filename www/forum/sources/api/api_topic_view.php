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
|   > $Date: 2005-10-10 14:08:54 +0100 (Mon, 10 Oct 2005) $
|   > $Revision: 23 $
|   > $Author: matt $
+---------------------------------------------------------------------------
|
|   > API: Languages
|   > Module written by Matt Mecham
|   > Date started: Wednesday 30th November 2005 (11:40)
|
+--------------------------------------------------------------------------
*/

/**
* API: Forums
*
* EXAMPLE USAGE
* <code>
  $api = new api_topic_view();
  $api->ipsclass =& $this->ipsclass;
  $api->topic_list_config['forums'] = array( 1,2,3,4 );
  $api->topic_list_config['limit'] = 10;
  $topics = $api->return_topic_list_data();
  // Loop on $topics and output the data
* </code>
*
* API will return attachment data in an array
* attachment_data - you are responsible for any
* processing or displaying if you wish to do so
*
* Attachment data returned will be in an array with the
* topic/post data like so:

            [attachment_data] => Array
                (
                    [0] => Array
                        (
                            [size] => 41.29
                            [method] => post
                            [id] => 24
                            [file] => somefile.jpg
                            [hits] => 0
                            [thumb_location] => 
                            [type] => image
                            [thumb_x] => 0
                            [thumb_y] => 0
                            [ext] => jpg
                        )

                )
* You can then loop on the attachment_data key (if
* it exists - it won't exist if there are no attachments)
* and process each attachment in the record.
*
* Attachment types - thumb = thumbnail, image = regular image
*	reg = regular attachment
*
* @package		InvisionPowerBoard
* @subpackage	APIs
* @author		Matt Mecham
* @copyright	Invision Power Services, Inc.
* @version		2.1
*/

if ( ! defined( 'IPS_API_PATH' ) )
{
	/**
	* Define classes path
	*/
	define( 'IPS_API_PATH', dirname(__FILE__) ? dirname(__FILE__) : '.' );
}

if ( ! class_exists( 'api_core' ) )
{
	require_once( IPS_API_PATH.'/api_core.php' );
}

/**
* API: Languages
*
* This class deals with all available language functions.
*
* @package		InvisionPowerBoard
* @subpackage	APIs
* @author  	 	Matt Mecham
* @version		2.1
* @since		2.1.0
*/
class api_topic_view extends api_core
{
	/**
	* IPS Class Object
	*
	* @var object
	*/
	//var $ipsclass;
	
	/**
	* Topic list config
	*
	* @var array
	*/
	var $topic_list_config = array( 'offset'      => 0,
								    'limit'       => 5,
									'forums'      => '*',
									'order_field' => 'last_post',
									'order_by'    => 'DESC' );
									
	var $attach_pids = array();
									
	/*-------------------------------------------------------------------------*/
	// Returns an array of topic data
	/*-------------------------------------------------------------------------*/
	/**
	* Returns an array of topic data
	* NOTE: Returns ALL topics regardless of permission as
	* if viewed from the ACP.
	*
	* @return   array	Array of topic data
	*/
	function return_topic_list_data( $view_as_guest=0 )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$topics = array();
		
		$this->ipsclass->init_load_cache( array( 'attachtypes' ) );
		
		//-----------------------------------------
		// Set up
		//-----------------------------------------

		$this->topic_list_config['order_field'] = ( $this->topic_list_config['order_field'] == 'started' )  ? 'start_date' : $this->topic_list_config['order_field'];
		$this->topic_list_config['order_field'] = ( $this->topic_list_config['order_field'] == 'lastpost' ) ? 'last_post'  : $this->topic_list_config['order_field'];
		$this->topic_list_config['forums']      = ( is_array( $this->topic_list_config['forums'] ) ) ? implode( ",", $this->topic_list_config['forums'] ) : $this->topic_list_config['forums'];
		
		//-----------------------------------------
		// Fix up allowed forums
		//-----------------------------------------
		
		if ( $this->topic_list_config['forums'] )
		{
			# Init forums...
			if ( $view_as_guest )
			{
				$this->ipsclass->perm_id_array           = explode( ',', $this->ipsclass->create_perms_from_group( $this->ipsclass->vars['guest_group'] ) );
				$this->ipsclass->forums->strip_invisible = 1;
			}
			
			$this->ipsclass->forums->forums_init();
			
			# Reset topics...
			if ( $this->topic_list_config['forums'] == '*' )
			{
				$_tmp_array 					   = array();
				$this->topic_list_config['forums'] = '';
				
				foreach( $this->ipsclass->forums->forum_by_id as $id => $data )
				{
					$_tmp_forums[] = $id;
				}
			}
			else
			{
				$_tmp_forums                       = explode( ',', $this->topic_list_config['forums'] );
				$_tmp_array 					   = array();
				$this->topic_list_config['forums'] = '';
			}
			
			foreach( $_tmp_forums as $_id )
			{
				if ( $view_as_guest )
				{
					if ( ! $this->ipsclass->forums->forums_quick_check_access( $_id ) )
					{
						$_tmp_array[] = $_id;
					}
				}
				else
				{
					$_tmp_array[] = $_id;
				}
			}
			
			$this->topic_list_config['forums'] = implode( ',', $_tmp_array );
		}
		
		//-----------------------------------------
		// Get from the DB
		//-----------------------------------------
		
		$this->ipsclass->DB->build_query( array( 'select'   => 't.*',
												 'from'     => array( 'topics' => 't' ),
												 'where'    => 't.approved=1 AND t.forum_id IN (0,'.$this->topic_list_config['forums'].')',
											     'order'    => $this->topic_list_config['order_field'].' '.$this->topic_list_config['order_by'],
												 'limit'    => array( $this->topic_list_config['offset'], $this->topic_list_config['limit'] ),
												 'add_join' => array( 
																	  0 => array( 'select' => 'p.*',
																				  'from'   => array( 'posts' => 'p' ),
																				  'where'  => 't.topic_firstpost=p.pid',
																				  'type'   => 'left' ),
																	  1 => array( 'select' => 'm.id as member_id, m.members_display_name as member_name, m.mgroup, m.email',
																	  			  'from'   => array( 'members' => 'm' ),
																				  'where'  => "m.id=p.author_id",
																				  'type'   => 'left' ),
																	  2 => array( 'select' => 'f.id as forum_id, f.name as forum_name, f.use_html',
																	  			  'from'   => array( 'forums' => 'f' ),
																				  'where'  => "t.forum_id=f.id",
																				  'type'   => 'left' ) )
										)      );
		
		$this->ipsclass->DB->exec_query();
		
		while( $row = $this->ipsclass->DB->fetch_row() )
		{
			if( $row['topic_hasattach'] )
			{
				$this->attach_pids[] = $row['pid'];
			}
			
			//-----------------------------------------
			// Guest name?
			//-----------------------------------------
			
			$row['member_name']    = $row['member_name'] ? $row['member_name'] : $row['author_name'];
			
			//-----------------------------------------
			// Topic link
			//-----------------------------------------
			
			$row['link-topic'] = $this->ipsclass->base_url.'showtopic='.$row['tid'];
			$row['link-forum'] = $this->ipsclass->base_url.'showforum='.$row['forum_id'];
			
			$topics[] = $row;
		}
		
		if( count( $this->attach_pids ) )
		{
			$final_attachments = array();
			
			$this->ipsclass->DB->simple_construct( array( 'select' => '*',
														  'from'   => 'attachments',
														  'where'  => "attach_rel_module='post' AND attach_rel_id IN (".implode(",", $this->attach_pids).")"
												 )      );

			$this->ipsclass->DB->simple_exec();
			
			while ( $a = $this->ipsclass->DB->fetch_row() )
			{
				$final_attachments[ $a[ 'attach_pid' ] ][ $a['attach_id'] ] = $a;
			}
			
			$final_topics = array();
			
			foreach( $topics as $mytopic )
			{
				$this_topic_attachments = array();
				
				foreach ( $final_attachments as $pid => $data )
				{
					if( $pid <> $mytopic['pid'] )
					{
						continue;
					}
					
					$temp_out = "";
					$temp_hold = array();
					
					foreach( $final_attachments[$pid] as $aid => $row )
					{
						//-----------------------------------------
						// Is it an image, and are we viewing the image in the post?
						//-----------------------------------------
						
						if ( $this->ipsclass->vars['show_img_upload'] and $row['attach_is_image'] )
						{
							if ( $this->ipsclass->vars['siu_thumb'] AND $row['attach_thumb_location'] AND $row['attach_thumb_width'] )
							{ 
								$this_topic_attachments[] = array( 'size' 		=> $this->ipsclass->size_format( $row['attach_filesize'] ),
																	'method' 	=> 'post',
																	'id'		=> $row['attach_id'],
																	'file'		=> $row['attach_file'],
																	'hits'		=> $row['attach_hits'],
																	'thumb_location'	=> $row['attach_thumb_location'],
																	'type'		=> 'thumb',
																	'thumb_x'	=> $row['attach_thumb_width'],
																	'thumb_y'	=> $row['attach_thumb_height'],
																	'ext'		=> $row['attach_ext'],
																);
							}
							else
							{
								$this_topic_attachments[] = array( 'size' 		=> $this->ipsclass->size_format( $row['attach_filesize'] ),
																	'method' 	=> 'post',
																	'id'		=> $row['attach_id'],
																	'file'		=> $row['attach_file'],
																	'hits'		=> $row['attach_hits'],
																	'thumb_location'	=> $row['attach_thumb_location'],
																	'type'		=> 'image',
																	'thumb_x'	=> $row['attach_thumb_width'],
																	'thumb_y'	=> $row['attach_thumb_height'],
																	'ext'		=> $row['attach_ext'],
																);
							}
						}
						else
						{
								$this_topic_attachments[] = array( 'size' 		=> $this->ipsclass->size_format( $row['attach_filesize'] ),
																	'method' 	=> 'post',
																	'id'		=> $row['attach_id'],
																	'file'		=> $row['attach_file'],
																	'hits'		=> $row['attach_hits'],
																	'thumb_location'	=> $row['attach_thumb_location'],
																	'type'		=> 'reg',
																	'thumb_x'	=> $row['attach_thumb_width'],
																	'thumb_y'	=> $row['attach_thumb_height'],
																	'ext'		=> $row['attach_ext'],
																);
						}
					}
				}

				if( count( $this_topic_attachments ) )
				{
					$mytopic['attachment_data'] = $this_topic_attachments;
				}
				
				$final_topics[] = $mytopic;
			}
		}
		
		//-----------------------------------------
		// Return...
		//-----------------------------------------
				
		if( count( $final_topics ) )
		{
			return $final_topics;
		}
		else
		{
			return $topics;
		}			
	}
	
}
?>