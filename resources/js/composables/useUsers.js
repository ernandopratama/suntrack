import { ref } from 'vue';
import api from '../utils/api';

export function useUsers() {
    const users = ref([]);
    const user = ref(null);
    const loading = ref(false);
    const error = ref(null);
    const pagination = ref({ current_page: 1, last_page: 1, total: 0, per_page: 15 });

    const fetchUsers = async (params = {}) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await api.get('/admin/users', { params });
            if (response.data.success) {
                users.value = response.data.data.users.data;
                const meta = response.data.data.users.meta || {};
                pagination.value = {
                    current_page: meta.current_page || 1,
                    last_page: meta.last_page || 1,
                    total: meta.total || 0,
                    per_page: meta.per_page || 15,
                };
            }
        } catch (e) {
            error.value = e.response?.data?.message || 'Error fetching users';
        } finally {
            loading.value = false;
        }
    };

    const fetchUser = async (id) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await api.get(`/admin/users/${id}`);
            if (response.data.success) {
                user.value = response.data.data.user;
            }
        } catch (e) {
            error.value = e.response?.data?.message || 'Error fetching user';
        } finally {
            loading.value = false;
        }
    };

    const createUser = async (data) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await api.post('/admin/users', data);
            return response.data.success;
        } catch (e) {
            error.value = e.response?.data?.errors || e.response?.data?.message || 'Error creating user';
            return false;
        } finally {
            loading.value = false;
        }
    };

    const updateUser = async (id, data) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await api.put(`/admin/users/${id}`, data);
            return response.data.success;
        } catch (e) {
            error.value = e.response?.data?.errors || e.response?.data?.message || 'Error updating user';
            return false;
        } finally {
            loading.value = false;
        }
    };

    const deleteUser = async (id) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await api.delete(`/admin/users/${id}`);
            return true;
        } catch (e) {
            error.value = e.response?.data?.message || 'Error deleting user';
            return false;
        } finally {
            loading.value = false;
        }
    };

    return {
        users,
        user,
        loading,
        error,
        pagination,
        fetchUsers,
        fetchUser,
        createUser,
        updateUser,
        deleteUser,
    };
}