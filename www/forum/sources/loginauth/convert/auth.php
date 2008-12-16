<?php
/*
+---------------------------------------------------------------------------
|   Invision Power Board V2.1.0
|   ========================================
|   by Matthew Mecham
|   (c) 2004 Invision Power Services
|   http://www.invisionpower.com
|   ========================================
+---------------------------------------------------------------------------
|   INVISION POWER DYNAMIC IS NOT FREE SOFTWARE!
|   http://www.invisionpower.com/dynamic/
+---------------------------------------------------------------------------
|
|   > LOG IN MODULE: Converted Board Modules
|   > Script written by Stewart Campbell
|   > Date started: October 15th 2005
|
+---------------------------------------------------------------------------
| NOTES:
| This module is part of the authentication suite of modules. It's designed
| to enable different types of authentication.
|
| RETURN CODES
| 'ERROR': Error, check array: $class->auth_errors
| 'NO_USER': No user found in LOCAL record set but auth passed in REMOTE dir
| 'WRONG_AUTH': Wrong password or username
| 'SUCCESS': Success, user and password matched
|
+---------------------------------------------------------------------------
| EXAMPLE USAGE
|
| $class = new login_method();
| $class->is_admin_auth = 0; // Boolean (0,1) Use different queries if desired
|                             // if logging into CP.
| $class->allow_create = 0;
| // $allow_create. Boolean flag (0,1) to tell the module whether its allowed
| // to create a member in the IPS product's database if the user passed authentication
| // but don't exist in the IPS product's database. Optional.
|
| $return_code = $class->authenticate( $username, $plain_text_password );
|
| if ( $return_code == 'SUCCESS' )
| {
|     print $class->member['member_name'];
| }
| else
| {
|       print "NO USER";
| }
+---------------------------------------------------------------------------
*/

class login_method extends login_core
{
    # Globals
    var $member;
    var $ipsclass;
    var $password_field = 'conv_password';

    /*-------------------------------------------------------------------------*/
    // Constructor
    /*-------------------------------------------------------------------------*/

    function login_method()
    {
    }

    /*-------------------------------------------------------------------------*/
    // Authentication
    /*-------------------------------------------------------------------------*/

    function authenticate( $username, $password )
    {
				// Check if we have actually converted.
        if($this->ipsclass->vars['conv_configured'] != 1)
        {
            $this->return_code = "WRONG_AUTH";
            return;
        }

   			// Check for old or new system
    		if(! $this->ipsclass->DB->field_exists('conv_password', 'members') )
    		{
    			$this->password_field = 'legacy_password';
    		}

        $this->_load_member( $username );

        $login_handler_code = end(explode("_", $this->ipsclass->vars['conv_chosen']));

        switch($login_handler_code)
        {
            case 'vb3':
            case 'vb35':
                $this->authenticate_vb3( $username, $password );
                break;
            case 'ib31':
                $this->authenticate_ib31( $username, $password );
                break;
            case 'smf11':
                $this->authenticate_smf11( $username, $password );
                break;
            case 'smf10':
            case 'yabbse':
                $this->authenticate_smf( $username, $password );
                break;
			case 'yabb20':
				$this->authenticate_yabb20( $username, $password );
            case 'ubbt5':
                $this->authenticate_ubbthreads5( $username, $password );
                break;
            case 'snitz':
                $this->authenticate_snitz( $username, $password );
                break;
			case 'punbb':
				$this->authenticate_punbb( $username, $password );
				break;
			case 'dcforumplus':
				$this->authenticate_dcforum( $username, $password );
				break;
			case 'wwwthreads':
				$this->authenticate_wwwthreads( $username, $password );
			case 'simpleforum':
				$this->authenticate_simpleforum( $username, $password );
            default:
                $this->return_code = "WRONG_AUTH";
                return;
        }
        return;
    }

	/*-------------------------------------------------------------------------*/
	// Authentication for DCForum+
	/*-------------------------------------------------------------------------*/

	function authenticate_dcforum( $username, $password )
		{
			$current_password = $this->member[ $this->password_field ];
			$crypted_password = crypt( $password, substr($current_password, 0, 2) );
			$single_md5_pass 	= md5( $password );

			if ($current_password == $crypted_password)
			{
				$this->_clean_convert_data( $single_md5_pass );
				$this->return_code = "SUCCESS";
			}
			else
			{
				$this->return_code = "WRONG_AUTH";
			}
			return;
		}

	/*-------------------------------------------------------------------------*/
	// Authentication for punBB
	/*-------------------------------------------------------------------------*/

	function authenticate_punbb( $username, $password )
		{
			$success = false;
			$single_md5_pass = md5( $password );

			if (function_exists('sha1'))
			{
				if(sha1($password) == $this->member[ $this->password_field ])
				{
					$success = true;
				}
			}
			else if (function_exists('mhash'))
			{
				if(bin2hex(mhash(MHASH_SHA1, $str)) == $this->member[ $this->password_field ])
				{
					$success = true;
				}
			}
			else
			{
				if(md5($password) == $this->member[ $this->password_field ])
				{
					$success = true;
				}
			}

			if( $success )
			{
				$this->_clean_convert_data( $single_md5_pass );
				$this->return_code = "SUCCESS";
				return;
			}

			$this->return_code = "WRONG_AUTH";
			return;
		}

    /*-------------------------------------------------------------------------*/
    // Authentication for Snitz
    /*-------------------------------------------------------------------------*/

    function authenticate_snitz( $username, $password )
    {
        require_once('./sources/loginauth/convert/auth_sha256.php');
        $sha = new auth_sha256();

        if ( $this->member['misc'])
        {
            $sha256_password = $sha->SHA256( $password );
            $single_md5_pass = md5( $password );

            if ( $sha256_password == $this->member['misc'] )
            {
                $this->_clean_convert_data( $single_md5_pass );

                $this->return_code = 'SUCCESS';
                return;
            }
        }
        $this->return_code = 'WRONG_AUTH';
    }

    /*-------------------------------------------------------------------------*/
    // Authentication for vB3
    /*-------------------------------------------------------------------------*/

    function authenticate_vb3( $username, $password )
    {
        if ( $this->member['misc'])
        {

            $single_md5_pass = md5( $password );

            $decr = md5( $single_md5_pass . $this->member['misc'] );

            if ( $decr == $this->member[ $this->password_field ] )
            {
                $this->_clean_convert_data( $single_md5_pass );

                $this->return_code = 'SUCCESS';
                return;
            }

        }
        $this->return_code = 'WRONG_AUTH';
    }

    /*-------------------------------------------------------------------------*/
    // Authentication for iB3.1
    /*-------------------------------------------------------------------------*/

    function authenticate_ib31( $username, $password )
    {
            $decr = md5( $password . $username );
            $single_md5_pass = md5( $password );

            if ( $decr == $this->member[ $this->password_field ] )
            {
                $this->_clean_convert_data( $single_md5_pass );

                $this->return_code = "SUCCESS";
                return;
            }

            $this->return_code = "WRONG_AUTH";
    }

    /*-------------------------------------------------------------------------*/
    // Authentication for SMF 1.1
    /*-------------------------------------------------------------------------*/

    function authenticate_smf11( $username, $password )
    {
        $single_md5_pass = md5($password);

        if($this->member['misc'])
        {
            $username_low = strtolower($username);

            $sha1_password = sha1($username_low . $password);

            $success = false;

            if($sha1_password == $this->member[ $this->password_field ])
            {
                $success = true;
            }
            else
            {
                $this->authenticate_smf( $username, $password );

                if($this->return_code == "SUCCESS")
                {
                    $success = true;
                }
            }
            if( $success )
            {
                $this->_clean_convert_data( $single_md5_pass );
                $this->return_code = "SUCCESS";
                return;
            }

            $this->return_code = "WRONG_AUTH";
            return;
        }
    }

    /*-------------------------------------------------------------------------*/
    // Authentication for SMF / YABB.SE
    /*-------------------------------------------------------------------------*/

    function authenticate_smf( $username, $password )
    {
        if($this->member['misc'])
        {
            $single_md5_pass = md5( $password );

            $success = false;

            if ( crypt( $password, substr( $password,0,2 ) ) == $this->member[ $this->password_field ] )
            {
                $success = true;
            }
            else if ( strlen($this->member[ $this->password_field ]) == 32  AND ( $this->_md5_hmac( $password, $username ) == $this->member[ $this->password_field ] ) )
            {
                $success = true;
            }
            else if ( strlen($this->member[ $this->password_field ]) == 32  AND ( $single_md5_pass == $this->member[ $this->password_field ] ) )
            {
                $success = true;
            }

            if( $success )
            {
                $this->_clean_convert_data( $single_md5_pass );
                $this->return_code = "SUCCESS";
                return;
            }

        }
        $this->return_code = "WRONG_AUTH";
        return;
    }

    //*-------------------------------------------------------------------------*/
    // Authentication for UBB.Threads 5
    //*-------------------------------------------------------------------------*/

    function authenticate_ubbthreads5( $username, $password )
    {
        $single_md5_pass = md5( $password );

        $success = false;

        if(crypt($password, $this->member[ $this->password_field ]) == $this->member[ $this->password_field ])
        {
            $success = true;
        }
        else if($single_md5_pass == $row['legacy_password'])
        {
            $success = true;
        }

        if( $success )
        {
            $this->_clean_convert_data( $single_md5_pass );
            $this->return_code = "SUCCESS";
            return;
        }

        $this->return_code = "WRONG_AUTH";
        return;
    }

    //*-------------------------------------------------------------------------*/
    // Authentication for WWWThreads
    //*-------------------------------------------------------------------------*/

    function authenticate_wwwthreads( $username, $password )
    {
		$single_md5_pass = md5( $password );

        if ( $this->member['misc'])
        {
	        $sha1_in_db = (strlen($db_password_hash) == 40) ? true : false;
			$sha1_available = (function_exists('sha1') || function_exists('mhash')) ? true : false;

			if ( function_exists('sha1') ) {
				$form_password_hash = sha1($str);
			} else if (function_exists('mhash')) {
				$form_password_hash = bin2hex(mhash(MHASH_SHA1, $str));
			} else {
				$form_password_hash = md5($str);
			}

			if ($sha1_in_db && $sha1_available && $db_password_hash == $form_password_hash) {
				$authorized = true;
			} else if (!$sha1_in_db && $db_password_hash == md5($form_password)) {
				$authorized = true;
			}

	        if( $authorized )
	        {
	            $this->_clean_convert_data( $single_md5_pass );
	            $this->return_code = "SUCCESS";
	            return;
	        }
		}

	    $this->return_code = "WRONG_AUTH";
	    return;
	}

    /*-------------------------------------------------------------------------*/
    // Authentication for YABB2.0
    /*-------------------------------------------------------------------------*/

    function authenticate_yabb20( $username, $password )
    {

    	$myhash = rtrim(base64_encode(pack("H*",md5($password))),"=");

        $single_md5_pass = md5( $password );

        $success = false;

        if ( $myhash == $this->member[ $this->password_field ] )
        {
            $success = true;
        }

        if( $success )
        {
            $this->_clean_convert_data( $single_md5_pass );
            $this->return_code = "SUCCESS";
            return;
        }

        $this->return_code = "WRONG_AUTH";
        return;
    }

    /*-------------------------------------------------------------------------*/
    // Authentication for Simple Forum
    /*-------------------------------------------------------------------------*/

    function authenticate_simpleforum( $username, $password )
    {

    	$myhash = crypt($password, 'SiMpLeFoRuM');

        $single_md5_pass = md5( $password );

        $success = false;

        if ( $myhash == $this->member[ $this->password_field ] )
        {
            $success = true;
        }

        if( $success )
        {
            $this->_clean_convert_data( $single_md5_pass );
            $this->return_code = "SUCCESS";
            return;
        }

        $this->return_code = "WRONG_AUTH";
        return;
    }

    /*-------------------------------------------------------------------------*/
    // Utility Functions
    /*-------------------------------------------------------------------------*/

    /*-------------------------------------------------------------------------*/
    // Load member from DB
    /*-------------------------------------------------------------------------*/

    function _load_member( $username )
    {
        $this->member = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*', 'from' => 'members', 'where' => "name='" . $username . "'" ) );
    }

    /*-------------------------------------------------------------------------*/
    // Clean-Up The Converted Data
    /*-------------------------------------------------------------------------*/

    function _clean_convert_data( $new_pass )
    {
    		$clear_pass_field = "," . $this->password_field . "=''";
        $this->ipsclass->DB->query("UPDATE ibf_members SET misc='' {$clear_pass_field} WHERE id={$this->member['id']}");
        $this->ipsclass->converge->converge_update_password( $new_pass, $this->member['email'] );
    }

 	function _md5_hmac($data, $key)
    {
        if (strlen($key) > 64)
            $key = pack('H*', md5($key));
        $key  = str_pad($key, 64, chr(0x00));

        $k_ipad = $key ^ str_repeat(chr(0x36), 64);
        $k_opad = $key ^ str_repeat(chr(0x5c), 64);

        return md5($k_opad . pack('H*', md5($k_ipad . $data)));
    }
}

?>