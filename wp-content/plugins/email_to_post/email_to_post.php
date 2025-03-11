<?php
/*
 * Plugin Name:       Email To Post
 * Plugin URI:        https://example.com/plugins/the-basics/
 * Description:       Fetches emails and creates posts from them.
 * Version:           1.1
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Bishaal Rauniyar
 * Author URI:        https://github.com/bishalxrauniyar/email-to-post
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Add a menu item in the admin dashboard
function etp_menu()
{
    add_menu_page(
        'Email To Post',
        'Email To Post',
        'manage_options',
        'email-to-post',
        'etp_fetch_emails_callback',
        'dashicons-email-alt'
    );
}
add_action('admin_menu', 'etp_menu');

// Callback function to fetch emails
function etp_fetch_emails_callback()
{
    echo '<h1>Email To Post</h1>';
    echo '<form method="post" action="">
    <input type="submit" name="fetch_emails" value="Fetch Emails" class="button button-primary">
    </form>';

    if (isset($_POST['fetch_emails'])) {
        etp_fetch_emails();
    }

    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<tr><th>From</th><th>Subject</th><th>Date</th><th>Message</th></tr>';

    $posts = get_posts(array('post_type' => 'post', 'numberposts' => -1));
    foreach ($posts as $post) {
        echo '<tr>';
        echo '<td>' . esc_html(get_post_meta($post->ID, 'email_from', true)) . '</td>';
        echo '<td>' . esc_html($post->post_title) . '</td>';
        echo '<td>' . esc_html($post->post_date) . '</td>';
        echo '<td>' . esc_html($post->post_content) . '</td>';
        echo '</tr>';
    }
    echo '</table>';
}

// Function to clean the email message
function clean_email_message($message)
{
    $message = quoted_printable_decode($message);
    $message = preg_replace('/On\s.*\bwrote:[\s\S]+?(?=\n\s*--|$)/s', '', $message);
    $message = trim($message);
    return preg_replace('/(^|\n)>.*$/m', '', $message);
}

// Function to fetch emails
function etp_fetch_emails()
{
    $hostname = '{wplocatepress.com:993/imap/ssl}INBOX';
    $username = 'pipetest@wplocatepress.com';
    $password = 'k342+11$c1_';

    $email = imap_open($hostname, $username, $password) or die('Cannot connect to email: ' . imap_last_error());
    $emails = imap_search($email, 'ALL');

    if ($emails) {
        foreach ($emails as $email_number) {
            $headerInfo = imap_headerinfo($email, $email_number);
            $from = $headerInfo->fromaddress ?? 'Unknown Sender';
            $subject = $headerInfo->subject ?? 'No Subject';
            $date = date("Y-m-d H:i:s", strtotime($headerInfo->date ?? 'now'));
            $message_id = $headerInfo->message_id ?? ''; // Unique identifier for the email
            $in_reply_to = $headerInfo->in_reply_to ?? ''; // Message ID of the email being replied to
            $references = $headerInfo->references ?? ''; // Message IDs of previous emails in the thread

            // Debugging: Check headers
            // var_dump($in_reply_to);
            // var_dump($references);
            // var_dump($message_id);
            // var_dump($from);


            $message = imap_fetchbody($email, $email_number, 1.1);
            if (empty($message)) {
                $message = imap_fetchbody($email, $email_number, 1);
            }
            $message = clean_email_message($message);

            if (empty($message_id)) continue;

            // Prevent duplicates
            if (get_posts(['meta_key' => 'email_message_id', 'meta_value' => $message_id])) {
                continue;
            }

            $original_post_id = null;
            if (!empty($references)) {
                $reference_ids = explode(' ', trim($references));
                $first_message_id = reset($reference_ids);
            } elseif (!empty($in_reply_to)) {
                $first_message_id = $in_reply_to;
            } else {
                $first_message_id = null;
            }

            // Find the original post 
            if (!empty($first_message_id)) {
                $original_query = new WP_Query([
                    'meta_key' => 'email_message_id',
                    'meta_value' => $first_message_id,
                    'post_type' => 'post',
                    'posts_per_page' => 1,
                    'fields' => 'ids',
                ]);
                if (!empty($original_query->posts)) {
                    $original_post_id = $original_query->posts[0];
                }
            }

            if ($original_post_id) {
                // Prevent duplicate comments
                $existing_comments = get_comments([
                    'post_id' => $original_post_id,
                    'meta_key' => 'email_message_id',
                    'meta_value' => $message_id,
                ]);

                $is_duplicate = false;
                foreach ($existing_comments as $comment) {
                    if (trim($comment->comment_content) == trim($message)) {
                        $is_duplicate = true;
                        break;
                    }
                }

                if (!$is_duplicate) {
                    // Insert comment
                    $comment_id = wp_insert_comment([
                        'comment_post_ID' => $original_post_id,
                        'comment_author' => $from,
                        'comment_content' => $message,
                        'comment_date' => $date,
                        'comment_approved' => 1,
                    ]);

                    // Add meta separately
                    if (!is_wp_error($comment_id) && $comment_id) {
                        add_comment_meta($comment_id, 'email_message_id', $message_id, true);
                    }
                }

                continue; // Skip creating a new post
            }


            // If no post exists, create a new one (for first-time emails)
            $useremail = $headerInfo->from[0]->mailbox . '@' . $headerInfo->from[0]->host;
            $user = get_user_by('email', $useremail);
            if (!$user) {
                $user_id = wp_insert_user(array(
                    'user_login' => explode('@', $useremail)[0],
                    'user_email' => $useremail,
                    'user_pass'  => wp_generate_password(),
                ));
            } else {
                $user_id = $user->ID;
            }

            // Create new post
            $post_id = wp_insert_post([
                'post_title' => $subject,
                'post_content' => $message,
                'post_date' => $date,
                'post_status' => 'publish',
                'post_author' => $user_id,
                'post_type' => 'post'
            ]);

            if (!is_wp_error($post_id)) {
                add_post_meta($post_id, 'email_message_id', $message_id);
                add_post_meta($post_id, 'email_from', $from);
            }
        }
    }


    imap_close($email);
}

// adding a feature that allows the user to reply to the email from the post original mail from email pipetest@wplocatepress.com through comment section of the post
function etp_reply_email($comment_id, $comment_approved, $commentdata)
{
    if ($comment_approved) {
        $comment = get_comment($comment_id);
        $post = get_post($comment->comment_post_ID);
        $email_from = get_post_meta($post->ID, 'email_from', true);
        $email_message_id = get_post_meta($post->ID, 'email_message_id', true);

        if ($email_from && $email_message_id) {
            $hostname = '{wplocatepress.com:993/imap/ssl}INBOX';
            $username = 'pipetest@wplocatepress.com';
            $password = 'k342+11$c1_';
            $email = imap_open($hostname, $username, $password) or die('Cannot connect to email: ' . imap_last_error());
            $email_message_id = trim($email_message_id); // Ensure no extra spaces
            $search_result = imap_search($email, 'HEADER Message-ID "' . $email_message_id . '"');

            if ($search_result && is_array($search_result)) {
                $message_num = $search_result[0]; // Get the first matching email

                $headerInfo = imap_headerinfo($email, $message_num);
                $reply_to = $headerInfo->reply_toaddress ?? $email_from;
                $subject = 'Re: ' . ($headerInfo->subject ?? $post->post_title);
                $message_id = $headerInfo->message_id ?? '';
                $in_reply_to = $headerInfo->in_reply_to ?? '';
                $references = $headerInfo->references ?? '';
            } else {
                error_log('Email with Message-ID ' . $email_message_id . ' not found.');
                return;
            }


            $message = $commentdata['comment_content'];
            $message = clean_email_message($message);

            $reply_message = "On " . date("Y-m-d H:i:s") . ", $email_from wrote:\n\n" . $message;

            $reply_headers = [
                'From' => $username,
                'Reply-To' => $reply_to,
                'In-Reply-To' => $message_id,
                'References' => $references . ' ' . $message_id,
            ];

            $reply_message = quoted_printable_encode($reply_message);
            $reply_message = wordwrap($reply_message, 70, "\r\n");

            imap_append($email, $hostname, "From: $username\r\nSubject: $subject\r\n$message_id\r\n$reply_message", $reply_headers);

            imap_close($email);
        }
    }
}
add_action('comment_post', 'etp_reply_email', 10, 3);
