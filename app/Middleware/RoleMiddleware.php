<?php
// app/Middleware/RoleMiddleware.php

require_once __DIR__ . '/../Core/Middleware.php';
require_once __DIR__ . '/../Core/Session.php';
require_once __DIR__ . '/../Core/Response.php';

class RoleMiddleware implements Middleware {
    private string $requiredRole;

    public function __construct(string $requiredRole = 'admin') {
        $this->requiredRole = $requiredRole;
    }

    public function handle(Request $request, Closure $next) {
        Session::start();
        $userRole = Session::get('role');

        if ($userRole !== $this->requiredRole) {
            if ($request->isAjax()) {
                Response::error('Forbidden: You do not have permission for this action.', null, 403);
            }
            http_response_code(403);
            Response::render('errors/403', ['message' => 'Administrative privileges required.']);
        }

        return $next();
    }
}
