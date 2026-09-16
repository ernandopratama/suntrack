import { ref } from 'vue';
import api from '../utils/api';

export function usePerformanceReports() {
    const reports = ref([]);
    const reportOptions = ref({ brands: [], report_types: ['daily', 'weekly', 'monthly'] });
    const pagination = ref({ current_page: 1, last_page: 1, total: 0, per_page: 15 });
    const loading = ref(false);
    const error = ref(null);

    const captureError = (exception, fallback) => {
        error.value = exception.response?.data?.errors || exception.response?.data?.message || fallback;
    };

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
            captureError(exception, 'Laporan tidak dapat dimuat.');
        } finally {
            loading.value = false;
        }
    };

    const fetchReport = async (id) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await api.get(`/admin/performance-reports/${id}`);
            return response.data.data.report;
        } catch (exception) {
            captureError(exception, 'Detail laporan tidak dapat dimuat.');
            return null;
        } finally {
            loading.value = false;
        }
    };

    const fetchReportOptions = async () => {
        try {
            const response = await api.get('/admin/performance-reports/options');
            reportOptions.value = response.data.data;
        } catch (exception) {
            captureError(exception, 'Pilihan brand tidak dapat dimuat.');
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
            captureError(exception, 'Laporan tidak dapat disimpan.');
            return null;
        } finally {
            loading.value = false;
        }
    };

    const publishReport = async (id) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await api.post(`/admin/performance-reports/${id}/publish`);
            return response.data.data.report;
        } catch (exception) {
            captureError(exception, 'Laporan tidak dapat dipublikasikan.');
            return null;
        } finally {
            loading.value = false;
        }
    };

    const uploadMedia = async (reportId, item) => {
        const payload = new FormData();
        payload.append('image', item.file);
        payload.append('title', item.title || '');
        payload.append('notes', item.notes || '');
        payload.append('sort_order', String(item.sort_order || 0));
        const response = await api.post(`/admin/performance-reports/${reportId}/media`, payload);
        return response.data.data.media;
    };

    const updateMedia = async (reportId, item) => {
        const response = await api.patch(`/admin/performance-reports/${reportId}/media/${item.id}`, {
            title: item.title || null,
            notes: item.notes || null,
            sort_order: item.sort_order || 0,
        });
        return response.data.data.media;
    };

    const deleteMedia = async (reportId, mediaId) => {
        await api.delete(`/admin/performance-reports/${reportId}/media/${mediaId}`);
    };

    const uploadAttachments = async (reportId, files) => {
        const payload = new FormData();
        files.forEach(file => payload.append('files[]', file));
        const response = await api.post(`/admin/performance-reports/${reportId}/attachments`, payload);
        return response.data.data.attachments;
    };

    const deleteAttachment = async (reportId, attachmentId) => {
        await api.delete(`/admin/performance-reports/${reportId}/attachments/${attachmentId}`);
    };

    const deleteReport = async (id) => {
        try {
            await api.delete(`/admin/performance-reports/${id}`);
            return true;
        } catch (exception) {
            captureError(exception, 'Laporan tidak dapat dihapus.');
            return false;
        }
    };

    return {
        reports, reportOptions, pagination, loading, error, fetchReports, fetchReport, fetchReportOptions,
        saveReport, publishReport, uploadMedia, updateMedia, deleteMedia, uploadAttachments, deleteAttachment, deleteReport,
    };
}
