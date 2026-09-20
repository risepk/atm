<?php
// app/Middleware/AuthMiddleware.php

require_once __DIR__ . '/../Core/Middleware.php';
require_once __DIR__ . '/../Core/Session.php';
require_once __DIR__ . '/../Core/Response.php';

class AuthMiddleware implements Middleware {
    public function handle(Request $request, Closure $next) {
        Session::start();
        $userId = Session::get('user_id');

        if (!$userId) {
            if ($request->isAjax()) {
                Response::error('Unauthenticated session. Please log in.', null, 401);
            }
            Session::setFlash('error', 'Please log in to continue.');
            Response::redirect('/login');
        }

        return $next();
    }
}
