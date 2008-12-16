<?php
/*--------------------------------------------------*/
/* FILE GENERATED BY INVISION POWER BOARD           */
/* CACHE FILE: Skin set id: 3                     */
/* CACHE FILE: Generated: Wed, 12 Nov 2008 04:54:08 GMT */
/* DO NOT EDIT DIRECTLY - THE CHANGES WILL NOT BE   */
/* WRITTEN TO THE DATABASE AUTOMATICALLY            */
/*--------------------------------------------------*/

class skin_login_3 {

 var $ipsclass;
//===========================================================================
// <ips:errors:desc::trigger:>
//===========================================================================
function errors($data="") {
$IPBHTML = "";
//--starthtml--//
$IPBHTML .= "<div class=\"borderwrap\">
	<div class=\"formsubtitle\">{$this->ipsclass->lang['errors_found']}</div>
	<div class=\"tablepad\"><span class=\"postcolor\">$data</span></div>
</div>
<br />";
//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// <ips:ShowForm:desc::trigger:>
//===========================================================================
function ShowForm($message="",$referer="",$extra_form="") {
$IPBHTML = "";
//--starthtml--//
$IPBHTML .= "<script language='JavaScript' type=\"text/javascript\">
<!--
function ValidateForm() {
	var Check = 0;
	if (document.LOGIN.UserName.value == '') { Check = 1; }
	if (document.LOGIN.PassWord.value == '') { Check = 1; }
	if (Check == 1) {
" . (($this->ipsclass->vars['ipbli_usertype']=='username') ? ("
alert(\"{$this->ipsclass->lang['blank_fields_user']}\");
") : ("
alert(\"{$this->ipsclass->lang['blank_fields_email']}\");
")) . "
		return false;
	} else {
		document.LOGIN.submit.disabled = true;
		return true;
	}
}
//-->
</script>
" . (($extra_form != '') ? ("
$extra_form
") : ("")) . "
<form action=\"{$this->ipsclass->base_url}act=Login&amp;CODE=01\" method=\"post\" name=\"LOGIN\" onsubmit=\"return ValidateForm()\">
	<input type=\"hidden\" name=\"referer\" value=\"$referer\" />
	<div class=\"borderwrap\">
		<div class=\"maintitle\"><{CAT_IMG}>&nbsp;{$this->ipsclass->lang['log_in']}</div>
		<div class='row2'>
			<div class=\"subtitle\">$message</div>
			<div class=\"errorwrap\" style='margin-bottom:0px;padding-bottom:0px'>
				<h4>{$this->ipsclass->lang['form_title_attention']}</h4>
				<p>{$this->ipsclass->lang['login_text']}</p>
				<p><b>{$this->ipsclass->lang['forgot_pass']} <a href=\"{$this->ipsclass->vars['board_url']}/index.{$this->ipsclass->vars['php_ext']}?act=Reg&amp;CODE=10\">{$this->ipsclass->lang['pass_link']}</a></b></p>
			</div>
		</div>
		<table class='ipbtable' cellspacing=\"0\">
			<tr>
				<td width=\"60%\" valign=\"top\" class='row2'>
					<fieldset>
						<legend><b>{$this->ipsclass->lang['log_in']}</b></legend>
						<table class='ipbtable' cellspacing=\"1\">
							<tr>
" . (($this->ipsclass->vars['ipbli_usertype'] == 'username') ? ("
<td width=\"50%\"><b>{$this->ipsclass->lang['enter_name']}</b></td>
								<td width=\"50%\"><input type=\"text\" size=\"25\" maxlength=\"64\" name=\"UserName\" /></td>
") : ("
<td width=\"50%\"><b>{$this->ipsclass->lang['enter_email']}</b></td>
								<td width=\"50%\"><input type=\"text\" size=\"25\" maxlength='150' value=\"{$this->ipsclass->input['UserName']}\" name=\"UserName\" /></td>
")) . "
							</tr>
							<tr>
								<td width=\"50%\"><b>{$this->ipsclass->lang['enter_pass']}</b></td>
								<td width=\"50%\"><input type=\"password\" size=\"25\" name=\"PassWord\" /></td>
							</tr>
						</table>
					</fieldset>
				</td>
				<td width=\"40%\" valign=\"top\" class='row2'>
					<fieldset>
						<legend><b>{$this->ipsclass->lang['options']}</b></legend>
						<table class='ipbtable' cellspacing=\"1\">
							<tr>
								<td width=\"10%\"><input class='checkbox' type=\"checkbox\" name=\"CookieDate\" value=\"1\" checked=\"checked\" /></td>
								<td width=\"90%\"><b>{$this->ipsclass->lang['rememberme']}</b><br /><span class=\"desc\">{$this->ipsclass->lang['notrecommended']}</span></td>
							</tr>
" . (($this->ipsclass->vars['disable_anonymous'] != 1) ? ("
<tr>
								<td width=\"10%\"><input class='checkbox' type=\"checkbox\" name=\"Privacy\" value=\"1\" /></td>
								<td width=\"90%\"><b>{$this->ipsclass->lang['form_invisible']}</b><br /><span class=\"desc\">{$this->ipsclass->lang['anon_name']}</span></td>
							</tr>
") : ("")) . "
						</table>
					</fieldset>
				</td>
			</tr>
			<tr>
				<td class=\"formbuttonrow\" colspan=\"2\"><input class=\"button\" type=\"submit\" name=\"submit\" value=\"{$this->ipsclass->lang['log_in_submit']}\" /></td>
			</tr>
			<tr>
				<td class=\"catend\" colspan=\"2\"><!-- no content --></td>
			</tr>
		</table>
	</div>
</form>";
//--endhtml--//
return $IPBHTML;
}



}

/*--------------------------------------------------*/
/*<changed bits>
ShowForm
</changed bits>*/
/* END OF FILE                                      */
/*--------------------------------------------------*/

?>