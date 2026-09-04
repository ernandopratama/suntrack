<template>
  <div class="space-y-6">
    <header class="rounded-3xl border border-default bg-surface p-6 shadow-sm sm:p-8">
      <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-center">
        <div>
          <div class="flex items-center gap-3">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-soft text-brand-strong">
              <i class="fa-solid fa-users"></i>
            </div>
            <div>
              <h1 class="text-2xl font-bold tracking-tight text-content">User & Hak Akses</h1>
              <p class="mt-1 text-sm text-content-soft">
                Kelola role, permission Tim, serta assignment Company dan Brand.
              </p>
            </div>
          </div>
        </div>

        <button
          v-if="$can('user.create')"
          type="button"
          class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand px-4 py-2.5 text-sm font-semibold text-white shadow-sm"
          @click="openCreate"
        >
          <i class="fa-solid fa-plus text-xs"></i>
          Tambah User
        </button>
      </div>
    </header>

    <section class="overflow-hidden rounded-3xl border border-default bg-surface shadow-sm">
      <div class="border-b border-default p-4 sm:p-5">
        <div class="relative max-w-md">
          <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-xs text-content-muted"></i>
          <input
            v-model="search"
            type="search"
            placeholder="Cari nama, username, atau email..."
            class="w-full rounded-xl border border-default bg-surface-muted py-2.5 pl-9 pr-3 text-sm text-content outline-none focus:border-brand"
            @input="searchUsers"
          />
        </div>
      </div>

      <div v-if="error" class="border-b border-rose-200 bg-rose-50 px-5 py-3 text-sm text-rose-700">
        {{ error }}
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-default">
          <thead class="bg-surface-muted">
            <tr>
              <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-content-muted">User</th>
              <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-content-muted">Role</th>
              <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-content-muted">Permission</th>
              <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-content-muted">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-default">
            <tr v-if="loading">
              <td colspan="4" class="px-5 py-12 text-center text-sm text-content-muted">Memuat user...</td>
            </tr>
            <tr v-else-if="!users.length">
              <td colspan="4" class="px-5 py-12 text-center">
                <i class="fa-solid fa-user-slash text-2xl text-content-muted"></i>
                <p class="mt-3 text-sm font-medium text-content">Tidak ada user ditemukan</p>
                <p class="mt-1 text-xs text-content-muted">Ubah pencarian atau tambah user baru.</p>
              </td>
            </tr>
            <tr v-for="row in users" v-else :key="row.id" class="hover:bg-surface-muted">
              <td class="px-5 py-4">
                <div class="flex items-center gap-3">
                  <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-soft font-semibold text-brand-strong">
                    {{ row.name?.charAt(0)?.toUpperCase() || 'U' }}
                  </div>
                  <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-content">{{ row.name }}</p>
                    <p class="truncate text-xs text-content-muted">{{ row.username }} · {{ row.email }}</p>
                  </div>
                </div>
              </td>
              <td class="px-5 py-4">
                <span class="rounded-full bg-brand-soft px-2.5 py-1 text-xs font-semibold text-brand-strong">
                  {{ row.roles?.[0] || 'Tanpa role' }}
                </span>
              </td>
              <td class="px-5 py-4 text-sm text-content-soft">
                {{ row.permissions?.length || 0 }} permission
              </td>
              <td class="px-5 py-4">
                <div class="flex justify-end gap-2">
                  <button
                    v-if="canEdit(row)"
                    type="button"
                    class="flex h-9 w-9 items-center justify-center rounded-xl border border-default text-content-soft hover:bg-surface-muted hover:text-brand"
                    title="Edit user dan hak akses"
                    @click="openEdit(row)"
                  >
                    <i class="fa-solid fa-pen-to-square text-xs"></i>
                  </button>
                  <button
                    v-if="canDelete(row)"
                    type="button"
                    class="flex h-9 w-9 items-center justify-center rounded-xl border border-rose-200 text-rose-500 hover:bg-rose-50"
                    title="Hapus user"
                    @click="removeUser(row)"
                  >
                    <i class="fa-solid fa-trash-can text-xs"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <footer class="flex items-center justify-between border-t border-default px-5 py-4 text-xs text-content-muted">
        <span>{{ pagination.total }} user</span>
        <div class="flex items-center gap-2">
          <button
            type="button"
            :disabled="pagination.current_page <= 1"
            class="rounded-lg border border-default px-3 py-2 disabled:opacity-40"
            @click="changePage(-1)"
          >
            Sebelumnya
          </button>
          <span>{{ pagination.current_page }} / {{ pagination.last_page }}</span>
          <button
            type="button"
            :disabled="pagination.current_page >= pagination.last_page"
            class="rounded-lg border border-default px-3 py-2 disabled:opacity-40"
            @click="changePage(1)"
          >
            Berikutnya
          </button>
        </div>
      </footer>
    </section>

    <UserForm
      :is-open="formOpen"
      :user="selectedUser"
      @close="closeForm"
      @saved="fetchData"
    />
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import UserForm from '../components/UserForm.vue';
import { useUsers } from '../composables/useUsers';
import { useAuthStore } from '../stores/auth';

const authStore = useAuthStore();
const { users, user, loading, error, pagination, fetchUsers, fetchUser, deleteUser } = useUsers();
const search = ref('');
const formOpen = ref(false);
const selectedUser = ref(null);
let searchTimer;

const fetchData = () => fetchUsers({
  page: pagination.value.current_page,
  search: search.value,
});

onMounted(fetchData);

const searchUsers = () => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => {
    pagination.value.current_page = 1;
    fetchData();
  }, 250);
};

const changePage = (delta) => {
  pagination.value.current_page += delta;
  fetchData();
};

const openCreate = () => {
  selectedUser.value = null;
  formOpen.value = true;
};

const openEdit = async (row) => {
  await fetchUser(row.id);
  selectedUser.value = user.value;
  if (selectedUser.value) formOpen.value = true;
};

const closeForm = () => {
  formOpen.value = false;
  selectedUser.value = null;
};

const canEdit = (row) => {
  if (!authStore.can('user.update')) return false;
  if (authStore.hasRole('Super Admin')) return true;
  return authStore.hasRole('Admin')
    && row.id !== authStore.user?.id
    && row.roles?.includes('Tim');
};

const canDelete = () => authStore.hasRole('Super Admin') && authStore.can('user.delete');

const removeUser = async (row) => {
  if (!window.confirm(`Hapus user "${row.name}"?`)) return;
  if (await deleteUser(row.id)) fetchData();
};
</script>
