<?php
/**
 * Logger and Debug Trait
 *
 * Combines logging and debugging functionalities.
 *
 * @package  Skydropx\Helper
 */
namespace Skydropx\Helper;

trait Logger_trait {
    private static $logger;
    private static $SOURCE = 'Skydropx';

    /**
     * Inits our logger singleton
     *
     * @return void
     */
    public static function init() {
        if ( function_exists( 'wc_get_logger' ) && ! isset( self::$logger ) ) {
            self::$logger = wc_get_logger();
        }
    }

    /**
     * Ensure logger is initialized before use
     *
     * @return void
     */
    private static function ensure_logger() {
        if ( ! isset( self::$logger ) ) {
            self::init();  // Initialize the logger if not already done
        }
    }

    /**
     * Check if logging is enabled
     *
     * @return bool
     */
    private static function has_logging_enabled() {
        // Adjust logic based on WP_DEBUG and environment
        // Enable logging if WP_DEBUG is true or if environment is not 'production'
        return ( defined('WP_DEBUG') && WP_DEBUG ) || ( defined('WP_ENV') && WP_ENV !== 'production' );
    }

    /**
     * Logs an info message
     *
     * @param mixed $msg Message to log.
     * @return void
     */
    public static function log_info( $msg ) {
        self::ensure_logger();
        if ( self::$logger) {
            self::$logger->info( wc_print_r( $msg, true ), array( 'source' => self::$SOURCE ) );
        }
    }

    /**
     * Logs an error message
     *
     * @param mixed $msg Message to log.
     * @return void
     */
    public static function log_error( $msg ) {
        self::ensure_logger();
        if ( self::$logger ) {
            self::$logger->error( wc_print_r( $msg, true ), array( 'source' => self::$SOURCE ) );
        }
    }

    /**
     * Logs a warning message
     *
     * @param mixed $msg Message to log.
     * @return void
     */
    public static function log_warning( $msg ) {
        self::ensure_logger();
        if ( self::$logger && self::has_logging_enabled() ) {
            self::$logger->warning( wc_print_r( $msg, true ), array( 'source' => self::$SOURCE ) );
        }
    }

    /**
     * Logs a debug message
     *
     * @param mixed $msg Message to log.
     * @return void
     */
    public static function log_debug( $msg ) {
        self::ensure_logger();
        if ( self::$logger && self::has_logging_enabled() ) {
            self::$logger->debug( wc_print_r( $msg, true ), array( 'source' => self::$SOURCE ) );
        }
    }

    /**
     * Logs the provided data.
     *
     * @param mixed $log Data to log.
     */
    public static function log( $log ) {
        if ( self::has_logging_enabled() ) {
            if ( is_array( $log ) || is_object( $log ) ) {
                self::log_debug( wp_json_encode( $log, JSON_UNESCAPED_UNICODE ) );
            } else {
                self::log_debug( $log );
            }
        }
    }

    /**
     * Handle errors using WP_Error.
     *
     * @param string $error_message Error message.
     * @param string $error_code Optional. Error code.
     */
    public static function handle_error( $error_message, $error_code = 'skydropx_error' ) {
        $error = new \WP_Error( $error_code, esc_html( $error_message ) );
        self::log_error( $error_message );
        return $error;
    }
}