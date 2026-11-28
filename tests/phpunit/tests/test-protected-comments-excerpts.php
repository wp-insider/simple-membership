<?php
/**
 * Comments and manual excerpts of protected posts are hidden from visitors in the places that skip the
 * `comment_text` and `the_content` filters: the comments feed, the Latest Comments block (comment excerpt),
 * and manual post excerpts (REST API, feeds, Post Excerpt block). From the 4.9.x information disclosure reports.
 */
class Test_Protected_Comments_Excerpts extends WP_UnitTestCase {

	const MARKER = 'SWPM-SECRET-MARKER';

	public function tear_down() {
		unset( $GLOBALS['comment'], $GLOBALS['post'] );
		parent::tear_down();
	}

	private function post( $protected, $excerpt = '' ) {
		$post_id = self::factory()->post->create(
			array(
				'post_content' => 'Members only content.',
				'post_excerpt' => $excerpt,
				'post_status'  => 'publish',
			)
		);
		if ( $protected ) {
			$level_id = SWPM_Test_Factory::create_level();
			SwpmProtection::apply_protection_to_post( $post_id, array( $level_id ) );
		}
		return $post_id;
	}

	private function comment( $post_id ) {
		return self::factory()->comment->create(
			array(
				'comment_post_ID'  => $post_id,
				'comment_content'  => 'A comment with ' . self::MARKER,
				'comment_approved' => 1,
			)
		);
	}

	public function test_comment_text_of_a_protected_post_is_hidden_from_a_visitor() {
		$comment_id = $this->comment( $this->post( true ) );

		$this->assertStringNotContainsString( self::MARKER, get_comment_text( $comment_id ) );
		$this->assertStringContainsString( 'You need to be logged in', get_comment_text( $comment_id ) );
	}

	public function test_comments_feed_description_of_a_protected_post_is_hidden_from_a_visitor() {
		$GLOBALS['comment'] = get_comment( $this->comment( $this->post( true ) ) );

		ob_start();
		comment_text_rss();
		$rss = ob_get_clean();

		$this->assertStringNotContainsString( self::MARKER, $rss );
	}

	public function test_comment_excerpt_of_a_protected_post_is_hidden_from_a_visitor() {
		$comment_id = $this->comment( $this->post( true ) );

		$this->assertStringNotContainsString( self::MARKER, get_comment_excerpt( $comment_id ) );
	}

	public function test_comments_of_an_unprotected_post_are_shown() {
		$comment_id = $this->comment( $this->post( false ) );

		$this->assertStringContainsString( self::MARKER, get_comment_text( $comment_id ) );
		$this->assertStringContainsString( self::MARKER, get_comment_excerpt( $comment_id ) );
	}

	public function test_manual_excerpt_of_a_protected_post_is_hidden_from_a_visitor() {
		$post_id = $this->post( true, 'Excerpt with ' . self::MARKER );

		$this->assertStringNotContainsString( self::MARKER, get_the_excerpt( $post_id ) );
		$this->assertStringContainsString( 'You need to be logged in', get_the_excerpt( $post_id ) );
	}

	public function test_rest_api_excerpt_of_a_protected_post_is_hidden_from_a_visitor() {
		$post_id = $this->post( true, 'Excerpt with ' . self::MARKER );

		$response = rest_do_request( new WP_REST_Request( 'GET', '/wp/v2/posts/' . $post_id ) );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertStringNotContainsString( self::MARKER, $data['excerpt']['rendered'] );
	}

	public function test_manual_excerpt_of_an_unprotected_post_is_shown() {
		$post_id = $this->post( false, 'Excerpt with ' . self::MARKER );

		$this->assertStringContainsString( self::MARKER, get_the_excerpt( $post_id ) );
	}

	public function test_auto_excerpt_of_a_protected_post_is_unchanged() {
		//No manual excerpt: the excerpt is made from the filtered content, the new filter leaves it alone.
		$post_id = $this->post( true );
		$GLOBALS['post'] = get_post( $post_id );//The_content filter of the plugin reads the global $post.

		$this->assertStringNotContainsString( 'Members only content', get_the_excerpt( $post_id ) );
	}
}
