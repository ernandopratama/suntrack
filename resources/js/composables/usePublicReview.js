import { ref, reactive } from 'vue';
import axios from 'axios';

export function usePublicReview() {
    const loading = ref(false);
    const error = ref(null);
    const reviewData = ref(null);
    const linkStatus = ref('Active'); // Active, Expired, Revoked, Not Found
    const errorMessage = ref('');

    // Cached reviewer identity in localStorage
    const reviewerIdentity = reactive({
        name: localStorage.getItem('suntrack_reviewer_name') || '',
        position: localStorage.getItem('suntrack_reviewer_position') || '',
        companyName: localStorage.getItem('suntrack_reviewer_company') || '',
        whatsappNumber: localStorage.getItem('suntrack_reviewer_whatsapp') || '',
    });

    const isIdentified = () => {
        return Boolean(reviewerIdentity.name && reviewerIdentity.name.trim() !== '');
    };

    const saveIdentity = async (token, identityData) => {
        loading.value = true;
        error.value = null;
        try {
            localStorage.setItem('suntrack_reviewer_name', identityData.name);
            localStorage.setItem('suntrack_reviewer_position', identityData.position || '');
            localStorage.setItem('suntrack_reviewer_company', identityData.companyName || '');
            localStorage.setItem('suntrack_reviewer_whatsapp', identityData.whatsappNumber || '');

            reviewerIdentity.name = identityData.name;
            reviewerIdentity.position = identityData.position || '';
            reviewerIdentity.companyName = identityData.companyName || '';
            reviewerIdentity.whatsappNumber = identityData.whatsappNumber || '';

            await axios.post(`/api/v1/public/review/${token}/identify`, {
                reviewer_name: identityData.name,
                reviewer_position: identityData.position,
                company_name: identityData.companyName,
                whatsapp_number: identityData.whatsappNumber,
            });
            return true;
        } catch (err) {
            error.value = err.response?.data?.message || 'Gagal menyimpan identitas reviewer.';
            return false;
        } finally {
            loading.value = false;
        }
    };

    const fetchReviewData = async (token) => {
        loading.value = true;
        error.value = null;
        linkStatus.value = 'Active';
        try {
            const response = await axios.get(`/api/v1/public/review/${token}`);
            reviewData.value = response.data.data;
            return response.data.data;
        } catch (err) {
            if (err.response) {
                linkStatus.value = err.response.data?.status || (err.response.status === 404 ? 'Not Found' : 'Error');
                errorMessage.value = err.response.data?.message || 'Tautan tidak dapat diakses.';
            } else {
                linkStatus.value = 'Error';
                errorMessage.value = 'Terjadi kesalahan jaringan.';
            }
            return null;
        } finally {
            loading.value = false;
        }
    };

    const submitApproval = async (token, variantId, status, rejectionNotes = '') => {
        loading.value = true;
        error.value = null;
        try {
            const response = await axios.post(`/api/v1/public/review/${token}/approval`, {
                variant_id: variantId,
                status: status,
                rejection_notes: rejectionNotes,
                reviewer_name: reviewerIdentity.name,
                reviewer_position: reviewerIdentity.position,
                company_name: reviewerIdentity.companyName,
                whatsapp_number: reviewerIdentity.whatsappNumber,
            });
            reviewData.value = response.data.data;
            return response.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Gagal memperbarui status approval.';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const submitComment = async (token, body) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await axios.post(`/api/v1/public/review/${token}/comment`, {
                body: body,
                author_name: reviewerIdentity.name,
                author_position: reviewerIdentity.position,
                author_type: 'Brand',
            });
            if (reviewData.value) {
                reviewData.value.comments.push(response.data.data);
            }
            return response.data.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Gagal mengirim komentar.';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const submitBatchApproval = async (token, action, variantIds = [], rejectionNotes = '') => {
        loading.value = true;
        error.value = null;
        try {
            const response = await axios.post(`/api/v1/public/review/${token}/batch-approval`, {
                action: action,
                variant_ids: variantIds,
                rejection_notes: rejectionNotes,
                reviewer_name: reviewerIdentity.name,
                reviewer_position: reviewerIdentity.position,
                company_name: reviewerIdentity.companyName,
                whatsapp_number: reviewerIdentity.whatsappNumber,
            });
            reviewData.value = response.data.data;
            return response.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Gagal memproses batch approval.';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const updateTaskProgress = async (token, taskId, progressStatus) => {
        error.value = null;
        loading.value = true;
        try {
            const response = await axios.post(`/api/v1/public/review/${token}/tasks/${taskId}/progress`, {
                progress_status: progressStatus,
                reviewer_name: reviewerIdentity.name,
                reviewer_position: reviewerIdentity.position,
                company_name: reviewerIdentity.companyName,
                whatsapp_number: reviewerIdentity.whatsappNumber,
            });
            if (reviewData.value?.tasks) {
                const idx = reviewData.value.tasks.findIndex(t => t.id === taskId);
                if (idx !== -1) reviewData.value.tasks[idx] = response.data.data;
            }
            return response.data;
        } catch (err) {
            // Log full response for debugging and set a helpful message
            console.error('updateTaskProgress error:', err.response || err);
            error.value = err.response?.data?.message || err.message || 'Gagal memperbarui status task.';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const submitTaskVisual = async (token, taskId, formData) => {
        error.value = null;
        try {
            const response = await axios.post(
                `/api/v1/public/review/${token}/tasks/${taskId}/visual`,
                formData,
                { headers: { 'Content-Type': 'multipart/form-data' } }
            );
            if (reviewData.value?.tasks) {
                const idx = reviewData.value.tasks.findIndex(t => t.id === taskId);
                if (idx !== -1) reviewData.value.tasks[idx] = response.data.data;
            }
            return response.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Gagal mengirim visual task.';
            throw err;
        }
    };

    const deleteTaskVisual = async (token, taskId) => {
        error.value = null;
        loading.value = true;
        try {
            const response = await axios.delete(`/api/v1/public/review/${token}/tasks/${taskId}/visual`);
            if (reviewData.value?.tasks) {
                const idx = reviewData.value.tasks.findIndex(t => t.id === taskId);
                if (idx !== -1) reviewData.value.tasks[idx] = response.data.data;
            }
            return response.data;
        } catch (err) {
            console.error('deleteTaskVisual error:', err.response || err);
            error.value = err.response?.data?.message || 'Gagal menghapus visual task.';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    return {
        loading,
        error,
        reviewData,
        linkStatus,
        errorMessage,
        reviewerIdentity,
        isIdentified,
        saveIdentity,
        fetchReviewData,
        submitApproval,
        submitBatchApproval,
        submitComment,
        updateTaskProgress,
        submitTaskVisual,
        deleteTaskVisual,
    };
}
