# email_to_post

email_to_post plugin

// //LOOP THROUGH EMAILS
// if ($emails) {
    //     foreach ($emails as $email_number) {
    //         $headerInfo = imap_headerinfo($email, $email_number); //get header info of the email
    //         $from = $headerInfo->fromaddress; //get the email address of the sender
    //         // var_dump($from);
// $subject = $headerInfo->subject; //get the subject of the email
    //         // var_dump($subject);
// $date = $headerInfo->date; //get the date of the email
    //         // var_dump($date);
// $message = imap_fetchbody($email, $email_number, 1.1); //The section 1.1 typically refers to the first part of a multipart message, often the plain text version of the email.
    //         // var_dump($message);
// //CREATE POST
// $post = array(
    //             'post_title' => $subject,
    //             'post_content' => $message,
    //             'post_date' => $date,
    //             'post_status' => 'publish',
    //             'post_author' => 1,
    //             'post_type' => 'post'
    //         );
    //         $post_id = wp_insert_post($post);
// //ADD POST META
// add_post_meta($post_id, 'email_from', $from);
    //         // MARK EMAIL AS READ
    //         imap_setflag_full($email, $email_number, "\Seen"); //Mark the email as read
// }
// }

logic for the message ( COMMENTS)

foreach ($emails as $email_number) {
    $headerInfo = imap_headerinfo($email, $email_number);
    $from = $headerInfo->fromaddress ?? 'Unknown Sender';
    $subject = $headerInfo->subject ?? 'No Subject';
    $date = date("Y-m-d H:i:s", strtotime($headerInfo->date ?? 'now'));
$in_reply_to = $headerInfo->in_reply_to ?? ''; // Get in-reply-to (original email's Message-ID)
$message_id = $headerInfo->message_id ?? ''; // Current email's Message-ID

    // Skip emails older than the last post
    if ($date <= $last_post_date) {
        continue;
    }

    // Fetch message body
    $message = imap_fetchbody($email, $email_number, 1);

    if (empty($message)) {
        $message = imap_fetchbody($email, $email_number, 1.1);
    }

    // Remove quoted reply sections and quoted lines
    $message = preg_replace('/(?:^On\s.*\sat\s.*wrote:.*|^>.*$)/m', '', $message);

    // Remove any leading/trailing whitespace or newlines
    $cleaned_message = trim($message);

    // Show the cleaned message (email content only)
    echo $cleaned_message . "\n";

}

// 3/10/2025

<?php
/*
 * Plugin Name:       Email To Post
 * Plugin URI:        https://example.com/plugins/the-basics/
 * Description:       Fetches emails and creates posts from them.
 * Version:           1.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Bishaal Rauniyar
 * Author URI:       https://github.com/bishalxrauniyar/email-to-post
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) exit; // Exit if accessed directly

function etp_menu()
{
    add_menu_page(
        'Email To Post',
        'Email To Post',
        'manage_options',
        'email-to-post',
        'etp_fetch_emails_callback',
        'dashicons-email-alt',
    );
}
add_action('admin_menu', 'etp_menu');

function etp_fetch_emails_callback()
{
    echo '<h1>Email To Post</h1>';
    // creating a button to fetch emails
    echo '<form method="post" action="">
    <input type="submit" name="fetch_emails" value="Fetch Emails" class="button button-primary">
    </form>';
    // if the button is clicked
    if (isset($_POST['fetch_emails']))
        etp_fetch_emails();
    // echo '<p>Emails fetched and posts created successfully.</p>';
    // creating a table to display the fetched emails
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<tr><th>From</th><th>Subject</th><th>Date</th><th>Message</th></tr>';


    // get all the posts of type 'post' by using get_posts() function which only 5 posts default so set it to -1 to get all the posts
    $posts = get_posts(array('post_type' => 'post', 'numberposts' => -1));
    foreach ($posts as $post) {
        echo '<tr>';
        echo '<td>' . get_post_meta($post->ID, 'email_from', true) . '</td>';
        echo '<td>' . $post->post_title . '</td>';
        echo '<td>' . $post->post_date . '</td>';
        echo '<td>' . $post->post_content . '</td>';
        echo '</tr>';
    }
    echo '</table>';
}

function clean_email_message($message)
{
    // First, decode the message
    $message = quoted_printable_decode($message);

    // Trim the message to remove unnecessary spaces
    $message = trim($message);

    // Step 1: Look for the "On <date> wrote:" and remove quoted content
    $message = preg_replace('/On\s.*\bwrote:[\s\S]+?(?=\n\s*--|$)/s', '', $message);

    // Step 2: Clean up any trailing quoted content (indicated by `>`)
    $message = preg_replace('/(^|\n)>.*$/s', '', $message);

    // Step 3: Ensure any signature starting with `--` is preserved
    if (preg_match('/(\R\s*--\s*\R*)/', $message, $matches)) {
        // Remove everything before the signature and preserve the signature
        $message = preg_replace('/[\s\S]+?(\R\s*--\s*\R*)/s', '$1', $message);
    }

    // Return the cleaned-up message
    return $message;
}

function etp_fetch_emails()
{
    $hostname = '{wplocatepress.com:993/imap/ssl}INBOX'; // email server
    $username = 'pipetest@wplocatepress.com'; // email
    $password = 'k342+11$c1_'; // password

    // CONNECT TO EMAIL
    $email = imap_open($hostname, $username, $password) or die('Cannot connect to email: ' . imap_last_error());

    // GET EMAILS
    $emails = imap_search($email, 'ALL');

    // Fetch the latest post date
    $args = array(
        'post_type'      => 'post',
        'orderby'        => 'post_date',
        'order'          => 'DESC',
        'posts_per_page' => 1,
        'fields'         => 'ids',
    );

    $query = new WP_Query($args);
    $last_post_date = '2000-01-01 00:00:00';

    if (!empty($query->posts)) {
        $last_post_date = get_the_date('Y-m-d H:i:s', $query->posts[0]);
    }

    wp_reset_postdata();

    if ($emails) {
        foreach ($emails as $email_number) {
            $headerInfo = imap_headerinfo($email, $email_number);
            $useremail = $headerInfo->from[0]->mailbox . '@' . $headerInfo->from[0]->host;
            $from = $headerInfo->fromaddress ?? 'Unknown Sender';
            $subject = $headerInfo->subject ?? 'No Subject';
            $date = date("Y-m-d H:i:s", strtotime($headerInfo->date ?? 'now'));
            $in_reply_to = $headerInfo->in_reply_to ?? ''; // In-reply-to field for replies
            $message_id = $headerInfo->message_id ?? ''; // Current email's Message-ID

            // Skip emails older than the last post
            if ($date <= $last_post_date) {
                continue;
            }

            // Fetch message body
            $message = imap_fetchbody($email, $email_number, 1);

            if (empty($message)) {
                $message = imap_fetchbody($email, $email_number, 1.1);
            }

            // Clean the email message
            $message = clean_email_message($message);

            // If it's a reply, find the parent post and add a comment
            if (!empty($in_reply_to)) {
                // Search for the parent post using the 'email_message_id' meta field
                $parent_query = new WP_Query(array(
                    'meta_key'   => 'email_message_id',
                    'meta_value' => $in_reply_to,
                    'post_type'  => 'post',
                    'posts_per_page' => 1,
                    'fields'     => 'ids',
                ));

                if (!empty($parent_query->posts)) {
                    $parent_post_id = $parent_query->posts[0];

                    // Insert the comment (reply)
                    if (!empty($message)) {
                        wp_insert_comment(array(
                            'comment_post_ID' => $parent_post_id,
                            'comment_author'  => $from,
                            'comment_content' => $message,
                            'comment_date'    => $date,
                            'comment_approved' => 1,
                        ));
                    }

                    continue; // Skip post creation since it's a reply
                }
            }

            // Check if the user exists, and create a new user if not
            $user = get_user_by('email', $useremail);
            $useremail = explode('@', $useremail)[0];
            if ($user) {
                $user_id = $user->ID;
            } else {
                $user_id = wp_insert_user(array(
                    'user_login' => $useremail,
                    'user_email' => $useremail,
                    'user_pass'  => wp_generate_password(),
                ));
            }

            // Create a new post if it's not a reply
            $post_id = wp_insert_post(array(
                'post_title'   => $subject,
                'post_content' => $message,
                'post_date'    => $date,
                'post_status'  => 'publish',
                'post_author'  => $user_id,
                'post_type'    => 'post'
            ));

            // Store the email's Message-ID for future replies
            if ($post_id && !empty($message_id)) {
                add_post_meta($post_id, 'email_message_id', $message_id);
            }

            if ($post_id) {
                add_post_meta($post_id, 'email_from', $from);
            }
        }
    }

    // CLOSE IMAP CONNECTION
    imap_close($email);
}



# daily log

Fixes:

Signature / with out signature email are displayed properly
username is extracted from the user email 
unwanted tail email and content is not displayed as required 

Issues:

Both emails sender and receiver are not been ale to make comments .
re-reply from the sender not parent ( post created user) are been created as post 
multiple fetch results into multiple comment ( same  Comment)

Working on:

To Remove The Duplicate Comments From The Posts.

Fix the issues .

@utsav Sir @djbaba Sir

# 3/10/2025
