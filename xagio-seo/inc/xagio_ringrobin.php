<?php
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

if (!class_exists('XAGIO_RINGROBIN')) {

    class XAGIO_RINGROBIN
    {

        const OPT_API_KEY            = 'xagio_ringrobin_api_key';
        const OPT_USER               = 'xagio_ringrobin_connected_user';
        const OPT_CAMPAIGN_ID        = 'xagio_ringrobin_campaign_id';
        const OPT_CAMPAIGN_NAME      = 'xagio_ringrobin_campaign_name';
        const OPT_DOMAIN_ID          = 'xagio_ringrobin_domain_id';
        const OPT_DOMAIN_NAME        = 'xagio_ringrobin_domain_name';
        const OPT_TRACKING_SNIPPET   = 'xagio_ringrobin_tracking_snippet';
        const OPT_IS_VERIFIED        = 'xagio_ringrobin_is_verified';
        const OPT_LAST_CHECKED_AT    = 'xagio_ringrobin_last_checked_at';
        const OPT_LAST_CHECK_STATUS  = 'xagio_ringrobin_last_check_status';
        const OPT_LAST_CHECK_MESSAGE = 'xagio_ringrobin_last_check_message';
        const OPT_CONFLICT_DISMISSED = 'xagio_ringrobin_conflict_dismissed';
        const OPT_WIDGETS            = 'xagio_ringrobin_widgets';
        const OPT_NUMBERS            = 'xagio_ringrobin_numbers';

        public static function initialize()
        {
            add_action('wp_ajax_xagio_rr_connect',               [__CLASS__, 'ajax_connect']);
            add_action('wp_ajax_xagio_rr_disconnect',            [__CLASS__, 'ajax_disconnect']);
            add_action('wp_ajax_xagio_rr_list_campaigns',        [__CLASS__, 'ajax_list_campaigns']);
            add_action('wp_ajax_xagio_rr_link_site',             [__CLASS__, 'ajax_link_site']);
            add_action('wp_ajax_xagio_rr_verify',                [__CLASS__, 'ajax_verify']);
            add_action('wp_ajax_xagio_rr_unlink_site',           [__CLASS__, 'ajax_unlink_site']);
            add_action('wp_ajax_xagio_rr_dismiss_conflict',      [__CLASS__, 'ajax_dismiss_conflict']);
            add_action('wp_ajax_xagio_rr_list_widgets',          [__CLASS__, 'ajax_list_widgets']);
            add_action('wp_ajax_xagio_rr_create_widget',         [__CLASS__, 'ajax_create_widget']);
            add_action('wp_ajax_xagio_rr_save_widget_selection', [__CLASS__, 'ajax_save_widget_selection']);
            add_action('wp_ajax_xagio_rr_remove_widget_local',   [__CLASS__, 'ajax_remove_widget_local']);
            add_action('wp_ajax_xagio_rr_search_numbers',        [__CLASS__, 'ajax_search_numbers']);
            add_action('wp_ajax_xagio_rr_buy_number',            [__CLASS__, 'ajax_buy_number']);
            add_action('wp_ajax_xagio_rr_refresh_numbers',       [__CLASS__, 'ajax_refresh_numbers']);
            add_action('wp_ajax_xagio_rr_render_panel',              [__CLASS__, 'ajax_render_panel']);
            add_action('admin_enqueue_scripts',                  [__CLASS__, 'localize_script']);
            add_action('wp_enqueue_scripts',                     [__CLASS__, 'render_tracking_snippet']);
        }

        public static function localize_script($hook)
        {
            if (strpos($hook, 'xagio-settings') === false) {
                return;
            }

            // Ensure the script is enqueued before localizing
            wp_enqueue_script('xagio_settings');

            // On every settings page load, refresh the widget cache from RingRobin so
            // widgets created outside this plugin (e.g. in the RingRobin app) show up.
            // The helper is internally idempotent per request and falls back to cached
            // data on transport/API errors — never wipes the local copy on a bad response.
            $widgets = self::is_linked() ? self::refresh_widgets_from_api() : get_option(self::OPT_WIDGETS, []);
            $numbers = get_option(self::OPT_NUMBERS, []);

            wp_localize_script('xagio_settings', 'xagioRingRobin', [
                'ajaxUrl'        => admin_url('admin-ajax.php'),
                'isLinked'       => self::is_linked(),
                'isVerified'     => (bool) get_option(self::OPT_IS_VERIFIED, false),
                'lastCheckedAt'  => (int) get_option(self::OPT_LAST_CHECKED_AT, 0),
                'lastStatus'     => (string) get_option(self::OPT_LAST_CHECK_STATUS, ''),
                'lastMessage'    => (string) get_option(self::OPT_LAST_CHECK_MESSAGE, ''),
                'domain'         => self::detect_site_domain(),
                'campaignId'     => (string) get_option(self::OPT_CAMPAIGN_ID, ''),
                'widgets'        => is_array($widgets) ? array_values($widgets) : [],
                'numbers'        => is_array($numbers) ? array_values($numbers) : [],
                'twilio'         => self::twilio_probe(),
                'onboardingLocation' => (string) get_option('XAGIO_ONBOARDING_LOCATION', ''),
                'nonces'         => [
                    'connect'              => wp_create_nonce('xagio_rr_connect'),
                    'disconnect'           => wp_create_nonce('xagio_rr_disconnect'),
                    'listCampaigns'        => wp_create_nonce('xagio_rr_list_campaigns'),
                    'linkSite'             => wp_create_nonce('xagio_rr_link_site'),
                    'verify'               => wp_create_nonce('xagio_rr_verify'),
                    'unlinkSite'           => wp_create_nonce('xagio_rr_unlink_site'),
                    'dismissConflict'      => wp_create_nonce('xagio_rr_dismiss_conflict'),
                    'listWidgets'          => wp_create_nonce('xagio_rr_list_widgets'),
                    'createWidget'         => wp_create_nonce('xagio_rr_create_widget'),
                    'saveWidgetSelection'  => wp_create_nonce('xagio_rr_save_widget_selection'),
                    'removeWidgetLocal'    => wp_create_nonce('xagio_rr_remove_widget_local'),
                    'searchNumbers'        => wp_create_nonce('xagio_rr_search_numbers'),
                    'buyNumber'            => wp_create_nonce('xagio_rr_buy_number'),
                    'refreshNumbers'       => wp_create_nonce('xagio_rr_refresh_numbers'),
                    'renderPanel'             => wp_create_nonce('xagio_rr_render_panel'),
                ],
                'i18n' => [
                    'keyRequired'        => __('Please enter your API key.', 'xagio-seo'),
                    'connecting'         => __('Connecting…', 'xagio-seo'),
                    'connected'          => __('Connected. Reloading…', 'xagio-seo'),
                    'genericError'       => __('Something went wrong. Please try again.', 'xagio-seo'),
                    'disconnectTitle'    => __('Disconnect RingRobin', 'xagio-seo'),
                    'confirmDisconnect'  => __('Disconnect RingRobin from this site? Your campaign, widgets, and phone numbers stay safe in your RingRobin account, and any widgets already on pages keep working. This plugin will stop managing them until you reconnect.', 'xagio-seo'),
                    'linkModalTitle'     => __('Link this site to RingRobin', 'xagio-seo'),
                    'selectExisting'     => __('Select existing campaign', 'xagio-seo'),
                    'createNew'          => __('Create new campaign', 'xagio-seo'),
                    'loadingCampaigns'   => __('Loading…', 'xagio-seo'),
                    'noCampaigns'        => __('No campaigns found — create a new one.', 'xagio-seo'),
                    'pickCampaign'       => __('Please select a campaign.', 'xagio-seo'),
                    'campaignNameReq'    => __('Please enter a campaign name.', 'xagio-seo'),
                    /* translators: %s: site domain name */
                    'domainWillRegister' => __('Domain %s will be registered on this campaign.', 'xagio-seo'),
                    'submit'             => __('Link Site', 'xagio-seo'),
                    'cancel'             => __('Cancel', 'xagio-seo'),
                    'checking'           => __('Checking…', 'xagio-seo'),
                    /* translators: 1: current attempt number, 2: maximum number of attempts */
                    'checkingAttempt'    => __('Checking… (attempt %1$d of %2$d)', 'xagio-seo'),
                    'verified'           => __('Verified ✓', 'xagio-seo'),
                    'notDetected'        => __('Not detected', 'xagio-seo'),
                    'notDetectedAfter'   => __('Not detected after 30 seconds. If your site uses caching, please clear it and click Verify Installation.', 'xagio-seo'),
                    'verifyButton'       => __('Verify Installation', 'xagio-seo'),
                    /* translators: %d: seconds remaining in the cooldown countdown */
                    'wait'               => __('Wait %ds…', 'xagio-seo'),
                    'unlinkTitle'        => __('Unlink this site', 'xagio-seo'),
                    'confirmUnlink'      => __('Stop tracking this site via RingRobin? The campaign and domain will remain in RingRobin, but this plugin will no longer inject the script.', 'xagio-seo'),
                    'notLinked'          => __('This site is not linked to RingRobin.', 'xagio-seo'),
                    'widgetsTitle'       => __('Widgets', 'xagio-seo'),
                    'createForm'         => __('Create Form widget', 'xagio-seo'),
                    'createText'         => __('Create Text widget', 'xagio-seo'),
                    'editOnRingRobin'    => __('Edit on RingRobin', 'xagio-seo'),
                    'removeFromSite'     => __('Remove from this site', 'xagio-seo'),
                    'removeWidgetTitle'   => __('Remove widget from site', 'xagio-seo'),
                    'confirmRemoveWidget' => __('Remove this widget from the plugin\'s list? It will stay in your RingRobin account, and pages already embedding it keep working. To take it off a page, edit that page and remove the widget there.', 'xagio-seo'),
                    'noWidgets'          => __('No widgets associated with this site yet.', 'xagio-seo'),
                    'numbersTitle'       => __('Phone Numbers', 'xagio-seo'),
                    'connectTwilio'      => __('Connect Twilio in RingRobin', 'xagio-seo'),
                    'twilioNotConnected' => __('To buy a phone number for this campaign, connect Twilio in your RingRobin account.', 'xagio-seo'),
                    'searchAndBuy'       => __('Search & buy a number', 'xagio-seo'),
                    'searchModalTitle'   => __('Search for a phone number', 'xagio-seo'),
                    'searchButton'       => __('Search', 'xagio-seo'),
                    'searching'          => __('Searching…', 'xagio-seo'),
                    'noResults'          => __('No numbers found. Try a different area code or location.', 'xagio-seo'),
                    'buy'                => __('Buy', 'xagio-seo'),
                    'buying'             => __('Buying…', 'xagio-seo'),
                    /* translators: 1: phone number, 2: monthly price, 3: currency code */
                    'confirmPurchase'    => __('Buy %1$s for %2$s %3$s / month?', 'xagio-seo'),
                    /* translators: 1: new monthly price, 2: currency code */
                    'priceChanged'       => __('Price changed to %1$s %2$s — confirm again to proceed.', 'xagio-seo'),
                    'numberUnavailable'  => __('That number was just taken. Please pick another.', 'xagio-seo'),
                    'noNumbers'          => __('No phone numbers attached to this site yet.', 'xagio-seo'),
                    'notWired'           => __('This endpoint is not yet available. Coming soon.', 'xagio-seo'),
                    'widgetNamePrompt'   => __('Widget name (leave blank for default):', 'xagio-seo'),
                    'wizardDone'         => __('Done', 'xagio-seo'),
                    'wizardContinue'     => __('Continue', 'xagio-seo'),
                    'addFormWidget'      => __('Add Form widget', 'xagio-seo'),
                    'addTextWidget'      => __('Add Text widget', 'xagio-seo'),
                    'formWidgetsTitle'   => __('Form widgets', 'xagio-seo'),
                    'textWidgetsTitle'   => __('Text widgets (click-to-text)', 'xagio-seo'),
                    'phoneNumbersTitle'  => __('Phone Numbers', 'xagio-seo'),
                    /* translators: %s: purchased phone number */
                    'createdNumberBuilt' => __('Number purchased: %s', 'xagio-seo'),
                    'swapFailed'         => __('Saved on RingRobin, but could not refresh the page state. Reload to see the latest.', 'xagio-seo'),
                ],
            ]);
        }

        public static function get_api_key()
        {
            $key = get_option(self::OPT_API_KEY);
            return !empty($key) ? $key : null;
        }

        public static function get_connected_user()
        {
            $user = get_option(self::OPT_USER);
            return is_array($user) ? $user : null;
        }

        public static function is_connected()
        {
            return self::get_api_key() !== null && self::get_connected_user() !== null;
        }

        public static function is_linked()
        {
            $campaign_id = get_option(self::OPT_CAMPAIGN_ID);
            $domain_id   = get_option(self::OPT_DOMAIN_ID);
            return !empty($campaign_id) && !empty($domain_id);
        }

        public static function detect_site_domain()
        {
            $host = wp_parse_url(home_url(), PHP_URL_HOST);
            if (empty($host)) {
                return '';
            }
            $host = strtolower($host);
            $host = preg_replace('/^www\./', '', $host);
            return $host;
        }

        public static function has_manual_tracker_in_scripts()
        {
            $scripts = (string) get_option('XAGIO_SEO_GLOBAL_SCRIPTS_HEAD', '');
            if ($scripts === '') {
                return false;
            }
            if (preg_match('#chirps-tracking(?:-v[0-9]+)?\.js#i', $scripts)) {
                return true;
            }
            if (preg_match('#ringrobin\.net#i', $scripts)) {
                return true;
            }
            return false;
        }

        private static function api_request($method, $path, $body = null, $extra_headers = [])
        {
            $api_key = self::get_api_key();
            if (empty($api_key)) {
                return new WP_Error(
                    'xagio_rr_no_key',
                    __('RingRobin is not connected.', 'xagio-seo')
                );
            }

            $headers = [
                'Authorization' => 'Bearer ' . $api_key,
                'Accept'        => 'application/json',
            ];
            if (is_array($extra_headers)) {
                foreach ($extra_headers as $h_name => $h_value) {
                    $headers[$h_name] = $h_value;
                }
            }

            $args = [
                'method'  => strtoupper($method),
                'headers' => $headers,
                'timeout' => 15,
            ];

            if ($body !== null) {
                $args['headers']['Content-Type'] = 'application/json';
                $args['body']                    = wp_json_encode($body);
            }

            $url         = XAGIO_RINGROBIN_API_BASE . $path;
            $maxAttempts = 2; // original + 1 retry on 5xx
            $attempt     = 0;
            $response    = null;

            while ($attempt < $maxAttempts) {
                $attempt++;
                $response = wp_remote_request($url, $args);

                // Transport-level failure — don't retry (spec: retry on 5xx only).
                if (is_wp_error($response)) {
                    return $response;
                }

                $code = (int) wp_remote_retrieve_response_code($response);

                // Retry only on 5xx, and only if attempts remain.
                if ($code >= 500 && $code < 600 && $attempt < $maxAttempts) {
                    usleep(500000); // 500 ms
                    continue;
                }

                break;
            }

            $code    = (int) wp_remote_retrieve_response_code($response);
            $decoded = json_decode(wp_remote_retrieve_body($response), true);
            if (!is_array($decoded)) {
                $decoded = [];
            }

            return [$code, $decoded];
        }

        private static function extract_api_error(array $body, $fallback)
        {
            if (isset($body['error']['message'])) {
                return $body['error']['message'];
            }
            if (isset($body['message'])) {
                return $body['message'];
            }
            return $fallback;
        }

        /**
         * Return the full error envelope (message + code + any context fields like
         * current_price_monthly, hint, retry_after_seconds, required_scope) so the
         * JS layer can dispatch on the stable code enum from the OpenAPI spec.
         */
        private static function extract_api_error_envelope(array $body, $fallback)
        {
            if (isset($body['error']) && is_array($body['error'])) {
                $envelope = $body['error'];
                if (!isset($envelope['message']) || $envelope['message'] === '') {
                    $envelope['message'] = $fallback;
                }
                return $envelope;
            }
            return ['message' => $fallback];
        }

        private static function require_capability()
        {
            if (!current_user_can('manage_options')) {
                wp_send_json_error(
                    ['message' => __('You do not have permission to do this.', 'xagio-seo')],
                    403
                );
            }
        }

        public static function ajax_connect()
        {
            check_ajax_referer('xagio_rr_connect', 'nonce');
            self::require_capability();

            $api_key = isset($_POST['api_key'])
                ? sanitize_text_field(wp_unslash($_POST['api_key']))
                : '';

            if (empty($api_key)) {
                wp_send_json_error(
                    ['message' => __('API key is required.', 'xagio-seo')],
                    400
                );
            }

            if (!preg_match('/^rr_live_[a-f0-9]{12}_[a-f0-9]{48}$/i', $api_key)) {
                wp_send_json_error(
                    ['message' => __('That does not look like a valid RingRobin API key.', 'xagio-seo')],
                    400
                );
            }

            $response = wp_remote_get(
                XAGIO_RINGROBIN_API_BASE . '/me',
                [
                    'headers' => ['Authorization' => 'Bearer ' . $api_key],
                    'timeout' => 15,
                ]
            );

            if (is_wp_error($response)) {
                wp_send_json_error(
                    [
                        'message' => sprintf(
                            /* translators: %s: underlying network or HTTP error message */
                            __('Could not reach RingRobin: %s', 'xagio-seo'),
                            $response->get_error_message()
                        ),
                    ],
                    502
                );
            }

            $code = (int) wp_remote_retrieve_response_code($response);
            $body = json_decode(wp_remote_retrieve_body($response), true);

            if ($code !== 200 || empty($body['user']['id'])) {
                $api_message = isset($body['error']['message'])
                    ? $body['error']['message']
                    : __('Unknown error', 'xagio-seo');

                wp_send_json_error(
                    [
                        'message' => sprintf(
                            /* translators: %s: error message returned by the RingRobin API */
                            __('RingRobin rejected the key: %s', 'xagio-seo'),
                            $api_message
                        ),
                    ],
                    $code > 0 ? $code : 400
                );
            }

            $user = [
                'id'           => isset($body['user']['id'])           ? sanitize_text_field($body['user']['id'])           : '',
                'display_name' => isset($body['user']['display_name']) ? sanitize_text_field($body['user']['display_name']) : '',
                'account_type' => isset($body['user']['account_type']) ? sanitize_text_field($body['user']['account_type']) : '',
            ];

            update_option(self::OPT_API_KEY, $api_key, false);
            update_option(self::OPT_USER,    $user,    false);

            wp_send_json_success([
                'user' => [
                    'display_name' => $user['display_name'],
                    'account_type' => $user['account_type'],
                ],
            ]);
        }

        public static function ajax_disconnect()
        {
            check_ajax_referer('xagio_rr_disconnect', 'nonce');
            self::require_capability();

            // Local cleanup — clear every RingRobin option so a reconnect
            // (possibly to a different account) starts from a clean slate
            // and stale data doesn't bleed through. The remote campaign,
            // widgets, and phone numbers stay intact on RingRobin's side,
            // and embeds already placed on pages keep working since those
            // scripts hit RingRobin's CDN directly.
            $options_to_clear = [
                self::OPT_API_KEY,
                self::OPT_USER,
                self::OPT_CAMPAIGN_ID,
                self::OPT_CAMPAIGN_NAME,
                self::OPT_DOMAIN_ID,
                self::OPT_DOMAIN_NAME,
                self::OPT_TRACKING_SNIPPET,
                self::OPT_IS_VERIFIED,
                self::OPT_LAST_CHECKED_AT,
                self::OPT_LAST_CHECK_STATUS,
                self::OPT_LAST_CHECK_MESSAGE,
                self::OPT_CONFLICT_DISMISSED,
                self::OPT_WIDGETS,
                self::OPT_NUMBERS,
            ];
            foreach ($options_to_clear as $opt) {
                delete_option($opt);
            }

            wp_send_json_success();
        }

        public static function ajax_list_campaigns()
        {
            check_ajax_referer('xagio_rr_list_campaigns', 'nonce');
            self::require_capability();

            $result = self::api_request('GET', '/campaigns');

            if (is_wp_error($result)) {
                wp_send_json_error(
                    [
                        'message' => sprintf(
                            /* translators: %s: underlying network or HTTP error message */
                            __('Could not reach RingRobin: %s', 'xagio-seo'),
                            $result->get_error_message()
                        ),
                    ],
                    502
                );
            }

            list($code, $body) = $result;

            if ($code < 200 || $code >= 300) {
                wp_send_json_error(
                    ['message' => self::extract_api_error($body, __('Could not load campaigns.', 'xagio-seo'))],
                    $code > 0 ? $code : 400
                );
            }

            $campaigns = isset($body['campaigns']) && is_array($body['campaigns'])
                ? $body['campaigns']
                : [];

            wp_send_json_success(['campaigns' => $campaigns]);
        }

        public static function ajax_link_site()
        {
            check_ajax_referer('xagio_rr_link_site', 'nonce');
            self::require_capability();

            $campaign_id   = isset($_POST['campaign_id'])
                ? sanitize_text_field(wp_unslash($_POST['campaign_id']))
                : '';
            $campaign_name = isset($_POST['campaign_name'])
                ? sanitize_text_field(wp_unslash($_POST['campaign_name']))
                : '';

            if (empty($campaign_id) && empty($campaign_name)) {
                wp_send_json_error(
                    ['message' => __('Please select an existing campaign or provide a new campaign name.', 'xagio-seo')],
                    400
                );
            }

            // Step 1 — if creating, POST /v1/campaigns
            if (!empty($campaign_name)) {
                $result = self::api_request('POST', '/campaigns', ['name' => $campaign_name]);

                if (is_wp_error($result)) {
                    wp_send_json_error(
                        [
                            'message' => sprintf(
                                /* translators: %s: underlying network or HTTP error message */
                                __('Could not reach RingRobin: %s', 'xagio-seo'),
                                $result->get_error_message()
                            ),
                        ],
                        502
                    );
                }

                list($code, $body) = $result;

                if ($code !== 201) {
                    wp_send_json_error(
                        ['message' => self::extract_api_error($body, __('Could not create campaign.', 'xagio-seo'))],
                        $code > 0 ? $code : 400
                    );
                }

                // Spec contract: POST /campaigns returns { campaign: {...} } — flat read no longer needed.
                $campaign_id = isset($body['campaign']['id']) ? (string) $body['campaign']['id'] : '';
            }

            if (empty($campaign_id)) {
                wp_send_json_error(
                    ['message' => __('RingRobin did not return a campaign id.', 'xagio-seo')],
                    502
                );
            }

            // Step 2 — GET campaign details (name, tracking_snippet)
            $result = self::api_request('GET', '/campaigns/' . rawurlencode($campaign_id));

            if (is_wp_error($result)) {
                wp_send_json_error(
                    [
                        'message' => sprintf(
                            /* translators: %s: underlying network or HTTP error message */
                            __('Could not reach RingRobin: %s', 'xagio-seo'),
                            $result->get_error_message()
                        ),
                    ],
                    502
                );
            }

            list($code, $body) = $result;

            if ($code !== 200) {
                wp_send_json_error(
                    ['message' => self::extract_api_error($body, __('Could not load campaign details.', 'xagio-seo'))],
                    $code > 0 ? $code : 400
                );
            }

            // Spec contract: GET /campaigns/{id} returns { campaign: {...}, tracking_snippet: "..." }.
            $campaign_obj     = isset($body['campaign']) && is_array($body['campaign']) ? $body['campaign'] : [];
            $resolved_name    = isset($campaign_obj['name']) ? (string) $campaign_obj['name'] : '';
            $tracking_snippet = isset($body['tracking_snippet']) ? (string) $body['tracking_snippet'] : '';

            // Step 3 — register the domain
            $domain = self::detect_site_domain();
            if (empty($domain)) {
                wp_send_json_error(
                    ['message' => __('Could not determine this site\'s domain.', 'xagio-seo')],
                    400
                );
            }

            $result = self::api_request(
                'POST',
                '/campaigns/' . rawurlencode($campaign_id) . '/domains',
                ['domain' => $domain]
            );

            if (is_wp_error($result)) {
                wp_send_json_error(
                    [
                        'message' => sprintf(
                            /* translators: %s: underlying network or HTTP error message */
                            __('Could not reach RingRobin: %s', 'xagio-seo'),
                            $result->get_error_message()
                        ),
                    ],
                    502
                );
            }

            list($code, $body) = $result;

            if ($code === 409) {
                $other_campaign_id = isset($body['error']['current_campaign_id'])
                    ? $body['error']['current_campaign_id']
                    : '';

                wp_send_json_error(
                    [
                        'message' => sprintf(
                            /* translators: %s: domain name */
                            __('The domain %s is already registered on a different RingRobin campaign. Unlink it there first, or link this site to the campaign it already belongs to.', 'xagio-seo'),
                            $domain
                        ),
                        'conflict_campaign_id' => $other_campaign_id,
                    ],
                    409
                );
            }

            if ($code !== 200 && $code !== 201) {
                wp_send_json_error(
                    ['message' => self::extract_api_error($body, __('Could not register this domain on the campaign.', 'xagio-seo'))],
                    $code > 0 ? $code : 400
                );
            }

            $domain_obj  = isset($body['domain']) && is_array($body['domain']) ? $body['domain'] : $body;
            $domain_id   = isset($domain_obj['id'])     ? (string) $domain_obj['id']     : '';
            $domain_name = isset($domain_obj['domain']) ? (string) $domain_obj['domain'] : $domain;
            $is_verified = !empty($domain_obj['is_verified']);

            if (empty($domain_id)) {
                wp_send_json_error(
                    ['message' => __('RingRobin did not return a domain id.', 'xagio-seo')],
                    502
                );
            }

            // Persist everything
            update_option(self::OPT_CAMPAIGN_ID,        $campaign_id,      false);
            update_option(self::OPT_CAMPAIGN_NAME,      $resolved_name,    false);
            update_option(self::OPT_DOMAIN_ID,          $domain_id,        false);
            update_option(self::OPT_DOMAIN_NAME,        $domain_name,      false);
            update_option(self::OPT_TRACKING_SNIPPET,   $tracking_snippet, false);
            update_option(self::OPT_IS_VERIFIED,        $is_verified,      false);
            update_option(self::OPT_LAST_CHECKED_AT,    0,                 false);
            update_option(self::OPT_LAST_CHECK_STATUS,  '',                false);
            update_option(self::OPT_LAST_CHECK_MESSAGE, '',                false);

            wp_send_json_success([
                'campaign' => [
                    'id'   => $campaign_id,
                    'name' => $resolved_name,
                ],
                'domain' => [
                    'id'          => $domain_id,
                    'domain'      => $domain_name,
                    'is_verified' => $is_verified,
                ],
            ]);
        }

        public static function ajax_verify()
        {
            check_ajax_referer('xagio_rr_verify', 'nonce');
            self::require_capability();

            $domain_id = get_option(self::OPT_DOMAIN_ID);
            if (empty($domain_id)) {
                wp_send_json_error(
                    ['message' => __('This site is not linked to RingRobin.', 'xagio-seo')],
                    400
                );
            }

            $result = self::api_request(
                'POST',
                '/domains/' . rawurlencode($domain_id) . '/verify',
                []
            );

            if (is_wp_error($result)) {
                wp_send_json_error(
                    [
                        'message' => sprintf(
                            /* translators: %s: underlying network or HTTP error message */
                            __('Could not reach RingRobin: %s', 'xagio-seo'),
                            $result->get_error_message()
                        ),
                    ],
                    502
                );
            }

            list($code, $body) = $result;

            if ($code !== 200) {
                wp_send_json_error(
                    ['message' => self::extract_api_error($body, __('Verification request failed.', 'xagio-seo'))],
                    $code > 0 ? $code : 400
                );
            }

            $verification = isset($body['verification']) && is_array($body['verification'])
                ? $body['verification']
                : $body;

            $status  = isset($verification['status'])  ? (string) $verification['status']  : '';
            $message = isset($verification['message']) ? (string) $verification['message'] : '';

            update_option(self::OPT_LAST_CHECKED_AT,    time(),   false);
            update_option(self::OPT_LAST_CHECK_STATUS,  $status,  false);
            update_option(self::OPT_LAST_CHECK_MESSAGE, $message, false);

            $is_verified = ($status === 'verified');
            if ($is_verified) {
                update_option(self::OPT_IS_VERIFIED, true, false);
            }

            wp_send_json_success([
                'status'      => $status,
                'message'     => $message,
                'is_verified' => $is_verified,
            ]);
        }

        public static function ajax_unlink_site()
        {
            check_ajax_referer('xagio_rr_unlink_site', 'nonce');
            self::require_capability();

            delete_option(self::OPT_CAMPAIGN_ID);
            delete_option(self::OPT_CAMPAIGN_NAME);
            delete_option(self::OPT_DOMAIN_ID);
            delete_option(self::OPT_DOMAIN_NAME);
            delete_option(self::OPT_TRACKING_SNIPPET);
            delete_option(self::OPT_IS_VERIFIED);
            delete_option(self::OPT_LAST_CHECKED_AT);
            delete_option(self::OPT_LAST_CHECK_STATUS);
            delete_option(self::OPT_LAST_CHECK_MESSAGE);
            // Local cleanup only — widget and number records on RingRobin are untouched.
            delete_option(self::OPT_WIDGETS);
            delete_option(self::OPT_NUMBERS);

            wp_send_json_success();
        }

        public static function ajax_dismiss_conflict()
        {
            check_ajax_referer('xagio_rr_dismiss_conflict', 'nonce');
            self::require_capability();

            update_option(self::OPT_CONFLICT_DISMISSED, true, false);

            wp_send_json_success();
        }

        /**
         * Refresh the local widget cache from RingRobin and return the merged list.
         *
         * Fetches both form and text widgets for the linked campaign and writes them
         * to OPT_WIDGETS. Per-request static cache prevents duplicate API calls when
         * both localize_script() and page.php call this in the same page load.
         *
         * Safety: only overwrites the local cache when BOTH GETs succeed. On partial
         * or full failure, leaves OPT_WIDGETS untouched and returns whatever was
         * already cached — better to show stale than to wipe the list.
         */
        public static function refresh_widgets_from_api()
        {
            static $cached = null;
            if ($cached !== null) {
                return $cached;
            }

            $campaign_id = (string) get_option(self::OPT_CAMPAIGN_ID, '');
            if ($campaign_id === '' || !self::is_connected()) {
                $cached = get_option(self::OPT_WIDGETS, []);
                if (!is_array($cached)) { $cached = []; }
                return $cached;
            }

            $merged = [];
            $all_ok = true;

            foreach (['form', 'text'] as $type) {
                $result = self::api_request(
                    'GET',
                    '/campaigns/' . rawurlencode($campaign_id) . '/widgets?type=' . $type
                );
                if (is_wp_error($result)) {
                    $all_ok = false;
                    continue;
                }
                list($code, $body) = $result;
                if ($code !== 200 || !isset($body['widgets']) || !is_array($body['widgets'])) {
                    $all_ok = false;
                    continue;
                }
                foreach ($body['widgets'] as $w) {
                    if (!is_array($w) || empty($w['id'])) {
                        continue;
                    }
                    $merged[] = [
                        'id'         => sanitize_text_field((string) $w['id']),
                        'type'       => isset($w['type']) && in_array($w['type'], ['form', 'text'], true)
                            ? (string) $w['type']
                            : $type,
                        'name'       => isset($w['name'])       ? sanitize_text_field((string) $w['name'])       : '',
                        'created_at' => isset($w['created_at']) ? sanitize_text_field((string) $w['created_at']) : '',
                        'edit_url'   => isset($w['edit_url'])   ? esc_url_raw((string) $w['edit_url'])           : '',
                    ];
                }
            }

            if ($all_ok) {
                update_option(self::OPT_WIDGETS, $merged, false);
                $cached = $merged;
            } else {
                $cached = get_option(self::OPT_WIDGETS, []);
                if (!is_array($cached)) { $cached = []; }
            }

            return $cached;
        }

        /**
         * Probe RingRobin for Twilio integration status.
         *
         * Server-side helper called during localize_script() and during page render.
         * Result is NOT cached — admin page traffic is low; staleness > the price of one GET.
         * On any error (not connected, transport, malformed), falls back to a "not connected"
         * shape with the canonical connect_url so the UI always renders something coherent.
         */
        public static function twilio_probe()
        {
            $fallback = [
                'connected'             => false,
                'account_friendly_name' => null,
                'connect_url'           => 'https://app.ringrobin.net/app/integrations?connect=twilio',
                '_diag'                 => ['source' => 'fallback', 'reason' => 'init'],
            ];

            if (!self::is_connected()) {
                $fallback['_diag'] = ['source' => 'fallback', 'reason' => 'no_api_key'];
                return $fallback;
            }

            $result = self::api_request('GET', '/account/integrations/twilio');
            if (is_wp_error($result)) {
                $fallback['_diag'] = [
                    'source'  => 'fallback',
                    'reason'  => 'transport_error',
                    'message' => $result->get_error_message(),
                ];
                return $fallback;
            }

            list($code, $body) = $result;
            if ($code !== 200 || !is_array($body)) {
                $fallback['_diag'] = [
                    'source' => 'fallback',
                    'reason' => 'non_200',
                    'code'   => $code,
                    'body'   => $body,
                ];
                return $fallback;
            }

            return [
                'connected'             => !empty($body['connected']),
                'account_friendly_name' => isset($body['account_friendly_name']) ? (string) $body['account_friendly_name'] : null,
                'connect_url'           => isset($body['connect_url']) ? (string) $body['connect_url'] : $fallback['connect_url'],
                '_diag'                 => ['source' => 'api', 'code' => 200, 'raw' => $body],
            ];
        }

        /**
         * Generate a v4 UUID for Idempotency-Key headers on POST /campaigns/{id}/numbers.
         * Used by ajax_buy_number; exposed as a helper for any future caller.
         */
        public static function generate_uuid_v4()
        {
            $data    = random_bytes(16);
            $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
            $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
            return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
        }

        // ────────────────────────────────────────────────────────────
        // AJAX HANDLERS — Widgets + Numbers (xagio-v2 endpoints)
        // Reads proxy GETs; writes proxy POSTs and update local options.
        // Local-only handlers (save_widget_selection, remove_widget_local)
        // don't touch RingRobin per the unlink-semantics contract.
        // ────────────────────────────────────────────────────────────

        private static function require_linked()
        {
            $campaign_id = (string) get_option(self::OPT_CAMPAIGN_ID, '');
            if ($campaign_id === '') {
                wp_send_json_error(
                    ['message' => __('This site is not linked to a RingRobin campaign.', 'xagio-seo')],
                    400
                );
            }
            return $campaign_id;
        }

        public static function ajax_list_widgets()
        {
            check_ajax_referer('xagio_rr_list_widgets', 'nonce');
            self::require_capability();
            $campaign_id = self::require_linked();

            $type = isset($_POST['type']) ? sanitize_text_field(wp_unslash($_POST['type'])) : '';
            if (!in_array($type, ['form', 'text'], true)) {
                wp_send_json_error(['message' => __('Widget type must be "form" or "text".', 'xagio-seo')], 400);
            }

            $result = self::api_request(
                'GET',
                '/campaigns/' . rawurlencode($campaign_id) . '/widgets?type=' . rawurlencode($type)
            );

            if (is_wp_error($result)) {
                wp_send_json_error(
                    [
                        'message' => sprintf(
                            /* translators: %s: underlying network or HTTP error message */
                            __('Could not reach RingRobin: %s', 'xagio-seo'),
                            $result->get_error_message()
                        ),
                    ],
                    502
                );
            }

            list($code, $body) = $result;
            if ($code < 200 || $code >= 300) {
                wp_send_json_error(
                    self::extract_api_error_envelope($body, __('Could not load widgets.', 'xagio-seo')),
                    $code > 0 ? $code : 400
                );
            }

            $widgets     = isset($body['widgets']) && is_array($body['widgets']) ? $body['widgets'] : [];
            $next_cursor = isset($body['next_cursor']) ? $body['next_cursor'] : null;

            wp_send_json_success([
                'widgets'     => $widgets,
                'next_cursor' => $next_cursor,
            ]);
        }

        public static function ajax_create_widget()
        {
            check_ajax_referer('xagio_rr_create_widget', 'nonce');
            self::require_capability();
            $campaign_id = self::require_linked();

            $type = isset($_POST['type']) ? sanitize_text_field(wp_unslash($_POST['type'])) : '';
            if (!in_array($type, ['form', 'text'], true)) {
                wp_send_json_error(['message' => __('Widget type must be "form" or "text".', 'xagio-seo')], 400);
            }

            $name    = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
            $payload = ['type' => $type];
            if ($name !== '') {
                $payload['name'] = $name;
            }

            $result = self::api_request(
                'POST',
                '/campaigns/' . rawurlencode($campaign_id) . '/widgets',
                $payload
            );

            if (is_wp_error($result)) {
                wp_send_json_error(
                    [
                        'message' => sprintf(
                            /* translators: %s: underlying network or HTTP error message */
                            __('Could not reach RingRobin: %s', 'xagio-seo'),
                            $result->get_error_message()
                        ),
                    ],
                    502
                );
            }

            list($code, $body) = $result;
            if ($code !== 201 || empty($body['id'])) {
                wp_send_json_error(
                    self::extract_api_error_envelope($body, __('Could not create widget.', 'xagio-seo')),
                    $code > 0 ? $code : 400
                );
            }

            // Persist the new widget in the local list so the panel reflects it without re-fetching.
            $widget = [
                'id'         => sanitize_text_field((string) $body['id']),
                'type'       => isset($body['type']) ? sanitize_text_field((string) $body['type']) : $type,
                'name'       => isset($body['name']) ? sanitize_text_field((string) $body['name']) : '',
                'created_at' => isset($body['created_at']) ? sanitize_text_field((string) $body['created_at']) : '',
                'edit_url'   => isset($body['edit_url']) ? esc_url_raw((string) $body['edit_url']) : '',
            ];

            $widgets = get_option(self::OPT_WIDGETS, []);
            if (!is_array($widgets)) {
                $widgets = [];
            }
            $widgets[] = $widget;
            update_option(self::OPT_WIDGETS, $widgets, false);

            wp_send_json_success(['widget' => $widget]);
        }

        public static function ajax_save_widget_selection()
        {
            check_ajax_referer('xagio_rr_save_widget_selection', 'nonce');
            self::require_capability();

            // Local-only — persists which widget objects the user picked at link time.
            // No RingRobin call required; works fully even before xagio-v2 ships.
            $raw     = isset($_POST['widgets']) ? wp_unslash($_POST['widgets']) : '[]';
            $decoded = json_decode($raw, true);
            if (!is_array($decoded)) {
                wp_send_json_error(
                    ['message' => __('Invalid widget data.', 'xagio-seo')],
                    400
                );
            }

            $sanitized = [];
            foreach ($decoded as $w) {
                if (!is_array($w) || empty($w['id']) || empty($w['type'])) {
                    continue;
                }
                if (!in_array($w['type'], ['form', 'text'], true)) {
                    continue;
                }
                $sanitized[] = [
                    'id'         => sanitize_text_field((string) $w['id']),
                    'type'       => (string) $w['type'],
                    'name'       => isset($w['name'])       ? sanitize_text_field((string) $w['name'])       : '',
                    'created_at' => isset($w['created_at']) ? sanitize_text_field((string) $w['created_at']) : '',
                    'edit_url'   => isset($w['edit_url'])   ? esc_url_raw((string) $w['edit_url'])           : '',
                ];
            }

            update_option(self::OPT_WIDGETS, $sanitized, false);
            wp_send_json_success(['widgets' => $sanitized]);
        }

        public static function ajax_remove_widget_local()
        {
            check_ajax_referer('xagio_rr_remove_widget_local', 'nonce');
            self::require_capability();

            $id = isset($_POST['id']) ? sanitize_text_field(wp_unslash($_POST['id'])) : '';
            if (empty($id)) {
                wp_send_json_error(
                    ['message' => __('Widget ID is required.', 'xagio-seo')],
                    400
                );
            }

            $widgets = get_option(self::OPT_WIDGETS, []);
            if (!is_array($widgets)) {
                $widgets = [];
            }

            $filtered = array_values(array_filter($widgets, function ($w) use ($id) {
                return !isset($w['id']) || $w['id'] !== $id;
            }));

            update_option(self::OPT_WIDGETS, $filtered, false);
            wp_send_json_success(['widgets' => $filtered]);
        }

        public static function ajax_search_numbers()
        {
            check_ajax_referer('xagio_rr_search_numbers', 'nonce');
            self::require_capability();

            $country  = isset($_POST['country'])   ? strtoupper(sanitize_text_field(wp_unslash($_POST['country'])))   : 'US';
            $area     = isset($_POST['area_code']) ? sanitize_text_field(wp_unslash($_POST['area_code']))             : '';
            $locality = isset($_POST['locality'])  ? sanitize_text_field(wp_unslash($_POST['locality']))              : '';
            $voice    = !empty($_POST['voice']);
            $sms      = !empty($_POST['sms']);
            $limit    = isset($_POST['limit']) ? max(1, min(30, (int) $_POST['limit'])) : 20;

            $qs = ['country' => $country, 'limit' => $limit];
            if ($area !== '')     { $qs['area_code'] = $area; }
            if ($locality !== '') { $qs['locality']  = $locality; }
            // Only forward capability filters when they're toggled off — sending true == server default.
            if (!$voice) { $qs['voice'] = 'false'; }
            if (!$sms)   { $qs['sms']   = 'false'; }

            $result = self::api_request('GET', '/twilio/available-numbers?' . http_build_query($qs));

            if (is_wp_error($result)) {
                wp_send_json_error(
                    [
                        'message' => sprintf(
                            /* translators: %s: underlying network or HTTP error message */
                            __('Could not reach RingRobin: %s', 'xagio-seo'),
                            $result->get_error_message()
                        ),
                    ],
                    502
                );
            }

            list($code, $body) = $result;
            if ($code < 200 || $code >= 300) {
                wp_send_json_error(
                    self::extract_api_error_envelope($body, __('Could not search numbers.', 'xagio-seo')),
                    $code > 0 ? $code : 400
                );
            }

            $numbers = isset($body['numbers']) && is_array($body['numbers']) ? $body['numbers'] : [];
            wp_send_json_success(['numbers' => $numbers]);
        }

        public static function ajax_buy_number()
        {
            check_ajax_referer('xagio_rr_buy_number', 'nonce');
            self::require_capability();
            $campaign_id = self::require_linked();

            $phone_number = isset($_POST['phone_number'])
                ? sanitize_text_field(wp_unslash($_POST['phone_number']))
                : '';
            if (empty($phone_number)) {
                wp_send_json_error(['message' => __('Phone number is required.', 'xagio-seo')], 400);
            }

            $expected_price = isset($_POST['expected_price_monthly'])
                ? sanitize_text_field(wp_unslash($_POST['expected_price_monthly']))
                : '';
            $currency = isset($_POST['currency'])
                ? sanitize_text_field(wp_unslash($_POST['currency']))
                : '';

            // Idempotency-Key: prefer the one minted client-side so user-driven retries reuse it;
            // generate a fresh one if the JS layer didn't supply it.
            $idempotency_key = isset($_POST['idempotency_key'])
                ? sanitize_text_field(wp_unslash($_POST['idempotency_key']))
                : '';
            if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $idempotency_key)) {
                $idempotency_key = self::generate_uuid_v4();
            }

            $payload = ['phone_number' => $phone_number];
            if ($expected_price !== '') {
                $payload['expected_price_monthly'] = $expected_price;
                // currency is required by the spec whenever expected_price_monthly is set.
                if ($currency !== '') {
                    $payload['currency'] = $currency;
                }
            }

            $result = self::api_request(
                'POST',
                '/campaigns/' . rawurlencode($campaign_id) . '/numbers',
                $payload,
                ['Idempotency-Key' => $idempotency_key]
            );

            if (is_wp_error($result)) {
                wp_send_json_error(
                    [
                        'message' => sprintf(
                            /* translators: %s: underlying network or HTTP error message */
                            __('Could not reach RingRobin: %s', 'xagio-seo'),
                            $result->get_error_message()
                        ),
                    ],
                    502
                );
            }

            list($code, $body) = $result;
            if ($code !== 201 || empty($body['id'])) {
                wp_send_json_error(
                    self::extract_api_error_envelope($body, __('Could not purchase number.', 'xagio-seo')),
                    $code > 0 ? $code : 400
                );
            }

            $number = [
                'id'            => sanitize_text_field((string) $body['id']),
                'phone_number'  => isset($body['phone_number'])  ? sanitize_text_field((string) $body['phone_number'])  : $phone_number,
                'friendly_name' => isset($body['friendly_name']) ? sanitize_text_field((string) $body['friendly_name']) : '',
                'locality'      => isset($body['locality'])      ? sanitize_text_field((string) $body['locality'])      : '',
                'region'        => isset($body['region'])        ? sanitize_text_field((string) $body['region'])        : '',
                'country'       => isset($body['country'])       ? sanitize_text_field((string) $body['country'])       : '',
                'twilio_sid'    => isset($body['twilio_sid'])    ? sanitize_text_field((string) $body['twilio_sid'])    : '',
                'attached_at'   => isset($body['attached_at'])   ? sanitize_text_field((string) $body['attached_at'])   : '',
                'price_monthly' => isset($body['price_monthly']) ? sanitize_text_field((string) $body['price_monthly']) : '',
                'price_setup'   => isset($body['price_setup'])   ? sanitize_text_field((string) $body['price_setup'])   : '',
                'currency'      => isset($body['currency'])      ? sanitize_text_field((string) $body['currency'])      : ($currency ?: 'USD'),
                'status'        => isset($body['status'])        ? sanitize_text_field((string) $body['status'])        : 'active',
                'status_reason' => isset($body['status_reason']) ? sanitize_text_field((string) $body['status_reason']) : '',
            ];

            $numbers = get_option(self::OPT_NUMBERS, []);
            if (!is_array($numbers)) {
                $numbers = [];
            }
            $numbers[] = $number;
            update_option(self::OPT_NUMBERS, $numbers, false);

            wp_send_json_success(['number' => $number]);
        }

        public static function ajax_refresh_numbers()
        {
            check_ajax_referer('xagio_rr_refresh_numbers', 'nonce');
            self::require_capability();
            $campaign_id = self::require_linked();

            $result = self::api_request('GET', '/campaigns/' . rawurlencode($campaign_id) . '/numbers');

            if (is_wp_error($result)) {
                wp_send_json_error(
                    [
                        'message' => sprintf(
                            /* translators: %s: underlying network or HTTP error message */
                            __('Could not reach RingRobin: %s', 'xagio-seo'),
                            $result->get_error_message()
                        ),
                    ],
                    502
                );
            }

            list($code, $body) = $result;
            if ($code < 200 || $code >= 300) {
                wp_send_json_error(
                    self::extract_api_error_envelope($body, __('Could not refresh numbers.', 'xagio-seo')),
                    $code > 0 ? $code : 400
                );
            }

            $numbers = isset($body['numbers']) && is_array($body['numbers']) ? $body['numbers'] : [];

            // Reconcile local cache — the canonical truth is RingRobin, so replace wholesale.
            $sanitized = [];
            foreach ($numbers as $n) {
                if (!is_array($n) || empty($n['id'])) { continue; }
                $sanitized[] = [
                    'id'            => sanitize_text_field((string) $n['id']),
                    'phone_number'  => isset($n['phone_number'])  ? sanitize_text_field((string) $n['phone_number'])  : '',
                    'friendly_name' => isset($n['friendly_name']) ? sanitize_text_field((string) $n['friendly_name']) : '',
                    'locality'      => isset($n['locality'])      ? sanitize_text_field((string) $n['locality'])      : '',
                    'region'        => isset($n['region'])        ? sanitize_text_field((string) $n['region'])        : '',
                    'country'       => isset($n['country'])       ? sanitize_text_field((string) $n['country'])       : '',
                    'twilio_sid'    => isset($n['twilio_sid'])    ? sanitize_text_field((string) $n['twilio_sid'])    : '',
                    'attached_at'   => isset($n['attached_at'])   ? sanitize_text_field((string) $n['attached_at'])   : '',
                    'price_monthly' => isset($n['price_monthly']) ? sanitize_text_field((string) $n['price_monthly']) : '',
                    'price_setup'   => isset($n['price_setup'])   ? sanitize_text_field((string) $n['price_setup'])   : '',
                    'currency'      => isset($n['currency'])      ? sanitize_text_field((string) $n['currency'])      : 'USD',
                    'status'        => isset($n['status'])        ? sanitize_text_field((string) $n['status'])        : 'active',
                    'status_reason' => isset($n['status_reason']) ? sanitize_text_field((string) $n['status_reason']) : '',
                ];
            }
            update_option(self::OPT_NUMBERS, $sanitized, false);

            wp_send_json_success(['numbers' => $sanitized]);
        }

        /**
         * Compute every local required by parts/linked-section.php (and the
         * header-band partial). Called from page.php for the initial render
         * and from ajax_render_panel for the AJAX swap. Keeps the local set
         * in one place so the two callers can't drift.
         */
        public static function get_panel_locals()
        {
            $is_connected   = self::is_connected();
            $connected_user = self::get_connected_user();
            $rr_is_linked   = $is_connected && self::is_linked();

            if ($rr_is_linked) {
                self::refresh_widgets_from_api();
            }

            $rr_widgets = get_option(self::OPT_WIDGETS, []);
            if (!is_array($rr_widgets)) { $rr_widgets = []; }
            $rr_numbers = get_option(self::OPT_NUMBERS, []);
            if (!is_array($rr_numbers)) { $rr_numbers = []; }

            $rr_form_widgets = [];
            $rr_text_widgets = [];
            foreach ($rr_widgets as $w) {
                if (!is_array($w) || empty($w['type'])) { continue; }
                if ($w['type'] === 'form') { $rr_form_widgets[] = $w; }
                if ($w['type'] === 'text') { $rr_text_widgets[] = $w; }
            }

            $rr_conflict           = self::has_manual_tracker_in_scripts();
            $rr_conflict_dismissed = (bool) get_option(self::OPT_CONFLICT_DISMISSED, false);

            $rr_campaign_id = (string) get_option(self::OPT_CAMPAIGN_ID, '');
            $rr_campaign_settings_url = $rr_campaign_id !== ''
                ? 'https://ringrobin.net/app/campaigns/' . rawurlencode($rr_campaign_id) . '/settings'
                : '';

            return [
                'is_connected'             => $is_connected,
                'connected_user'           => $connected_user,
                'rr_site_domain'           => self::detect_site_domain(),
                'rr_is_linked'             => $rr_is_linked,
                'rr_campaign_id'           => $rr_campaign_id,
                'rr_campaign_settings_url' => $rr_campaign_settings_url,
                'rr_campaign_name'         => (string) get_option(self::OPT_CAMPAIGN_NAME, ''),
                'rr_domain_name'        => (string) get_option(self::OPT_DOMAIN_NAME, ''),
                'rr_is_verified'        => (bool) get_option(self::OPT_IS_VERIFIED, false),
                'rr_last_checked_at'    => (int) get_option(self::OPT_LAST_CHECKED_AT, 0),
                'rr_last_check_status'  => (string) get_option(self::OPT_LAST_CHECK_STATUS, ''),
                'rr_last_check_message' => (string) get_option(self::OPT_LAST_CHECK_MESSAGE, ''),
                'rr_show_conflict'      => $rr_conflict && !$rr_conflict_dismissed,
                'rr_scripts_tab_url'    => admin_url('admin.php?page=xagio-seo'),
                'rr_widgets'            => $rr_widgets,
                'rr_numbers'            => $rr_numbers,
                'rr_twilio'             => $rr_is_linked ? self::twilio_probe() : [
                    'connected'   => false,
                    'connect_url' => 'https://app.ringrobin.net/app/integrations?connect=twilio',
                ],
                'rr_form_widgets'       => $rr_form_widgets,
                'rr_text_widgets'       => $rr_text_widgets,
            ];
        }

        /**
         * Render the full RingRobin panel as two HTML fragments — the header
         * band (right side of the panel title row) and the body (two-column
         * grid). Returning both lets the JS swap them together so the panel
         * never shows a mismatched header/body during a state transition.
         */
        public static function ajax_render_panel()
        {
            check_ajax_referer('xagio_rr_render_panel', 'nonce');
            self::require_capability();

            $locals = self::get_panel_locals();
            extract($locals, EXTR_SKIP);

            ob_start();
            $header = XAGIO_PATH . '/modules/settings/parts/connection-section.php';
            if (file_exists($header)) {
                include $header;
            }
            $header_band_html = ob_get_clean();

            ob_start();
            $body = XAGIO_PATH . '/modules/settings/parts/linked-section.php';
            if (file_exists($body)) {
                include $body;
            }
            $body_html = ob_get_clean();

            wp_send_json_success([
                'header_band_html' => $header_band_html,
                'body_html'        => $body_html,
            ]);
        }

        /**
         * Enqueue the RingRobin tracking script on the front-end. The
         * raw snippet returned by the RingRobin API is a <script src="…">
         * tag — we parse out the src and hand it to wp_enqueue_script so
         * the snippet integrates with WordPress's normal script pipeline
         * (de-duplication, caching, footer placement).
         *
         * If the snippet contains inline JS instead of a src, we register
         * a no-op handle and attach the JS via wp_add_inline_script so
         * WordPress still owns the print.
         */
        public static function render_tracking_snippet()
        {
            if (is_admin() || !self::is_linked()) {
                return;
            }

            $snippet = (string) get_option(self::OPT_TRACKING_SNIPPET, '');
            if ($snippet === '') {
                return;
            }

            if (self::has_manual_tracker_in_scripts()) {
                return;
            }

            // External-src form: <script src="..."></script>
            if (preg_match('#<script\b[^>]*\bsrc=("|\')([^"\']+)\1[^>]*>\s*</script>#i', $snippet, $m)) {
                $src    = $m[2];
                $handle = 'xagio-rr-tracking-' . substr(md5($src), 0, 12);
                wp_enqueue_script($handle, $src, [], null, true);
                return;
            }

            // Inline form: <script>…JS…</script>
            if (preg_match('#<script\b[^>]*>(.+?)</script>#is', $snippet, $m)) {
                $code   = trim($m[1]);
                $handle = 'xagio-rr-tracking-inline';
                // wp_register_script requires a src; use a small data URI
                // placeholder so WP has something to attach inline JS to.
                wp_register_script($handle, '', [], null, true);
                wp_enqueue_script($handle);
                wp_add_inline_script($handle, $code);
            }
        }

        public static function removeTable()
        {
            delete_option(self::OPT_API_KEY);
            delete_option(self::OPT_USER);
            delete_option(self::OPT_CAMPAIGN_ID);
            delete_option(self::OPT_CAMPAIGN_NAME);
            delete_option(self::OPT_DOMAIN_ID);
            delete_option(self::OPT_DOMAIN_NAME);
            delete_option(self::OPT_TRACKING_SNIPPET);
            delete_option(self::OPT_IS_VERIFIED);
            delete_option(self::OPT_LAST_CHECKED_AT);
            delete_option(self::OPT_LAST_CHECK_STATUS);
            delete_option(self::OPT_LAST_CHECK_MESSAGE);
            delete_option(self::OPT_CONFLICT_DISMISSED);
            delete_option(self::OPT_WIDGETS);
            delete_option(self::OPT_NUMBERS);
        }
    }
}
