##Email to Post Plugin
The Email to Post Plugin is a WordPress plugin that enables users to create new posts on their WordPress site by simply sending an email. This functionality allows for convenient content creation, especially when access to the WordPress admin dashboard is limited.

#Features
Post via Email: Create new posts by sending an email to a specified address.
Customizable Settings: Configure email parameters such as subject prefixes to categorize posts.
Attachment Handling: Automatically include attachments from emails into the post content.
Installation
Download the Plugin:

Clone or download the plugin files from the GitHub repository.
Upload to WordPress:

Navigate to the WordPress admin dashboard.
Go to Plugins > Add New > Upload Plugin.
Select the downloaded plugin ZIP file and click Install Now.
Activate the Plugin:

After installation, click Activate Plugin to enable its functionality.
Configuration
Access Settings:

In the WordPress admin dashboard, navigate to Settings > Email to Post.
Set Email Parameters:

Mail Server: Enter the mail server address (e.g., mail.example.com).
Port: Specify the port number ( 993 for IMAP).
Username: Provide the email account username.
Password: Enter the email account password.
Define Post Settings:

Default Category: Select the category for posts created via email.
Post Status: Choose the default status (Draft, Pending, or Published).
Author: Assign a default author for these posts.
Save Changes:

Click Save Changes to apply the configurations.
Usage
Compose an Email:

Use the configured email account to compose a new email.
Subject Line: The email subject will become the post title.
Email Body: The content of the email will be the post content.
Send the Email:

Send the email to the address monitored by the plugin.
Automatic Posting:

The plugin will check the email account at regular intervals and publish new posts based on received emails.
Troubleshooting
Emails Not Processing:

Ensure that the email account credentials are correct.
Verify that the mail server settings are accurate.
Check for any conflicts with other plugins.
Attachments Not Appearing:

Confirm that the attachments are in supported formats (e.g., images, PDFs).
Ensure that the wp-content/uploads directory is writable.
Support
For support and feature requests, please visit the GitHub issues page.

Contributing
Contributions are welcome! Feel free to fork the repository and submit pull requests.

License
This plugin is licensed under the MIT License.
