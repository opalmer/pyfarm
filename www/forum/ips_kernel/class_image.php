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
|   > $Date: 2005-10-10 14:03:20 +0100 (Mon, 10 Oct 2005) $
|   > $Revision: 22 $
|   > $Author: matt $
+---------------------------------------------------------------------------
|
|   > GD / IMAGE handling methods (KERNEL)
|   > Module written by Matt Mecham
|   > Date started: 2nd Feb. 2004
|
|	> Module Version Number: 1.0.0
+--------------------------------------------------------------------------
*/

/**
* IPS Kernel Pages: Image Class
*
* This class contains all generic functions to
* handle GD 1 & 2 thumbnail generation
*
* Example Usage:
* <code>
* $image = new class_image();
* 
* $image->in_type        = 'file';
* $image->out_type       = 'file';
* $image->in_file_dir    = './uploads';
* $image->in_file_name   = 'somepicture.jpg';
* $image->desired_width  = 200;
* $image->desired_height = 200;
* $image->gd_version     = 2;
* 
* $return = $image->generate_thumbnail();
* 
* print_r( $return );
* // Prints
* Array( thumb_width    => int
*		 thumb_height   => int
*        thumb_location => string )
* </code>
*
* @package		IPS_KERNEL
* @author		Matt Mecham
* @copyright	Invision Power Services, Inc.
* @version		2.1
*/

/**
*
*/

/**
* Image Class
*
* Methods and functions for handling thumbnail generation
*
* @package	IPS_KERNEL
* @author   Matt Mecham
* @version	2.1
*/
class class_image
{
	/**
	* Type of incoming data
	*
	* @var string Type
	*/
	var $in_type          = 'file';
	
	/**
	* Type of outgoing data
	*
	* @var string Type
	*/
	var $out_type         = 'file';
	
	/**
	* Save file as...
	*
	* @var string File name
	*/
	var $out_file_name    = '';
	
	/**
	* Save file in...
	*
	* @var string Directory
	*/
	var $out_file_dir     = '';
	
	/**
	* In file directory
	*
	* @var string Directory
	*/
	var $in_file_dir      = '.';
	
	/**
	* Name of input file name
	*
	* @var string File name
	*/
	var $in_file_name     = '';
	
	/**
	* Complete file name with path
	*
	* @var string
	*/
	var $in_file_complete = '';
	
	/**
	* Max. thumbnail width
	*
	* @var integer Pixels
	*/
	var $desired_width    = 0;
	
	/**
	* Max. thumbnail height
	*
	* @var integer Pixels
	*/
	var $desired_height   = 0;
	
	/**
	* GD version
	*
	* @var integer	GD version
	*/
	var $gd_version       = 2;
	
	/**
	* Image type (PNG, JPG, GIF)
	*
	* @var string Image type
	*/
	var $image_type       = '';
	
	/**
	* File extension
	*
	* @var string
	*/
	var $file_extension   = '';
	
	/*-------------------------------------------------------------------------*/
	// CONSTRUCTOR
	/*-------------------------------------------------------------------------*/
	
	function class_image()
	{
		//-----------------------------------
		// Full path?
		//-----------------------------------
	}
	
	/*-------------------------------------------------------------------------*/
	// Clean paths
	/*-------------------------------------------------------------------------*/
	
	/**
	* Cleans up paths, generates var $in_file_complete
	*
	* @return void
	*/
	function clean_paths()
	{
		$this->in_file_dir  = preg_replace( "#/$#", "", $this->in_file_dir );
		$this->out_file_dir = preg_replace( "#/$#", "", $this->out_file_dir );
		
		if ( $this->in_file_dir and $this->in_file_name )
		{
			$this->in_file_complete = $this->in_file_dir.'/'.$this->in_file_name;
		}
		else
		{
			$this->in_file_complete = $this->in_file_name;
		}
		
		if ( ! $this->out_file_dir )
		{
			$this->out_file_dir = $this->in_file_dir;
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// GENERATE THUMBNAIL
	/*-------------------------------------------------------------------------*/
	
	/**
	* Generates thumnbail
	*
	* @return array	[ thumb_width, thumb_height, thumb_location ]
	*/
	function generate_thumbnail()
	{
		$return = array();
		$image  = "";
		$thumb  = "";
		
		//-----------------------------------
		// Set up paths
		//-----------------------------------
		
		$this->clean_paths();
		
		$remap  = array( 1 => 'GIF', 2 => 'JPG', 3 => 'PNG', 4 => 'SWF', 5 => 'PSD', 6 => 'BMP' );
		
		if ( $this->desired_width and $this->desired_height )
		{
			//----------------------------------------------------
			// Tom Thumb!
			//----------------------------------------------------
			
			$img_size = array();
			
			if ( $this->in_type == 'file' )
			{
				$img_size = @GetImageSize( $this->in_file_complete );
			}
			
			if ( $img_size[0] < 1 and $img_size[1] < 1 )
			{
				$img_size    = array();
				$img_size[0] = $this->desired_width;
				$img_size[1] = $this->desired_height;
				
				$return['thumb_width']    = $this->desired_width;
				$return['thumb_height']   = $this->desired_height;
				
				if ( $this->out_type == 'file' )
				{
					$return['thumb_location'] = $this->in_file_name;
					return $return;
				}
				else
				{
					//----------------------------------------------------
					// Show image
					//----------------------------------------------------
					
					$this->show_non_gd();
				}
			}
			
			//----------------------------------------------------
			// Do we need to scale?
			//----------------------------------------------------
			
			if ( ( $img_size[0] > $this->desired_width ) OR ( $img_size[1] > $this->desired_height ) )
			{ 
				
				$im = $this->scale_image( array(
												 'max_width'  => $this->desired_width,
												 'max_height' => $this->desired_height,
												 'cur_width'  => $img_size[0],
												 'cur_height' => $img_size[1]
										)      );
									   
				$return['thumb_width']     = $im['img_width'];
				$return['thumb_height']    = $im['img_height'];
				$return['original_width']  = $img_size[0];
				$return['original_height'] = $img_size[1];
				
				//-----------------------------------------------
				// May as well scale properly.
				//-----------------------------------------------
				
				if ( $im['img_width'] )
				{
					$this->desired_width = $im['img_width'];
				}
				
				if ( $im['img_height'] )
				{
					$this->desired_height = $im['img_height'];
				}
				
				//-----------------------------------------------
				// GD functions available?
				//-----------------------------------------------
				
				if ( $remap[ $img_size[2] ] == 'GIF' )
				{
					if ( function_exists( 'imagecreatefromgif') )
					{
						if ( $image = @imagecreatefromgif( $this->in_file_complete ) )
						{
							$this->image_type = 'gif';
						}
						else
						{
							if ( $this->out_type == 'file' )
							{
								$return['thumb_width']     = $this->desired_width;
								$return['thumb_height']    = $this->desired_height;
								$return['thumb_location']  = $this->in_file_name;
								$return['original_width']  = $img_size[0];
								$return['original_height'] = $img_size[1];
							}
							else
							{
								//-----------------------------------------------
								// Show Image..
								//-----------------------------------------------
								
								$this->show_non_gd();
								
							}
						}
					}
				}
				else if ( $remap[ $img_size[2] ] == 'PNG' )
				{
					if ( function_exists( 'imagecreatefrompng') )
					{
						if ( $image = @imagecreatefrompng( $this->in_file_complete ) )
						{
							$this->image_type = 'png';
						}
						else
						{
							if ( $this->out_type == 'file' )
							{
								$return['thumb_width']     = $this->desired_width;
								$return['thumb_height']    = $this->desired_height;
								$return['thumb_location']  = $this->in_file_name;
								$return['original_width']  = $img_size[0];
								$return['original_height'] = $img_size[1];
							}
							else
							{
								//-----------------------------------------------
								// Show Image..
								//-----------------------------------------------
								
								$this->show_non_gd();
								
							}
						}
					}
				}
				else if ( $remap[ $img_size[2] ] == 'JPG' )
				{
					if ( function_exists( 'imagecreatefromjpeg') )
					{ 
						if ( $image = @imagecreatefromjpeg( $this->in_file_complete ) )
						{
							$this->image_type = 'jpg';
						}
						else
						{
							if ( $this->out_type == 'file' )
							{
								$return['thumb_width']     = $this->desired_width;
								$return['thumb_height']    = $this->desired_height;
								$return['thumb_location']  = $this->in_file_name;
								$return['original_width']  = $img_size[0];
								$return['original_height'] = $img_size[1];
							}
							else
							{
								//-----------------------------------------------
								// Show Image..
								//-----------------------------------------------
								
								$this->show_non_gd();
								
							}
						}
					}
				}
				
				//----------------------------------------------------
				// Did we get a return from imagecreatefrom?
				//----------------------------------------------------
				
				if ( $image )
				{
					if ( $this->gd_version == 1 )
					{
						$thumb = @imagecreate( $im['img_width'], $im['img_height'] );
						@imagecopyresized( $thumb, $image, 0, 0, 0, 0, $im['img_width'], $im['img_height'], $img_size[0], $img_size[1] );
					}
					else
					{
						$thumb = @imagecreatetruecolor( $im['img_width'], $im['img_height'] );
						
                        if( $this->image_type == 'png' )
                        {
                            @imagealphablending( $thumb, FALSE );
                            @imagesavealpha( $thumb, TRUE );
                            
                            $transparent = @imagecolorallocatealpha($thumb, 255, 255, 255, 127);
                            @imagefilledrectangle($thumb, 0, 0, $im['img_width'], $im['img_height'], $transparent);
                        }
                                                
                        @imagecopyresampled($thumb, $image, 0, 0, 0, 0, $im['img_width'], $im['img_height'], $img_size[0], $img_size[1] );
					}
					
					//-----------------------------------------------
					// Saving?
					//-----------------------------------------------
					
					if ( $this->out_type == 'file' )
					{
						if ( ! $this->out_file_name )
						{
							//-----------------------------------------------
							// Remove file extension...
							//-----------------------------------------------
							
							$this->out_file_name = preg_replace( "/^(.*)\..+?$/", "\\1", $this->in_file_name ) . '_thumb';
						}
						
                        if ( function_exists( 'imagepng' ) AND $this->image_type == 'png' )
                        {
                            $this->file_extension = 'png';
                            @imagepng( $thumb, $this->out_file_dir."/".$this->out_file_name.'.png' );
                            @chmod( $this->out_file_dir."/".$this->out_file_name.'.png', 0777 );
                            @imagedestroy( $thumb );
                            @imagedestroy( $image );
                            $return['thumb_location'] = $this->out_file_name.'.png';
                            return $return;
                        }
                        else if ( function_exists( 'imagejpeg' ) )
                        {
                            $this->file_extension = 'jpg';
                            @imagejpeg( $thumb, $this->out_file_dir."/".$this->out_file_name.'.jpg' );
                            @chmod( $this->out_file_dir."/".$this->out_file_name.'.jpg', 0777 );
                            @imagedestroy( $thumb );
                            @imagedestroy( $image );
                            $return['thumb_location'] = $this->out_file_name.'.jpg';
                            return $return;
                        }
						else
						{
							//--------------------------------------
							// Can't save...
							//--------------------------------------
							
							$return['thumb_width']     = $this->desired_width;
							$return['thumb_height']    = $this->desired_height;
							$return['thumb_location']  = $this->in_file_name;
							$return['original_width']  = $img_size[0];
							$return['original_height'] = $img_size[1];
							
							return $return;
						}
					}
					else
					{
						//-----------------------------------------------
						// Show image
						//-----------------------------------------------
						
						$this->show_image( $thumb, $this->image_type );
					
					}
				}
				else
				{
					//----------------------------------------------------
					// Could not GD, return..
					//----------------------------------------------------
					
					if ( $this->out_type == 'file' )
					{
						$return['thumb_width']     = $this->desired_width;
						$return['thumb_height']    = $this->desired_height;
						$return['thumb_location']  = $this->in_file_name;
						$return['original_width']  = $img_size[0];
						$return['original_height'] = $img_size[1];
					}
					else
					{
						//-----------------------------------------------
						// Show Image..
						//-----------------------------------------------
						
						$this->show_non_gd();
						
					}
				
					return $return;
				}
			}
			//----------------------------------------------------
			// No need to scale..
			//----------------------------------------------------
			else
			{ 
				if ( $this->out_type == 'file' )
				{
					//$return['thumb_location']  = $this->in_file_name;
					$return['thumb_width']     = $img_size[0];
					$return['thumb_height']    = $img_size[1];
					$return['original_width']  = $img_size[0];
					$return['original_height'] = $img_size[1];
					
					return $return;
				}
				else
				{
					//-----------------------------------------------
					// Show Image..
					//-----------------------------------------------
					
					$this->show_non_gd();
					
				}
			}
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Show GD image
	/*-------------------------------------------------------------------------*/
	
	/**
	* Show GD image
	*
	* @param	string	Thumbnail data
	* @param	string	Thumbnail type (gif, png, jpg)
	* @return void
	*/
	function show_image( $thumb, $type )
	{
		flush();
		
		if ( $type == 'gif' )
		{
			@header('Content-type: image/gif');
		}
		else if ( $type == 'png' )
		{
			@header('Content-Type: image/png' );
		}
		else
		{
			@header('Content-Type: image/jpeg' );
		}
		
		print $thumb;
		
		exit();
	}
	
	/*-------------------------------------------------------------------------*/
	// Show non GD image
	/*-------------------------------------------------------------------------*/
	
	/**
	* Show a NON GD image
	*
	* @return void
	*/
	function show_non_gd()
	{
		$file_extension = preg_replace( ".*\.(\w+)$", "\\1", $this->in_file_name );
		$file_extension = strtolower( $file_extension );
		$file_extension = $file_extension == 'jpeg' ? 'jpg' : $file_extension;
		
		if ( strstr( ' gif jpg png ', ' '.$file_extension.' ' ) )
		{
			if ( $data = @file_get_contents( $this->in_file_complete ) )
			{
				$this->show_image( $data, $file_extension );
			}
		}
	}
	
	
	/*-------------------------------------------------------------------------*/
	// Return scaled down image
	/*-------------------------------------------------------------------------*/
	
	/**
	* Scale an image
	*
	* @param	array	[ cur_height, cur_width, max_width, max_height ]
	* @return	array	[ img_height, img_width ]
	*/
	function scale_image($arg)
	{
		// max_width, max_height, cur_width, cur_height
		
		$ret = array(
					  'img_width'  => $arg['cur_width'],
					  'img_height' => $arg['cur_height']
					);
		
		if ( $arg['cur_width'] > $arg['max_width'] )
		{
			$ret['img_width']  = $arg['max_width'];
			$ret['img_height'] = ceil( ( $arg['cur_height'] * ( ( $arg['max_width'] * 100 ) / $arg['cur_width'] ) ) / 100 );
			$arg['cur_height'] = $ret['img_height'];
			$arg['cur_width']  = $ret['img_width'];
		}
		
		if ( $arg['cur_height'] > $arg['max_height'] )
		{
			$ret['img_height']  = $arg['max_height'];
			$ret['img_width']   = ceil( ( $arg['cur_width'] * ( ( $arg['max_height'] * 100 ) / $arg['cur_height'] ) ) / 100 );
		}
		
	
		return $ret;
	}
	
	
}

?>