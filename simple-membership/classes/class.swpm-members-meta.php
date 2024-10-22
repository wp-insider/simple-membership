<?php

class SwpmMembersMeta {

	/**
	 * Get members meta table full name.
	 *
	 * @return string
	 */
	public static function get_table_name(){
		global $wpdb;
		return $wpdb->prefix . 'swpm_members_meta_tbl';
	}

	/**
	 * Add new meta for a member
	 *
	 * @param int $member_id
	 * @param string $meta_key
	 * @param mixed $meta_value
	 * 
	 * @return boolean
	 */
	public static function add($member_id, $meta_key, $meta_value) {
		global $wpdb;

		// Check if meta already exists
		if (self::get($member_id, $meta_key)) {
			return false; // Meta already exists
		}

		return $wpdb->insert(
			self::get_table_name(),
			array(
				'member_id'  => $member_id,
				'meta_key'   => $meta_key,
				'meta_value' => maybe_serialize($meta_value),
			),
			array('%d', '%s', '%s')
		);
	}

	/**
	 * Get meta for a member
	 *
	 * @param int $member_id
	 * @param string $meta_key
	 * @param boolean $single
	 * 
	 * @return mixed
	 */
	public static function get($member_id, $meta_key = '', $single = false) {
		global $wpdb;

		$query = $wpdb->prepare(
			"SELECT meta_value FROM ".self::get_table_name()." WHERE member_id = %d",
			$member_id
		);

		if (!empty($meta_key)) {
			$query .= $wpdb->prepare(" AND meta_key = %s", $meta_key);
		}

		$results = $wpdb->get_results($query);

		if (empty($results)) {
			return false;
		}

		// Handle returning single or multiple results
		$meta_values = array_map( 'SwpmMembersMeta::unserialize_meta_value', $results);

		return $single ? reset($meta_values) : $meta_values;
	}


	/**
	 * Update meta for a member
	 *
	 * @param int $member_id
	 * @param string $meta_key
	 * @param mixed $meta_value
	 * 
	 * @return boolean
	 */
	public static function update($member_id, $meta_key, $meta_value) {
		global $wpdb;

		// Check if meta already exists
		if (self::get($member_id, $meta_key)) {
			return $wpdb->update(
				self::get_table_name(),
				array('meta_value' => maybe_serialize($meta_value)),
				array(
					'member_id' => $member_id,
					'meta_key'  => $meta_key,
				),
				array('%s'),
				array('%d', '%s')
			);
		} else {
			// Add new meta if not exists
			return self::add($member_id, $meta_key, $meta_value);
		}
	}

	/**
	 * Delete meta for a member
	 *
	 * @param int $member_id
	 * @param string $meta_key
	 * 
	 * @return void
	 */
	public static function delete($member_id, $meta_key = '') {
		global $wpdb;

		$where = array('member_id' => $member_id);
		$where_format = array('%d');

		if (!empty($meta_key)) {
			$where['meta_key'] = $meta_key;
			$where_format[] = '%s';
		}

		return $wpdb->delete(self::get_table_name(), $where, $where_format);
	}

	/**
	 * Unserialize meta value if needed.
	 *
	 * @param object $meta_data
	 * 
	 * @return mixed
	 */
	public static function unserialize_meta_value($meta_data){
		return maybe_unserialize($meta_data->meta_value);
	}
}
