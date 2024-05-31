<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User as User_;
use App\Services\JWT\JWT;
use Homeleon\Http\Request;
use ReCaptcha\ReCaptcha;

class LoginController extends Controller
{
    public function wsToken(Request $request, JWT $jwt)
    {
        try {
            $user = $jwt->decode($request->get('jwt'));
        } catch (\Exception $e) {
            return;
        }
        if (isProd()) {
            usleep(500000);
            echo generateToken((int)$user->userId);
        } else {
            echo generateToken((int)($request->get('id') ?? $user->userId));
        }
    }

    public function forcedLogin(Request $request, JWT $jwt)
    {
        if (is_array($recaptchaVerifyResult = $this->recaptchaVerify($request))) {
            return $recaptchaVerifyResult;
        }

        if (isProd()) {
            $userId = User_::create();
        } else {
            $userId = $request->get('id') ?? 1;
            if (is_null(User_::find($userId))) {
                return ['errors' => 'User with id ' . $userId . ' not found.'];
            }
        }

        $jwtToken = $jwt->encode(['userId' => $userId]);
        return ['jwt' => $jwtToken];
    }

    private function recaptchaVerify(Request $request): array|bool
    {
        $host = parse_url($_SERVER["HTTP_ORIGIN"] ?? $_SERVER["HTTP_REFERER"] ?? $_SERVER["SERVER_NAME"], PHP_URL_HOST);
        $recaptcha = new ReCaptcha(config('recaptcha_secret_key'));
        $resp = $recaptcha->setExpectedHostname($host)
            ->verify($request->get('g-recaptcha-response'), $_SERVER["REMOTE_ADDR"]);
        if (!$resp->isSuccess()) {
            $errors = $resp->getErrorCodes();
            return ['errors' => $errors];
        }

        return true;
    }
}
