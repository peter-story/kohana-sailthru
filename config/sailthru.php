<?php defined('SYSPATH') or die('No direct script access.');

return array(
    'api_uri'          => 'http://api.sailthru.com',
    'version'          => '1.0',
    'api_key'          => 'example_key&*(*^(*^',
    'secret'           => 'example_secret()&*^T^G',
    'order_site_id'    => 'site_id',
    'site_information' => array(
        'default' => array(
            'icon_file'      => 'example_icon.jpg',
            'name'           => 'Example Title',
            'site_url'       => 'http://www.example.com/',
            'reply_email'    => 'service@example.com',
            'email_template' => 'default_template'
        )
    )
);