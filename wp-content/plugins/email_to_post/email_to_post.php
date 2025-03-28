<?php
/*
 * Plugin Name:       Email To Post
 * Plugin URI:        https://github.com/bishalxrauniyar/
 * Description:       Fetches emails and creates posts from them, reply to the mail from the post comment section will send the email to the post author.
 * Version:           1.1
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Bishal Rauniyar
 * Author URI:        https://github.com/bishalxrauniyar/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       email-to-post
 */


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

// Callback function to fetch emails triggers when fetch emails button is clicked
// Fetches emails and displays them in a table
function etp_fetch_emails_callback()
{
    echo '<h1>Email To Post</h1>';
    echo '<p>Click the button below to fetch emails</p>';
    echo '<form method="post" action="">';
    wp_nonce_field('etp_fetch_emails_action', 'etp_fetch_emails_nonce'); // Add nonce field for security
    echo '<input type="submit" name="fetch_emails" value="Fetch Emails" class="button button-primary">';
    echo '</form>';
    // Check if the fetch emails button is clicked and nonce is valid
    if (isset($_POST['fetch_emails']) && check_admin_referer('etp_fetch_emails_action', 'etp_fetch_emails_nonce')) {
        etp_fetch_emails();
    }

    echo '<table class="wp-list-table widefat fixed striped ">';
    echo '<tr><th>From</th><th>Subject</th><th>Date</th><th>Message</th></tr>';

    $posts = get_posts(
        [
            'post_type' => 'post',
            'meta_key' => 'email_message_id',
            'numberposts' => -1 // Get all posts with email_message_id meta
        ]
    );
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
    // Email configuration
    $hostname = '{wplocatepress.com:993/imap/ssl}INBOX';
    $username = 'pipetest@wplocatepress.com';
    $password = 'k342+11$c1_';
    // Connect to email
    $email = imap_open($hostname, $username, $password) or die('Cannot connect to email: ' . imap_last_error());
    $emails = imap_search($email, 'ALL'); // Search for all emails

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
// imap cannot be used to send emails, so we will use wp_mail() function to send emails. email from must be pipetest@wplocatepress.com and the email to must be the post author email
// Post comments as email replies
function etp_reply_email($comment_id, $comment_approved)
{
    if ($comment_approved) {
        $comment = get_comment($comment_id); // Get the comment object
        $post = get_post($comment->comment_post_ID); // Get the post object
        $email_from = get_post_meta($post->ID, 'email_from', true); //  Get the email from the post meta
        $email_message_id = get_post_meta($post->ID, 'email_message_id', true); // Get the email message id from the post meta

        preg_match('/<(.*?)>/', $email_from, $matches); // Extract email from angle brackets
        $pure_email = $matches[1] ?? $email_from;       // Get email without angle brackets

        //validate email
        if (!filter_var($pure_email, FILTER_VALIDATE_EMAIL)) {
            error_log('Invalid sender email: ' . $pure_email);
            return;
        }

        $reply_to = $pure_email;
        $subject = 'Re: ' . $post->post_title;
        $comment_content = $comment->comment_content;
        $reply_message = "On " . date("Y-m-d H:i:s", strtotime($post->post_date)) . ", $email_from wrote:\n\n" . $comment_content;

        $headers = [
            'From: Email To Post <pipetest@wplocatepress.com>',
            'Reply-To: ' . $reply_to,
            'References: ' . $email_message_id,
            'In-Reply-To: ' . $email_message_id,
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8'
        ];

        $mail_sent = wp_mail($reply_to, $subject, $reply_message, $headers); // Send email

        if (!$mail_sent) {
            error_log('Email failed to send to ' . $reply_to); // Log error if email fails to send
        } else {
            error_log('Email successfully sent to ' . $reply_to); // Log success if email is sent
        }
    }
}
add_action('comment_post', 'etp_reply_email', 10, 2); // Hook into comment_post action which is triggered when a comment is posted
