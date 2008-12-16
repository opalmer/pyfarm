<?php get_header() ?>    
    <div class='content span-16'>
        <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
   			<div <?php if (function_exists('post_class')) { post_class(); } else { echo 'class="post"'; } ?> id="post-<?php the_ID(); ?>">
                <h1><?php the_time('d M y'); ?> <a href="<?php the_permalink(); ?>" rel="bookmark" title="Permanent Link to <?php the_title(); ?>"><?php the_title(); ?></a></h1>       
                <?php the_content(); ?>
                <?php wp_link_pages(array('before' => '<p><strong>Pages:</strong> ', 'after' => '</p>', 'next_or_number' => 'number')); ?>
                <?php if (function_exists('the_tags')) { ?>
                <?php the_tags('<p>Tags: ', ', ', '</p>'); ?>
                <?php } ?>
                <div style="clear:both;"></div>
                <div class="postmeta">Filed in <?php the_category(', ') ?> <?php edit_post_link('Edit', ' | '); ?></div>
			</div>                

            <div class="navigation">
                <div class="alignleft"><?php previous_post_link('&laquo; %link') ?></div>
                <div class="alignright"><?php next_post_link('%link &raquo;') ?></div>
            </div>

			<?php comments_template(); ?>

		<?php endwhile; ?>

		<?php else : ?>
			<h1>Not Found</h1>
			<p>Sorry, but you are looking for something that isn't here.</p>
			<?php include (TEMPLATEPATH . "/searchform.php"); ?>
			<div class="postmeta"></div>
		<?php endif; ?>		
			<div style="clear:both"></div>
			<div class="navigation">
			</div>
    </div>
      
	<?php get_sidebar() ?>

<?php get_footer() ?>