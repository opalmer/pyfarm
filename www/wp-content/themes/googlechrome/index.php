<?php get_header() ?>    
    <div class='content span-16'>
        <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
   			<div <?php if (function_exists('post_class')) { post_class(); } else { echo 'class="post"'; } ?> id="post-<?php the_ID(); ?>">
                <h1><?php the_time('d M y'); ?> <a href="<?php the_permalink(); ?>" rel="bookmark" title="Permanent Link to <?php the_title(); ?>"><?php the_title(); ?></a></h1>       
                <?php the_content(); ?>
                <?php if (function_exists('the_tags')) { ?>
                <?php the_tags('<p>Tags: ', ', ', '</p>'); ?>
                <?php } ?>
                <div style="clear:both;"></div>
                <div class="postmeta">Filed in <?php the_category(', ') ?> with <?php comments_popup_link('0 Comments', '1 Comment', '% Comments'); ?> <?php edit_post_link('Edit', ' | '); ?></div>
			</div>
		<?php endwhile; ?>

		<?php else : ?>
			<h1>Not Found</h1>
			<p>Sorry, but you are looking for something that isn't here.</p>
			<?php include (TEMPLATEPATH . "/searchform.php"); ?>
			<div class="postmeta"></div>
		<?php endif; ?>		
			<div style="clear:both"></div>
			<div class="navigation">
				<div class="alignleft"><?php next_posts_link('&laquo; Previous Posts') ?></div>
				<div class="alignright"><?php previous_posts_link('Next Posts &raquo;') ?></div>
			</div>
    </div>
      
	<?php get_sidebar() ?>

<?php get_footer() ?>