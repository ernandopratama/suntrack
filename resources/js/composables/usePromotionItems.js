import { ref } from 'vue';
import api from '../utils/api';

export function usePromotionItems() {
  const items = ref([]);
  const preview = ref(null);
  const loading = ref(false);
  const error = ref(null);

  const fetchItems = async (promotionId) => {
    loading.value = true;
    error.value = null;
    try {
      const response = await api.get(`/admin/promotions/${promotionId}/items`);
      items.value = response.data.data.items || [];
      return items.value;
    } catch (exception) {
      error.value = exception.response?.data?.message || 'Data produk promosi gagal dimuat.';
      return false;
    } finally {
      loading.value = false;
    }
  };

  const downloadTemplate = async (promotionId) => {
    loading.value = true;
    error.value = null;
    try {
      const response = await api.get(`/admin/promotions/${promotionId}/items/template`, {
        responseType: 'blob',
      });
      const url = URL.createObjectURL(response.data);
      const anchor = document.createElement('a');
      anchor.href = url;
      anchor.download = 'template-data-promosi.xlsx';
      document.body.appendChild(anchor);
      anchor.click();
      anchor.remove();
      URL.revokeObjectURL(url);
      return true;
    } catch (exception) {
      error.value = exception.response?.data?.message || 'Template Excel gagal diunduh.';
      return false;
    } finally {
      loading.value = false;
    }
  };

  const previewImport = async (promotionId, file) => {
    loading.value = true;
    error.value = null;
    preview.value = null;
    try {
      const formData = new FormData();
      formData.append('file', file);
      const response = await api.post(
        `/admin/promotions/${promotionId}/items/import/preview`,
        formData,
        { headers: { 'Content-Type': 'multipart/form-data' } },
      );
      preview.value = response.data.data;
      return preview.value;
    } catch (exception) {
      error.value = exception.response?.data?.message || 'File Excel gagal diperiksa.';
      preview.value = exception.response?.data?.errors || null;
      return false;
    } finally {
      loading.value = false;
    }
  };

  const importItems = async (promotionId, file) => {
    loading.value = true;
    error.value = null;
    try {
      const formData = new FormData();
      formData.append('file', file);
      const response = await api.post(
        `/admin/promotions/${promotionId}/items/import`,
        formData,
        { headers: { 'Content-Type': 'multipart/form-data' } },
      );
      items.value = response.data.data.items || [];
      preview.value = null;
      return response.data.data;
    } catch (exception) {
      error.value = exception.response?.data?.message || 'Data produk promosi gagal diimpor.';
      return false;
    } finally {
      loading.value = false;
    }
  };

  const updateItem = async (promotionId, itemId, payload) => {
    loading.value = true;
    error.value = null;
    try {
      const response = await api.put(`/admin/promotions/${promotionId}/items/${itemId}`, payload);
      items.value = response.data.data.items || [];
      return response.data.data.item;
    } catch (exception) {
      error.value = exception.response?.data?.errors || exception.response?.data?.message || 'Produk gagal diperbarui.';
      return false;
    } finally {
      loading.value = false;
    }
  };

  const deleteItem = async (promotionId, itemId) => {
    loading.value = true;
    error.value = null;
    try {
      const response = await api.delete(`/admin/promotions/${promotionId}/items/${itemId}`);
      items.value = response.data.data.items || [];
      return true;
    } catch (exception) {
      error.value = exception.response?.data?.message || 'Produk gagal dihapus.';
      return false;
    } finally {
      loading.value = false;
    }
  };

  return {
    items,
    preview,
    loading,
    error,
    fetchItems,
    downloadTemplate,
    previewImport,
    importItems,
    updateItem,
    deleteItem,
  };
}
