<?php

if (!defined('WPINC')) {
    die;
}

/**
 * Handles automated email notifications.
 */
class SENDISES_Email_Automation {

    /**
     * Sends notification when a new post is published.
     *
     * @param int $post_id The ID of the post being published.
     * @param WP_Post $post The post object.
     */
    public static function send_notification_on_publish($post_id, $post) {
        try {
            // Ensure this only runs for new posts of type 'post'
            if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id) || $post->post_status !== 'publish' || $post->post_type !== 'post') {
                return;
            }

            // Check if this is the first time the post is published
            $post_publish_date = get_post_time('Y-m-d H:i:s', true, $post_id);
            $post_modified_date = get_post_modified_time('Y-m-d H:i:s', true, $post_id);
            // Compare dates within a small buffer to account for microseconds
            if (strtotime($post_modified_date) - strtotime($post_publish_date) > 5) {
                // This is an update to an already published post, not a new one.
                return;
            }

            // REQUIREMENT: Only send for posts in the 'weekly-update' category.
            if (!has_term('weekly-update', 'category', $post)) {
                return; // Exit if the post is not in the specified category.
            }

            $options = get_option('sendises_settings');
            $target_role = isset($options['notification_user_role']) ? $options['notification_user_role'] : '';

            if (empty($target_role)) {
                return; // No user role configured for notifications
            }
            
            $users = get_users(['role' => $target_role]);
            if (empty($users)) {
                return;
            }

            // REQUIREMENT: Subject should be the post title.
            $subject = $post->post_title;

            // Get the full post content, with formatting and shortcodes applied.
            $post_content = apply_filters('the_content', $post->post_content);
            $post_content = str_replace(']]>', ']]&gt;', $post_content);

            global $wpdb;
            $table_name = $wpdb->prefix . 'sendises_notifications';

            $sender_email = isset($options['ses_from_email']) ? $options['ses_from_email'] : get_option('admin_email');
            $sender_name = "אולפנת צביה מעלה אדומים";
            $sender_address = "דרך קדם 30 מעלה אדומים";
            $post_permalink = get_permalink($post_id);

            foreach ($users as $user) {
                $recipient_email = $user->user_email;

                // Build the full HTML email template
                $message = <<<HTML
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$subject}</title>
    <style type="text/css">
        /* Using @font-face for better client support, especially on mobile */
        @media screen {
            @font-face {
                font-family: 'Heebo';
                font-style: normal;
                font-weight: 400;
                src: url(https://fonts.gstatic.com/s/heebo/v22/NGS6v5_NC0k9P9H0TbF_ew.woff2) format('woff2');
                unicode-range: U+0590-05FF, U+200C-2010, U+20AA, U+25CC, U+FB1D-FB4F;
            }
            @font-face {
                font-family: 'Heebo';
                font-style: normal;
                font-weight: 700;
                src: url(https://fonts.gstatic.com/s/heebo/v22/NGS6v5_NC0k9P9H0bZ1_ew.woff2) format('woff2');
                unicode-range: U+0590-05FF, U+200C-2010, U+20AA, U+25CC, U+FB1D-FB4F;
            }
        }
        
        /* General styling and resets */
        body, table, td, p, a, li, blockquote { -webkit-text-size-adjust:100%; -ms-text-size-adjust:100%; }
        table, td { mso-table-lspace:0pt; mso-table-rspace:0pt; }
        img { -ms-interpolation-mode:bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }

        /* Responsive Image Fix */
        .email-content img {
            max-width: 100% !important;
            height: auto !important;
            display: block;
        }
        
        /* Typography and Colors */
        .email-content h1,
        .email-content h2,
        .email-content h3,
        .email-content h4,
        .email-content h5,
        .email-content h6 {
            color: #4d2d5d !important; /* Dark Plum */
        }
        
        /* Force RTL alignment on all elements inside the content */
        .email-content * {
            text-align: right !important;
            direction: rtl !important;
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #fdf5f6; font-family: 'Heebo', Arial, sans-serif; direction: rtl; text-align: right;">
    <table border="0" cellpadding="0" cellspacing="0" width="100%">
        <tr>
            <td style="background-color: #fdf5f6; padding: 10px;">
                <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; border-collapse: collapse; margin: 20px auto; background-color: #ffffff; border: 1px solid #dddddd; border-radius: 8px;">
                    <tr>
                        <td align="center" style="padding: 20px 0; font-size: 14px; color: #555555;">
                            <p style="margin: 0;">להצגת העדכון המלא באתר, <a href="{$post_permalink}" style="color: #0073aa;">לחצו כאן</a>.</p>
                        </td>
                    </tr>
                    <tr>
                        <td bgcolor="#ffffff" style="padding: 25px 35px; word-wrap: break-word; word-break: break-word;">
                             <div class="email-content">
                                {$post_content}
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding: 20px; font-size: 12px; color: #777777; line-height: 1.5; border-top: 1px solid #eeeeee;">
                            <p style="margin: 0;">נשלח לכתובת {$recipient_email} מהכתובת {$sender_email}</p>
                            <p style="margin: 5px 0 0 0;"><strong>שולח:</strong> {$sender_name}</p>
                            <p style="margin: 5px 0 0 0;"><strong>כתובת השולח:</strong> {$sender_address}</p>
                            <p style="margin: 15px 0 0 0;">
                                <a href="#" style="color: #777777;">הסרה</a> |
                                <a href="#" style="color: #777777;">עדכון פרטים</a> |
                                <a href="#" style="color: #777777;">דיווח דיוור לא מורשה</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
                $result = SENDISES_SES_Integration::send_email($recipient_email, subject: $subject, body_html: $message);
                
                // Log the result
                $wpdb->insert(
                    $table_name,
                    [
                        'post_id'         => $post_id,
                        'sent_to'         => $user->user_email,
                        'sent_at'         => current_time('mysql'),
                        'delivery_status' => $result['status'],
                        'error_message'   => ($result['status'] === 'error') ? $result['message'] : '',
                    ],
                    ['%d', '%s', '%s', '%s', '%s']
                );
            }
        } catch (\Throwable $t) {
            // Log the error to the server's error log to avoid showing a critical error to the user.
            error_log('SENDISES Plugin Critical Error on Publish: ' . $t->getMessage());
        }
    }
}

// Hook to the publishing action, ensuring it runs after the post is saved.
// Priority is set to 99 to run later, accepts 2 arguments.
add_action('publish_post', ['SENDISES_Email_Automation', 'send_notification_on_publish'], 99, 2);
