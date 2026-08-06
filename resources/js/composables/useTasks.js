import { ref } from 'vue';
import api from '../utils/api';

export function useTasks() {
    const tasks = ref([]);
    const task = ref(null);
    const loading = ref(false);
    const error = ref(null);
    const pagination = ref({ current_page: 1, last_page: 1, total: 0, per_page: 15 });

    const fetchTasks = async (params = {}) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await api.get('/admin/tasks', { params });
            if (response.data.success) {
                tasks.value = response.data.data.tasks.data;
                pagination.value = {
                    current_page: response.data.data.tasks.current_page || 1,
                    last_page: response.data.data.tasks.last_page || 1,
                    total: response.data.data.tasks.total || 0,
                    per_page: response.data.data.tasks.per_page || 15,
                };
            }
        } catch (e) {
            error.value = e.response?.data?.message || 'Error fetching tasks';
        } finally {
            loading.value = false;
        }
    };

    const fetchTask = async (id) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await api.get(`/admin/tasks/${id}`);
            if (response.data.success) {
                task.value = response.data.data.task;
            }
        } catch (e) {
            error.value = e.response?.data?.message || 'Error fetching task';
        } finally {
            loading.value = false;
        }
    };

    const createTask = async (data) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await api.post('/admin/tasks', data);
            return response.data.success;
        } catch (e) {
            error.value = e.response?.data?.errors || e.response?.data?.message || 'Error creating task';
            return false;
        } finally {
            loading.value = false;
        }
    };

    const updateTask = async (id, data) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await api.put(`/admin/tasks/${id}`, data);
            return response.data.success;
        } catch (e) {
            error.value = e.response?.data?.errors || e.response?.data?.message || 'Error updating task';
            return false;
        } finally {
            loading.value = false;
        }
    };

    const deleteTask = async (id) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await api.delete(`/admin/tasks/${id}`);
            return true;
        } catch (e) {
            error.value = e.response?.data?.message || 'Error deleting task';
            return false;
        } finally {
            loading.value = false;
        }
    };

    return {
        tasks,
        task,
        loading,
        error,
        pagination,
        fetchTasks,
        fetchTask,
        createTask,
        updateTask,
        deleteTask,
    };
}