<?php
// app/Middleware/CsrfMiddleware.php

require_once __DIR__ . '/../Core/Middleware.php';
require_once __DIR__ . '/../Core/CSRF.php';
require_once __DIR__ . '/../Core/Response.php';

class CsrfMiddleware implements Middleware {
    public function handle(Request $request, Closure $next) {
        $method = $request->getMethod();

        if (in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
            $token = $request->getCsrfToken();
            if (!CSRF::validate($token)) {
                if ($request->isAjax()) {
                    Response::error('Invalid or expired CSRF token.', null, 403);
                }
                http_response_code(403);
                Response::render('errors/403', ['message' => 'CSRF verification failed. Please refresh and try again.']);
            }
        }

        return $next();
    }
}
