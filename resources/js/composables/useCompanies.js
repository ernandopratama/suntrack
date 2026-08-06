import { ref } from 'vue';
import api from '../utils/api';

export function useCompanies() {
    const companies = ref([]);
    const loading = ref(false);
    const error = ref(null);
    const pagination = ref({ current_page: 1, last_page: 1, total: 0, per_page: 15 });

    const fetchCompanies = async (params = {}) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await api.get('/admin/companies', { params });
            if (response.data.success) {
                // Support paginated & non-paginated response
                if (response.data.data.companies?.data) {
                    companies.value = response.data.data.companies.data;
                    pagination.value = {
                        current_page: response.data.data.companies.current_page || 1,
                        last_page: response.data.data.companies.last_page || 1,
                        total: response.data.data.companies.total || 0,
                        per_page: response.data.data.companies.per_page || 15,
                    };
                } else {
                    companies.value = response.data.data.companies;
                }
            }
        } catch (e) {
            error.value = e.response?.data?.message || 'Error fetching companies';
        } finally {
            loading.value = false;
        }
    };

    const createCompany = async (data) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await api.post('/admin/companies', data);
            return response.data.success;
        } catch (e) {
            error.value = e.response?.data?.errors || e.response?.data?.message || 'Error creating company';
            return false;
        } finally {
            loading.value = false;
        }
    };

    const updateCompany = async (id, data) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await api.put(`/admin/companies/${id}`, data);
            return response.data.success;
        } catch (e) {
            error.value = e.response?.data?.errors || e.response?.data?.message || 'Error updating company';
            return false;
        } finally {
            loading.value = false;
        }
    };

    const deleteCompany = async (id) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await api.delete(`/admin/companies/${id}`);
            return true;
        } catch (e) {
            error.value = e.response?.data?.message || 'Error deleting company';
            return false;
        } finally {
            loading.value = false;
        }
    };

    return {
        companies,
        loading,
        error,
        pagination,
        fetchCompanies,
        createCompany,
        updateCompany,
        deleteCompany,
    };
}