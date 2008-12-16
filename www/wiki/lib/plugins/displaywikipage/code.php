<?php

/**
 * Display Wiki Page for DokuWiki
 * 
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author Etienne Gauthier, Terence J. Grant<tjgrant@tatewake.com>
 */

/* History...
	Etienne Gauthier, 04/23/2006 - Initial version
	Terence J. Grant, 02/14/2007 - Rewrite
*/

function dwp_display_wiki_page($wikipagename) 
{
	global $conf, $lang;
	global $auth;
	global $ID, $REV;

	//save status
	$backup['ID']	= $ID; 
	$backup['REV']	= $REV;

	$result = '';

	//Check user permissions...
	//**This call is broken, please contact the DokuWiki authors and ask them to fix this.**
	$perm = auth_quickaclcheck($wikipagename);

	if(@file_exists(wikiFN($wikipagename)))
	{
		if ($perm >= AUTH_READ)
		{
			//check page permissions
			if ($perm >= AUTH_READ)
			{
				$result = p_wiki_xhtml($wikipagename,'',false);

				if ($perm >= AUTH_EDIT)
				{
					// create and add the 'edit' button
					$result .='<div class="secedit2"><a href="' . DOKU_BASE . 'doku.php?id=' . $wikipagename . '&amp;do=edit'
					. '">' . $lang['btn_secedit'] . '</a></div>';
				}
			}
			else	//show access denied
			{
				$result = p_locale_xhtml('<b>Access Denied</b>');
			}
		}
	}
	else
	{
		if ($perm >= AUTH_CREATE)
		{
			// create and add the 'create' button
			$result .='<div class="secedit2"><a href="' . DOKU_BASE . 'doku.php?id=' . $wikipagename . '&amp;do=edit'
			. '">' . $lang['btn_create'] . '</a></div>';
		}
	}

	//display page with edits
	echo $result;

	//restore status
	$ID = $backup['ID'];
	$REV = $backup['REV'];
}

