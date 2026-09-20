<?php
// app/Middleware/GuestMiddleware.php

require_once __DIR__ . '/../Core/Middleware.php';
require_once __DIR__ . '/../Core/Session.php';
require_once __DIR__ . '/../Core/Response.php';

class GuestMiddleware implements Middleware {
    public function handle(Request $request, Closure $next) {
        Session::start();
        if (Session::has('user_id')) {
            Response::redirect('/dashboard');
        }
        return $next();
    }
}
