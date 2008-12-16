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
|   > $Date: 2007-07-13 13:51:15 -0400 (Fri, 13 Jul 2007) $
|   > $Revision: 1087 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > API: Components
|   > Module written by Matt Mecham
|   > Date started: Wednesday 19th July 2005 (16:57)
|
+--------------------------------------------------------------------------
*/

/**
* API: Components
*
* EXAMPLE USAGE
* <code>
* //-- UPDATE EXISTING ROW --//
* $api =  new api_components();
* # Optional - if $ipsclass is not passed, it'll init
* $api->ipsclass =& $this->ipsclass;
* $api->api_init();
* $update = array (
*					'com_version' => '1.3',
*					'com_menu_data' => array( 0 => array(
*												    	  'menu_text'      => 'Link text',
*												    	  'menu_url'       => 'code=showme',
*												    	  'menu_redirect'  => 0,
*												    	  'menu_permbit'   => 'keyhere',
*												    	  'menu_permlang'  => 'Allow CREATE Something' ),
*											 1 => array(
*												    	  'menu_text'      => 'Link text 2',
*												    	  'menu_url'       => 'section=tools&act=op&code=showme2',
*												    	  'menu_redirect'  => 1,
*												    	  'menu_permbit'   => 'add',
*												    	  'menu_permlang'  => '' )
*											) );
*												    	 
* $api->acp_component_update( 'blog', $update );
* //-- ENABLE A COMPONENT --//
* $api->acp_component_enable( 'blog' );
* </code>
*
* COMPONENT FIELDS
* <code>
* 'com_title'       = Component Title
* 'com_author'      = Component Author
* 'com_version'     = Component Version
* 'com_url'         = Component Home URL
* 'com_menu_data'   = Component Menu Data Array
* 'com_enabled'     = Component Enabled
* 'com_safemode'    = Component Safemode (admins cannot alter)
* 'com_section_key' = Component Section (Key, must match filename. eg: blog if using blog.php)
* 'com_description' = Component Description
* 'com_url_uri'     = Component Board Header URL URI
* 'com_url_title'   = Component Board Header URL Title
* </code>
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

if ( ! defined('IPS_API_CORE_LOADED') )
{
	require_once( IPS_API_PATH.'/api_core.php' );
	
	/**
	* CORE LOADED
	*/
	define( 'IPS_API_CORE_LOADED', TRUE );
}

/**
* API: Components
*
* This class deals with all available components functions.
*
* @package		InvisionPowerBoard
* @subpackage	APIs
* @author  	 	Matt Mecham
* @version		2.1
* @since		2.1.0
*/
class api_components extends api_core
{
	/**
	* Loaded component
	*
	* @var array
	*/
	var $component;
	
	/**
	* XML Parser Object
	*
	* @var object
	*/	
	var $xml;
	
	/*-------------------------------------------------------------------------*/
	// Get component by key
	/*-------------------------------------------------------------------------*/
	/**
	* Updates a component
	*
	* @param	string	Component key (eg: blog)
	* @return	void	Populates $this->component
	*/
	function get_component_by_key( $key )
	{
		$this->component = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*',
																			 'from'   => 'components',
																			 'where'  => 'com_section="'.$key.'"' ) );
	}
	
	/*-------------------------------------------------------------------------*/
	// Create menu
	/*-------------------------------------------------------------------------*/
	/**
	* Creates serialized string from a menu array
	* <code>
	* $menu_data [ $item_id ]['menu_text']
	* $menu_data [ $item_id ]['menu_url']
	* $menu_data [ $item_id ]['menu_redirect']
	* $menu_data [ $item_id ]['menu_permbit']
	* $menu_data [ $item_id ]['menu_permlang']
	* </code>
	*
	* @param	array	multi-dimenisonal array
	* @return void;
	*/
	function acp_component_create_menu_data( $menu_data )
	{
		if ( is_array( $menu_data ) )
		{
			return serialize( $menu_data );
		}
	}
	
	
	/*-------------------------------------------------------------------------*/
	// Import XML File
	/*-------------------------------------------------------------------------*/
	/**
	* Updates/inserts a component from xml file
	*
	* @param	string	Path to XML File
	* @return 	array;
	*/
	function acp_component_import( $xml_file_path )
	{	
		if( !file_exists($xml_file_path) )
		{
			return array( 'inserted' => 0, 'updated' => 0 );
		}
		
        $content = implode( '', @file( $xml_file_path ) );
        
        if( !$content )
        {
	        return array( 'inserted' => 0, 'updated' => 0 );
        }

        //-----------------------------------------
        // Get current components.
        //-----------------------------------------
        $cur_components = array();
        
        $this->ipsclass->DB->simple_construct( array( 'select' => 'com_id, com_section',
                                                      'from'   => 'components',
                                                      'order'  => 'com_id' ) );

        $this->ipsclass->DB->simple_exec();

        while ( $r = $this->ipsclass->DB->fetch_row() )
        {
            $cur_components[ $r['com_section'] ] = $r['com_id'];
        }

        //-----------------------------------------
        // Get xml mah-do-dah
        //-----------------------------------------

        require_once( KERNEL_PATH.'class_xml.php' );

        $this->xml = new class_xml();

        //-----------------------------------------
        // Unpack the datafile
        //-----------------------------------------

        $this->xml->xml_parse_document( $content );

        //-----------------------------------------
        // pArse
        //-----------------------------------------

        $fields = array( 'com_title'   , 'com_description', 'com_author' , 'com_url', 'com_version', 'com_menu_data',
                         'com_enabled' , 'com_safemode'   , 'com_section', 'com_filename', 'com_url_title', 'com_url_uri' );

        if ( ! is_array( $this->xml->xml_array['componentexport']['componentgroup']['component'][0]  ) )
        {
            //-----------------------------------------
            // Ensure [0] is populated
            //-----------------------------------------

            $this->xml->xml_array['componentexport']['componentgroup']['component'] = array( 0 => $this->xml->xml_array['componentexport']['componentgroup']['component'] );
        }

        $updated = $inserted = 0;
        
        foreach( $this->xml->xml_array['componentexport']['componentgroup']['component'] as $id => $entry )
        {
            $newrow = array();

            foreach( $fields as $f )
            {
                $newrow[$f] = $entry[ $f ]['VALUE'];
            }

            $this->ipsclass->DB->force_data_type = array( 'com_version' => 'string' );

            if ( $cur_components[ $entry['com_section']['VALUE'] ] )
            {
                //-----------------------------------------
                // Update
                //-----------------------------------------

                $this->ipsclass->DB->do_update( 'components', $newrow, 'com_id='.$cur_components[ $entry['com_section']['VALUE'] ] );
                $updated++;
            }
            else
            {
                //-----------------------------------------
                // INSERT
                //-----------------------------------------

                $newrow['com_date_added'] = time();

                $this->ipsclass->DB->do_insert( 'components', $newrow );
                $inserted++;
            }
        }
        
        $this->acp_component_rebuildcache();
        
        return array( 'inserted' => $inserted, 'updated' => $updated );
    }	
	
	/*-------------------------------------------------------------------------*/
	// Update component
	/*-------------------------------------------------------------------------*/
	/**
	* Updates a component
	*
	* @param	string	Component key (eg: blog)
	* @param	array	Component fields ( must match class fields, see class docs )
	* @return void;
	*/
	function acp_component_insert( $key, $fields )
	{
		//-------------------------------
		// INIT
		//-------------------------------
		
		$key_saved = 0;
		
		$com_title       = trim( $fields['com_title'] );
		$com_version     = trim( $fields['com_version'] );
		$com_description = trim( $this->ipsclass->txt_stripslashes( $this->ipsclass->txt_UNhtmlspecialchars($fields['com_description'])) );
		$com_author      = trim( $fields['com_author'] );
		$com_url         = trim( $fields['com_url'] );
		$com_menu_data   = $fields['com_menu_data'];
		$com_enabled     = intval($fields['com_enabled']);
		$com_section_key = trim( $fields['com_section_key'] );
		$com_safemode    = intval($fields['com_safemode']);
		$com_url_title   = trim( $this->ipsclass->txt_stripslashes( $this->ipsclass->txt_windowstounix($fields['com_url_title']) ) );
		$com_url_uri     = trim( $this->ipsclass->txt_stripslashes( $this->ipsclass->txt_windowstounix($fields['com_url_uri']) ) );
		
		//-------------------------------
		// Check?
		//-------------------------------
		
		if ( ! $com_title OR ! $com_menu_data OR ! $com_section_key )
		{
			$this->api_error[] = "input_missing_fields";
			return;
		}
		
		//-------------------------------
		// Already a key in use?
		//-------------------------------
		
		$this->get_component_by_key( $key );
		
		if ( $this->component['com_id'] )
		{
			$this->api_error[] = "duplicate_component_key";
			return;
		}
		
		//-------------------------------
		// Menu not already serialized?
		//-------------------------------
		
		if ( is_array( $com_menu_data ) )
		{
			$com_menu_data = $this->acp_component_create_menu_data( $com_menu_data );
		}
		
		//-------------------------------
		// Save it
		//-------------------------------
		
		$this->ipsclass->DB->force_data_type = array( 'com_version' => 'string' );
		
		$array = array( 'com_title'       => $com_title,
						'com_author'      => $com_author,
						'com_version'     => $com_version,
						'com_url'         => $com_url,
						'com_menu_data'   => $com_menu_data,
						'com_enabled'     => $com_enabled,
						'com_safemode'    => $com_safemode,
						'com_section'     => $com_section_key,
						'com_filename'    => $com_section_key,
						'com_description' => $com_description,
						'com_url_uri'     => $com_url_uri,
						'com_url_title'   => $com_url_title,
						'com_date_added'  => time(),
					 );
			
		//-------------------------------
		// Got anything to save?
		//-------------------------------
		
		if ( count( $array ) )
		{
			$this->ipsclass->DB->do_insert( 'components', $array );
		}
		
		//-------------------------------
		// Rebuild cache
		//-------------------------------
		
		$this->acp_component_rebuildcache();
	}
	
	/*-------------------------------------------------------------------------*/
	// Update component
	/*-------------------------------------------------------------------------*/
	/**
	* Updates a component
	*
	* @param	string	Component key (eg: blog)
	* @param	array	Component fields ( must match class fields, see class docs )
	* @return void;
	*/
	function acp_component_update( $key, $fields )
	{
		//-------------------------------
		// INIT
		//-------------------------------
		
		$key_saved = 0;
		$save      = array();
		
		//-------------------------------
		// Check?
		//-------------------------------
		
		if ( ! $fields OR ! $key )
		{
			$this->api_error[] = "input_missing_fields";
			return;
		}
		
		//-------------------------------
		// Get stuff from DB
		//-------------------------------
		
		$this->get_component_by_key( $key );
		
		if ( ! $this->component['com_id'] )
		{
			$this->api_error[] = "duplicate_component_key";
			return;
		}
		
		$this->component['_com_section'] = $this->component['com_section'];
		
		//-------------------------------
		// Update...
		//-------------------------------
		
		foreach( $fields as $k => $v )
		{
			if ( ( $k == 'com_section_key' OR $k == 'com_section' OR $k == 'com_filename' ) AND $key_saved == 0 )
			{
				//-------------------------------
				// Make sure we don't have another
				// key the same
				//-------------------------------
				
				if ( $v != $this->component['_com_section'] )
				{
					$tmp = $this->ipsclass->DB->build_and_exec_query( array( 'select' => 'com_id',
																			 'from'   => 'components',
																			 'where'  => 'com_section="'.$v.'"' ) );
																			 
					if ( $tmp['com_id'] AND $tmp['com_id'] != $this->component['com_id'] )
					{
						$this->api_error[] = "duplicate_component_key";
						return;
					}
				}
				
				$save['com_section']  = $v;
				$save['com_filename'] = $v;
				
				$key_saved = 1;
			}
			else if ( $k == 'com_menu_data' )
			{
				//-------------------------------
				// Menu not already serialized?
				//-------------------------------
				
				if ( is_array( $v ) )
				{
					$v = $this->acp_component_create_menu_data( $v );
				}
				
				$save[ $k ] = $v;
			}
			else
			{
				$save[ $k ] = $v;
			}
		}
			
		//-------------------------------
		// Got anything to save?
		//-------------------------------
		
		if ( count( $save ) )
		{
			$this->ipsclass->DB->do_update( 'components', $save, 'com_id='.$this->component['com_id'] );
		}
		
		$this->acp_component_rebuildcache();
	}
	
	/*-------------------------------------------------------------------------*/
	// Remove component
	/*-------------------------------------------------------------------------*/
	/**
	* Remove a component [ no confirmation screens! ]
	*
	* @param	string	Component key (eg: blog)
	* @return	void	
	*/
	function acp_component_remove( $key )
	{
		$this->ipsclass->DB->build_and_exec_query( array( 'delete' => 'components', 'where' => 'com_section="'.$key.'"' ) );
		$this->acp_component_rebuildcache();
	}
	
	/*-------------------------------------------------------------------------*/
	// Enable component
	/*-------------------------------------------------------------------------*/
	/**
	* Enable a component
	*
	* @param	string	Component key (eg: blog)
	* @return	void	
	*/
	function acp_component_enable( $key )
	{
		$this->acp_component_update( $key, array( 'com_enabled' => 1 ) );
		$this->acp_component_rebuildcache();
	}
	
	/*-------------------------------------------------------------------------*/
	// Disable component
	/*-------------------------------------------------------------------------*/
	/**
	* Enable a component
	*
	* @param	string	Component key (eg: blog)
	* @return	void	
	*/
	function acp_component_disable( $key )
	{
		$this->acp_component_update( $key, array( 'com_enabled' => 0 ) );
		$this->acp_component_rebuildcache();
	}
	
	/*-------------------------------------------------------------------------*/
	// Components Rebuild Cache
	/*-------------------------------------------------------------------------*/
	/**
	* Rebuild cache
	*
	* @return	void	
	*/
	function acp_component_rebuildcache()
	{
		require_once( ROOT_PATH. 'sources/action_admin/components.php' );
		$components           =  new ad_components();
		$components->ipsclass =& $this->ipsclass;
		$components->components_rebuildcache();
	}
	
	
	
}



?>