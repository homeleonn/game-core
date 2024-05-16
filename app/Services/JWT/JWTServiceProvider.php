<?php

namespace App\Services\JWT;

use App\Http\Providers\AppServiceProvider;
use Firebase\JWT\Key;

class JWTServiceProvider extends AppServiceProvider
{
    public function register()
    {
        $this->app->set(JWT::class, function ($app) {
            $JWT_config = $this->app->make('config')->get('JWT');
            return new JWT(
                $JWT_config['secret'],
                $JWT_config['algorithm']
            );
        });
    }
}
