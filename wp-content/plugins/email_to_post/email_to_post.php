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


function etp_fetch_emails()
{
    $hostname = '{wplocatepress.com:993/imap/ssl}INBOX'; // email server
    $username = 'pipetest@wplocatepress.com'; // email
    $password = 'k342+11$c1_'; // password

    // CONNECT TO EMAIL
    $email = imap_open($hostname, $username, $password) or die('Cannot connect to email: ' . imap_last_error());

    // GET EMAILS
    $emails = imap_search($email, 'ALL');

    // Fetch last post date
    $args = array(
        'post_type'      => 'post',
        'orderby'        => 'ID',
        'order'          => 'DESC',
        'posts_per_page' => 1,
    );
    $query = new WP_Query($args);
    $last_post_date = '2000-01-01 00:00:00';

    if ($query->have_posts()) {
        $last_post_date = get_the_date('Y-m-d H:i:s', $query->posts[0]->ID);
    }
    wp_reset_postdata();

    if ($emails) {
        foreach ($emails as $email_number) {
            $headerInfo = imap_headerinfo($email, $email_number);
            $from = $headerInfo->fromaddress ?? 'Unknown Sender';
            $subject = $headerInfo->subject ?? 'No Subject';
            $date = date("Y-m-d H:i:s", strtotime($headerInfo->date ?? 'now'));
            $in_reply_to = $headerInfo->in_reply_to ?? '';

            if ($date <= $last_post_date) {
                continue; // Skip emails older than the last post
            }
            if (empty($messsage)) {
                $message = imap_fetchbody($email, $email_number, 1);
            } elseif (empty($message)) {
                $message = imap_fetchbody($email, $email_number, 1.1);
            }

            if ($in_reply_to) {
                $args = array(
                    'post_type' => 'post',
                    'meta_query' => array(
                        array(
                            'key' => 'email_from',
                            'value' => $from,
                        ),
                    ),
                );
                $query = new WP_Query($args);
                if ($query->have_posts()) {
                    $query->the_post();
                    $post_id = $query->posts[0]->ID;
                    $post_content = get_post_field('post_content', $post_id);
                    $post_content .= '<hr>' . $message;
                    wp_update_post(array(
                        'ID' => $post_id,
                        'post_content' => $post_content,
                    ));
                    // var_dump($post_id);
                    var_dump($post_content);
                }
                wp_reset_postdata();
            }
            var_dump($in_reply_to);

            // CREATE POST  
            $post_id = wp_insert_post(array(
                'post_title'   => $subject,
                'post_content' => $message,
                'post_date'    => $date,
                'post_status'  => 'publish',
                'post_author'  => 1,
                'post_type'    => 'post'
            ));

            if ($post_id) {
                add_post_meta($post_id, 'email_from', $from);
            }
        }
    }

    // CLOSE IMAP CONNECTION
    imap_close($email);
}
//end of 3/6/2025