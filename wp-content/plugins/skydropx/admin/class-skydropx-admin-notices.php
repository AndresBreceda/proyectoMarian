<?php
namespace Skydropx\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class Skydropx_Admin_Notices
{
    const NOTICES_OPTION_KEY = 'admin_notices_key';

    /**
     * Output stored notices.
     */
    public static function output_notices()
    {
        $notices = self::get_notices();
        if (empty($notices) || !is_admin()) {
            return;
        }

        foreach ($notices as $type => $messages) {
            foreach ($messages as $key => $message) {
                printf(
                    '<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
                    esc_attr($type),
                    wp_kses(
                        $message,
                        [
                            'a'      => [
                                'href'   => [],
                                'target' => [],
                                'rel'    => [],
                            ],
                            'strong' => [],
                            'em'     => [],
                            'br'     => [],
                        ]
                    )
                );
            }
        }

        self::update_notices([]);
    }

    /**
     * Retrieve stored notices.
     *
     * @return array
     */
    private static function get_notices()
    {
        return get_option(self::NOTICES_OPTION_KEY, []);
    }

    /**
     * Update notices in the options table.
     *
     * @param array $notices
     */
    private static function update_notices(array $notices)
    {
        update_option(self::NOTICES_OPTION_KEY, $notices);
    }

    /**
     * Add a notice with a unique key.
     *
     * @param string $key Unique key for the notice.
     * @param string $message The message to display.
     * @param string $type The type of notice (success, error, warning, info).
     */
    private static function add_notice($key, $message, $type = 'success')
    {
        $notices = self::get_notices();

        // Ensure no duplicates using the key
        if (!isset($notices[$type][$key])) {
            $notices[$type][$key] = $message;
            self::update_notices($notices);
        }
    }

    /**
     * Add a success notice.
     *
     * @param string $key Unique key for the notice.
     * @param string $message The message to display.
     */
    public static function add_success($key, $message)
    {
        self::add_notice($key, $message, 'success');
    }

    /**
     * Add an error notice.
     *
     * @param string $key Unique key for the notice.
     * @param string $message The message to display.
     */
    public static function add_error($key, $message)
    {
        self::add_notice($key, $message, 'error');
    }

    /**
     * Add a warning notice.
     *
     * @param string $key Unique key for the notice.
     * @param string $message The message to display.
     */
    public static function add_warning($key, $message)
    {
        self::add_notice($key, $message, 'warning');
    }

    /**
     * Add an info notice.
     *
     * @param string $key Unique key for the notice.
     * @param string $message The message to display.
     */
    public static function add_info($key, $message)
    {
        self::add_notice($key, $message, 'info');
    }
}