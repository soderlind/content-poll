<?php

declare(strict_types=1);

namespace ContentVote\REST;

use ContentVote\Services\AISuggestionService;

class SuggestionController
{
    private string $namespace = 'content-vote/v1';

    public function register(): void
    {
        if (! function_exists('register_rest_route')) {
            return;
        }
        add_action('rest_api_init', function () {
            register_rest_route($this->namespace, '/suggest', [
                'methods'             => 'GET',
                'callback'            => [ $this, 'suggest' ],
                'permission_callback' => function () {
                    return current_user_can('edit_posts');
                },
                'args'                => [
                    'postId' => [ 'type' => 'integer', 'required' => true ],
                    ],
            ]);
        });
    }

    public function suggest($request)
    {
        $post_id = (int) $request->get_param('postId');
        if ($post_id <= 0) {
            return $this->error('invalid_post', 'Invalid post id', 400);
        }
        $post = function_exists('get_post') ? get_post($post_id) : null;
        if (! $post) {
            return $this->error('not_found', 'Post not found', 404);
        }
        $service = new AISuggestionService();
        $result  = $service->suggest($post->post_content ?? '');
        return $result;
    }

    private function error(string $code, string $message, int $status)
    {
        if (class_exists('WP_Error')) {
            return new \WP_Error($code, $message, [ 'status' => $status ]);
        }
        return [ 'error' => true, 'code' => $code, 'message' => $message, 'status' => $status ];
    }
}
