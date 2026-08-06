import { ref } from 'vue';
import axios from 'axios';

export function useSecureLink() {
    const loading = ref(false);
    const error = ref(null);
    const secureLink = ref(null);
    const approvalHistories = ref([]);
    const comments = ref([]);

    const fetchPromotionLink = async (promotionId) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await axios.get(`/api/v1/admin/promotions/${promotionId}/secure-link`);
            secureLink.value = response.data.data;
            return response.data.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Gagal mengambil data secure link.';
            return null;
        } finally {
            loading.value = false;
        }
    };

    const generatePromotionLink = async (promotionId, expiresAt = null) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await axios.post(`/api/v1/admin/promotions/${promotionId}/secure-link`, {
                expires_at: expiresAt,
            });
            secureLink.value = response.data.data;
            return response.data.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Gagal membuat secure link.';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const regeneratePromotionLink = async (promotionId) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await axios.put(`/api/v1/admin/promotions/${promotionId}/secure-link/regenerate`);
            secureLink.value = response.data.data;
            return response.data.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Gagal meregenerasi token secure link.';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const revokePromotionLink = async (promotionId) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await axios.delete(`/api/v1/admin/promotions/${promotionId}/secure-link`);
            secureLink.value = response.data.data;
            return response.data.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Gagal menonaktifkan secure link.';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const fetchCampaignLink = async (campaignId) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await axios.get(`/api/v1/admin/campaigns/${campaignId}/secure-link`);
            secureLink.value = response.data.data;
            return response.data.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Gagal mengambil data secure link campaign.';
            return null;
        } finally {
            loading.value = false;
        }
    };

    const generateCampaignLink = async (campaignId, expiresAt = null) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await axios.post(`/api/v1/admin/campaigns/${campaignId}/secure-link`, {
                expires_at: expiresAt,
            });
            secureLink.value = response.data.data;
            return response.data.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Gagal membuat secure link campaign.';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const revokeCampaignLink = async (campaignId) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await axios.delete(`/api/v1/admin/campaigns/${campaignId}/secure-link`);
            secureLink.value = response.data.data;
            return response.data.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Gagal menonaktifkan secure link campaign.';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const fetchPromotionHistories = async (promotionId) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await axios.get(`/api/v1/admin/promotions/${promotionId}/approval-histories`);
            approvalHistories.value = response.data.data || [];
            return approvalHistories.value;
        } catch (err) {
            error.value = err.response?.data?.message || 'Gagal mengambil riwayat approval.';
            return [];
        } finally {
            loading.value = false;
        }
    };

    const postPromotionComment = async (promotionId, body) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await axios.post(`/api/v1/admin/promotions/${promotionId}/comments`, {
                body: body,
            });
            return response.data.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Gagal mengirim komentar.';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const postCampaignComment = async (campaignId, body) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await axios.post(`/api/v1/admin/campaigns/${campaignId}/comments`, {
                body: body,
            });
            return response.data.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Gagal mengirim komentar.';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    return {
        loading,
        error,
        secureLink,
        approvalHistories,
        comments,
        fetchPromotionLink,
        generatePromotionLink,
        regeneratePromotionLink,
        revokePromotionLink,
        fetchCampaignLink,
        generateCampaignLink,
        revokeCampaignLink,
        fetchPromotionHistories,
        postPromotionComment,
        postCampaignComment,
    };
}