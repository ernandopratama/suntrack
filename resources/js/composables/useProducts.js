import { ref } from 'vue';
import api from '../utils/api';

export function useProducts() {
    const products = ref([]);
    const product = ref(null);
    const variants = ref([]);
    const loading = ref(false);
    const error = ref(null);
    const pagination = ref({ current_page: 1, last_page: 1, total: 0 });

    const fetchProducts = async (params = {}) => {
        loading.value = true;
        error.value = null;
        try {
            const res = await api.get('/admin/products', { params });
            if (res.data.success) {
                products.value = res.data.data.products.data;
                const meta = res.data.data.products.meta || {};
                pagination.value = {
                    current_page: meta.current_page || 1,
                    last_page: meta.last_page || 1,
                    total: meta.total || 0,
                };
            }
        } catch (e) {
            error.value = e.response?.data?.message || 'Error fetching products';
        } finally {
            loading.value = false;
        }
    };

    const fetchProduct = async (id) => {
        loading.value = true;
        error.value = null;
        try {
            const res = await api.get(`/admin/products/${id}`);
            if (res.data.success) product.value = res.data.data.product;
        } catch (e) {
            error.value = e.response?.data?.message || 'Error fetching product';
        } finally {
            loading.value = false;
        }
    };

    const createProduct = async (data) => {
        loading.value = true; error.value = null;
        try {
            const res = await api.post('/admin/products', data);
            return res.data.success ? res.data.data.product : false;
        } catch (e) {
            error.value = e.response?.data?.errors || e.response?.data?.message || 'Error';
            return false;
        } finally { loading.value = false; }
    };

    const updateProduct = async (id, data) => {
        loading.value = true; error.value = null;
        try {
            const res = await api.put(`/admin/products/${id}`, data);
            return res.data.success ? res.data.data.product : false;
        } catch (e) {
            error.value = e.response?.data?.errors || e.response?.data?.message || 'Error';
            return false;
        } finally { loading.value = false; }
    };

    const deleteProduct = async (id) => {
        loading.value = true; error.value = null;
        try {
            const res = await api.delete(`/admin/products/${id}`);
            return true;
        } catch (e) {
            error.value = e.response?.data?.message || 'Error deleting product';
            return false;
        } finally { loading.value = false; }
    };

    // Variants
    const fetchVariants = async (productId, params = {}) => {
        loading.value = true; error.value = null;
        try {
            const res = await api.get(`/admin/products/${productId}/variants`, { params });
            if (res.data.success) {
                variants.value = res.data.data.variants.data || [];
            }
        } catch (e) {
            error.value = e.response?.data?.message || 'Error fetching variants';
        } finally { loading.value = false; }
    };

    const createVariant = async (productId, data) => {
        loading.value = true; error.value = null;
        try {
            const res = await api.post(`/admin/products/${productId}/variants`, data);
            return res.data.success ? res.data.data.variant : false;
        } catch (e) {
            error.value = e.response?.data?.errors || 'Error creating variant';
            return false;
        } finally { loading.value = false; }
    };

    const updateVariant = async (productId, variantId, data) => {
        loading.value = true; error.value = null;
        try {
            const res = await api.put(`/admin/products/${productId}/variants/${variantId}`, data);
            return res.data.success ? res.data.data.variant : false;
        } catch (e) {
            error.value = e.response?.data?.errors || e.response?.data?.message || 'Error updating variant';
            return false;
        } finally { loading.value = false; }
    };

    const deleteVariant = async (productId, variantId) => {
        loading.value = true; error.value = null;
        try {
            const res = await api.delete(`/admin/products/${productId}/variants/${variantId}`);
            return res.data.success;
        } catch (e) {
            error.value = e.response?.data?.message || 'Error deleting variant';
            return false;
        } finally { loading.value = false; }
    };

    // Promotion Variant Mapping
    const fetchPromotionVariants = async (promotionId, params = {}) => {
        loading.value = true; error.value = null;
        try {
            const res = await api.get(`/admin/promotions/${promotionId}/variants`, { params });
            if (res.data.success) {
                variants.value = res.data.data.variants.data || [];
            }
        } catch (e) {
            error.value = e.response?.data?.message || 'Error fetching promotion variants';
        } finally { loading.value = false; }
    };

    const addVariantToPromotion = async (promotionId, data) => {
        loading.value = true; error.value = null;
        try {
            const res = await api.post(`/admin/promotions/${promotionId}/variants`, data);
            return res.data.success ? res.data : false;
        } catch (e) {
            error.value = e.response?.data?.errors || e.response?.data?.message || 'Error';
            return false;
        } finally { loading.value = false; }
    };

    const removeVariantFromPromotion = async (promotionId, variantId) => {
        loading.value = true; error.value = null;
        try {
            const res = await api.delete(`/admin/promotions/${promotionId}/variants/${variantId}`);
            return res.data.success;
        } catch (e) {
            error.value = e.response?.data?.message || 'Error';
            return false;
        } finally { loading.value = false; }
    };

    const bulkDeleteProducts = async (ids) => {
        loading.value = true; error.value = null;
        try {
            const res = await api.post('/admin/products/bulk-delete', { ids });
            return res.data.success;
        } catch (e) {
            error.value = e.response?.data?.message || 'Error bulk deleting products';
            return false;
        } finally { loading.value = false; }
    };

    return {
        products, product, variants, loading, error, pagination,
        fetchProducts, fetchProduct, createProduct, updateProduct, deleteProduct, bulkDeleteProducts,
        fetchVariants, createVariant, updateVariant, deleteVariant,
        fetchPromotionVariants, addVariantToPromotion, removeVariantFromPromotion,
    };
}
