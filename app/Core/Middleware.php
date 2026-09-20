<?php
// app/Core/Middleware.php

interface Middleware {
    public function handle(Request $request, Closure $next);
}
