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
