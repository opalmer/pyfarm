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
|   > $Revision: 128 $
|   > $Date: 2006-01-20 12:46:26 +0000 (Fri, 20 Jan 2006) $
+---------------------------------------------------------------------------
|
|   > CAPTCHA ANTI-SPAM CLASS
|   > Script written by Matt Mecham
|   > Date started: Thursday 2 February (11:08)
|
+---------------------------------------------------------------------------
*/

/**
* CAPTCHA CLASS
* ipsclass dependent method of showing an anti-spam image
*
* This creates a unique MD5 hash and stores it in the DB with
* an 8 character string of 0-9a-z
*
* <code>
* CREATE TABLE captcha (
* 	captcha_unique_id	VARCHAR(32) NOT NULL default '',
* 	captcha_string		VARCHAR(100) NOT NULL default '',
* 	captcha_ipaddress	VARCHAR(16) NOT NULL default '',
* 	captcha_date		INT(10) NOT NULL default '',
* 	PRIMARY KEY (captcha_unique_id )
* );
* </code>
* @package		IPDYNAMIC
* @author		Matt Mecham
* @copyright	Invision Power Services, Inc.
* @version		1.0
*/

class class_captcha
{
	/**
	* Global IPS class array
	* @var	object
	*/
	var $ipsclass;
	
	/**
	* GD version
	* @var integer
	*/
	var $gd_version = 0;
	
	/**
	* Backgrounds path
	* @var string
	*/
	var $path_background = '';
	
	/**
	* Fonts path
	* @var string
	*/
	var $path_fonts      = '';
	
	/**
	* Return an error if GD is not installed?
	* My most verbose string name. I'm so proud.
	* @var boolean
	*/
	var $show_error_gd_img = FALSE;
	
	/**
	* Error string
	* @var string
	*/
	var $error_string = '';
	
	/*-------------------------------------------------------------------------*/
	// Initializes captcha session
	/*-------------------------------------------------------------------------*/
	/**
	* Initializes captcha session
	* Clears out "dead" entries, adds new row and returns
	* unique ID for use in an image
	*
	* @return string	Captcha unique ID
	*/
	function captcha_create_session()
	{
		//-----------------------------------------
		// Clear old
		//-----------------------------------------
		
		$this->captcha_clear_sessions( $this->ipsclass->ip_address );
		
		//-----------------------------------------
		// Create new ID
		//-----------------------------------------
		
		$captcha_unique_id = md5( uniqid( time() ) );
		$captcha_string    = "";
		
		//-----------------------------------------
		// Create new string
		//-----------------------------------------
		
		mt_srand( (double) microtime() * 1000000 );
		$final_rand = md5( uniqid( mt_rand(), TRUE ) );
		mt_srand(); 

		for( $i = 0; $i < 6; $i++ )
		{
			$captcha_string .= $final_rand{ mt_rand(0, 31) };
		}
		
		//-----------------------------------------
		// Add to the DB
		//-----------------------------------------
		
		$this->ipsclass->DB->do_insert( 'captcha', array( 'captcha_unique_id' => $captcha_unique_id,
														  'captcha_string'    => $captcha_string,
														  'captcha_ipaddress' => $this->ipsclass->ip_address,
														  'captcha_date'      => time() ) );
														
		//-----------------------------------------
		// Return ID
		//-----------------------------------------
		
		return $captcha_unique_id;
	}
	
	/*-------------------------------------------------------------------------*/
	// Captcha validate
	/*-------------------------------------------------------------------------*/
	
	/**
	* Show the captcha bot image 
	*
	* @param	string	captcha_unique_id	The captcha DB row
	* @param	string	captcha_input		The user entered data
	* @return	void
	* @since	1.0
	*/
	function captcha_validate( $captcha_unique_id, $captcha_input )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$captcha_input = trim( strtolower($captcha_input) );
		
		//-----------------------------------------
		// Get the info from the DB
		//-----------------------------------------
		
		$captcha = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*',
																	 'from'   => 'captcha',
																	 'where'  => "captcha_unique_id='".$captcha_unique_id."'" ) );
	
		if ( ! $captcha['captcha_unique_id'] )
		{
			return FALSE;
		}
		
		//-----------------------------------------
		// Check...
		//-----------------------------------------
		
		if ( $captcha['captcha_string'] != $captcha_input )
		{
			return FALSE;
		}
		else
		{
			return TRUE;
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Captcha show image
	/*-------------------------------------------------------------------------*/
	
	/**
	* Show the captcha bot image 
	*
	* @param	string	captcha_unique_id
	* @return	void
	* @since	1.0
	*/
	function captcha_show_image( $captcha_unique_id )
	{
		//-----------------------------------------
		// CHECK
		//-----------------------------------------
		
		if ( ! $captcha_unique_id )
		{
			return FALSE;
		}
		
		//-----------------------------------------
		// Get the info from the DB
		//-----------------------------------------
		
		$captcha = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*',
																	 'from'   => 'captcha',
																	 'where'  => "captcha_unique_id='".$captcha_unique_id."'" ) );
	
		if ( ! $captcha['captcha_unique_id'] )
		{
			return FALSE;
		}
		
		//-----------------------------------------
		// Show it...
		//-----------------------------------------
		
		$this->captcha_show_gd_img( $captcha['captcha_string'] );
	}
	
	/*-------------------------------------------------------------------------*/
	// Show GD created security image...
	/*-------------------------------------------------------------------------*/
	
	/**
	* Show anti-spam bot GD image numbers
	*
	* @param	string	Number string
	* @return	void
	* @since	1.0
	*/
	function captcha_show_gd_img( $content="" )
	{
		//-----------------------------------------
		// Is GD Available?
		//-----------------------------------------
		
		if ( ! extension_loaded('gd') )
		{
			if ( $this->show_error_gd_img )
			{
				@header( "Content-Type: image/gif" );
				print base64_decode( "R0lGODlhyAA8AJEAAN/f3z8/P8zMzP///yH5BAAAAAAALAAAAADIADwAAAL/nI+py+0Po5y02ouz3rz7D4biSJbmiabqyrbuC8fyTNf2jef6zvf+DwwKh8Si8YhMlgACwEHQXESfzgOzykQAqgMmFMr9Rq+GbHlqAFsFWnFVrfwIAvQAu15P0A14Nn8/ADhXd4dnl2YYCAioGHBgyDbnNzBIV0gYxzEYWdg1iLAnScmFuQdAt2UKZTcl+mip+HoYG+tKOfv3N5l5garnmPt6CwyaFzrranu7i0crObvoaKvMyMhrIQhFuyzcyGwXcOpoLYg7DGsZXk5r6DSN51RNfF0RPU5sy7gpnH5bjLhrk7Y9/RQNisfq2CRJauTR6xUuFyBx/yrWypMMmTlq/9IwQnKWcKG5cvMeShBIMOFIaV9w2eti6SBABAyjvBRFMaZCMaxsqtxl8iShjpj+VfqGCJg4XzOfJCK5DVWliFNXlSIENKjWrVy7ev0KNqzYsWTFwhlFU8waLk+efGF7hi0aSgu3iGmV1cxdoGTinimbiGOeP8SWhps6z3AkeMMWW20mMykqQyuJDbYWdufKBJWc2uWmAJZdO0yOKfTCCqHGiO4E/oKGriTYaBw5g/MDqynNlW9Uhmx66tM2i05dNcM8O6Rg2MHLYKraLTpDcLebTke4APkcduAoku0DWpe24MI96ewZPdiy6Rlx/0Y+XJevlNu/z6vtlHFxZbpv9f9edYkfxgVmjnqSxXYOYPfFVMgXIHGC0ltWDNXYJ6Lsw82AFVpWEk4pEabgbsfBM5FphyDWRh1OLCUgbC06NtNU6UV1T1Jl3YhjjjruyGOPPv4IZJBC6tDXGUDB4UUaK06RhRl/0aWWF3CR4YWESraR1ZCh6dMOiMNIFE2GI/bRJYiIEeULiloyUNSFLzWC3VXcqEJXTBe1qApDpbXUEYxr2tYeQCyyGMuIcxbokHfPvPjHf25mqeWHoLEX0iH0UScmUzSWNxmj20yH6Z+/yNTeM0N1cumkg9E4GHnluLcfeKLm95yLoO5ZKJrhgeaQm4xlFGshcK3pYZ9LradQrmY5nmhVdMm+qqpKYkIqpDyltWkpjJJaaKmd6kXjHUXvDPborOaei2666q7LbrvuvgtvvPLOS2+9YBUAADs=" );
				exit();
			}
			else
			{
				exit();
			}
		}
				
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$content       = '  '. preg_replace( "/(\w)/", "\\1 ", $content ) .' ';
		$allow_fonts   = isset( $this->ipsclass->vars['captcha_allow_fonts'] ) ? $this->ipsclass->vars['captcha_allow_fonts'] : 1;
		$use_fonts     = 0;
		$tmp_x         = 135;
		$tmp_y         = 20;
		$image_x       = 200;
		$image_y       = 60;
		$circles       = 3;
		$continue_loop = TRUE;
		$_started      = FALSE;
		
		//-----------------------------------------
		// Get backgrounds and fonts...
		//-----------------------------------------
		
		$backgrounds = $this->_captcha_show_gd_img_get_backgrounds();
		$fonts       = $this->_captcha_show_gd_img_get_fonts();
	
		//-----------------------------------------
		// Seed rand functions for PHP versions that don't
		//-----------------------------------------
		
		mt_srand( (double) microtime() * 1000000 );
		
		//-----------------------------------------
		// Got a background?
		//-----------------------------------------
		
		if ( $this->ipsclass->vars['gd_version'] > 1 )
		{
			while ( $continue_loop )
			{
				if ( is_array( $backgrounds ) AND count( $backgrounds ) )
				{
					$i = mt_rand(0, count( $backgrounds ) - 1 );
				
					$background      = $backgrounds[ $i ];
					$_file_extension = preg_replace( "#^.*\.(\w{2,4})$#is", "\\1", strtolower( $background ) );
				
					switch( $_file_extension )
					{
						case 'jpg':
						case 'jpe':
						case 'jpeg':
							if ( ! function_exists('imagecreatefromjpeg') OR ! $im = @imagecreatefromjpeg($background) )
							{
								unset( $backgrounds[ $i ] );
							}
							else
							{
								$continue_loop = FALSE;
								$_started      = TRUE;
							}
							break;
						case 'gif':
							if ( ! function_exists('imagecreatefromgif') OR ! $im = @imagecreatefromgif($background) )
							{
								unset( $backgrounds[ $i ] );
							}
							else
							{
								$continue_loop = FALSE;
								$_started      = TRUE;
							}
							break;
						case 'png':
							if ( ! function_exists('imagecreatefrompng') OR ! $im = @imagecreatefrompng($background) )
							{
								unset( $backgrounds[ $i ] );
							}
							else
							{
								$continue_loop = FALSE;
								$_started      = TRUE;
							}
							break;
					}
				}
				else
				{
					$continue_loop = FALSE;
				}
			}
		}
		
		//-----------------------------------------
		// Still not got one? DO OLD FASHIONED
		//-----------------------------------------
		
		if ( $_started !== TRUE )
		{
			if ( $this->ipsclass->vars['gd_version'] == 1 )
			{
				$im   = imagecreate($image_x, $image_y);
				$tmp  = imagecreate($tmp_x, $tmp_y);
			}
			else
			{
				$im  = imagecreatetruecolor($image_x, $image_y);
				$tmp = imagecreatetruecolor($tmp_x, $tmp_y);
			}
			
			$white  = ImageColorAllocate($tmp, 255, 255, 255);
			$black  = ImageColorAllocate($tmp, 0, 0, 0);
			$grey   = ImageColorAllocate($tmp, 200, 200, 200 );

			imagefill($tmp, 0, 0, $white);

			for ( $i = 1; $i <= $circles; $i++ )
			{
				$values = array(
								0  => rand(0, $tmp_x - 10),
								1  => rand(0, $tmp_y - 3),
								2  => rand(0, $tmp_x - 10),
								3  => rand(0, $tmp_y - 3),
								4  => rand(0, $tmp_x - 10),
								5  => rand(0, $tmp_y - 3),
								6  => rand(0, $tmp_x - 10),
								7  => rand(0, $tmp_y - 3),
								8  => rand(0, $tmp_x - 10),
								9  => rand(0, $tmp_y - 3),
								10 => rand(0, $tmp_x - 10),
								11 => rand(0, $tmp_y - 3),
						     );

				$randomcolor = imagecolorallocate( $tmp, rand(100,255), rand(100,255),rand(100,255) );
				imagefilledpolygon($tmp, $values, 6, $randomcolor );
			}

			$num     = strlen($content);
			$x_param = 0;
			$y_param = 0;

			for( $i = 0; $i < $num; $i++ )
			{
				$x_param += rand(-1,12);
				$y_param = rand(-3,8);
				
				if( $x_param + 18 > $image_x )
				{
					$x_param -= ceil( $x_param + 18 - $image_x );
				}

				$randomcolor = imagecolorallocate( $tmp, rand(0,150), rand(0,150),rand(0,150) );

				imagestring($tmp, 5, $x_param+1, $y_param+1, $content{$i}, $grey);
				imagestring($tmp, 5, $x_param, $y_param, $content{$i}, $randomcolor);
			}

			//-----------------------------------------
			// Distort by resizing
			//-----------------------------------------

			imagecopyresized($im, $tmp, 0, 0, 0, 0, $image_x, $image_y, $tmp_x, $tmp_y);

			imagedestroy($tmp);
			
			//-----------------------------------------
			// Background dots and lines
			//-----------------------------------------

			$random_pixels = $image_x * $image_y / 10;

			for ($i = 0; $i < $random_pixels; $i++)
			{
				$randomcolor = imagecolorallocate( $im, rand(0,150), rand(0,150),rand(0,150) );
				ImageSetPixel($im, rand(0, $image_x), rand(0, $image_y), $randomcolor);
			}

			$no_x_lines = ($image_x - 1) / 5;

			for ( $i = 0; $i <= $no_x_lines; $i++ )
			{
				ImageLine( $im, $i * $no_x_lines, 0, $i * $no_x_lines, $image_y, $grey );
				ImageLine( $im, $i * $no_x_lines, 0, ($i * $no_x_lines)+$no_x_lines, $image_y, $grey );
			}

			$no_y_lines = ($image_y - 1) / 5;

			for ( $i = 0; $i <= $no_y_lines; $i++ )
			{
				ImageLine( $im, 0, $i * $no_y_lines, $image_x, $i * $no_y_lines, $grey );
			}
		}
		else
		{
			//-----------------------------------------
			// Can we use fonts?
			//-----------------------------------------
			
			if ( $allow_fonts AND function_exists('imagettftext') AND is_array( $fonts ) AND count( $fonts ) )
			{
				if ( function_exists('imageantialias') )
				{
					imageantialias( $im, TRUE );
				}
				
				$num       = strlen($content);
				$x_param   = -18;
				$y_param   = 0;
				$_font     = $fonts[ mt_rand( 0, count( $fonts ) - 1 ) ];
				
				for( $i = 0; $i < $num; $i++ )
				{
					$y_param     = rand( 35, 48 );
					
					# Main color
					$col_r       = rand(50,200);
					$col_g       = rand(0,150);
					$col_b       = rand(50,200);
					# High light
					$col_r_l     = ( $col_r + 50 > 255 ) ? 255 : $col_r + 50;
					$col_g_l     = ( $col_g + 50 > 255 ) ? 255 : $col_g + 50;
					$col_b_l     = ( $col_b + 50 > 255 ) ? 255 : $col_b + 50;
					# Low light
					$col_r_d     = ( $col_r - 50 < 0 ) ? 0 : $col_r - 50;
					$col_g_d     = ( $col_g - 50 < 0 ) ? 0 : $col_g - 50;
					$col_b_d     = ( $col_b - 50 < 0 ) ? 0 : $col_b - 50;
					
					$color_main  = imagecolorallocate( $im, $col_r, $col_g, $col_b );
					$color_light = imagecolorallocate( $im, $col_r_l, $col_g_l, $col_b_l );
					$color_dark  = imagecolorallocate( $im, $col_r_d, $col_g_d, $col_b_d );
					$_slant      = mt_rand( -20, 40 );
					
					if ( $i == 1 OR $i == 3 OR $i == 5 )
					{
						for( $ii = 0 ; $ii < 2 ; $ii++ )
						{
							$a   = $x_param + 50;
							$b   = mt_rand(0,100);
							$c   = $a + 20;
							$d   = $b + 20;
							$e   = ( $i == 3 ) ? mt_rand( 280, 320 ) : mt_rand( -280, -320 );
							
							imagearc( $im, $a  , $b  , $c, $d, 0, $e, $color_light );
							imagearc( $im, $a+1, $b+1, $c, $d, 0, $e, $color_main );
						}
					}
					
					if ( ! $_result = @imagettftext( $im, 24, $_slant, $x_param - 1, $y_param - 1, $color_light, $_font, $content{$i} ) )
					{
						$use_fonts = FALSE;
						break;
					}
					else
					{
						@imagettftext( $im, 24, $_slant, $x_param + 1, $y_param + 1, $color_dark, $_font, $content{$i} );
						@imagettftext( $im, 24, $_slant, $x_param, $y_param, $color_main, $_font, $content{$i} );
					}
					
					$x_param += rand( 15, 18 );
					
					if( $x_param + 18 > $image_x )
					{
						$x_param -= ceil( $x_param + 18 - $image_x );
					}					
				}
				
				$use_fonts = TRUE;
			}
			
			if ( ! $use_fonts )
			{
				//-----------------------------------------
				// Continue with nice background image
				//-----------------------------------------
			
				$tmp         = imagecreatetruecolor($tmp_x  , $tmp_y  );
				$tmp2        = imagecreatetruecolor($image_x, $image_y);
		
				$white       = imagecolorallocate( $tmp, 255, 255, 255 );
				$black       = imagecolorallocate( $tmp, 0, 0, 0 );
				$grey        = imagecolorallocate( $tmp, 100, 100, 100 );
				$transparent = imagecolorallocate( $tmp2, 255, 255, 255 );
				$_white      = imagecolorallocate( $tmp2, 255, 255, 255 );
			
				imagefill($tmp , 0, 0, $white );
				imagefill($tmp2, 0, 0, $_white);
			
				$num         = strlen($content);
				$x_param     = 0;
				$y_param     = 0;

				for( $i = 0; $i < $num; $i++ )
				{
					if ( $i > 0 )
					{
						$x_param += rand( 6, 12 );
						
						if( $x_param + 18 > $image_x )
						{
							$x_param -= ceil( $x_param + 18 - $image_x );
						}
					}
				
					$y_param  = rand( 0, 5 );
				
					$randomcolor = imagecolorallocate( $tmp, rand(50,200), rand(50,200),rand(50,200) );

					imagestring( $tmp, 5, $x_param + 1, $y_param + 1, $content{$i}, $grey );
					imagestring( $tmp, 5, $x_param    , $y_param    , $content{$i}, $randomcolor );
				}
			
				imagecopyresized($tmp2, $tmp, 0, 0, 0, 0, $image_x, $image_y, $tmp_x, $tmp_y );
			
				$tmp2 = $this->captcha_show_gd_img_wave( $tmp2, 8, true );
			
				imagecolortransparent( $tmp2, $transparent );
				imagecopymerge( $im, $tmp2, 0, 0, 0, 0, $image_x, $image_y, 100 );
		
				imagedestroy($tmp);
				imagedestroy($tmp2);
			}
		}
		
		//-----------------------------------------
		// Blur?
		//-----------------------------------------
		
		if ( function_exists( 'imagefilter' ) )
		{
			@imagefilter( $im, IMG_FILTER_GAUSSIAN_BLUR );
		}
		
		//-----------------------------------------
		// Render a border
		//-----------------------------------------
		
		$black = imagecolorallocate( $im, 0, 0, 0 );
		
		imageline( $im, 0, 0, $image_x, 0, $black );
		imageline( $im, 0, 0, 0, $image_y, $black );
		imageline( $im, $image_x - 1, 0, $image_x - 1, $image_y, $black );
		imageline( $im, 0, $image_y - 1, $image_x, $image_y - 1, $black );
		
		//-----------------------------------------
		// Show it!
		//-----------------------------------------
		
		@header( "Content-Type: image/jpeg" );
		
		imagejpeg( $im );
		imagedestroy( $im );
		
		exit();
	}
	
	/*-------------------------------------------------------------------------*/
	// Create wave effect for GD images...
	/*-------------------------------------------------------------------------*/
	
	/**
	* Create wave effect for GD images
	*
	* @param	object	Image resource
	* @return	object  Returned image resource
	* @since	1.0
	*/
	function captcha_show_gd_img_wave( $im, $wave=10 )
	{
		$_width  = imagesx( $im );
		$_height = imagesy( $im );

		$tmp = imagecreatetruecolor( $_width, $_height );

		$_direction = ( time() % 2 ) ? TRUE : FALSE;

		for ( $x = 0; $x < $_width; $x++ )
		{
			for ( $y = 0 ; $y < $_height ; $y++ )
			{
				$xo = $wave * sin( 2 * 3.1415 * $y / 128 );
				$yo = $wave * cos( 2 * 3.1415 * $x / 128 );

				$_x = $x - $xo;
				$_y = $y - $yo;
				
				if ( ($_x > 0 AND $_x < $_width) AND ($_y > 0 AND $_y < $_height) )
				{
					$index  = imagecolorat($im, $_x, $_y);
               		$colors = imagecolorsforindex($im, $index);
               		$color  = imagecolorresolve( $tmp, $colors['red'], $colors['green'], $colors['blue'] );
				}
				else
				{
					$color = imagecolorresolve( $tmp, 255, 255, 255 );
				}

				imagesetpixel( $tmp, $x, $y, $color );
			}
		}

		return $tmp;
	}
	
	/*-------------------------------------------------------------------------*/
	// Removes extinct captcha sessions
	/*-------------------------------------------------------------------------*/
	/**
	* Removes extinct captcha sessions
	* Clears out "dead" entries
	*
	* @param	string	Optional IP address to clear out on
	* @return	string	void
	*/
	
	function captcha_clear_sessions( $ip_address='' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$time  = time() - 60 * 3600;
		$extra = ( $ip_address ) ? ' OR captcha_ipaddress="'.$ip_address.'"' : '';
		
		//-----------------------------------------
		// Remove...
		//-----------------------------------------
		
		$this->ipsclass->DB->build_and_exec_query( array( 'delete' => 'captcha',
													  	  'where'  => 'captcha_date < ' . $time . $extra ) );
		
	}
	
	/*-------------------------------------------------------------------------*/
	// Load up the backgrounds
	/*-------------------------------------------------------------------------*/
	/**
	* Loads up the backgrounds.
	*
	* @return	array	backgrounds
	*/
	
	function _captcha_show_gd_img_get_backgrounds()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$images = array();
		$_path  = $this->path_background;
		
		if ( $_dir = @opendir( $_path ) )
		{
			while( false !== ( $_file = @readdir( $_dir ) ) )
			{
				if ( preg_match( "#\.(gif|jpeg|jpg|png)$#i", $_file ) )
				{
					$images[] = $_path . '/' . $_file;
				}
			}
		}
		
		return $images;
	}
	
	/*-------------------------------------------------------------------------*/
	// Load up the fonts
	/*-------------------------------------------------------------------------*/
	/**
	* Loads up the fonts.
	*
	* @return	array	fonts
	*/
	
	function _captcha_show_gd_img_get_fonts()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$fonts  = array();
		$_path  = $this->path_fonts;
		
		if ( $_dir = @opendir( $_path ) )
		{
			while( false !== ( $_file = @readdir( $_dir ) ) )
			{
				if ( preg_match( "#\.(ttf)$#i", $_file ) )
				{
					$fonts[] = $_path . '/' . $_file;
				}
			}
		}
		
		return $fonts;
	}
	
}

?>