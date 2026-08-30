<?php

declare(strict_types=1);

/**
 * Exemple d'intégration minimale dans AuthController::login().
 *
 * Nécessite AuthExtraServiceProvider + (optionnel) migration remember_tokens.
 */

use Astral\AuthExtra\Logging\LoginAttemptLogger;
use Astral\AuthExtra\RememberMe\RememberMeManager;
use Astral\AuthExtra\Throttle\LoginThrottler;
use Core\Http\Response;

/*
public function login(
    LoginThrottler $throttler,
    LoginAttemptLogger $attempts,
    RememberMeManager $remember,
): Response {
    $email = (string) ($this->request->post()['email'] ?? '');
    $ip    = (string) ($_SERVER['REMOTE_ADDR'] ?? '');

    if ($throttler->tooManyAttempts($email, $ip)) {
        return $this->render('auth/login', [
            'errors' => ['email' => [sprintf(
                'Trop de tentatives. Réessayez dans %d seconde(s).',
                $throttler->availableIn($email, $ip)
            )]],
            'old' => ['email' => $email],
        ]);
    }

    $user = $this->userDao->authenticate($email, $password);
    if ($user === null) {
        $throttler->hit($email, $ip);
        $attempts->failure($email, $ip);
        // …
    }

    $throttler->clear($email, $ip);
    $attempts->success($email, $ip, (int) $user->id);
    $this->auth->login($user);

    $response = $this->redirect('/');

    if (!empty($this->request->post()['remember'])) {
        $token = $remember->create((int) $user->id);
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        $response->addHeader(
            'Set-Cookie',
            $remember->buildSetCookieHeader($token['cookie_value'], $token['expires_at'], $secure)
        );
    }

    return $response;
}
*/
