import { ref } from 'vue';
import api from '../utils/api';

export function useCampaigns() {
    const campaigns = ref([]);
    const campaign = ref(null);
    const loading = ref(false);
    const error = ref(null);
    const pagination = ref({ current_page: 1, last_page: 1, total: 0 });

    const fetchCampaigns = async (params = {}) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await api.get('/admin/campaigns', { params });
            if (response.data.success) {
                campaigns.value = response.data.data.campaigns.data;
                const meta = response.data.data.campaigns.meta || {};
                pagination.value = {
                    current_page: meta.current_page || 1,
                    last_page: meta.last_page || 1,
                    total: meta.total || 0,
                };
            }
        } catch (e) {
            error.value = e.response?.data?.message || 'Error fetching campaigns';
        } finally {
            loading.value = false;
        }
    };

    const fetchCampaign = async (id) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await api.get(`/admin/campaigns/${id}`);
            if (response.data.success) {
                campaign.value = response.data.data.campaign;
            }
        } catch (e) {
            error.value = e.response?.data?.message || 'Error fetching campaign';
        } finally {
            loading.value = false;
        }
    };

    const createCampaign = async (data) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await api.post('/admin/campaigns', data);
            return response.data.success;
        } catch (e) {
            error.value = e.response?.data?.errors || e.response?.data?.message || 'Error creating campaign';
            return false;
        } finally {
            loading.value = false;
        }
    };

    const updateCampaign = async (id, data) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await api.put(`/admin/campaigns/${id}`, data);
            return response.data.success;
        } catch (e) {
            error.value = e.response?.data?.errors || e.response?.data?.message || 'Error updating campaign';
            return false;
        } finally {
            loading.value = false;
        }
    };

    return {
        campaigns,
        campaign,
        loading,
        error,
        pagination,
        fetchCampaigns,
        fetchCampaign,
        createCampaign,
        updateCampaign,
    };
}
