<?php

class cp_skin_taskmanager {

var $ipsclass;


//===========================================================================
// TASK MANAGER: Overview
//===========================================================================
function task_manager_logsshow_wrapper( $last5 ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='tableborder'>
 <div class='tableheaderalt'>Task Manager Logs</div>
 <table cellpadding='0' cellspacing='0' width='100%'>
 <tr>
  <td class='tablesubheader'>Task Run</td>
  <td class='tablesubheader'>Date Run</td>
  <td class='tablesubheader'>Log Info</td>
 </tr>
 $last5
 </table>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// TASK MANAGER: Overview
//===========================================================================
function task_manager_logs_wrapper( $last5, $form ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='tableborder'>
 <div class='tableheaderalt'>Last 5 Run Tasks</div>
 <table cellpadding='0' cellspacing='0' width='100%'>
 <tr>
  <td class='tablesubheader'>Task Run</td>
  <td class='tablesubheader'>Date Run</td>
  <td class='tablesubheader'>Log Info</td>
 </tr>
 $last5
 </table>
</div>

<br />

<form action='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;do=task_log_show' method='post'>
<input type='hidden' name='_admin_auth_key' value='{$this->ipsclass->_admin_auth_key}' />
<div class='tableborder'>
 <div class='tableheaderalt'>View Task Manager Logs</div>
 <table cellpadding='0' cellspacing='0' width='100%'>
 <tr>
  <td class='tablerow1'><strong>View logs for task</strong></td>
  <td class='tablerow2'>{$form['task_title']}</td>
 </tr>
 <tr>
  <td class='tablerow1'><strong>Show <em>n</em> log entries</strong></td>
  <td class='tablerow2'>{$form['task_count']}</td>
 </tr>
 <tr>
  <td colspan='2' class='tablefooter' align='center'><input class='realbutton' type='submit' value='View Logs' /></td>
 </tr>
 </table>
</div>
</form>

<br />

<form action='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;do=task_log_delete' method='post'>
<input type='hidden' name='_admin_auth_key' value='{$this->ipsclass->_admin_auth_key}' />
<div class='tableborder'>
 <div class='tableheaderalt'>Delete Task Manager Logs</div>
 <table cellpadding='0' cellspacing='0' width='100%'>
 <tr>
  <td class='tablerow1'><strong>Delete logs for task</strong></td>
  <td class='tablerow2'>{$form['task_title_delete']}</td>
 </tr>
 <tr>
  <td class='tablerow1'><strong>Delete logs older than <em>n</em> days</strong></td>
  <td class='tablerow2'>{$form['task_prune']}</td>
 </tr>
 <tr>
  <td colspan='2' class='tablefooter' align='center'><input class='realbutton' type='submit' value='DELETE Logs' /></td>
 </tr>
 </table>
</div>
</form>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// TRAFFIC: POPULAR row
//===========================================================================
function task_manager_last5_row( $data ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<tr>
 <td width='25%' class='tablerow1'><strong>{$data['log_title']}</strong></td>
 <td width='15%' class='tablerow2'>{$data['log_date']}</td>
 <td width='45%' class='tablerow2'>{$data['log_desc']}</td>
</tr>
EOF;

//--endhtml--//
return $IPBHTML;
}


//===========================================================================
// Task manager form
//===========================================================================
function task_manager_form( $form, $button, $formbit, $type, $title, $task ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<script type='text/javascript' language='javascript'>
function updatepreview()
{
	var formobj  = document.adminform;
	var dd_wday  = new Array();
	
	dd_wday[0]   = 'Sunday';
	dd_wday[1]   = 'Monday';
	dd_wday[2]   = 'Tuesday';
	dd_wday[3]   = 'Wednesday';
	dd_wday[4]   = 'Thursday';
	dd_wday[5]   = 'Friday';
	dd_wday[6]   = 'Saturday';
	
	var output       = '';
	
	chosen_min   = formobj.task_minute.options[formobj.task_minute.selectedIndex].value;
	chosen_hour  = formobj.task_hour.options[formobj.task_hour.selectedIndex].value;
	chosen_wday  = formobj.task_week_day.options[formobj.task_week_day.selectedIndex].value;
	chosen_mday  = formobj.task_month_day.options[formobj.task_month_day.selectedIndex].value;
	
	var output_min   = '';
	var output_hour  = '';
	var output_day   = '';
	var timeset      = 0;
	
	if ( chosen_mday == -1 && chosen_wday == -1 )
	{
		output_day = '';
	}
	
	if ( chosen_mday != -1 )
	{
		output_day = 'On day '+chosen_mday+'.';
	}
	
	if ( chosen_mday == -1 && chosen_wday != -1 )
	{
		output_day = 'On ' + dd_wday[ chosen_wday ]+'.';
	}
	
	if ( chosen_hour != -1 && chosen_min != -1 )
	{
		output_hour = 'At '+chosen_hour+':'+formatnumber(chosen_min)+'.';
	}
	else
	{
		if ( chosen_hour == -1 )
		{
			if ( chosen_min == 0 )
			{
				output_hour = 'On every hour';
			}
			else
			{
				if ( output_day == '' )
				{
					if ( chosen_min == -1 )
					{
						output_min = 'Every minute';
					}
					else
					{
						output_min = 'Every '+chosen_min+' minutes.';
					}
				}
				else
				{
					output_min = 'At '+formatnumber(chosen_min)+' minutes past the first available hour';
				}
			}
		}
		else
		{
			if ( output_day != '' )
			{
				output_hour = 'At ' + chosen_hour + ':00';
			}
			else
			{
				output_hour = 'Every ' + chosen_hour + ' hours';
			}
		}
	}
	
	output = output_day + ' ' + output_hour + ' ' + output_min;
	
	formobj.showtask.value = output;
}
							
function formatnumber(num)
{
	if ( num == -1 )
	{
		return '00';
	}
	if ( num < 10 )
	{
		return '0'+num;
	}
	else
	{
		return num;
	}
}
</script>
<form name='adminform' action='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;do=$formbit&amp;task_id={$task['task_id']}&amp;type=$type' method='post'>
<input type='hidden' name='_admin_auth_key' value='{$this->ipsclass->_admin_auth_key}' />
<input type='hidden' name='task_cronkey' value='{$task['task_cronkey']}' />
<div class='tableborder'>
 <div class='tableheaderalt'>
  <div style='float:left'>$title</div>
  <div align='right' style='padding-right:5px'><input type='text' name='showtask' class='realbutton' size='50' style='font-size:10px;width:auto;font-weight:normal;'/></div>
 </div>
 <table cellpadding='0' cellspacing='0' border='0' width='100%'>
 <tr>
   <td width='40%' class='tablerow1'><strong>Task Title</strong></td>
   <td width='60%' class='tablerow2'>{$form['task_title']}</td>
 </tr>
 <tr>
   <td width='40%' class='tablerow1'><strong>Task Short Description</strong></td>
   <td width='60%' class='tablerow2'>{$form['task_description']}</td>
 </tr>
 <tr>
   <td width='40%' class='tablerow1'><strong>Task PHP File To Run</strong><div class='desctext'>This is the PHP file that is run when the task is run.</div></td>
   <td width='60%' class='tablerow2'>./sources/tasks/ {$form['task_file']}</td>
 </tr>
 <tr>
   <td width='40%' class='tablerow1' colspan='2'>
    <fieldset>
    <legend><strong>Time Options</strong></legend>
    <table cellpadding='0' cellspacing='0' border='0' width='100%'>
    <tr>
   		<td width='40%' class='tablerow1'><strong>Task Time: Minutes</strong><div class='desctext'>Choose 'Every Minute' to run each minute or a number for a specific minute of an hour</div></td>
   		<td width='60%' class='tablerow2'>{$form['task_minute']}</td>
 	</tr>
 	<tr>
   		<td width='40%' class='tablerow1'><strong>Task Time: Hours</strong><div class='desctext'>Choose 'Every Hour' to run each hour or a number for a specific hour of a day</div></td>
   		<td width='60%' class='tablerow2'>{$form['task_hour']}</td>
 	</tr>
 	<tr>
   		<td width='40%' class='tablerow1'><strong>Task Time: Week Day</strong><div class='desctext'>Choose 'Every Day' to run each day or a week day for a specific week day of a month</div></td>
   		<td width='60%' class='tablerow2'>{$form['task_week_day']}</td>
 	</tr>
 	<tr>
   		<td width='40%' class='tablerow1'><strong>Task Time: Month Day</strong><div class='desctext'>Choose 'Every Day' to run each day or a month day for a specific month day of a month</div></td>
   		<td width='60%' class='tablerow2'>{$form['task_month_day']}</td>
 	</tr>
    </table>
   </fieldset>
  </td>
 </tr>
 <tr>
   <td width='40%' class='tablerow1'><strong>Enable Task Logging</strong><div class='desctext'>Will write to the task log each time the task is run, not recommended for regular tasks run every few minutes.</div></td>
   <td width='60%' class='tablerow2'>{$form['task_log']}</td>
 </tr>
 <tr>
   <td width='40%' class='tablerow1'><strong>Enable Task?</strong><div class='desctext'>If you are using CRON, you might wish to disable this task from the internal manager.</div></td>
   <td width='60%' class='tablerow2'>{$form['task_enabled']}</td>
 </tr>
EOF;
//startif
if ( $form['task_key'] != "" )
{		
$IPBHTML .= <<<EOF
 <tr>
   <td width='40%' class='tablerow1'><strong>Task Key</strong><div class='desctext'>This is used to call a task where the ID of the task might change</div></td>
   <td width='60%' class='tablerow2'>{$form['task_key']}</td>
 </tr>
 <tr>
   <td width='40%' class='tablerow1'><strong>Task Safe Mode</strong><div class='desctext'>If set to 'yes', this will not be editable by admins</div></td>
   <td width='60%' class='tablerow2'>{$form['task_safemode']}</td>
 </tr>
EOF;
}//endif
$IPBHTML .= <<<EOF
 </table>
 <div align='center' class='tablefooter'><input type='submit' class='realbutton' value='$button' /></div>
</div>
</form>
EOF;

//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// TASK MANAGER: Overview
//===========================================================================
function task_manager_wrapper($content, $date) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='tableborder'>
 <div class='tableheaderalt'>Scheduled Tasks</div>
 <table cellpadding='0' cellspacing='0' width='100%'>
 <tr>
  <td class='tablesubheader' width='40%'>Title</td>
  <td class='tablesubheader' width='25%'>Next Run</td>
  <td class='tablesubheader' width='5%'>Min</td>
  <td class='tablesubheader' width='5%'>Hour</td>
  <td class='tablesubheader' width='5%'>MDay</td>
  <td class='tablesubheader' width='5%'>WDay</td>
  <td class='tablesubheader' width='1%'>Options</td>
 </tr>
 $content
 </table>
 <div align='center' class='tablefooter'><div class='fauxbutton-wrapper'><span class='fauxbutton'><a href='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;do=task_add'>Add New Task</a></span></div></div>
</div>
<br />
<div align='center' class='desctext'><a href='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;do=task_rebuild_xml'>Rebuild tasks from tasks.xml</a></em></div>
<br />
<div align='center' class='desctext'><em>All times GMT. GMT time now is: $date</em></div>
EOF;
//--endhtml--//
return $IPBHTML;
}

//===========================================================================
// TRAFFIC: POPULAR row
//===========================================================================
function task_manager_row( $row ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<tr>
 <td class='tablerow1'>
  <table cellpadding='0' cellspacing='0' width='100%'>
  <tr>
   <td width='99%' style='font-size:10px'>
	 <strong{$row['_class']}>
EOF;
//startif
if ( $row['task_locked'] > 0 )
{		
$IPBHTML .= <<<EOF
 <a href='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;do=task_unlock&amp;task_id={$row['task_id']}'><img src='{$this->ipsclass->skin_acp_url}/images/lock_close.gif' border='0' alt='Unlock' class='ipd' /></a>
EOF;
}//endif
$IPBHTML .= <<<EOF
	 {$row['task_title']}{$row['_title']}</strong>
	 <div style='color:gray'><em>{$row['task_description']}</em></div>
	   <div align='center' style='position:absolute;width:auto;display:none;text-align:center;background:#EEE;border:2px outset #555;padding:4px' id='pop{$row['task_id']}'>
		curl -s -o /dev/null {$this->ipsclass->vars['board_url']}/index.{$this->ipsclass->vars['php_ext']}?{$this->ipsclass->form_code}&amp;ck={$row['task_cronkey']}
	   </div>
   </td>
   <td width='1%' nowrap='nowrap'>
	<a href='#' onclick="toggleview('pop{$row['task_id']}');return false;" title='Show CURL to use in a cron'><img src='{$this->ipsclass->skin_acp_url}/images/task_cron.gif' border='0' alt='Cron' /></a>
	<a href='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;do=task_run_now&amp;task_id={$row['task_id']}' title='Run task now (id: {$row['task_id']})'><img src='{$this->ipsclass->skin_acp_url}/images/{$row['_image']}'  border='0' alt='Run' /></a>
   </td>
  </tr>
 </table>
 </td>
 <td class='tablerow2'>{$row['_next_run']}</td>
 <td class='tablerow2'>{$row['task_minute']}</td>
 <td class='tablerow2'>{$row['task_hour']}</td>
 <td class='tablerow2'>{$row['task_month_day']}</td>
 <td class='tablerow2'>{$row['task_week_day']}</td>
 <td class='tablerow1'><img id="menu{$row['task_id']}" src='{$this->ipsclass->skin_acp_url}/images/filebrowser_action.gif' border='0' alt='Options' class='ipd' /></td>
</tr>
<script type="text/javascript">
  menu_build_menu(
  "menu{$row['task_id']}",
  new Array( img_edit   + " <a href='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;do=task_edit&amp;task_id={$row['task_id']}'>Edit Task...</a>",
  			 img_password   + " <a href='{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;do=task_unlock&amp;task_id={$row['task_id']}'>Unlock Task...</a>",
  			 img_delete   + " <a href='#' onclick='confirm_action(\"{$this->ipsclass->base_url}&amp;{$this->ipsclass->form_code}&amp;do=task_delete&amp;task_id={$row['task_id']}\"); return false;'>Delete Task...</a>"
		    ) );
 </script>
EOF;

//--endhtml--//
return $IPBHTML;
}


}


?>