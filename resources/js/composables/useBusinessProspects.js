import { reactive, ref } from 'vue';
import api from '../utils/api';

export function useBusinessProspects() {
    const prospects = ref([]);
    const options = ref({ statuses: [], conversion_statuses: [], pics: [], companies: [], brands: [] });
    const loading = ref(false);
    const pagination = reactive({ current_page: 1, last_page: 1, per_page: 10, total: 0 });

    const fetchProspects = async (filters = {}) => {
        loading.value = true;
        try {
            const response = await api.get('/admin/business-prospects', { params: { ...filters, page: pagination.current_page, per_page: pagination.per_page } });
            const result = response.data.data.prospects;
            prospects.value = result.data || [];
            Object.assign(pagination, { current_page: result.current_page || 1, last_page: result.last_page || 1, per_page: result.per_page || pagination.per_page, total: result.total || 0 });
        } finally {
            loading.value = false;
        }
    };

    const fetchOptions = async () => {
        const response = await api.get('/admin/business-prospects/options');
        options.value = response.data.data;
    };

    return {
        prospects, options, loading, pagination, fetchProspects, fetchOptions,
        createProspect: (payload) => api.post('/admin/business-prospects', payload),
        updateProspect: (id, payload) => api.put(`/admin/business-prospects/${id}`, payload),
        deleteProspect: (id) => api.delete(`/admin/business-prospects/${id}`),
        restoreProspect: (id) => api.post(`/admin/business-prospects/${id}/restore`),
        requestConversion: (id) => api.post(`/admin/business-prospects/${id}/request-conversion`),
        approveConversion: (id, payload) => api.post(`/admin/business-prospects/${id}/approve-conversion`, payload),
        rejectConversion: (id, reason) => api.post(`/admin/business-prospects/${id}/reject-conversion`, { reason }),
        previewImport: (file) => {
            const data = new FormData();
            data.append('file', file);
            return api.post('/admin/business-prospects/import/preview', data);
        },
        confirmImport: (rows) => api.post('/admin/business-prospects/import', { rows }),
    };
}
