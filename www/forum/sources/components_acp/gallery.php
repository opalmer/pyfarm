<?php

/*
+--------------------------------------------------------------------------
|   IP.Gallery Component
|   ========================================
|   by Brandon Farber
|   (c) 2001 - 2007 Invision Power Services
|   http://www.invisionpower.com
|   ========================================
|   Web:	http://www.invisiongallery.com
|   Email:	brandon@invisionpower.com
+---------------------------------------------------------------------------
|   > $Date: 2007-06-21 14:09:44 -0400 (Thu, 21 Jun 2007) $
|   > $Revision: 340 $
|	> $Author: bfarber $
|
|   > Main Admin Module Loader
|   > Script written by Brandon Farber
|	> Script Started by Joshua Williams
|   $Id: gallery.php 340 2007-06-21 18:09:44Z bfarber $
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_ACP' ) )
{
	die( "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'." );
}

define( 'GALLERY_PATH'     , ROOT_PATH . 'sources/components_acp/gallery/' );
define( 'GALLERY_LIBS'     , ROOT_PATH . 'sources/components_public/gallery/lib/' );

class ad_gallery 
{
	# Objects
	var $ipsclass;
	var $gallery_lib;

	function auto_run()
	{
		//-----------------------------------------
		// Is Gallery installed?
		//-----------------------------------------
		
		if ( ! @is_dir( GALLERY_PATH ) )
		{
			$this->ipsclass->admin->show_inframe( "" );
		}
		else
		{
			define( 'IPB_CALLED', 1 );
			
			$this->ipsclass->admin->page_title	=& $this->ipsclass->acp_lang['gbl_gallery_title'];
			$this->ipsclass->admin->page_detail	=& $this->ipsclass->acp_lang['gbl_gallery_detail'];
			$this->ipsclass->admin->nav[] = array( 'section=components&amp;act=gallery', $this->ipsclass->acp_lang['gbl_gallery_nav'] );
		
    		$this->ipsclass->DB->load_cache_file( ROOT_PATH . 'sources/sql/' . SQL_DRIVER . '_gallery_admin_queries.php', 'gallery_admin_sql_queries' );
           
			//-----------------------------------------
			// What are we doing?
			//-----------------------------------------

            $section = $this->ipsclass->input['code'] ? 'ad_' . $this->ipsclass->input['code'] : 'ad_overview';
            
            if( !in_array( $section, array( 'ad_overview', 'ad_albums', 'ad_cats', 'ad_groups', 'ad_media', 'ad_postform', 'ad_stats', 'ad_tools' ) ) )
            {
	            $section = 'ad_overview';
            }
            
            $this->ipsclass->vars['gallery_images_path'] 	= str_replace( "&#092;", "\\", $this->ipsclass->vars['gallery_images_path'] 	);
            $this->ipsclass->vars['gallery_watermark_path'] = str_replace( "&#092;", "\\", $this->ipsclass->vars['gallery_watermark_path'] 	);

			//-----------------------------------------
			// Load generic library
			//-----------------------------------------
			
			require GALLERY_LIBS . 'lib_gallery.php';
            $this->gallery_lib 				=  new lib_gallery();
            $this->gallery_lib->ipsclass 	=& $this->ipsclass;

			//-----------------------------------------
			// Run the main module
			//-----------------------------------------
			
			require GALLERY_PATH . $section . '.php';
          	$PLUGIN 			=  new ad_plugin_gallery_sub();
           	$PLUGIN->ipsclass 	=& $this->ipsclass;
           	$PLUGIN->glib 		=& $this->gallery_lib;
           	$PLUGIN->auto_run();
		}		
	}		
}

?>