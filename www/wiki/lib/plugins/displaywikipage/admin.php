<?php
/**
 * Display Wiki Page for DokuWiki
 * 
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Etienne Gauthier, Terence J. Grant<tjgrant@tatewake.com>
 */
 
if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'admin.php');

//--- Exported code
include_once(DOKU_PLUGIN.'displaywikipage/code.php');
//--- Exported code

/**
 * All DokuWiki plugins to extend the admin function
 * need to inherit from this class
 */
class admin_plugin_displaywikipage extends DokuWiki_Admin_Plugin
{
	/**
	 * Constructor
	 */
	function admin_plugin_displaywikipage()
	{
		$this->setupLocale();
	}

	/**
	 * return some info
	 */
	function getInfo()
	{
		return array(
			'author' => 'Terence J. Grant',
			'email'  => 'tjgrant@tatewake.com',
			'date'   => '2007-02-14',
			'name'   => 'Display Wiki Page Plugin',
			'desc'   => 'Plugin that defines an additional template function such that you can display more than one wiki page at a time on any given document.',
			'url'    => 'http://tatewake.com/wiki/projects:display_wiki_page_for_dokuwiki',
		);
	}

	/**
	 * return sort order for position in admin menu
	 */
	function getMenuSort()
	{
		return 999;
	}
	
	/**
	 *  return a menu prompt for the admin menu
	 *  NOT REQUIRED - its better to place $lang['menu'] string in localised string file
	 *  only use this function when you need to vary the string returned
	 */
	function getMenuText()
	{
		return 'Display Wiki Page';
	}

	/**
	 * handle user request
	 */
	function handle()
	{
		$this->state = 0;
	
		if (!isset($_REQUEST['cmd'])) return;   // first time - nothing to do

		if (!is_array($_REQUEST['cmd'])) return;

		$this->displaywikipage = $_REQUEST['displaywikipage'];

		if (is_array($this->displaywikipage))
		{
			$this->state = 1;
		}
	}

	/**
	 * output appropriate html
	 */
	function html()
	{
		global $conf;
		global $ga_loaded, $ga_settings;

		print $this->plugin_locale_xhtml('intro');
	}
}

