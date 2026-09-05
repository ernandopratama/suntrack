import { ref, onUnmounted } from 'vue';
import api from '../utils/api';

export function useDashboard() {
  const stats = ref(null);
  const loading = ref(false);
  const error = ref(null);
  const lastRefreshed = ref(null);
  let refreshTimer = null;

  const defaultStats = {
    kpi: {
      campaigns: { active: 0, total: 0, completed: 0 },
      promotions: { active: 0, total: 0, pending: 0, approved: 0, partially_approved: 0, rejected: 0 },
      catalog: { total_products: 0, total_variants: 0, total_secure_links: 0, total_brand_reviews: 0 },
      tasks: { total: 0, open: 0, urgent: 0, waiting_review: 0, overdue: 0, completed: 0 },
      performance_reports: { total: 0, draft: 0, waiting_review: 0, approved: 0, published: 0 },
      extended: { approval_rate: 0 },
    },
    deadlines: {
      today: [],
      tomorrow: [],
      next_7_days: [],
      overdue: [],
      expiring_links: [],
    },
    recent_activities: [],
    server_time: null,
  };

  /**
   * Fetch aggregated statistics from the Dashboard Operational Command Center API.
   */
  const fetchStats = async () => {
    loading.value = true;
    error.value = null;
    try {
      const res = await api.get('/admin/dashboard/stats');
      const dashboard = res.data?.data?.dashboard ?? res.data?.dashboard ?? res.data;
      stats.value = {
        kpi: {
          campaigns: { ...defaultStats.kpi.campaigns, ...(dashboard?.kpi?.campaigns || {}) },
          promotions: { ...defaultStats.kpi.promotions, ...(dashboard?.kpi?.promotions || {}) },
          catalog: { ...defaultStats.kpi.catalog, ...(dashboard?.kpi?.catalog || {}) },
          tasks: { ...defaultStats.kpi.tasks, ...(dashboard?.kpi?.tasks || {}) },
          performance_reports: { ...defaultStats.kpi.performance_reports, ...(dashboard?.kpi?.performance_reports || {}) },
          extended: { ...defaultStats.kpi.extended, ...(dashboard?.kpi?.extended || {}) },
        },
        deadlines: { ...defaultStats.deadlines, ...(dashboard?.deadlines || {}) },
        recent_activities: dashboard?.recent_activities || defaultStats.recent_activities,
        server_time: dashboard?.server_time || defaultStats.server_time,
      };
      lastRefreshed.value = new Date();
    } catch (err) {
      error.value = err.response?.data?.message || err.message || 'Failed to load dashboard statistics.';
    } finally {
      loading.value = false;
    }
  };

  /**
   * Start auto-refresh polling (Refinement #2).
   * Default interval is 60 seconds.
   */
  const startAutoRefresh = (intervalSeconds = 60) => {
    stopAutoRefresh();
    refreshTimer = setInterval(() => {
      fetchStats();
    }, intervalSeconds * 1000);
  };

  /**
   * Stop auto-refresh polling.
   */
  const stopAutoRefresh = () => {
    if (refreshTimer) {
      clearInterval(refreshTimer);
      refreshTimer = null;
    }
  };

  /**
   * Trigger report download using ReportingService Adapter (Refinement #6).
   */
  const exportReport = async (type = 'campaign', format = 'csv') => {
    try {
      const res = await api.get(`/admin/dashboard/export?type=${type}&format=${format}`, {
        responseType: 'blob'
      });
      
      const url = window.URL.createObjectURL(new Blob([res.data]));
      const link = document.createElement('a');
      link.href = url;
      const timestamp = new Date().toISOString().slice(0, 19).replace(/[:-]/g, '');
      const ext = format === 'excel' ? 'xls' : (format === 'pdf' ? 'html' : format);
      link.setAttribute('download', `suntrack_${type}_report_${timestamp}.${ext}`);
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.URL.revokeObjectURL(url);
    } catch (err) {
      alert('Gagal mengunduh laporan: ' + (err.response?.data?.message || err.message));
    }
  };

  onUnmounted(() => {
    stopAutoRefresh();
  });

  return {
    stats,
    loading,
    error,
    lastRefreshed,
    fetchStats,
    startAutoRefresh,
    stopAutoRefresh,
    exportReport,
  };
}
