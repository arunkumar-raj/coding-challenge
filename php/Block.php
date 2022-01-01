<?php
/**
 * Block class.
 *
 * @package SiteCounts
 */

namespace XWP\SiteCounts;

use WP_Block;
use WP_Query;

/**
 * The Site Counts dynamic block.
 *
 * Registers and renders the dynamic block.
 */
class Block {

	/**
	 * The Plugin instance.
	 *
	 * @var Plugin
	 */
	protected $plugin;

	/**
	 * Instantiates the class.
	 *
	 * @param Plugin $plugin The plugin object.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Adds the action to register the block.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'init', [ $this, 'register_block' ] );
	}

	/**
	 * Registers the block.
	 */
	public function register_block() {
		register_block_type_from_metadata(
			$this->plugin->dir(),
			[
				'render_callback' => [ $this, 'render_callback' ],
			]
		);
	}

	/**
	 * Renders the block.
	 *
	 * @param array    $attributes The attributes for the block.
	 * @param string   $content    The block content, if any.
	 * @param WP_Block $block      The instance of this block.
	 * @return string The markup of the block.
	 */
	public function render_callback( $attributes, $content, $block ) {
		global $post;
		$post_id = $post->ID;
		$post_types = get_post_types(  [ 'public' => true ] );
		$class_name = isset($attributes['className'])?$attributes['className']:'';
		ob_start();
		?>
        <div class="<?php echo $class_name; ?>">
			<h2><?php _e( 'Post Counts' , 'site-count'); ?></h2>
			<ul>
				<?php
				foreach ( $post_types as $post_type_slug ) :
					$post_type_object = get_post_type_object( $post_type_slug  );
					$post_count = count(
						get_posts(
							[
								'post_type' => $post_type_slug,
								'posts_per_page' => -1,
							]
						)
					);

					?>
					<li><?php printf( __( 'There are %d %s.', 'site-count' ),$post_count,$post_type_object->labels->name); ?></li>
				<?php endforeach;	?>
			</ul><p><?php printf( __( 'The current post ID is %d.', 'site-count' ),$post_id); ?></p>

			<?php
			//get Tags on the post and send it to query 
			//The previous code was hardcoded that won't serve the result as per page plugin
			$post_tags = get_the_tags($post_id);
			$tag_slug = $tag_names = array();
			if ( !empty($post_tags) ) {
				foreach( $post_tags as $tag ) {
					$tag_slug[] = $tag->slug; 
					$tag_names[] = $tag->name;
				}
			}
			$query_tags = implode(',',$tag_names);

			//get Category name on the post and send it to query 
			$category_detail=get_the_category($post_id);
			$cat_ids = $cat_names = array();
			if ( !empty($category_detail) ) {
				foreach($category_detail as $category){
					$cat_ids[] = $category->term_id;
					$cat_names[] = $category->name;
				}
			}
			$query_category_names = implode(',',$cat_names);
			
			$query = new WP_Query(  array(
				'post_type' => ['post', 'page'],
				'post_status' => 'any',
				'date_query' => array(
					array(
						'hour'      => 9,
						'compare'   => '>=',
					),
					array(
						'hour' => 17,
						'compare'=> '<=',
					),
				),
                'tag_slug__in'  => $tag_slug,
                'category__in'  => $cat_ids,
				'post__not_in' => [ get_the_ID() ],
			));

			if ( $query->found_posts ) :
				?>
				 <h2>
					 <?php 
					 _e( '5 posts' , 'site-count');
					 if($query_tags !='')
					 	printf( __( ' with the tag of %s', 'site-count' ),$query_tags); 
					 if($query_tags !='' && $query_category_names != '')
					 	_e( ' and ' , 'site-count');
					 if($query_category_names != '')
					 	printf( __( ' the category of %s.', 'site-count' ),$query_category_names); 
					 ?>
				 </h2>
                <ul>
                <?php
                foreach ( array_slice( $query->posts, 0, 5 ) as $post ) :
                    ?><li><?php echo $post->post_title ?></li><?php
				endforeach;
			endif;
		 	?>
				</ul>
			
		</div>
		<?php

		return ob_get_clean();
	}
}
