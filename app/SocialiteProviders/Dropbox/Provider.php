<?php

namespace App\SocialiteProviders\Dropbox;

class Provider extends \SocialiteProviders\Dropbox\Provider
{
    /**
     * {@inheritdoc}
     */
    protected $parameters = [
        'token_access_type' => 'offline',
    ];

    /**
     * {@inheritdoc}
     */
    protected $scopes = [
        // 'account_info.read',
        // 'files.content.read',
        // 'files.content.write',
    ];
}