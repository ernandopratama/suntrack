import { ref } from 'vue';
import api from '../utils/api';

export function usePerformanceReports() {
    const reports = ref([]);
    const pagination = ref({ current_page: 1, last_page: 1, total: 0, per_page: 15 });
    const loading = ref(false);
    const error = ref(null);

    const fetchReports = async (params = {}) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await api.get('/admin/performance-reports', { params });
            const data = response.data.data.reports;
            reports.value = data.data || [];
            pagination.value = {
                current_page: data.current_page || 1,
                last_page: data.last_page || 1,
                total: data.total || 0,
                per_page: data.per_page || 15,
            };
        } catch (exception) {
            error.value = exception.response?.data?.errors || exception.response?.data?.message || 'Unable to load reports.';
        } finally {
            loading.value = false;
        }
    };

    const saveReport = async (id, payload) => {
        loading.value = true;
        error.value = null;
        try {
            const response = id
                ? await api.put(`/admin/performance-reports/${id}`, payload)
                : await api.post('/admin/performance-reports', payload);
            return response.data.data.report;
        } catch (exception) {
            error.value = exception.response?.data?.errors || exception.response?.data?.message || 'Unable to save report.';
            return null;
        } finally {
            loading.value = false;
        }
    };

    const transitionReport = async (id, status, note = null) => {
        try {
            const response = await api.post(`/admin/performance-reports/${id}/transition`, { status, note });
            return response.data.data.report;
        } catch (exception) {
            error.value = exception.response?.data?.errors || exception.response?.data?.message || 'Unable to update report status.';
            return null;
        }
    };

    const deleteReport = async (id) => {
        try {
            await api.delete(`/admin/performance-reports/${id}`);
            return true;
        } catch (exception) {
            error.value = exception.response?.data?.errors || exception.response?.data?.message || 'Unable to delete report.';
            return false;
        }
    };

    const createVersion = async (id) => {
        try {
            const response = await api.post(`/admin/performance-reports/${id}/versions`);
            return response.data.data.report;
        } catch (exception) {
            error.value = exception.response?.data?.errors || exception.response?.data?.message || 'Unable to create report version.';
            return null;
        }
    };

    return { reports, pagination, loading, error, fetchReports, saveReport, transitionReport, deleteReport, createVersion };
}
