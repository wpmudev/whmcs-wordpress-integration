<?php
class WHMCS_Account_Widget extends WP_Widget{

	public function __construct(){

		parent::__construct(
			'whmcs-account-widget',
			'WHMCS Account' ,
			array( 'description' => __("Displays the current logged in WHMCS user's account information. If no user is logged in it will display a login form and Knowledgebase search.",WHMCS_TEXT_DOMAIN) ) );
	}

	public function widget( $args, $instance ) {
		extract( $args );

		$title = apply_filters('widget_title', $instance['title'] );

		echo $before_widget;

		if ( $title ) echo $before_title . $title . $after_title;

		echo do_shortcode( '[wcp_account]' );

		echo $after_widget;
	}

	public function form( $instance ) {
		global $WHMCS_Wordpress_Integration;
		if( !empty($WHMCS_Wordpress_Integration->template) && 'portal' != $WHMCS_Wordpress_Integration->template ){
			?>
			<p class="error-message"><?php _e('This widget is only compatible with Portal template, which is deprecated since WHMCS v6.0.'); ?></p>
			<?php
		}

		if ( isset( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
		}
		else {
			$title = __( 'New title', 'text_domain' );
		}
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		/* Strip tags (if needed) and update the widget settings. */
		$instance['title'] = strip_tags( $new_instance['title'] );

		return $instance;
	}

}