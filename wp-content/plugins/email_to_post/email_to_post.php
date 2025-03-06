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
    echo '<p>Emails fetched and posts created successfully.</p>';
    // creating a table to display the fetched emails
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<tr><th>From</th><th>Subject</th><th>Date</th><th>Message</th></tr>';
    // get all the posts of type 'post'
    $posts = get_posts(array('post_type' => 'post'));
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

    $hostname = '{wplocatepress.com:993/imap/ssl}INBOX';
    $username = 'pipetest@wplocatepress.com';
    $password = '*1j?4(+l2#:x';


    //CONNECT TO EMAIL
    $email = imap_open($hostname, $username, $password) or die('Cannot connect to email: ' . imap_last_error());
    //GET EMAILS
    $emails = imap_search($email, 'UNSEEN '); //for unseen emails

    //LOOP THROUGH EMAILS
    if ($email) {
        foreach ($email as $emails) {
            $headerInfo = imap_headerinfo($email, $emails); //get header info of the email
            $from = $headerInfo->fromaddress; //get the email address of the sender
            $subject = $headerInfo->subject; //get the subject of the email
            $date = $headerInfo->date; //get the date of the email
            $message = imap_fetchbody($email, $emails, 1.1); //The section 1.1 typically refers to the first part of a multipart message, often the plain text version of the email.
            //CREATE POST
            $post = array(
                'post_title' => $subject,
                'post_content' => $message,
                'post_date' => $date,
                'post_status' => 'publish',
                'post_author' => 1,
                'post_type' => 'post'
            );
            $post_id = wp_insert_post($post);
            //ADD POST META
            add_post_meta($post_id, 'email_from', $from);
            //MARK EMAIL AS READ
            imap_setflag_full($email, $emails, "\Seen"); //Mark the email as read
        }
    }
}


//