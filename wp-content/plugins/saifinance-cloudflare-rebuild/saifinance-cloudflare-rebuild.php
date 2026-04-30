<?php
/**
 * Plugin Name: SaiFinance Cloudflare Rebuild
 * Description: Batches WordPress content changes and triggers a single Cloudflare Pages rebuild after a short delay.
 * Version: 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/*
|--------------------------------------------------------------------------
| CONFIG
|--------------------------------------------------------------------------
|
| Add these in wp-config.php:
|
| define('SF_CF_HOOK_STAGING', '');
| define('SF_CF_HOOK_PRODUCTION', 'YOUR_MAIN_BRANCH_DEPLOY_HOOK_URL');
|
| Since your staging.saifinance.com.au is currently running from main branch,
| only SF_CF_HOOK_PRODUCTION is needed right now.
|
*/

/**
 * Delay before actual rebuild runs.
 * 300 = 5 minutes
 */
if (!defined('SF_CF_REBUILD_DELAY')) {
    define('SF_CF_REBUILD_DELAY', 300);
}

/**
 * Cron event name
 */
define('SF_CF_REBUILD_EVENT', 'sf_cf_process_scheduled_rebuild');

/**
 * Option keys
 */
define('SF_CF_PENDING_OPTION', 'sf_cf_rebuild_pending');
define('SF_CF_LAST_REASON_OPTION', 'sf_cf_last_reason');
define('SF_CF_LAST_POST_OPTION', 'sf_cf_last_post_id');
define('SF_CF_LAST_TRIGGERED_OPTION', 'sf_cf_last_triggered_at');

/**
 * Basic logger
 */
function sf_cf_log($message) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('[SF_CF] ' . $message);
    }
}

/**
 * Send POST to Cloudflare deploy hook
 */
function sf_cf_post_json($url, $payload = []) {
    if (empty($url)) {
        sf_cf_log('Hook URL is empty, skipping.');
        return false;
    }

    sf_cf_log('Sending rebuild request to: ' . $url);
    sf_cf_log('Payload: ' . wp_json_encode($payload));

    $response = wp_remote_post($url, [
        'timeout' => 20,
        'headers' => [
            'Content-Type' => 'application/json',
        ],
        'body' => wp_json_encode($payload),
    ]);

    if (is_wp_error($response)) {
        sf_cf_log('Request failed: ' . $response->get_error_message());
        return false;
    }

    $code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);

    sf_cf_log('Response code: ' . $code);
    sf_cf_log('Response body: ' . $body);

    return ($code >= 200 && $code < 300);
}

/**
 * Actually trigger Cloudflare rebuild
 */
function sf_cf_trigger_rebuild_now($reason = 'scheduled_batch', $post_id = 0) {
    $payload = [
        'reason'  => $reason,
        'post_id' => $post_id,
        'time'    => current_time('mysql'),
        'site'    => home_url(),
    ];

    $success = false;

    // Since your staging domain is mapped to main branch, this is the main one that matters.
    if (defined('SF_CF_HOOK_PRODUCTION') && SF_CF_HOOK_PRODUCTION) {
        $result = sf_cf_post_json(SF_CF_HOOK_PRODUCTION, $payload);
        if ($result) {
            $success = true;
        }
    }

    // Optional, only if you want separate staging hook later.
    if (defined('SF_CF_HOOK_STAGING') && SF_CF_HOOK_STAGING) {
        $result = sf_cf_post_json(SF_CF_HOOK_STAGING, $payload);
        if ($result) {
            $success = true;
        }
    }

    if ($success) {
        update_option(SF_CF_LAST_TRIGGERED_OPTION, time(), false);
        sf_cf_log('Cloudflare rebuild triggered successfully.');
    } else {
        sf_cf_log('Cloudflare rebuild trigger failed.');
    }

    return $success;
}

/**
 * Mark rebuild as pending and schedule a single cron event
 */
function sf_cf_queue_rebuild($reason = 'content_changed', $post_id = 0) {
    update_option(SF_CF_PENDING_OPTION, 1, false);
    update_option(SF_CF_LAST_REASON_OPTION, $reason, false);
    update_option(SF_CF_LAST_POST_OPTION, (int) $post_id, false);

    // If already scheduled, don't schedule another one
    if (!wp_next_scheduled(SF_CF_REBUILD_EVENT)) {
        wp_schedule_single_event(time() + SF_CF_REBUILD_DELAY, SF_CF_REBUILD_EVENT);
        sf_cf_log('Scheduled rebuild in ' . SF_CF_REBUILD_DELAY . ' seconds.');
    } else {
        sf_cf_log('Rebuild already scheduled, batching this change.');
    }
}

/**
 * Process the scheduled rebuild
 */
function sf_cf_process_scheduled_rebuild() {
    $pending = (int) get_option(SF_CF_PENDING_OPTION, 0);

    if (!$pending) {
        sf_cf_log('No pending rebuild found.');
        return;
    }

    $reason  = get_option(SF_CF_LAST_REASON_OPTION, 'scheduled_batch');
    $post_id = (int) get_option(SF_CF_LAST_POST_OPTION, 0);

    sf_cf_log('Processing scheduled rebuild.');

    $success = sf_cf_trigger_rebuild_now($reason, $post_id);

    if ($success) {
        delete_option(SF_CF_PENDING_OPTION);
        delete_option(SF_CF_LAST_REASON_OPTION);
        delete_option(SF_CF_LAST_POST_OPTION);
        sf_cf_log('Pending rebuild cleared.');
    } else {
        // Retry once after delay if failed
        if (!wp_next_scheduled(SF_CF_REBUILD_EVENT)) {
            wp_schedule_single_event(time() + SF_CF_REBUILD_DELAY, SF_CF_REBUILD_EVENT);
            sf_cf_log('Rebuild failed, retry scheduled.');
        }
    }
}
add_action(SF_CF_REBUILD_EVENT, 'sf_cf_process_scheduled_rebuild');

/**
 * Create / update published blog posts
 */
add_action('save_post_post', function ($post_id, $post, $update) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (wp_is_post_revision($post_id)) return;
    if (!$post) return;
    if ($post->post_type !== 'post') return;
    if ($post->post_status !== 'publish') return;

    sf_cf_queue_rebuild($update ? 'post_updated' : 'post_created', $post_id);
}, 20, 3);

/**
 * Publish/unpublish transitions
 */
add_action('transition_post_status', function ($new_status, $old_status, $post) {
    if (!$post) return;
    if ($post->post_type !== 'post') return;
    if ($new_status === $old_status) return;

    // Draft -> Publish
    if ($new_status === 'publish' && $old_status !== 'publish') {
        sf_cf_queue_rebuild('post_published', $post->ID);
        return;
    }

    // Publish -> Draft/Trash/Private
    if ($old_status === 'publish' && $new_status !== 'publish') {
        sf_cf_queue_rebuild('post_unpublished', $post->ID);
        return;
    }
}, 20, 3);

/**
 * Permanent delete
 */
add_action('before_delete_post', function ($post_id, $post) {
    if (!$post) return;
    if ($post->post_type !== 'post') return;

    sf_cf_queue_rebuild('post_deleted', $post_id);
}, 20, 2);

/**
 * Clear scheduled event on plugin deactivation
 */
function sf_cf_deactivate_plugin() {
    $timestamp = wp_next_scheduled(SF_CF_REBUILD_EVENT);
    if ($timestamp) {
        wp_unschedule_event($timestamp, SF_CF_REBUILD_EVENT);
    }
}
register_deactivation_hook(__FILE__, 'sf_cf_deactivate_plugin');