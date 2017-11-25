<?php

namespace Parsedownparty;

class Plugin {

	const METAKEY = 'kizu514_use_markdown';

	const NONCE = '_kizu514_parsedown_party';

	/**
	 * @var Plugin
	 */
	private static $instance = null;

	/**
	 * @var \ParsedownExtra
	 */
	private $parsedown;

	/**
	 * @return Plugin
	 */
	static public function init() {
		if ( is_null( self::$instance ) ) {
			$extra = new \ParsedownExtra();
			self::$instance = new self( $extra );
			self::hooks( self::$instance );
		}
		return self::$instance;
	}

	/**
	 * @param Plugin $obj
	 */
	static public function hooks( Plugin $obj ) {
		add_action( 'post_submitbox_misc_actions', [ $obj, 'createMarkdownLink' ] );
		add_action( 'save_post', [ $obj, 'saveMarkdownMeta' ] );
		add_filter( 'wp_editor_settings', [ $obj, 'parseEditorSettings' ] );
		add_action( 'admin_enqueue_scripts', [ $obj, 'overrideEditor' ] );
		add_filter( 'the_content', [ $obj, 'parseTheContent' ], 9 );
	}

	/**
	 * @param \ParsedownExtra $parsedown
	 */
	public function __construct( $parsedown ) {
		$this->parsedown = $parsedown;
	}

	/**
	 * Use markdown for post?
	 *
	 * @param \WP_Post $post
	 *
	 * @return bool
	 */
	public function useMarkdownForPost( $post = null ) {
		if ( ! $post ) {
			$post = get_post();
			if ( ! $post ) {
				// Try to find using deprecated means
				global $id;
				$post = get_post( $id );
			}
		}
		if ( $post && get_post_meta( $post->ID, self::METAKEY, true ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Create a markdown activation link in the post editor submit box
	 *
	 * @see https://developer.wordpress.org/resource/dashicons/
	 *
	 * @param \WP_Post $post
	 */
	public function createMarkdownLink( $post ) {
		$use_markdown = $this->useMarkdownForPost( $post );
		wp_nonce_field( $post->ID, self::NONCE );
		echo '<input type="hidden" value="' . (int) $use_markdown . '" name="' . self::METAKEY . '"  id="' . self::METAKEY . '" />';
		if ( $use_markdown ) {
			?>
			<div class="misc-pub-section">
				<span class="dashicons dashicons-editor-code"></span> Markdown:
				<a href="javascript:{}"
				   onclick="document.getElementById('<?php echo self::METAKEY; ?>').value = 0; document.getElementById('post').submit(); return false;"><?php _e( 'Disable' ) ?></a>
			</div>
			<?php
		} else {
			?>
			<div class="misc-pub-section">
				<span class="dashicons dashicons-editor-code"></span> Markdown:
				<a href="javascript:{}"
				   onclick="document.getElementById('<?php echo self::METAKEY; ?>').value = 1; document.getElementById('post').submit(); return false;"><?php _e( 'Enable' ) ?></a>
			</div>
			<?php
		}
	}

	/**
	 * Save markdown post meta
	 *
	 * @param int $post_id Post ID.
	 */
	public function saveMarkdownMeta( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! isset( $_POST[ self::NONCE ] ) || ! wp_verify_nonce( $_POST[ self::NONCE ], $post_id ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( ! empty( $_POST[ self::METAKEY ] ) ) {
			update_post_meta( $post_id, self::METAKEY, 1 );
		} else {
			delete_post_meta( $post_id, self::METAKEY );
		}
	}

	/**
	 * If markdown, then disable most of the post editor settings
	 *
	 * @param array $settings
	 *
	 * @return array
	 */
	public function parseEditorSettings( $settings ) {
		if ( $this->useMarkdownForPost() ) {
			$settings['wpautop'] = false;
			$settings['media_buttons'] = false;
			$settings['tinymce'] = false;
			$settings['quicktags'] = false;
		}
		return $settings;
	}

	/**
	 * If markdown, then replace the post editor with CodeMirror configured for markdown
	 *
	 * @see https://make.wordpress.org/core/2017/10/22/code-editing-improvements-in-wordpress-4-9/
	 */
	public function overrideEditor() {
		if ( $this->useMarkdownForPost() ) {
			$settings = wp_enqueue_code_editor( [ 'type' => 'text/x-markdown' ] );
			if ( false === $settings ) {
				// Bail if user disabled CodeMirror.
				return;
			}
			wp_add_inline_script(
				'code-editor',
				sprintf( 'jQuery( function() { wp.codeEditor.initialize( "content", %s ); } );', wp_json_encode( $settings ) )
			);
		}
	}

	/**
	 * If markdown, then parse the_content using Parsedown
	 *
	 * @param string $content
	 *
	 * @return string
	 */
	public function parseTheContent( $content ) {
		if ( $this->useMarkdownForPost() ) {
			$content = $this->parsedown->parse( $content );
		}
		return $content;
	}
}
