import { ref } from 'vue';
import api from '../utils/api';

export function usePromotions() {
    const promotions = ref([]);
    const promotion = ref(null);
    const loading = ref(false);
    const error = ref(null);
    const pagination = ref({ current_page: 1, last_page: 1, total: 0 });

    const fetchPromotions = async (params = {}) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await api.get('/admin/promotions', { params });
            if (response.data.success) {
                promotions.value = response.data.data.promotions.data;
                pagination.value = {
                    current_page: response.data.data.promotions.current_page,
                    last_page: response.data.data.promotions.last_page,
                    total: response.data.data.promotions.total,
                };
            }
        } catch (e) {
            error.value = e.response?.data?.message || 'Error fetching promotions';
        } finally {
            loading.value = false;
        }
    };

    const fetchPromotion = async (id) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await api.get(`/admin/promotions/${id}`);
            if (response.data.success) {
                promotion.value = response.data.data.promotion;
            }
        } catch (e) {
            error.value = e.response?.data?.message || 'Error fetching promotion';
        } finally {
            loading.value = false;
        }
    };

    const createPromotion = async (data) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await api.post('/admin/promotions', data);
            return response.data.success ? response.data.data.promotion : false;
        } catch (e) {
            error.value = e.response?.data?.errors || e.response?.data?.message || 'Error creating promotion';
            return false;
        } finally {
            loading.value = false;
        }
    };

    const updatePromotion = async (id, data) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await api.put(`/admin/promotions/${id}`, data);
            return response.data.success ? response.data.data.promotion : false;
        } catch (e) {
            error.value = e.response?.data?.errors || e.response?.data?.message || 'Error updating promotion';
            return false;
        } finally {
            loading.value = false;
        }
    };

    const deletePromotion = async (id) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await api.delete(`/admin/promotions/${id}`);
            return true;
        } catch (e) {
            error.value = e.response?.data?.message || 'Error deleting promotion';
            return false;
        } finally {
            loading.value = false;
        }
    };

    return {
        promotions,
        promotion,
        loading,
        error,
        pagination,
        fetchPromotions,
        fetchPromotion,
        createPromotion,
        updatePromotion,
        deletePromotion,
    };
}
