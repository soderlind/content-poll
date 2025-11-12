<?php

declare(strict_types=1);

namespace ContentVote\REST;

use ContentVote\Security\SecurityHelper;

class NonceController
{
    private string $namespace = 'content-vote/v1';

    public function register(): void
    {
        if (! function_exists('register_rest_route')) {
            return;
        }
        add_action('rest_api_init', function () {
            register_rest_route($this->namespace, '/nonce', [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_nonce' ],
                'permission_callback' => '__return_true', // Public nonce for vote action only
            ]);
        });
    }

    public function get_nonce($request)
    {
        return [ 'nonce' => SecurityHelper::create_nonce() ];
    }
}
