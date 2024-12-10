<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if (!class_exists('XAGIO_MODEL_IMAGECONVERTER')) {

    class XAGIO_MODEL_IMAGECONVERTER
    {

        public static function initialize()
        {
            add_filter('bulk_actions-upload', ['XAGIO_MODEL_IMAGECONVERTER', 'addAction']);
            add_filter('handle_bulk_actions-upload', ['XAGIO_MODEL_IMAGECONVERTER', 'handleAction'], 10, 3);
        }

        public static function addAction($bulk_actions)
        {
            $bulk_actions['convert'] = 'Convert to JPEG';
            return $bulk_actions;
        }

        public static function handleAction($redirect_to, $action_name, $attachment_ids)
        {
            if ('convert' === $action_name) {
                // Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
                require_once(ABSPATH . 'wp-admin/includes/image.php');

                foreach ($attachment_ids as $id) {

                    $mime = get_post_mime_type($id);
                    $file = get_attached_file($id);

                    if ($mime == 'image/png') {
                        $image = imagecreatefrompng($file);
                        $ext   = '.png';
                    } else if ($mime == 'image/gif') {
                        $image = imagecreatefromgif($file);
                        $ext   = '.gif';
                    } else {
                        continue;
                    }

                    $outputFile = str_replace($ext, '.jpg', $file);

                    imagejpeg($image, $outputFile, 100);
                    imagedestroy($image);

                    // Check the type of file. We'll use this as the 'post_mime_type'.
                    $filetype = wp_check_filetype(basename($outputFile), NULL);

                    // Get the path to the upload directory.
                    $wp_upload_dir = wp_upload_dir();

                    // Prepare an array of post data for the attachment.
                    $attachment = [
                        'guid'           => $wp_upload_dir['url'] . '/' . basename($outputFile),
                        'post_mime_type' => $filetype['type'],
                        'post_title'     => preg_replace('/\.[^.]+$/', '', basename($outputFile)),
                        'post_content'   => '',
                        'post_status'    => 'inherit',
                    ];

                    // Insert the attachment.
                    $attach_id = wp_insert_attachment($attachment, $outputFile);

                    // Generate the metadata for the attachment, and update the database record.
                    $attach_data = wp_generate_attachment_metadata($attach_id, $outputFile);
                    wp_update_attachment_metadata($attach_id, $attach_data);
                }
                $redirect_to = add_query_arg('bulk_convert_images_processed', count($attachment_ids), $redirect_to);
                return $redirect_to;
            } else {
                return $redirect_to;
            }
        }

    }

}