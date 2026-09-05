import { ref } from 'vue';
import api from '../utils/api';

export function useWorkflowOptions() {
    const pics = ref([]);
    const teamMembers = ref([]);
    const campaigns = ref([]);
    const loadingOptions = ref(false);

    const fetchWorkflowOptions = async (brandId) => {
        if (!brandId) {
            pics.value = [];
            teamMembers.value = [];
            campaigns.value = [];
            return;
        }

        loadingOptions.value = true;
        try {
            const response = await api.get('/admin/workflow/options', {
                params: { brand_id: brandId },
            });
            pics.value = response.data.data.pics || [];
            teamMembers.value = response.data.data.team_members || [];
            campaigns.value = response.data.data.campaigns || [];
        } finally {
            loadingOptions.value = false;
        }
    };

    return { pics, teamMembers, campaigns, loadingOptions, fetchWorkflowOptions };
}
