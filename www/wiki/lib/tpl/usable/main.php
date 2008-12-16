<?php if (!defined('DOKU_INC')) die('Must be run within dokuwiki!'); ?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $conf['lang']?>" lang="<?php echo $conf['lang']?>" dir="<?php echo $lang['direction']?>">

<head>
  <meta http-equiv="Content-type" content="text/html; charset=utf-8" />
  <meta http-equiv="Content-Language" content="en-us" />

  <title><?php tpl_pagetitle()?> | <?php echo strip_tags($conf['title'])?></title>
    
  <link rel='stylesheet' type='text/css' href='<?php echo DOKU_TPL?>/blueprint/screen.css' />
  
  <script src='<? echo DOKU_TPL?>/scripts.js' type='text/javascript'></script>
  
  <?php tpl_metaheaders()?>
  <?php if (file_exists(DOKU_PLUGIN.'displaywikipage/code.php')) include_once(DOKU_PLUGIN.'displaywikipage/code.php'); ?>
  
</head>

<body>

  <!-- Top Header -->
  <div id='fsuNav'>
    <div id='fsuNavInner'>
    </div>
  </div>
  
  <!-- Top Bar -->
  <div id="topBar" class='clearfix'>
    
    <div id="topBarContent" class='clearfix'>
      <h1><?php echo strip_tags($conf['title'])?></h1>
      <p>
        <?php if($conf['youarehere']): ?>
          <?php tpl_youarehere() ?>
        <?php else: ?>
          <?php tpl_breadcrumbs()?>
        <?php endif; ?>
      </p>
    </div>
    
    <div id="topBarButtons">
      <?php tpl_button('edit')?>
      <?php tpl_button('history')?>
    </div>
  
  </div>

  <!-- Main Area -->
  <div id="mainArea" class='clearfix'>
  
    <!-- Left Navigation -->
    <div id="leftNav">
    
      <p id='accountInfo'><?php tpl_userinfo(); ?><br /><?php tpl_actionlink('profile', '', '', 'My Account'); ?> | <?php tpl_actionlink('login'); ?> <?php if(auth_isadmin()): ?>| <?php tpl_actionlink('admin'); ?><?php endif; ?></p>
            
      <form id='search' class='clearfix' accept-charset='utf-8' action='/doku.php/'>
        <p>
          <input type="hidden" value="search"  name="do"/>
          <input type='text' id="navSearch" onfocus="toggleNavSearchFocus()" onblur="toggleNavSearchBlur()" value='search' name="id" accesskey="f" /> <button class='button' type='submit'>&raquo;</button>
        </p>
        
      </form>
      
      <div class='clearfix'>
        <h3>Index</h3>
        
        <?php if (function_exists('dwp_display_wiki_page')): ?>
          <?php dwp_display_wiki_page("shared:sidebar"); ?>
        <?php else: ?>
          <?php include(dirname(__FILE__) . '/sidebar.php'); ?>
        <?php endif; ?>
        
      </div>
      
      <div>
        <h3>Meta</h3>
        <ul>
          <li><a href="<?php echo DOKU_URL; ?>/feed.php">Wiki RSS Feed</a></li>
          <li><a href="<?php echo DOKU_URL; ?>doku.php/wiki:editing_guide">Style and Editing Guide</a></li>
          <li><a href="<?php echo DOKU_URL; ?>doku.php/wiki:syntax">Wiki Syntax Guide</a></li>
          <li><a href="http://wiki.splitbrain.org/wiki%3Amanual">DokuWiki Documentation</a></li>
        </ul>
      </div>
    </div>
    
    <!-- Content Area -->
    <div id="content">
      <div class='page dokuwiki'>
        <?php flush();?>
        <!-- wikipage start -->
        <?php tpl_content();?>
        <!-- wikipage stop -->
        <?php flush();?>
      </div>
    </div>
  
  </div>
    
  <!-- Footer -->
  <div id="footer">
    <p>
      <?php tpl_pageinfo(); ?> | <?php tpl_actionlink('subscription')?> | <?php tpl_actionlink('edit')?>
      <br />Copyright (c) <?php echo(date('Y'));?> <!-- Your company here? -->
    </p>
  </div>

  <div class="no"><?php /* provide DokuWiki housekeeping, required in all templates */ tpl_indexerWebBug()?></div>
  
</body>
</html>
