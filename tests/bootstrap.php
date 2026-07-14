<?php
/**
 * PHPUnit bootstrap for WP Notifier plugin tests.
 *
 * Loads plugin classes without a full WordPress environment by defining
 * lightweight stubs for the WordPress functions and globals the classes depend on.
 */

if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', '/tmp/wp/' );
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- test stubs must mirror WP function names exactly

// ---------------------------------------------------------------------------
// 1. WordPress constants & globals
// ---------------------------------------------------------------------------
define( 'WP_NOTIFIER_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
define( 'SECURE_AUTH_KEY', 'test-secret-key-for-phpunit-runs-only' );

// Minimal $wpdb stub (global used by several classes)
if ( ! isset( $GLOBALS['wpdb'] ) ) {
    $GLOBALS['wpdb'] = new class {
        public string $prefix = 'wp_';
        public int $insert_id = 1;
        public int $rows_affected = 0;

        /** @var array<array<mixed>> Rows returned by next get_results call */
        public array $next_results = [];

        /** @var mixed Value returned by next get_var call */
        public mixed $next_var = null;

        /** @var array<mixed> Captured insert/update calls for assertion */
        public array $inserts = [];
        public array $updates = [];
        public array $queries = [];

        public function prepare( string $sql, ...$args ): string {
            // Very simplified: just substitute %s / %d in order for tests
            foreach ( $args as $arg ) {
                $pos = strpos( $sql, '%' );
                if ( $pos !== false ) {
                    $sql = substr_replace( $sql, (string) $arg, $pos, 2 );
                }
            }
            return $sql;
        }

        public function get_var( string $sql ): mixed {
            return $this->next_var;
        }

        public function get_results( string $sql, $output = OBJECT ): array {
            return $this->next_results;
        }

        public function insert( string $table, array $data, $format = null ): int|false {
            $this->inserts[] = [ 'table' => $table, 'data' => $data ];
            $this->insert_id++;
            return 1;
        }

        public function update( string $table, array $data, array $where, $format = null, $where_format = null ): int|false {
            $this->updates[] = [ 'table' => $table, 'data' => $data, 'where' => $where ];
            $this->rows_affected = 1;
            return 1;
        }

        public function query( string $sql ): int|bool {
            $this->queries[] = $sql;
            return true;
        }
    };
}

// ---------------------------------------------------------------------------
// 2. WordPress function stubs
// ---------------------------------------------------------------------------
if ( ! function_exists( 'add_action' ) ) {
    function add_action( string $hook, callable $callback, int $priority = 10, int $args = 1 ): void {}
}

if ( ! function_exists( 'add_filter' ) ) {
    function add_filter( string $hook, callable $callback, int $priority = 10, int $args = 1 ): bool { return true; }
}

if ( ! function_exists( 'current_time' ) ) {
    function current_time( string $type ): string {
        return ( $type === 'mysql' ) ? gmdate( 'Y-m-d H:i:s' ) : (string) time();
    }
}

if ( ! function_exists( 'wp_json_encode' ) ) {
    function wp_json_encode( mixed $data ): string|false {
        return json_encode( $data );
    }
}

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
    function wp_strip_all_tags( string $str, bool $remove_breaks = false ): string {
        $str = preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', $str );
        $str = strip_tags( $str ); // phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags -- this IS the wp_strip_all_tags stub; no alternative available
        if ( $remove_breaks ) {
            $str = preg_replace( '/[\r\n\t ]+/', ' ', $str );
        }
        return trim( $str );
    }
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
    function sanitize_text_field( string $str ): string {
        return wp_strip_all_tags( trim( $str ) );
    }
}

if ( ! function_exists( 'sanitize_textarea_field' ) ) {
    function sanitize_textarea_field( string $str ): string {
        return implode( "\n", array_map( 'sanitize_text_field', explode( "\n", $str ) ) );
    }
}

if ( ! function_exists( 'esc_html' ) ) {
    function esc_html( string $text ): string {
        return htmlspecialchars( $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
    }
}

if ( ! function_exists( 'esc_attr' ) ) {
    function esc_attr( string $text ): string {
        return htmlspecialchars( $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
    }
}

if ( ! function_exists( 'wp_verify_nonce' ) ) {
    function wp_verify_nonce( string $nonce, string $action ): int|false {
        // For tests: 'valid-nonce' passes, anything else fails
        return ( $nonce === 'valid-nonce' ) ? 1 : false;
    }
}

if ( ! function_exists( 'wp_create_nonce' ) ) {
    function wp_create_nonce( string $action ): string {
        return 'valid-nonce';
    }
}

if ( ! function_exists( '__' ) ) {
    function __( string $text, string $domain = 'default' ): string {
        return $text;
    }
}

if ( ! function_exists( 'sprintf' ) ) {
    // sprintf is a PHP builtin — just ensure it's available (it always is).
}

if ( ! function_exists( 'get_user_meta' ) ) {
    function get_user_meta( int $user_id, string $key, bool $single = false ): mixed {
        return '';
    }
}

if ( ! function_exists( 'update_user_meta' ) ) {
    function update_user_meta( int $user_id, string $key, mixed $value ): int|bool {
        return true;
    }
}

if ( ! function_exists( 'delete_user_meta' ) ) {
    function delete_user_meta( int $user_id, string $key ): bool {
        return true;
    }
}

if ( ! function_exists( 'get_post_meta' ) ) {
    function get_post_meta( int $post_id, string $key, bool $single = false ): mixed {
        return '';
    }
}

if ( ! function_exists( 'update_post_meta' ) ) {
    function update_post_meta( int $post_id, string $key, mixed $value ): int|bool {
        return true;
    }
}

if ( ! function_exists( 'wp_next_scheduled' ) ) {
    function wp_next_scheduled( string $hook, array $args = [] ): int|false {
        return false;
    }
}

if ( ! function_exists( 'wp_schedule_event' ) ) {
    function wp_schedule_event( int $timestamp, string $recurrence, string $hook, array $args = [] ): bool {
        return true;
    }
}

if ( ! function_exists( 'get_bloginfo' ) ) {
    function get_bloginfo( string $show = '' ): string {
        return 'Test Site';
    }
}

if ( ! function_exists( 'admin_url' ) ) {
    function admin_url( string $path = '' ): string {
        return 'http://example.com/wp-admin/' . ltrim( $path, '/' );
    }
}

if ( ! function_exists( 'get_option' ) ) {
    function get_option( string $option, mixed $default = false ): mixed {
        return $default;
    }
}

if ( ! function_exists( 'wc_get_order' ) ) {
    function wc_get_order( int $order_id ): mixed {
        return false;
    }
}

// OBJECT constant used by wpdb
if ( ! defined( 'OBJECT' ) ) {
    define( 'OBJECT', 'OBJECT' );
}

// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound

// ---------------------------------------------------------------------------
// 3. Load plugin classes (skipping WooCommerce-specific ones that need full WP)
// ---------------------------------------------------------------------------
require_once WP_NOTIFIER_PLUGIN_DIR . 'includes/class-wn-security.php';
require_once WP_NOTIFIER_PLUGIN_DIR . 'includes/class-wn-phone-validator.php';
require_once WP_NOTIFIER_PLUGIN_DIR . 'includes/class-wn-logger.php';
require_once WP_NOTIFIER_PLUGIN_DIR . 'includes/class-wn-bulk-dispatcher.php';
