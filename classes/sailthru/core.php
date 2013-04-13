<?php defined('SYSPATH') or die('No direct script access.');

/*******************************************************************************
 * Copyright (c) 2007 Sailthru, Inc.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR ``AS IS'' AND ANY EXPRESS OR
 * IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES
 * OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF
 * THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 ******************************************************************************/

 /**
  * Example
  * 
  * Sailthru::instance()->send($template_name, $email_address, array('replace' => $email_order));
  * 
  * Delayed Send
  * 
  * $scheduled_delivery = strtotime("+2 weeks");
  * 
  * Date formatted Appropriatly for Sailthru API.
  * 
  * $send_date = date('Y-m-d H:i e',$scheduled_delivery);
  * 
  * Replacement
  * 
  * Sailthru::instance()->send($template_name, $email_address, array('replace' => $message_replace), array(), $send_date); 
  * 
  */

class Sailthru_Core {
    private static $_instance;

    private $_config = array();

    // singleton function
    public static function instance() {
        if ( ! is_object(self::$_instance)) {
            self::$_instance = new self;
        }

        return self::$_instance;
    }

    /**
     * Instantiate a new client; constructor optionally takes overrides for key/secret/uri.
     *
     * @param string $api_key
     * @param string $secret
     * @param string $api_uri
     * @return Sailthru_Client
     */
    public function __construct() {
        $this->_config = Kohana::config('sailthru');
    }

    /**
     * Remotely send an email template to a single email address.
     *
     * If you pass the $schedule_time parameter, the send will be scheduled for a future time.
     *
     * Options:
     *   replyto: override Reply-To header
     *   test: send as test email (subject line will be marked, will not count towards stats)
     *
     * @param string $template_name
     * @param string $email
     * @param array $vars
     * @param array $options
     * @param string $schedule_time
     * @return array
     */
    function send($template_name, $email, $vars = array(), $options = array(), $schedule_time = null) {
        $post = array(
            'template' => $template_name,
            'email'    => is_array($email) ? implode(',', $email) : $email,
            'vars'     => $vars,
            'options'  => $options
        );

        if ($schedule_time) {
            $post['schedule_time'] = $schedule_time;
        }

        return $this->api_post('send', $post);
    }

    /**
     * Get the status of a send.
     *
     * @param string $send_id
     * @return array
     */
    function get_send($send_id) {
        return $this->api_get('send', array('send_id' => $send_id));
    }

    /**
     * Cancel a send that was scheduled for a future time.
     *
     * @param string $send_id
     * @return array
     */
    function cancel_send($send_id) {
        return $this->api_post('send', array('send_id' => $send_id), 'DELETE');
    }

    /**
     * Return information about an email address, including replacement vars and lists.
     *
     * @param string $email
     * @return array
     */
    function get_email($email) {
        return $this->api_get('email', array('email' => $email));
    }

    /**
     * Set replacement vars and/or list subscriptions for an email address.
     *
     * $lists should be an assoc array mapping list name => 1 for subscribed, 0 for unsubscribed
     *
     * @param string $email
     * @param array $vars
     * @param array $lists
     * @param array $templates
     * @return array
     */
    function set_email($email, $vars = array(), $lists = array(), $templates = array()) {
        $data = array('email' => $email);

        if ($vars) {
            $data['vars'] = $vars;
        }

        if ($lists) {
            $data['lists'] = $lists;
        }

        if ($templates) {
            $data['templates'] = $templates;
        }

        return $this->api_post('email', $data);
    }

    /**
     * Get information on a previously scheduled email blast
     *
     * @param integer $blast_id
     * @return array
     */
    function get_blast($blast_id) {
        return $this->api_get('blast', array('blast_id' => $blast_id));
    }

    /**
     * Schedule a mass mail blast
     *
     * @param string $name
     * @param string $list
     * @param string $schedule_time
     * @param string $from_name
     * @param string $from_email
     * @param string $subject
     * @param string $content_html
     * @param string $content_text
     * @param array $options
     * @return array
     */
    function schedule_blast($name, $list, $schedule_time, $from_name, $from_email, $subject, $content_html, $content_text, $options = array()) {
        $data = array(
            'name'          => $name,
            'list'          => $list,
            'schedule_time' => $schedule_time,
            'from_name'     => $from_name,
            'from_email'    => $from_email,
            'subject'       => $subject,
            'content_html'  => $content_html,
            'content_text'  => $content_text
        );

        return $this->api_post('blast', array_merge($data, $options));
    }

    /**
     * Fetch email contacts from an address book at one of the major email providers (aol/gmail/hotmail/yahoo)
     *
     * Use the third, optional parameter if you want to return the names of the contacts along with their emails
     *
     * @param string $email
     * @param string $password
     * @param boolean $include_names
     * @return array
     */
    function import_contacts($email, $password, $include_names = false) {
        $data = array(
            'email'    => $email,
            'password' => $password,
        );

        if ($include_names) {
            $data['names'] = 1;
        }

        return $this->api_post('contacts', $data);
    }

    /**
     * Get a template.
     *
     * @param string $template_name
     * @return array
     */
    function get_template($template_name) {
        return $this->api_get('template', array('template' => $template_name));
    }

    /**
     * Save a template.
     *
     * @param string $template_name
     * @param array $template_fields
     * @return array
     */
    function save_template($template_name, $template_fields) {
        $data             = $template_fields;

        $data['template'] = $template_name;

        return $this->api_post('template', $data);
    }

    /**
     * Returns true if the incoming request is an authenticated verify post.
     *
     * @return boolean
     */
    function receive_verify_post() {
        $params = $_POST;

        foreach (array('action', 'email', 'send_id', 'sig') as $k) {
            if (!isset($params[$k]))
                return FALSE;
        }

        if ($params['action'] != 'verify')
            return FALSE;

        $sig = $params['sig'];

        unset($params['sig']);

        if ($sig != Sailthru::get_signature_hash($params, $this->_config['secret']))
            return FALSE;

        $send = $this->get_send($params['send_id']);

        return isset($send['email']) && ($send['email'] == $params['email']);
    }

    function receive_optout_post() {
        $params = $_POST;

        foreach (array('action', 'email', 'sig') as $k) {
            if (!isset($params[$k]))
                return FALSE;
        }

        if ($params['action'] != 'optout')
            return FALSE;

        $sig = $params['sig'];

        unset($params['sig']);

        return $sig == Sailthru::get_signature_hash($params, $this->_config['secret']);
    }

    function set_horizon_cookie($email, $domain = null, $duration = null, $secure = false) {
        $data = $this->api_get('horizon', array('email' => $email, 'hid_only' => 1));

        if ( ! isset($data['hid']))
            return FALSE;

        if (!$domain) {
            $domain_parts = explode('.', $_SERVER['HTTP_HOST']);

            $domain       = $domain_parts[sizeof($domain_parts) - 2] . '.' . $domain_parts[sizeof($domain_parts) - 1];
        }

        if ($duration === null) {
            $expire = time() + 31556926;
        } else if ($duration) {
            $expire = time() + $duration;
        } else {
            $expire = 0;
        }

        setcookie('sailthru_hid', $data['hid'], $expire, '/', $domain, $secure);
    }

    /**
     * Perform an API GET request, using the shared-secret auth hash.
     *
     * @param string $action
     * @param array $data
     * @return array
     */
    function api_get($action, $data) {
        return $this->_api_call($action, $data, 'GET');
    }

    /**
     * Perform an API POST request, using the shared-secret auth hash.
     *
     * @param array $data
     * @return array
     */
    function api_post($action, $data) {
        return $this->_api_call($action, $data, 'POST');
    }

    protected function _api_call($action, $data, $method = 'POST') {
        $data['api_key'] = $this->_config['api_key'];

        $data['format']  = isset($data['format']) ? $data['format'] : 'php';

        $data['sig']     = Sailthru::get_signature_hash($data, $this->_config['secret']);

        $result          = $this->http_request("{$this->_config['api_uri']}/$action", $data, $method);

        $unserialized    = @unserialize($result);

        return $unserialized ? $unserialized : $result;
    }

    /**
     * Perform an HTTP request, checking for curl extension support
     *
     * @param string $url
     * @param array $data
     * @param array $headers
     * @return string
     */
    function http_request($url, $data, $method = 'POST') {
        if (function_exists('curl_init'))
            return $this->http_request_curl($url, $data, $method);

        return $this->http_request_without_curl($url, $data, $method);
    }

    /**
     * Perform an HTTP request using the curl extension
     *
     * @param string $url
     * @param array $data
     * @param array $headers
     * @return string
     */
    function http_request_curl($url, $data, $method = 'POST') {
        $ch = curl_init();

        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);

            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data, '', '&'));
        } else {
            $url .= '?' . http_build_query($data, '', '&');

            if ($method != 'GET') {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            }
        }

        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_HEADER, false);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $data = curl_exec($ch);

        if ( ! $data)
            throw new HTTP_Exception_500("Bad response received from $url - ".curl_error($ch));

        return $data;
    }

    /**
     * Adapted from: http://netevil.org/blog/2006/nov/http-post-from-php-without-curl
     *
     * @param string $url
     * @param array $data
     * @param array $headers
     * @return string
     */
    function http_request_without_curl($url, $data, $method = 'POST') {
        $params = array('http' => array('method' => $method));

        if ($method == 'POST') {
            $params['http']['content'] = is_array($data) ? http_build_query($data, '', '&') : $data;
        } else {
            $url .= '?' . http_build_query($data, '', '&');
        }

        $params['http']['header'] = "User-Agent: Sailthru API PHP5 Client $this->version " . phpversion() . "\nContent-Type: application/x-www-form-urlencoded";

        $ctx = stream_context_create($params);

        $fp = @fopen($url, 'rb', false, $ctx);

        if ( ! $fp)
            throw new HTTP_Exception_500("Unable to open stream: $url");

        return @stream_get_contents($fp);
    }

    /**
     * Extracts the values of a set of parameters, recursing into nested assoc arrays.
     *
     * @param array $params
     * @param array $values
     */
    static function extract_param_values($params, &$values) {
        foreach ($params as $k => $v) {
            if (is_array($v) || is_object($v)) {
                Sailthru::extract_param_values($v, $values);
            } else {
                $values[] = $v;
            }
        }
    }

    /**
     * Returns the unhashed signature string (secret + sorted list of param values) for an API call.
     *
     * Note that the SORT_STRING option sorts case-insensitive.
     *
     * @param array $params
     * @param string $secret
     * @return string
     */
    static function get_signature_string($params, $secret) {
        $values = array();

        Sailthru::extract_param_values($params, $values);

        sort($values, SORT_STRING);

        return $secret.implode('', $values);
    }

    /**
     * Returns an MD5 hash of the signature string for an API call.
     *
     * This hash should be passed as the 'sig' value with any API request.
     *
     * @param array $params
     * @param string $secret
     * @return string
     */
    static function get_signature_hash($params, $secret) {
        return md5(Sailthru::get_signature_string($params, $secret));
    }

    function get_sender_details($order) {
        $site_id      = Arr::get($this->_config, 'order_site_id');

        $site         = $site_id ? strtolower($order->$site_id) : 'default';

        $default_site = Arr::path($this->_config, "site_information.default", array());

        $this_site    = Arr::path($this->_config, "site_information.$site", array());

        foreach ($default_site as $key => $val) {
            if (empty($this_site[$key])) {
                $this_site[$key] = $val;
            }
        }

        return $this_site;
    }
}