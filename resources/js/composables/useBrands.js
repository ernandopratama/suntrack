import { ref } from 'vue';
import api from '../utils/api';

export function useBrands() {
    const brands = ref([]);
    const brand = ref(null);
    const loading = ref(false);
    const error = ref(null);
    const pagination = ref({ current_page: 1, last_page: 1, total: 0 });

    const fetchBrands = async (params = {}) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await api.get('/admin/brands', { params });
            if (response.data.success) {
                brands.value = response.data.data.brands.data;
                const meta = response.data.data.brands.meta || {};
                pagination.value = {
                    current_page: meta.current_page || 1,
                    last_page: meta.last_page || 1,
                    total: meta.total || 0,
                    per_page: meta.per_page || 15,
                };
            }
        } catch (e) {
            error.value = e.response?.data?.message || 'Error fetching brands';
        } finally {
            loading.value = false;
        }
    };

    const fetchBrand = async (id) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await api.get(`/admin/brands/${id}`);
            if (response.data.success) {
                brand.value = response.data.data.brand;
            }
        } catch (e) {
            error.value = e.response?.data?.message || 'Error fetching brand';
        } finally {
            loading.value = false;
        }
    };

    const createBrand = async (data) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await api.post('/admin/brands', data);
            return response.data.success;
        } catch (e) {
            error.value = e.response?.data?.errors || e.response?.data?.message || 'Error creating brand';
            return false;
        } finally {
            loading.value = false;
        }
    };

    const updateBrand = async (id, data) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await api.put(`/admin/brands/${id}`, data);
            return response.data.success;
        } catch (e) {
            error.value = e.response?.data?.errors || e.response?.data?.message || 'Error updating brand';
            return false;
        } finally {
            loading.value = false;
        }
    };

    const deleteBrand = async (id) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await api.delete(`/admin/brands/${id}`);
            return response.data.success;
        } catch (e) {
            error.value = e.response?.data?.message || 'Error deleting brand';
            return false;
        } finally {
            loading.value = false;
        }
    };

    return {
        brands,
        brand,
        loading,
        error,
        pagination,
        fetchBrands,
        fetchBrand,
        createBrand,
        updateBrand,
        deleteBrand,
    };
}