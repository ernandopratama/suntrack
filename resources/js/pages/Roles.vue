<template>
  <div class="space-y-6">
    <header class="rounded-3xl border border-default bg-surface p-6 shadow-sm sm:p-8">
      <div class="flex items-center gap-3">
        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-soft text-brand-strong">
          <i class="fa-solid fa-user-shield"></i>
        </div>
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-content">Role & Permission</h1>
          <p class="mt-1 text-sm text-content-soft">
            Kelola permission tiga role final dan lihat user pada setiap role.
          </p>
        </div>
      </div>
    </header>

    <div v-if="errorMessage" class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ errorMessage }}
    </div>

    <section class="overflow-hidden rounded-3xl border border-default bg-surface shadow-sm">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-default">
          <thead class="bg-surface-muted">
            <tr>
              <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-content-muted">Role</th>
              <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-content-muted">Permission Role</th>
              <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-content-muted">User Aktif</th>
              <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-content-muted">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-default">
            <tr v-if="loading">
              <td colspan="4" class="px-5 py-12 text-center text-sm text-content-muted">Memuat role...</td>
            </tr>
            <tr v-for="role in roles" v-else :key="role.id" class="hover:bg-surface-muted">
              <td class="px-5 py-4">
                <div class="flex items-center gap-3">
                  <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-soft text-brand-strong">
                    <i :class="roleIcon(role.name)"></i>
                  </div>
                  <div>
                    <p class="text-sm font-semibold text-content">{{ role.name }}</p>
                    <p class="mt-0.5 text-xs text-content-muted">{{ roleDescription(role.name) }}</p>
                  </div>
                </div>
              </td>
              <td class="px-5 py-4">
                <span class="text-sm font-semibold text-content">{{ role.permissions.length }}</span>
                <span class="ml-1 text-xs text-content-muted">permission aktif</span>
              </td>
              <td class="px-5 py-4">
                <button
                  type="button"
                  class="inline-flex items-center gap-2 rounded-xl border border-default bg-surface px-3 py-2 text-sm font-semibold text-content-soft hover:bg-surface-muted hover:text-brand"
                  @click="openUsers(role)"
                >
                  <i class="fa-solid fa-users text-xs"></i>
                  {{ role.users_count }} user
                </button>
              </td>
              <td class="px-5 py-4">
                <div class="flex justify-end gap-2">
                  <button
                    type="button"
                    class="flex h-9 w-9 items-center justify-center rounded-xl border border-default text-content-soft hover:bg-surface-muted hover:text-brand"
                    title="Lihat user pemilik role"
                    @click="openUsers(role)"
                  >
                    <i class="fa-solid fa-eye text-xs"></i>
                  </button>
                  <button
                    v-if="role.editable"
                    type="button"
                    class="flex h-9 w-9 items-center justify-center rounded-xl border border-default text-content-soft hover:bg-surface-muted hover:text-brand"
                    title="Edit permission role"
                    @click="openPermissions(role)"
                  >
                    <i class="fa-solid fa-sliders text-xs"></i>
                  </button>
                  <span
                    v-else
                    class="inline-flex h-9 items-center gap-2 rounded-xl border border-default bg-surface-muted px-3 text-xs font-semibold text-content-muted"
                  >
                    <i class="fa-solid fa-lock"></i>
                    Terkunci
                  </span>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <Teleport to="body">
      <div v-if="permissionModalOpen" class="fixed inset-0 z-[9999] overflow-y-auto" role="dialog" aria-modal="true">
        <button class="fixed inset-0 h-full w-full bg-slate-950/50 backdrop-blur-sm" aria-label="Tutup modal" @click="closePermissionModal"></button>
        <div class="relative flex min-h-full items-center justify-center p-4">
          <section class="relative w-full max-w-3xl rounded-3xl border border-default bg-surface shadow-2xl">
            <header class="flex items-center justify-between border-b border-default px-6 py-5">
              <div>
                <h2 class="text-lg font-bold text-content">Permission {{ selectedRole?.name }}</h2>
                <p class="mt-1 text-xs text-content-muted">Permission ini diwarisi oleh seluruh user dalam role.</p>
              </div>
              <button type="button" class="rounded-xl p-2 text-content-muted hover:bg-surface-muted" @click="closePermissionModal">
                <i class="fa-solid fa-xmark"></i>
              </button>
            </header>

            <div class="max-h-[65vh] overflow-y-auto p-6">
              <div class="mb-4 flex items-center justify-between gap-3">
                <p class="text-sm text-content-soft">{{ selectedPermissions.length }} permission dipilih</p>
                <button type="button" class="text-xs font-semibold text-brand" @click="toggleAllPermissions">
                  {{ allPermissionsSelected ? 'Kosongkan' : 'Pilih semua' }}
                </button>
              </div>

              <div class="space-y-5">
                <section v-for="group in permissionGroups" :key="group.name">
                  <h3 class="mb-2 text-xs font-bold uppercase tracking-wide text-content-muted">{{ group.name }}</h3>
                  <div class="grid gap-2 sm:grid-cols-2">
                    <label
                      v-for="permission in group.permissions"
                      :key="permission"
                      class="flex items-center gap-2 rounded-xl border border-default bg-surface-muted px-3 py-2.5 text-sm text-content-soft"
                    >
                      <input v-model="selectedPermissions" type="checkbox" :value="permission" class="accent-blue-600" />
                      <span>{{ permissionLabel(permission) }}</span>
                    </label>
                  </div>
                </section>
              </div>
            </div>

            <footer class="flex justify-end gap-2 border-t border-default bg-surface-muted px-6 py-4">
              <button type="button" class="rounded-xl border border-default bg-surface px-4 py-2.5 text-sm font-semibold text-content-soft" @click="closePermissionModal">
                Batal
              </button>
              <button type="button" :disabled="saving" class="rounded-xl bg-brand px-5 py-2.5 text-sm font-semibold text-white disabled:opacity-50" @click="savePermissions">
                {{ saving ? 'Menyimpan...' : 'Simpan Permission' }}
              </button>
            </footer>
          </section>
        </div>
      </div>

      <div v-if="usersModalOpen" class="fixed inset-0 z-[9999] overflow-y-auto" role="dialog" aria-modal="true">
        <button class="fixed inset-0 h-full w-full bg-slate-950/50 backdrop-blur-sm" aria-label="Tutup modal" @click="closeUsersModal"></button>
        <div class="relative flex min-h-full items-center justify-center p-4">
          <section class="relative w-full max-w-2xl rounded-3xl border border-default bg-surface shadow-2xl">
            <header class="flex items-center justify-between border-b border-default px-6 py-5">
              <div>
                <h2 class="text-lg font-bold text-content">User Role {{ selectedRole?.name }}</h2>
                <p class="mt-1 text-xs text-content-muted">{{ usersPagination.total }} user aktif memiliki role ini.</p>
              </div>
              <button type="button" class="rounded-xl p-2 text-content-muted hover:bg-surface-muted" @click="closeUsersModal">
                <i class="fa-solid fa-xmark"></i>
              </button>
            </header>

            <div class="max-h-[60vh] overflow-y-auto p-6">
              <p v-if="usersLoading" class="py-10 text-center text-sm text-content-muted">Memuat user...</p>
              <div v-else-if="roleUsers.length" class="space-y-2">
                <div v-for="user in roleUsers" :key="user.id" class="flex items-center gap-3 rounded-2xl border border-default bg-surface-muted p-3">
                  <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-soft font-semibold text-brand-strong">
                    {{ user.name?.charAt(0)?.toUpperCase() || 'U' }}
                  </div>
                  <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-content">{{ user.name }}</p>
                    <p class="truncate text-xs text-content-muted">{{ user.username }} · {{ user.email }}</p>
                  </div>
                </div>
              </div>
              <div v-else class="py-10 text-center">
                <i class="fa-solid fa-user-slash text-2xl text-content-muted"></i>
                <p class="mt-3 text-sm font-medium text-content">Belum ada user pada role ini.</p>
              </div>
            </div>

            <footer class="flex items-center justify-between border-t border-default bg-surface-muted px-6 py-4 text-xs text-content-muted">
              <span>Halaman {{ usersPagination.current_page }} dari {{ usersPagination.last_page }}</span>
              <div class="flex gap-2">
                <button type="button" :disabled="usersPagination.current_page <= 1 || usersLoading" class="rounded-lg border border-default bg-surface px-3 py-2 disabled:opacity-40" @click="loadRoleUsers(usersPagination.current_page - 1)">
                  Sebelumnya
                </button>
                <button type="button" :disabled="usersPagination.current_page >= usersPagination.last_page || usersLoading" class="rounded-lg border border-default bg-surface px-3 py-2 disabled:opacity-40" @click="loadRoleUsers(usersPagination.current_page + 1)">
                  Berikutnya
                </button>
              </div>
            </footer>
          </section>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import api from '../utils/api';

const roles = ref([]);
const loading = ref(false);
const saving = ref(false);
const usersLoading = ref(false);
const errorMessage = ref('');
const selectedRole = ref(null);
const selectedPermissions = ref([]);
const permissionModalOpen = ref(false);
const usersModalOpen = ref(false);
const roleUsers = ref([]);
const usersPagination = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 });

const permissionGroups = computed(() => {
  const groups = {};
  for (const permission of selectedRole.value?.allowed_permissions || []) {
    const [group] = permission.split('.');
    groups[group] ||= [];
    groups[group].push(permission);
  }

  return Object.entries(groups).map(([name, permissions]) => ({ name, permissions }));
});

const allPermissionsSelected = computed(() => {
  const allowed = selectedRole.value?.allowed_permissions || [];
  return allowed.length > 0 && allowed.every(permission => selectedPermissions.value.includes(permission));
});

const fetchRoles = async () => {
  loading.value = true;
  errorMessage.value = '';
  try {
    const response = await api.get('/admin/roles');
    roles.value = response.data.data.roles;
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Role tidak dapat dimuat.';
  } finally {
    loading.value = false;
  }
};

const openPermissions = (role) => {
  selectedRole.value = role;
  selectedPermissions.value = [...role.permissions];
  permissionModalOpen.value = true;
};

const closePermissionModal = () => {
  permissionModalOpen.value = false;
  selectedRole.value = null;
  selectedPermissions.value = [];
};

const toggleAllPermissions = () => {
  selectedPermissions.value = allPermissionsSelected.value
    ? []
    : [...selectedRole.value.allowed_permissions];
};

const savePermissions = async () => {
  saving.value = true;
  errorMessage.value = '';
  try {
    await api.put(`/admin/roles/${selectedRole.value.id}/permissions`, {
      permissions: selectedPermissions.value,
    });
    closePermissionModal();
    await fetchRoles();
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Permission role tidak dapat disimpan.';
  } finally {
    saving.value = false;
  }
};

const openUsers = async (role) => {
  selectedRole.value = role;
  usersModalOpen.value = true;
  await loadRoleUsers(1);
};

const loadRoleUsers = async (page) => {
  if (!selectedRole.value) return;
  usersLoading.value = true;
  errorMessage.value = '';
  try {
    const response = await api.get(`/admin/roles/${selectedRole.value.id}/users`, {
      params: { page, per_page: usersPagination.value.per_page },
    });
    roleUsers.value = response.data.data.users.data;
    usersPagination.value = {
      current_page: response.data.data.users.current_page,
      last_page: response.data.data.users.last_page,
      per_page: response.data.data.users.per_page,
      total: response.data.data.users.total,
    };
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Daftar user role tidak dapat dimuat.';
  } finally {
    usersLoading.value = false;
  }
};

const closeUsersModal = () => {
  usersModalOpen.value = false;
  selectedRole.value = null;
  roleUsers.value = [];
  usersPagination.value = { current_page: 1, last_page: 1, per_page: 15, total: 0 };
};

const roleIcon = (role) => ({
  'Super Admin': 'fa-solid fa-crown',
  Admin: 'fa-solid fa-user-gear',
  Tim: 'fa-solid fa-people-group',
}[role] || 'fa-solid fa-user-shield');

const roleDescription = (role) => ({
  'Super Admin': 'Seluruh akses sistem; permission dikunci.',
  Admin: 'Kelola user Tim dan seluruh modul operasional.',
  Tim: 'Akses operasional sesuai permission dan assignment.',
}[role] || '');

const permissionLabel = permission => permission.replace('.', ' · ').replaceAll('-', ' ');

onMounted(fetchRoles);
</script>
