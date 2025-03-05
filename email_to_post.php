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