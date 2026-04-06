<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

if (!class_exists('XAGIO_S3')) {

    class XAGIO_S3
    {

        /**
         * @var string AWS Access Key ID.
         */
        protected $access_key;

        /**
         * @var string AWS Secret Access Key.
         */
        protected $secret_key;

        /**
         * @var string AWS Region (e.g. "us-east-2").
         */
        protected $region;

        /**
         * @var string S3 Bucket Name.
         */
        protected $bucket;

        /**
         * @var string S3 Service Name.
         */
        protected $service = 's3';

        /**
         * Define a chunk size for asynchronous multipart uploads.
         * (S3 parts must be at least 5 MB except for the last part; here we use 50 MB.)
         */
        const UPLOAD_CHUNK_SIZE = 52428800; // 50 MB

        /**
         * Constructor.
         *
         * @param string $access_key AWS Access Key ID.
         * @param string $secret_key AWS Secret Access Key.
         * @param string $region AWS Region (e.g. "us-east-2").
         * @param string $bucket S3 Bucket Name.
         */
        public function __construct(string $access_key = '', string $secret_key = '', string $region = 'us-east-2', string $bucket = '')
        {
            $this->access_key = $access_key;
            $this->secret_key = $secret_key;
            $this->region     = $region;
            $this->bucket     = $bucket;
        }

        /**
         * List files in the S3 bucket (using ListObjectsV2).
         *
         * @param string $path Optional "folder" prefix. e.g. 'myfolder'
         * @return array|WP_Error An array of file keys (and details) or WP_Error on failure.
         */
        public function list_files($path = '')
        {
            $method     = 'GET';
            $base_query = 'list-type=2';

            // If path is not empty, rawurlencode it (without leading/trailing slashes).
            if ($path) {
                $path           = trim($path, '/');
                $prefix_encoded = rawurlencode($path);
                $base_query     .= '&prefix=' . $prefix_encoded;
            }

            $uri  = '/?' . $base_query;
            $host = $this->bucket . '.s3.' . $this->region . '.amazonaws.com';

            $xagio_response = $this->do_request($method, $host, $uri);

            if (is_wp_error($xagio_response)) {
                return $xagio_response;
            }

            $body   = wp_remote_retrieve_body($xagio_response);
            $status = wp_remote_retrieve_response_code($xagio_response);

            if (200 !== $status) {
                return new WP_Error('s3_list_error', 'Failed to list S3 objects. HTTP Status: ' . $status);
            }

            $xml     = simplexml_load_string($body);
            $results = [];

            if (isset($xml->Contents)) {
                foreach ($xml->Contents as $xagio_content) {
                    $results[] = [
                        'Key'          => (string)$xagio_content->Key,
                        'LastModified' => (string)$xagio_content->LastModified,
                        'Size'         => (int)$xagio_content->Size,
                    ];
                }
            }

            if (!empty($path) && empty($results)) {
                $this->create_folder($path);
            }

            return $results;
        }

        /**
         * Create a zero-byte "folder" object in S3 (e.g., "myfolder/").
         *
         * @param string $folder The folder name (without trailing slash).
         * @return bool|WP_Error True on success, WP_Error on failure.
         */
        public function create_folder($folder)
        {
            $folder = trim($folder, '/') . '/';

            $method = 'PUT';
            $uri    = '/' . $folder;
            $host   = $this->bucket . '.s3.' . $this->region . '.amazonaws.com';

            $body = '';

            $extra_headers = [
                'Content-Type'   => 'application/x-directory',
                'Content-Length' => '0',
            ];

            $xagio_response = $this->do_request($method, $host, $uri, $extra_headers, $body);
            if (is_wp_error($xagio_response)) {
                return $xagio_response;
            }

            $status = wp_remote_retrieve_response_code($xagio_response);
            if (200 !== $status && 201 !== $status) {
                return new WP_Error('s3_folder_create_error', 'Failed to create folder. HTTP Status: ' . $status);
            }

            return true;
        }

        /**
         * Download a file from the S3 bucket.
         *
         * @param string $xagio_key The object key in S3 (e.g., "myfolder/image.jpg").
         * @param string $local_path Local path to save the downloaded file.
         * @return bool|WP_Error True on success, WP_Error on failure.
         */
        public function download_file($xagio_key, $local_path)
        {
            $method = 'GET';
            $uri    = '/' . ltrim($xagio_key, '/');
            $host   = $this->bucket . '.s3.' . $this->region . '.amazonaws.com';

            $xagio_response = $this->do_request($method, $host, $uri);
            if (is_wp_error($xagio_response)) {
                return $xagio_response;
            }

            $status = wp_remote_retrieve_response_code($xagio_response);
            if (200 !== $status) {
                return new WP_Error('s3_download_error', 'Failed to download file. HTTP Status: ' . $status);
            }

            $body = wp_remote_retrieve_body($xagio_response);
            if (!xagio_file_put_contents($local_path, $body)) {
                return new WP_Error('file_write_error', 'Failed to write file to local path.');
            }

            return true;
        }

        /**
         * Generate a presigned URL to download an S3 object.
         *
         * @param string $xagio_key The S3 object key (e.g. "myfolder/image.jpg").
         * @param int $expires Time in seconds until the presigned URL expires (default 3600 = 1 hour).
         * @return string|WP_Error A usable URL if successful, WP_Error on failure.
         */
        public function get_download_link($xagio_key, $expires = 3600)
        {
            $method = 'GET';
            $host   = $this->bucket . '.s3.' . $this->region . '.amazonaws.com';
            $uri    = '/' . ltrim($xagio_key, '/');

            $amz_date = gmdate('Ymd\THis\Z');
            $xagio_date     = gmdate('Ymd');

            $algorithm        = 'AWS4-HMAC-SHA256';
            $credential_scope = "{$xagio_date}/{$this->region}/{$this->service}/aws4_request";
            $credential       = "{$this->access_key}/{$credential_scope}";
            $signed_headers   = 'host';

            $query_params = [
                'X-Amz-Algorithm'      => $algorithm,
                'X-Amz-Content-Sha256' => 'UNSIGNED-PAYLOAD',
                'X-Amz-Credential'     => $credential,
                'X-Amz-Date'           => $amz_date,
                'X-Amz-Expires'        => $expires,
                'X-Amz-SignedHeaders'  => $signed_headers,
            ];

            ksort($query_params);

            $canonical_query_str = http_build_query($query_params, '', '&', PHP_QUERY_RFC3986);

            $canonical_headers_str = "host:{$host}\n";
            $payload_hash          = 'UNSIGNED-PAYLOAD';

            $canonical_request = implode("\n", [
                $method,
                $uri,
                $canonical_query_str,
                $canonical_headers_str,
                $signed_headers,
                $payload_hash,
            ]);

            $string_to_sign = implode("\n", [
                $algorithm,
                $amz_date,
                $credential_scope,
                hash('sha256', $canonical_request),
            ]);

            $signing_key = $this->get_signing_key($this->secret_key, $xagio_date, $this->region, $this->service);
            $signature   = hash_hmac('sha256', $string_to_sign, $signing_key);

            $signed_query = $canonical_query_str . '&X-Amz-Signature=' . $signature;

            $xagio_url = "https://{$host}{$uri}?{$signed_query}";
            return $xagio_url;
        }

        /**
         * Remove (delete) a file from the S3 bucket.
         *
         * @param string $xagio_key The object key in S3.
         * @return bool|WP_Error True on success, WP_Error on failure.
         */
        public function remove_file($xagio_key)
        {
            $method = 'DELETE';
            $uri    = '/' . ltrim($xagio_key, '/');
            $host   = $this->bucket . '.s3.' . $this->region . '.amazonaws.com';

            $xagio_response = $this->do_request($method, $host, $uri);
            if (is_wp_error($xagio_response)) {
                return $xagio_response;
            }

            $status = wp_remote_retrieve_response_code($xagio_response);
            if (204 !== $status && 200 !== $status) {
                return new WP_Error('s3_delete_error', 'Failed to delete file. HTTP Status: ' . $status);
            }

            return true;
        }

        /* ================================
         * Multipart Upload Methods
         * ================================
         */

        /**
         * Initiate a multipart upload.
         *
         * @param string $xagio_key The desired S3 object key.
         * @param string $mime MIME type for the object (default: application/octet-stream).
         * @param array $extra_headers Optional additional headers.
         * @return string|WP_Error The UploadId on success, WP_Error on failure.
         */
        public function create_multipart_upload($xagio_key, $mime = 'application/octet-stream', $extra_headers = [])
        {
            $method = 'POST';
            $uri    = '/' . ltrim($xagio_key, '/') . '?uploads=';
            $host   = $this->bucket . '.s3.' . $this->region . '.amazonaws.com';

            $headers  = array_merge(['Content-Type' => $mime], $extra_headers);
            $xagio_response = $this->do_request($method, $host, $uri, $headers, '');
            if (is_wp_error($xagio_response)) {
                return $xagio_response;
            }

            $body   = wp_remote_retrieve_body($xagio_response);
            $status = wp_remote_retrieve_response_code($xagio_response);
            if (200 !== $status && 201 !== $status) {
                return new WP_Error('s3_create_multipart_upload_error', 'Failed to create multipart upload. HTTP Status: ' . $status);
            }

            $xml = simplexml_load_string($body);
            if (!$xml || !isset($xml->UploadId)) {
                return new WP_Error('s3_create_multipart_upload_error', 'Failed to parse multipart upload creation response.');
            }

            return (string)$xml->UploadId;
        }

        /**
         * Upload a part using provided data.
         *
         * @param string $data The binary data for the part.
         * @param string $xagio_key The S3 object key.
         * @param string $uploadId The upload ID.
         * @param int $partNumber The part number.
         * @param string $mime MIME type for the part.
         * @return string|WP_Error The ETag for the uploaded part, or WP_Error on failure.
         */
        protected function upload_part_data($data, $xagio_key, $uploadId, $partNumber, $mime = 'application/octet-stream')
        {
            $method = 'PUT';
            $uri    = '/' . ltrim($xagio_key, '/') . '?partNumber=' . $partNumber . '&uploadId=' . urlencode($uploadId);
            $host   = $this->bucket . '.s3.' . $this->region . '.amazonaws.com';

            $headers = [
                'Content-Type' => $mime,
            ];

            $xagio_response = $this->do_request($method, $host, $uri, $headers, $data);
            if (is_wp_error($xagio_response)) {
                return $xagio_response;
            }

            $status = wp_remote_retrieve_response_code($xagio_response);
            if (200 !== $status) {
                return new WP_Error('s3_upload_part_error', 'Failed to upload part. HTTP Status: ' . $status);
            }

            $etag = wp_remote_retrieve_header($xagio_response, 'etag');
            if (!$etag) {
                return new WP_Error('s3_upload_part_error', 'Failed to retrieve ETag for part upload.');
            }

            return $etag;
        }

        /**
         * Complete a multipart upload.
         *
         * @param string $xagio_key The S3 object key.
         * @param string $uploadId The upload ID.
         * @param array $parts An array of parts, each with 'PartNumber' and 'ETag'.
         * @return bool|WP_Error True on success, WP_Error on failure.
         */
        public function complete_multipart_upload($xagio_key, $uploadId, $parts)
        {
            $xml = new SimpleXMLElement('<CompleteMultipartUpload></CompleteMultipartUpload>');
            foreach ($parts as $part) {
                $part_xml = $xml->addChild('Part');
                $part_xml->addChild('PartNumber', $part['PartNumber']);
                $part_xml->addChild('ETag', $part['ETag']);
            }
            $body = $xml->asXML();

            $method = 'POST';
            $uri    = '/' . ltrim($xagio_key, '/') . '?uploadId=' . urlencode($uploadId);
            $host   = $this->bucket . '.s3.' . $this->region . '.amazonaws.com';

            $headers = [
                'Content-Type' => 'application/xml',
            ];

            $xagio_response = $this->do_request($method, $host, $uri, $headers, $body);
            if (is_wp_error($xagio_response)) {
                return $xagio_response;
            }

            $status = wp_remote_retrieve_response_code($xagio_response);
            if (200 !== $status) {
                return new WP_Error('s3_complete_multipart_upload_error', 'Failed to complete multipart upload. HTTP Status: ' . $status);
            }

            return true;
        }

        /**
         * Abort a multipart upload.
         *
         * @param string $xagio_key The S3 object key.
         * @param string $uploadId The upload ID.
         * @return bool|WP_Error True on success, WP_Error on failure.
         */
        public function abort_multipart_upload($xagio_key, $uploadId)
        {
            $method = 'DELETE';
            $uri    = '/' . ltrim($xagio_key, '/') . '?uploadId=' . urlencode($uploadId);
            $host   = $this->bucket . '.s3.' . $this->region . '.amazonaws.com';

            $xagio_response = $this->do_request($method, $host, $uri);
            if (is_wp_error($xagio_response)) {
                return $xagio_response;
            }

            $status = wp_remote_retrieve_response_code($xagio_response);
            if (204 !== $status && 200 !== $status) {
                return new WP_Error('s3_abort_multipart_upload_error', 'Failed to abort multipart upload. HTTP Status: ' . $status);
            }

            return true;
        }

        /**
         * Perform an AWS SigV4-signed HTTP request to S3 using WordPress HTTP API.
         *
         * @param string $method HTTP method (GET, PUT, DELETE, etc).
         * @param string $host S3 endpoint (e.g., "mybucket.s3.us-east-2.amazonaws.com").
         * @param string $uri The request path (e.g., "/file.txt" or "/?list-type=2").
         * @param array $extra_headers Additional headers to include in the request.
         * @param string $body The request body content (if any).
         * @return array|WP_Error Response or WP_Error on failure.
         */
        protected function do_request($method, $host, $uri, $extra_headers = [], $body = '')
        {
            $amz_date = gmdate('Ymd\THis\Z');
            $xagio_date     = gmdate('Ymd');

            $parsed          = wp_parse_url($uri);
            $canonical_uri   = isset($parsed['path']) ? $parsed['path'] : '/';
            $canonical_uri   = $canonical_uri ?: '/';
            $canonical_query = isset($parsed['query']) ? $parsed['query'] : '';

            $payload_hash = hash('sha256', $body);

            $all_canonical_headers = [
                'host'                 => $host,
                'x-amz-content-sha256' => $payload_hash,
                'x-amz-date'           => $amz_date,
            ];

            foreach ($extra_headers as $header_key => $header_val) {
                $lower_key                         = strtolower($header_key);
                $all_canonical_headers[$lower_key] = trim($header_val);
            }

            ksort($all_canonical_headers);

            $canonical_headers_str = '';
            $signed_headers_arr    = [];

            foreach ($all_canonical_headers as $xagio_k => $v) {
                $canonical_headers_str .= $xagio_k . ':' . $v . "\n";
                $signed_headers_arr[]  = $xagio_k;
            }

            $signed_headers    = implode(';', $signed_headers_arr);
            $canonical_request = implode("\n", [
                $method,
                $canonical_uri,
                $canonical_query,
                $canonical_headers_str,
                $signed_headers,
                $payload_hash,
            ]);

            $algorithm        = 'AWS4-HMAC-SHA256';
            $credential_scope = $xagio_date . '/' . $this->region . '/' . $this->service . '/aws4_request';
            $string_to_sign   = implode("\n", [
                $algorithm,
                $amz_date,
                $credential_scope,
                hash('sha256', $canonical_request),
            ]);

            $signing_key = $this->get_signing_key($this->secret_key, $xagio_date, $this->region, $this->service);
            $signature   = hash_hmac('sha256', $string_to_sign, $signing_key);

            $auth_header = sprintf(
                '%s Credential=%s/%s, SignedHeaders=%s, Signature=%s', $algorithm, $this->access_key, $credential_scope, $signed_headers, $signature
            );

            $headers = [
                'Host'                 => $host,
                'x-amz-date'           => $amz_date,
                'x-amz-content-sha256' => $payload_hash,
                'Authorization'        => $auth_header,
            ];

            foreach ($extra_headers as $xagio_k => $v) {
                $headers[$xagio_k] = $v;
            }

            $endpoint = 'https://' . $host . $canonical_uri;
            if ($canonical_query) {
                $endpoint .= '?' . $canonical_query;
            }

            $xagio_args = [
                'method'  => $method,
                'headers' => $headers,
                'body'    => $body,
                'timeout' => 400,
            ];

            $xagio_response = wp_remote_request($endpoint, $xagio_args);
            if (is_wp_error($xagio_response)) {
                return $xagio_response;
            }

            return $xagio_response;
        }

        /**
         * Generate AWS Signature Version 4 signing key.
         *
         * @param string $secret_key
         * @param string $date_stamp (e.g. '20241227')
         * @param string $region
         * @param string $service
         * @return string Binary signing key.
         */
        protected function get_signing_key($secret_key, $date_stamp, $region, $service)
        {
            $k_date    = hash_hmac('sha256', $date_stamp, 'AWS4' . $secret_key, true);
            $k_region  = hash_hmac('sha256', $region, $k_date, true);
            $k_service = hash_hmac('sha256', $service, $k_region, true);
            $k_signing = hash_hmac('sha256', 'aws4_request', $k_service, true);
            return $k_signing;
        }

        // -------------------------------------------------------------
        // NEW ASYNCHRONOUS MULTIPART UPLOAD METHODS VIA WP CRON
        // -------------------------------------------------------------

        /**
         * Initiate an asynchronous multipart upload.
         *
         * This method:
         * - Checks that the local file exists.
         * - Initiates a multipart upload (via create_multipart_upload).
         * - Schedules a WP Cron event (hook: 'XAGIO_S3_Process_Upload') to process the file upload in chunks.
         *
         * @param string $local_path Full path to the local file.
         * @param string $xagio_key The desired S3 object key (e.g., "myfolder/largefile.zip").
         * @param int $createID (Optional) A job or backup ID for logging purposes.
         * @return true|WP_Error True on success, WP_Error on failure.
         */
        public function upload($local_path, $xagio_key, $createID = 0)
        {
            if (!file_exists($local_path)) {
                return new WP_Error('file_not_found', 'Local file does not exist: ' . $local_path);
            }

            $filesize = filesize($local_path);

            // Determine the MIME type.
            $type = wp_check_filetype($local_path);
            $mime = !empty($type['type']) ? $type['type'] : 'application/octet-stream';

            // Initiate the multipart upload.
            $uploadId = $this->create_multipart_upload($xagio_key, $mime);
            if (is_wp_error($uploadId)) {
                return $uploadId;
            }

            // Prepare data for WP Cron.
            // The array contains: local file path, key, uploadId, current offset (0),
            // file size, createID, and an empty parts array to store ETags.
            $upload_data = array(
                $local_path,
                $xagio_key,
                $uploadId,
                0,
                $filesize,
                $createID,
                array()
            );

            // Schedule the first WP Cron event if one isn’t already scheduled.
            if (!wp_next_scheduled('XAGIO_S3_Process_Upload', $upload_data)) {
                wp_schedule_single_event(time() + 10, 'XAGIO_S3_Process_Upload', $upload_data);
            }

            return true;
        }

        /**
         * Static method to process one chunk of an asynchronous multipart upload.
         *
         * Expected to be hooked to a WP Cron event (hook: 'XAGIO_S3_Process_Upload').
         *
         * @param string $local_path The local file path.
         * @param string $xagio_key The S3 object key.
         * @param string $uploadId The multipart upload ID.
         * @param int $offset The current file offset (in bytes).
         * @param int $file_size The total file size (in bytes).
         * @param int $createID A job or backup ID for logging purposes.
         * @param array $parts Array of parts uploaded so far (each item contains PartNumber and ETag).
         */
        public static function processUploadQueue($local_path, $xagio_key, $uploadId, $offset, $file_size, $createID, $parts)
        {
            $chunk_size = self::UPLOAD_CHUNK_SIZE;

            // Ensure the file exists.
            if (!file_exists($local_path)) {
                if (class_exists('XAGIO_MODEL_BACKUPS')) {
                    XAGIO_MODEL_BACKUPS::handleOutput($createID, 'error', 'Local file does not exist, cannot upload.');
                }
                return;
            }

            // Open the file and seek to the current offset.
            $handle = xagio_fopen($local_path, 'rb');
            if (!$handle || xagio_fseek($handle, $offset) !== 0) {
                if ($handle) {
                    xagio_fclose($handle);
                }
                if (class_exists('XAGIO_MODEL_BACKUPS')) {
                    XAGIO_MODEL_BACKUPS::handleOutput($createID, 'error', 'Cannot open local file for multipart upload.');
                }
                return;
            }

            // Read the next chunk.
            $data = xagio_fread($handle, $chunk_size);
            xagio_fclose($handle);

            $xagio_tokens = get_option("XAGIO_BACKUP_SETTINGS");

            $backup_AmazonAccessKey = $xagio_tokens["amazon"]["access_key"] ?? "";
            $backup_AmazonSecretKey = $xagio_tokens["amazon"]["secret_key"] ?? "";
            $backup_AmazonBucket    = $xagio_tokens["amazon"]["bucket"] ?? "";
            $backup_AmazonRegion    = $xagio_tokens["amazon"]["region"] ?? "";

            if (empty($backup_AmazonAccessKey) || empty($backup_AmazonSecretKey) || empty($backup_AmazonBucket) || empty($backup_AmazonRegion)) {
                if (class_exists('XAGIO_MODEL_BACKUPS')) {
                    XAGIO_MODEL_BACKUPS::handleOutput($createID, 'error', 'Amazon S3 credentials are not set.');
                }
                return;
            }

            if ($data === false || strlen($data) === 0) {
                // If unable to read, abort the upload.
                $s3 = new self(
                    $backup_AmazonAccessKey, $backup_AmazonSecretKey, $backup_AmazonRegion, $backup_AmazonBucket
                );
                $s3->abort_multipart_upload($xagio_key, $uploadId);
                if (class_exists('XAGIO_MODEL_BACKUPS')) {
                    XAGIO_MODEL_BACKUPS::handleOutput($createID, 'error', 'Failed to read a chunk from the file.');
                }
                return;
            }

            $chunk_length = strlen($data);
            $partNumber   = floor($offset / $chunk_size) + 1;

            // Instantiate the S3 client (assumes S3 credentials are available as constants).
            $s3 = new self(
                $backup_AmazonAccessKey, $backup_AmazonSecretKey, $backup_AmazonRegion, $backup_AmazonBucket
            );

            // Determine MIME type.
            $type = wp_check_filetype($local_path);
            $mime = !empty($type['type']) ? $type['type'] : 'application/octet-stream';

            // Upload this part using the existing helper method.
            $etag = $s3->upload_part_data($data, $xagio_key, $uploadId, $partNumber, $mime);
            if (is_wp_error($etag)) {
                $s3->abort_multipart_upload($xagio_key, $uploadId);
                if (class_exists('XAGIO_MODEL_BACKUPS')) {
                    XAGIO_MODEL_BACKUPS::handleOutput($createID, 'error', 'Error uploading part ' . $partNumber . ': ' . $etag->get_error_message());
                }
                return;
            }

            // Add the successful part info.
            $parts[] = [
                'PartNumber' => $partNumber,
                'ETag'       => $etag,
            ];

            $offset += $chunk_length;

            if ($offset < $file_size) {
                // More data remains. Schedule the next WP Cron event.
                $upload_data = array(
                    $local_path,
                    $xagio_key,
                    $uploadId,
                    $offset,
                    $file_size,
                    $createID,
                    $parts
                );
                wp_schedule_single_event(time() + 10, 'XAGIO_S3_Process_Upload', $upload_data);
            } else {
                // All parts have been uploaded. Complete the multipart upload.
                $xagio_result = $s3->complete_multipart_upload($xagio_key, $uploadId, $parts);
                if (is_wp_error($xagio_result)) {
                    if (class_exists('XAGIO_MODEL_BACKUPS')) {
                        XAGIO_MODEL_BACKUPS::handleOutput($createID, 'error', 'Failed to complete multipart upload: ' . $xagio_result->get_error_message());
                    }
                } else {
                    if (class_exists('XAGIO_MODEL_BACKUPS')) {
                        XAGIO_MODEL_BACKUPS::handleOutput($createID, 'success', 'File successfully uploaded to S3.');
                    }
                }
                // Optionally, delete the local file once the upload is complete.
                wp_delete_file($local_path);
            }
        }
    }
}

