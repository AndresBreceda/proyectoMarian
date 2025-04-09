<?php

namespace Skydropx\Includes;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

use Skydropx\Helper\Helper;

class Skydropx_Repository
{
    /**
     * Create WooCommerce API keys for the current user.
     *
     * @return array|WP_Error [consumer_key, consumer_secret] or WP_Error on failure.
     */
    public function create_wc_api_keys()
    {
        global $wpdb;

        $user = wp_get_current_user();
        if (!$user || empty($user->ID)) {
            return new WP_Error('invalid_user', __('Invalid user', 'skydropx'));
        }

        // Generate API keys.
        $consumer_key = 'ck_' . wc_rand_hash();
        $consumer_secret = 'cs_' . wc_rand_hash();
        $hashed_key = wc_api_hash($consumer_key);

        $data = [
            'user_id' => $user->ID,
            'description' => 'Skydropx',
            'permissions' => 'read_write',
            'consumer_key' => $hashed_key,
            'consumer_secret' => $consumer_secret,
            'truncated_key' => substr($consumer_key, -7),
        ];

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $result = $wpdb->insert("{$wpdb->prefix}woocommerce_api_keys", $data, ['%d', '%s', '%s', '%s', '%s', '%s']);
        if ($result === false) {
            return new WP_Error('db_insert_error', __('Could not create the API key.', 'skydropx'));
        }

        return [$consumer_key, $consumer_secret];
    }

    /**
     * Fetch API keys created by Skydropx for a given user ID.
     *
     * @param int $user_id The WordPress user ID.
     * @return array|null Array of API keys or null if not found.
     */
    public function fetch_api_keys_by_user($user_id)
    {
        global $wpdb;

        // Add a wildcard to the description search
        $like_condition = '%' . $wpdb->esc_like('Skydropx') . '%';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT consumer_key, consumer_secret
                FROM {$wpdb->prefix}woocommerce_api_keys
                WHERE user_id = %d AND description LIKE %s
                LIMIT 1",
                $user_id,
                $like_condition
            ),
            ARRAY_A
        );
    }

    /**
     * Validate WooCommerce API keys against the database.
     *
     * @param string $consumer_key The consumer key to validate.
     * @return bool True if valid, false otherwise.
     */
    public function validate_api_key($consumer_key)
    {
        global $wpdb;

        $hashed_key = wc_api_hash($consumer_key);

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}woocommerce_api_keys WHERE consumer_key = %s",
                $hashed_key
            )
        ) > 0;
    }

    private function log_query_results($result)
    {
        if ($result === false) {
            // Translators: %s is the error message returned from the database.
            Helper::log_error(sprintf(__('Error executing query: %s', 'skydropx'), esc_html($wpdb->last_error)));
        } else {
            // Translators: %d is the number of rows affected by the query.
            Helper::log_info(sprintf(__('Query executed successfully, rows affected: %d', 'skydropx'), $result));
        }
    }

    /**
     * Clean webhooks linked to skydropx services.
     */
    public function skydropx_clean_webhooks()
    {
        global $wpdb;

        // Define the table name safely without interpolation inside the prepare call
        $table_name = $wpdb->prefix . 'wc_webhooks';

        // Sanitize and escape the condition
        $like_condition = '%' . $wpdb->esc_like('SKYDROPX') . '%';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $result = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$table_name} WHERE status = %s AND delivery_url LIKE %s",
                'active',
                $like_condition
            )
        );

        $this->log_query_results($result);
    }

    /**
     * Delete Skydropx API keys 
     */
    public function delete_api_keys()
    {
        try {
            global $wpdb;

            // Define and sanitize table name safely
            $table_name = esc_sql($wpdb->prefix . 'woocommerce_api_keys');
            $like_condition = '%' . $wpdb->esc_like('skydropx') . '%';

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $result = $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$table_name} WHERE description LIKE %s AND permissions = %s",
                    $like_condition,
                    'read_write'
                )
            );

            $this->log_query_results($result);
        } catch (\Throwable $th) {
            Helper::log_error(sprintf(
                // Translators: %s is the error message encountered during API key deletion.
                __('Exception occurred deleting API keys: %s', 'skydropx'),
                esc_html($th->getMessage())
            ));
        }
    }

    public function reset_api_keys()
    {
        $this->delete_api_keys();
        return $this->create_wc_api_keys();
    }
}
