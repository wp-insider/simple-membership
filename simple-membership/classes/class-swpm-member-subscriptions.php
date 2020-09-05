<?php

class SWPM_Member_Subscriptions {

	private $active_statuses   = array( 'trialing', 'active' );
	private $active_subs_count = 0;
	private $subs_count        = 0;
	private $subs              = array();
	private $member_id;

	public function __construct( $member_id ) {

		$this->member_id = $member_id;

		$query_args = array(
			'post_type'  => 'swpm_transactions',
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key'     => 'member_id',
					'value'   => $member_id,
					'compare' => '=',
				),
				array(
					'key'     => 'gateway',
					'value'   => 'stripe-sca-subs',
					'compare' => '=',
				),
			),
		);

		$found_subs = new WP_Query( $query_args );

		$this->subs_count = $found_subs->post_count;

		foreach ( $found_subs->posts as $found_sub ) {
			$sub            = array();
			$post_id        = $found_sub->ID;
			$sub['post_id'] = $post_id;
			$sub_id         = get_post_meta( $post_id, 'subscr_id', true );

			$status = get_post_meta( $post_id, 'subscr_status', true );

			$sub['status'] = $status;

			if ( $this->is_active( $status ) ) {
				$this->active_subs_count++;
			}

			$cancel_token = get_post_meta( $post_id, 'subscr_cancel_token', true );

			if ( empty( $cancel_token ) ) {
				$cancel_token = md5( $post_id . $sub_id . uniqid() );
				update_post_meta( $post_id, 'subscr_cancel_token', $cancel_token );
			}

			$sub['cancel_token'] = $cancel_token;

			$is_live        = get_post_meta( $post_id, 'is_live', true );
			$is_live        = empty( $is_live ) ? false : true;
			$sub['is_live'] = $is_live;

			$sub['payment_button_id'] = get_post_meta( $post_id, 'payment_button_id', true );

			$this->subs[ $sub_id ] = $sub;
		}

		$this->recheck_status_if_needed();

	}

	public function get_active_subs_count() {
		return $this->active_subs_count;
	}

	public function is_active( $status ) {
		return  in_array( $status, $this->active_statuses, true );
	}

	private function recheck_status_if_needed() {
		foreach ( $this->subs as $sub_id => $sub ) {
			if ( ! empty( $sub['status'] ) ) {
				continue;
			}

			$api_keys = SwpmMiscUtils::get_stripe_api_keys_from_payment_button( $sub['payment_button_id'], $sub['is_live'] );

			SwpmMiscUtils::load_stripe_lib();

			\Stripe\Stripe::setApiKey( $api_keys['secret'] );

			$stripe_sub = \Stripe\Subscription::retrieve( $sub_id );

			$this->subs[ $sub_id ]['status'] = $stripe_sub['status'];

			if ( $this->is_active( $stripe_sub['status'] ) ) {
				$this->active_subs_count++;
			}

			update_post_meta( $sub['post_id'], 'subscr_status', $stripe_sub['status'] );

		}
	}

	public function get_cancel_url( $sub_id = false ) {
		if ( empty( $this->active_subs_count ) ) {
			return '';
		}
		if ( false === $sub_id ) {
			$sub_id = array_key_first( $this->subs );
		}
		$sub = $this->subs[ $sub_id ];

		return sprintf( '<a href="%s">Cancel</a>', $sub_id );
	}

}
