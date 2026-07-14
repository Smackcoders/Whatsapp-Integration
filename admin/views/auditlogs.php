<?php

namespace Smackcoders\WN;

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class WhatsApp_Audit_Log_Table extends \WP_List_Table
{

    public function __construct()
    {
        parent::__construct(array(
            'singular' => 'log',
            'plural' => 'logs',
            'ajax' => false
        ));
    }

    public function get_columns()
    {
        return array(
            'cb' => '<input type="checkbox" />',
            'event_type' => __('Event Type', 'wp-notifier'),
            'recipient_phone' => __('Recipient Phone', 'wp-notifier'),
            'message' => __('Message', 'wp-notifier'),
            'sent_status' => __('Status', 'wp-notifier'),
            'response_message' => __('Reasons', 'wp-notifier'),
            'provider' => __('Provider', 'wp-notifier'),
            'created_at' => __('Date', 'wp-notifier')
        );
    }

    public function get_sortable_columns()
    {
        return array(
            'event_type' => array('event_type', true),
            'recipient_phone' => array('recipient_phone', false),
            'sent_status' => array('sent_status', true),
            'created_at' => array('created_at', true)
        );
    }

    public function get_bulk_actions()
    {
        return array(
            'bulk_delete' => __('Delete', 'wp-notifier')
        );
    }

    public function column_default($item, $column_name)
    {
        return $item[$column_name] ?? '';
    }

    public function column_cb($item)
    {
        return sprintf('<input type="checkbox" name="bulk_delete_ids[]" value="%s" />', esc_attr($item['id']));
    }

    public function prepare_items()
    {
        global $wpdb;

        $columns = $this->get_columns();
        $hidden = get_hidden_columns($this->screen);
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);

        if (!empty($_POST) && (!isset($_POST['filter_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['filter_nonce'])), 'filter_action'))) {
            wp_die(esc_html__('Security check failed. Please try again.', 'wp-notifier'));
        }

        $query = "SELECT id, event_type, recipient_phone, message, sent_status, response_message, provider, created_at FROM {$wpdb->prefix}whatsapp_notifications";


        $where_clauses = array();
        $args = array();

        if (!empty($_REQUEST['s'])) {
            $search_term = '%' . $wpdb->esc_like(sanitize_text_field(wp_unslash($_REQUEST['s']))) . '%';
            $where_clauses[] = "(event_type LIKE %s OR recipient_phone LIKE %s OR provider LIKE %s)";
            $args[] = $search_term;
            $args[] = $search_term;
            $args[] = $search_term;
        }

        if (!empty($_REQUEST['event_type'])) {
            $event_type = sanitize_text_field(wp_unslash($_REQUEST['event_type']));
            $where_clauses[] = "event_type = %s";
            $args[] = $event_type;
        }

        if (!empty($_REQUEST['start_date']) && !empty($_REQUEST['end_date'])) {
            $start_date = sanitize_text_field(wp_unslash($_REQUEST['start_date']));
            $end_date = sanitize_text_field(wp_unslash($_REQUEST['end_date']));

            if (strtotime($start_date) && strtotime($end_date)) {
                $where_clauses[] = "DATE(created_at) BETWEEN %s AND %s";
                $args[] = $start_date;
                $args[] = $end_date;
            }
        }

        if (!empty($where_clauses)) {
            $query .= " WHERE " . implode(' AND ', $where_clauses);
        }

        $orderby = sanitize_text_field(wp_unslash($_REQUEST['orderby'] ?? 'created_at'));
        $order = sanitize_text_field(wp_unslash($_REQUEST['order'] ?? 'DESC'));
        $query .= " ORDER BY $orderby $order";

        $count_query = "SELECT COUNT(*) FROM {$wpdb->prefix}whatsapp_notifications";
        if (!empty($where_clauses)) {
            $count_query .= " WHERE " . implode(' AND ', $where_clauses);
        }

        if (!empty($args)) {
            $prepared_count_query = $wpdb->prepare($count_query, $args); // phpcs:ignore
            $total_items = $wpdb->get_var($prepared_count_query); // phpcs:ignore
            $prepared_query = $wpdb->prepare($query, $args); // phpcs:ignore
        } else {
            $total_items = $wpdb->get_var($count_query); // phpcs:ignore
            $prepared_query = $query; // phpcs:ignore
        }

        $per_page = $this->get_items_per_page('whatsapp_logs_per_page', 20);
        $current_page = $this->get_pagenum();
        $offset = ($current_page - 1) * $per_page;

        $this->items = $wpdb->get_results($prepared_query . " LIMIT $offset, $per_page", ARRAY_A); // phpcs:ignore

        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page' => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ));
    }
}

function render_whatsapp_audit_log_page()
{
    // phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Sorry, you are not allowed to access this page.', 'wp-notifier'));
    }

    global $wpdb;

    // 1. Direct Deletion Handling (Handle early)
    if (isset($_POST['bulk_delete_ids']) && !empty($_POST['bulk_delete_ids'])) {
        // Verify nonce before processing bulk delete (security fix)
        if (!isset($_POST['bulk_delete_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['bulk_delete_nonce'])), 'bulk-delete-nonce')) {
            wp_die(esc_html__('Security check failed. Please try again.', 'wp-notifier'));
        }

        $action = '';
        if (isset($_POST['action'])) {
            $action = sanitize_text_field(wp_unslash($_POST['action']));
        } elseif (isset($_POST['action2'])) {
            $action = sanitize_text_field(wp_unslash($_POST['action2']));
        }

        if ($action === 'bulk_delete') {
            $ids = array_map('intval', (array) wp_unslash($_POST['bulk_delete_ids']));
            foreach ($ids as $id) {
                $wpdb->delete("{$wpdb->prefix}whatsapp_notifications", ['id' => $id], ['%d']); // phpcs:ignore
                $wpdb->delete("{$wpdb->prefix}whatsapp_error_logs", ['notification_id' => $id], ['%d']); // phpcs:ignore
            }
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html(count($ids)) . ' ' . esc_html__('logs deleted successfully.', 'wp-notifier') . '</p></div>';
        }
    }

    $event_types = $wpdb->get_col("SELECT DISTINCT event_type FROM {$wpdb->prefix}whatsapp_notifications ORDER BY event_type ASC"); // phpcs:ignore
    $list_table = new WhatsApp_Audit_Log_Table();
    $list_table->prepare_items();
    ?>
    

    <div class="wrap">
        <h1><?php echo esc_html__('WhatsApp Notifications Audit Log', 'wp-notifier'); ?></h1>

        <form method="post">
            <input type="hidden" name="page"
                value="<?php echo esc_attr(sanitize_text_field(wp_unslash($_REQUEST['page'] ?? ''))); ?>" />

            <?php wp_nonce_field('filter_action', 'filter_nonce'); ?>
            <?php wp_nonce_field('bulk-delete-nonce', 'bulk_delete_nonce'); ?>

            <div class="tablenav top">
                <div class="alignleft actions">
                    <select name="event_type">
                        <option value=""><?php esc_html_e('All Event Types', 'wp-notifier'); ?></option>
                        <?php foreach ($event_types as $event): ?>
                            <option value="<?php echo esc_attr($event); ?>" <?php selected(sanitize_text_field(wp_unslash($_REQUEST['event_type'] ?? '')), $event); ?>>
                                <?php echo esc_html($event); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label>
                        <span class="screen-reader-text"><?php esc_html_e('From:', 'wp-notifier'); ?></span>
                        <input type="date" name="start_date"
                            value="<?php echo esc_attr(sanitize_text_field(wp_unslash($_REQUEST['start_date'] ?? ''))); ?>">
                    </label>

                    <label>
                        <span class="screen-reader-text"><?php esc_html_e('To:', 'wp-notifier'); ?></span>
                        <input type="date" name="end_date"
                            value="<?php echo esc_attr(sanitize_text_field(wp_unslash($_REQUEST['end_date'] ?? ''))); ?>">
                    </label>

                    <?php submit_button(__('Filter', 'wp-notifier'), 'primary', 'filter_action', false); ?>

                    <?php if (!empty($_REQUEST['event_type']) || !empty($_REQUEST['start_date']) || !empty($_REQUEST['end_date'])): ?>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=whatsapp-audit-logs')); ?>"
                            class="button"><?php esc_html_e('Reset', 'wp-notifier'); ?></a>
                    <?php endif; ?>
                </div>
                <?php $list_table->search_box('Search', 'whatsapp-log-search'); ?>
            </div>

            <?php $list_table->display(); ?>
        </form>
    </div>

    <?php
    // phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
}