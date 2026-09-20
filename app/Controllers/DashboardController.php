<?php
// app/Controllers/DashboardController.php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Services/DashboardService.php';

class DashboardController extends BaseController {
    private DashboardService $dashboardService;

    public function __construct() {
        parent::__construct();
        $this->dashboardService = new DashboardService();
    }

    public function index(Request $request): void {
        if ($this->isAdmin()) {
            $metrics = $this->dashboardService->getAdminMetrics();
            $this->render('dashboard/admin', $metrics);
        } else {
            $metrics = $this->dashboardService->getUserMetrics($this->userId());
            $this->render('dashboard/user', $metrics);
        }
    }
}
