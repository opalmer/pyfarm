<?php

require_once(dirname(__FILE__).'/ajax.php');
require_once(dirname(__FILE__).'/cache.php');
require_once(dirname(__FILE__).'/const.php');
require_once(dirname(__FILE__).'/config_ui.php');
require_once(dirname(__FILE__).'/l10n.php');
require_once(dirname(__FILE__).'/user_ui.php');
require_once(dirname(__FILE__).'/write_ui.php');

// -----------------------------------------------------------------------------
// the ExecPhp_Admin class provides functionality common to all displayed
// admin menus
// -----------------------------------------------------------------------------

// use this guard to avoid error messages in WP admin panel if plugin
// is disabled because of a version conflict but you still try to reload
// the plugins config interface
if (!class_exists('ExecPhp_Admin')) :
class ExecPhp_Admin
{
	var $m_cache = NULL;
	var $m_ajax = NULL;
	var $m_write_ui = NULL;
	var $m_user_ui = NULL;
	var $m_config_ui = NULL;

	// ---------------------------------------------------------------------------
	// init
	// ---------------------------------------------------------------------------

	function ExecPhp_Admin(&$cache)
	{
		global $wp_version;

		if (version_compare($wp_version, '2.1.dev') < 0)
			return;

		$this->m_cache =& $cache;

		// ajax server needs to be installed without is_admin() check
		$this->m_ajax =& new ExecPhp_Ajax($this->m_cache);
		if (!is_admin())
			return;

		if (version_compare($wp_version, '2.6.dev') >= 0)
			load_plugin_textdomain(ExecPhp_PLUGIN_ID, false, ExecPhp_HOMEDIR. '/languages');
		else
			load_plugin_textdomain(ExecPhp_PLUGIN_ID, ExecPhp_PLUGINDIR. '/'. ExecPhp_HOMEDIR. '/languages');

		$this->m_write_ui =& new ExecPhp_WriteUi($this->m_cache);
		$this->m_user_ui =& new ExecPhp_UserUi($this->m_cache);
		$this->m_config_ui =& new ExecPhp_ConfigUi($this->m_cache);

		add_action('admin_head', array(&$this, 'action_admin_head'));
		add_action('admin_notices', array(&$this, 'action_admin_notices'), 5);
		add_action('admin_footer', array(&$this, 'action_admin_footer'));
	}

	// ---------------------------------------------------------------------------
	// hooks
	// ---------------------------------------------------------------------------

	function action_admin_head()
	{
		if (function_exists('wp_print_scripts'))
			wp_print_scripts(array('sack'));
?>
	<script type="text/javascript">
		//<![CDATA[
		function ExecPhp_setMessage(heading, text)
		{
			var message = '<p><strong>' + heading + '</strong> ' + text + '</p>';
			var parent = document.getElementById("<?php echo ExecPhp_ID_MESSAGE; ?>");
			try
			{
				container = document.createElement("div");
				container.className = "updated fade";
				container.innerHTML = container.innerHTML + message;
				parent.appendChild(container);
			}
			catch(e) {;}
		}
		//]]>
	</script>

<?php
		if (current_user_can(ExecPhp_CAPABILITY_EDIT_PLUGINS)
			|| current_user_can(ExecPhp_CAPABILITY_EDIT_USERS))
		{
?>
	<script type="text/javascript">
		//<![CDATA[
		var g_execphp_ajax = new sack("<?php bloginfo('wpurl'); ?>/wp-admin/admin-ajax.php");
		var g_execphp_error_message = "";
		var g_execphp_retries = 0;
		var g_execphp_max_retries = 3;
		var g_execphp_feature = "";

		function ExecPhp_subscribeForFeature(feature)
		{
			if (g_execphp_feature.length)
				g_execphp_feature += ",";
			g_execphp_feature += feature;
		}

		function ExecPhp_fillContainer(container_id, text)
		{
			var container = document.getElementById(container_id);
			try {container.innerHTML = text;}
			catch (e) {;}
		}

		function ExecPhp_markContainer(container_id)
		{
			var container = document.getElementById(container_id + "-container");
			try {container.style.backgroundColor = "red";}
			catch (e) {;}

		}
		function ExecPhp_ajaxCompletion()
		{
			var edit_others_php = "";
			var switch_themes = "";
			var exec_php = "";

			eval(g_execphp_ajax.response);

			if (!exec_php.length)
				exec_php = "<p><?php echo escape_dquote(__s('No user matching the query.', ExecPhp_PLUGIN_ID)); ?></p>";
			ExecPhp_fillContainer("<?php echo ExecPhp_ID_INFO_EXECUTE_ARTICLES; ?>", exec_php);

			if (!switch_themes.length)
				switch_themes = "<p><?php echo escape_dquote(__s('No user matching the query.', ExecPhp_PLUGIN_ID)); ?></p>";
			ExecPhp_fillContainer("<?php echo ExecPhp_ID_INFO_WIDGETS; ?>", switch_themes);

			if (!edit_others_php.length)
				edit_others_php = "<p><?php echo escape_dquote(__s('No user matching the query.', ExecPhp_PLUGIN_ID)); ?></p>";
			else
			{
				heading = "<?php echo escape_dquote(__s('Exec-PHP Security Alert.', ExecPhp_PLUGIN_ID)); ?>";
				text = "<?php echo escape_dquote(__s('The Exec-PHP plugin found a security hole with the configured user rights of this blog. For further information consult the plugin configuration menu or contact your blog administrator.', ExecPhp_PLUGIN_ID)); ?>";
				ExecPhp_setMessage(heading, text);
				ExecPhp_markContainer("<?php echo ExecPhp_ID_INFO_SECURITY_HOLE; ?>");
			}
			ExecPhp_fillContainer("<?php echo ExecPhp_ID_INFO_SECURITY_HOLE; ?>", edit_others_php);
		}

		function ExecPhp_ajaxError()
		{
			g_execphp_error_message += "<br />"
				+ g_execphp_ajax.responseStatus[0] + " " + g_execphp_ajax.responseStatus[1];

			if (g_execphp_retries < g_execphp_max_retries)
			{
				// retry call; sometimes it seems that the AJAX admin script returns 404
				++g_execphp_retries;
				g_execphp_ajax.runAJAX();
			}
			else
			{
				// finally give up after certain amount of retries
				var error_message = "<p><?php echo escape_dquote(__s("Exec-PHP AJAX HTTP error when receiving data from ", ExecPhp_PLUGIN_ID)); ?>"
					+ g_execphp_ajax.requestFile + ": " + g_execphp_error_message;

				ExecPhp_markContainer("<?php echo ExecPhp_ID_INFO_EXECUTE_ARTICLES; ?>");
				ExecPhp_fillContainer("<?php echo ExecPhp_ID_INFO_EXECUTE_ARTICLES; ?>", error_message);

				ExecPhp_markContainer("<?php echo ExecPhp_ID_INFO_WIDGETS; ?>");
				ExecPhp_fillContainer("<?php echo ExecPhp_ID_INFO_WIDGETS; ?>", error_message);

				ExecPhp_markContainer("<?php echo ExecPhp_ID_INFO_SECURITY_HOLE; ?>");
				ExecPhp_fillContainer("<?php echo ExecPhp_ID_INFO_SECURITY_HOLE; ?>", error_message);

				g_execphp_error_message = "";
				g_execphp_retries = 0;
			}
		}

		function ExecPhp_requestUser()
		{
			ExecPhp_subscribeForFeature('<?php echo ExecPhp_REQUEST_FEATURE_SECURITY_HOLE; ?>');
			g_execphp_ajax.setVar("cookie", document.cookie);
			g_execphp_ajax.setVar("action", "<?php echo ExecPhp_ACTION_REQUEST_USERS; ?>");
			g_execphp_ajax.setVar("feature", g_execphp_feature);
			g_execphp_ajax.onError = ExecPhp_ajaxError;
			g_execphp_ajax.onCompletion = ExecPhp_ajaxCompletion;
			g_execphp_ajax.runAJAX();
			g_execphp_feature = "";
		}
		//]]>
	</script>

	<style type="text/css">
		/* <![CDATA[ */
		#<?php echo ExecPhp_ID_INFO_SECURITY_HOLE; ?> li,
		#<?php echo ExecPhp_ID_INFO_WIDGETS; ?> li,
		#<?php echo ExecPhp_ID_INFO_EXECUTE_ARTICLES; ?> li {
			float: left;
			line-height: 1em;
			width: 20em;
		}

		#<?php echo ExecPhp_ID_INFO_SECURITY_HOLE; ?> p,
		#<?php echo ExecPhp_ID_INFO_WIDGETS; ?> p,
		#<?php echo ExecPhp_ID_INFO_EXECUTE_ARTICLES; ?> p {
			text-align: center;
		}

		#<?php echo ExecPhp_ID_INFO_SECURITY_HOLE; ?> p *,
		#<?php echo ExecPhp_ID_INFO_WIDGETS; ?> p *,
		#<?php echo ExecPhp_ID_INFO_EXECUTE_ARTICLES; ?> p * {
			vertical-align: middle;
		}
<?php
			global $wp_version;
			if (version_compare($wp_version, '2.5.dev') >= 0)
			{
?>

		div#wpbody > div.wrap > form#<?php echo ExecPhp_ID_CONFIG_FORM; ?> > fieldset,
		div#wpbody > div.wrap > form#<?php echo ExecPhp_ID_INFO_FORM; ?> > fieldset{
			border: 0;
			margin: 0;
			padding: 0;
			width: 100%;
		}
<?php
			}
?>

		/* ]]> */
	</style>
<?php
		}
	}

	function action_admin_notices()
	{
?>
<div id="<?php echo ExecPhp_ID_MESSAGE; ?>"></div>
<?php
	}

	function action_admin_footer()
	{
		if (current_user_can(ExecPhp_CAPABILITY_EDIT_PLUGINS)
			|| current_user_can(ExecPhp_CAPABILITY_EDIT_USERS))
		{
?>
	<script type="text/javascript">
		//<![CDATA[
		ExecPhp_requestUser();
		//]]>
	</script>
<?php
		}
	}
}
endif;

?>