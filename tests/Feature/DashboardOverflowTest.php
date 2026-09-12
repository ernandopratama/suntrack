<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DashboardOverflowTest extends TestCase
{
    public function test_sidebar_closes_automatically_after_configured_delay(): void
    {
        $layout = File::get(resource_path('js/layouts/AdminLayout.vue'));

        $this->assertStringContainsString(
            'const SIDEBAR_AUTO_CLOSE_DELAY_MS = 5000;',
            $layout,
        );
        $this->assertStringContainsString(
            'sidebarAutoCloseTimer = window.setTimeout(() => {',
            $layout,
        );
        $this->assertStringContainsString(
            'sidebarOpen.value = false;',
            $layout,
        );
    }

    public function test_dashboard_layout_owns_vertical_scroll_without_page_overflow(): void
    {
        $layout = File::get(resource_path('js/layouts/AdminLayout.vue'));
        $dashboard = File::get(resource_path('js/pages/Dashboard.vue'));

        $this->assertStringContainsString(
            'fixed inset-0 flex overflow-hidden',
            $layout,
        );
        $this->assertStringContainsString(
            'flex min-w-0 flex-1 flex-col overflow-hidden',
            $layout,
        );
        $this->assertStringContainsString(
            'min-w-0 flex-1 overflow-x-hidden overflow-y-auto',
            $layout,
        );
        $this->assertStringContainsString(
            'pointer-events-none absolute inset-0 overflow-hidden rounded-2xl',
            $dashboard,
        );
    }

    public function test_collapsed_sidebar_shows_immediate_accessible_tooltips(): void
    {
        $layout = File::get(resource_path('js/layouts/AdminLayout.vue'));

        $this->assertStringContainsString('@mouseover="showSidebarTooltip"', $layout);
        $this->assertStringContainsString('@focusin="showSidebarTooltip"', $layout);
        $this->assertStringContainsString('data-sidebar-label="Performance Reports"', $layout);
        $this->assertStringContainsString('data-sidebar-label="Secure Link PMS"', $layout);
        $this->assertStringContainsString('v-if="!sidebarOpen && sidebarTooltip.visible"', $layout);
        $this->assertStringContainsString('role="tooltip"', $layout);
        $this->assertStringContainsString('class="suntrack-sidebar-tooltip"', $layout);
        $this->assertStringContainsString('SIDEBAR_TOOLTIP_THEMES', $layout);
        $this->assertStringContainsString('--tooltip-gradient', $layout);
    }
}
