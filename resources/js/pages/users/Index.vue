<script setup lang="ts">
import { DataTable, type ColumnDef } from '@/components/data-table';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

// Types
interface UserRow {
    id: string;
    name: string;
    email: string;
    created_by?: string | null;
    created_at: string;
    updated_at: string;
}

// Inertia props
const page = usePage();
const users = computed<any>(() => (page.props as any).users);
const rows = computed<UserRow[]>(() => (users.value?.data ?? []) as UserRow[]);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Users',
        href: '/users',
    },
];

const columns: ColumnDef[] = [
    { key: 'name', header: 'Name', sortable: true },
    { key: 'email', header: 'Email', sortable: true },
    {
        key: 'created_by',
        header: 'Created by',
        sortable: true,
        accessor: (row: UserRow) => row.created_by ?? 'System',
    },
    {
        key: 'created_at',
        header: 'Created',
        sortable: true,
        accessor: (row: UserRow) => new Date(row.created_at).toLocaleString(),
    },
    {
        key: 'updated_at',
        header: 'Updated',
        sortable: true,
        accessor: (row: UserRow) => new Date(row.updated_at).toLocaleString(),
    },
];

// Create/Edit state
const openForm = ref(false);
const editing = ref<UserRow | null>(null);
const form = useForm({ name: '', email: '' });

function startCreate() {
    editing.value = null;
    form.reset();
    openForm.value = true;
}

function startEdit(row: UserRow) {
    editing.value = row;
    form.name = row.name;
    form.email = row.email;
    openForm.value = true;
}

async function submitForm() {
    const isEdit = !!editing.value;
    if (isEdit) {
        form.put(`/users/${editing.value!.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                openForm.value = false;
            },
        });
    } else {
        form.post('/users', {
            preserveScroll: true,
            onSuccess: () => {
                openForm.value = false;
            },
        });
    }
}

// Delete state
const deleting = ref<UserRow | null>(null);
async function confirmDelete() {
    if (!deleting.value) return;
    const id = deleting.value.id;
    router.delete(`/users/${id}`, {
        preserveScroll: true,
        onSuccess: () => {
            deleting.value = null;
        },
    });
}
</script>

<template>
    <Head title="Users" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <div
                v-if="is('super-admin | admin')"
                class="flex items-center justify-between"
            >
                <h1 class="text-xl font-semibold">Users</h1>
                <Button @click="startCreate">Add user</Button>
            </div>

            <DataTable :columns="columns" :rows="rows" :page-size="10">
                <template #cell-email="{ row }">
                    <a
                        class="text-primary hover:underline"
                        :href="`mailto:${row.email}`"
                        >{{ row.email }}</a
                    >
                </template>
                <template #row-actions="{ row }">
                    <div class="flex justify-end gap-2">
                        <Button
                            v-if="is('super-admin | admin')"
                            size="sm"
                            variant="outline"
                            @click="startEdit(row)"
                            >Edit</Button
                        >
                        <AlertDialog v-if="is('super-admin | admin')">
                            <AlertDialogTrigger as-child>
                                <Button
                                    size="sm"
                                    variant="destructive"
                                    @click="deleting = row"
                                    >Delete</Button
                                >
                            </AlertDialogTrigger>
                            <AlertDialogContent>
                                <AlertDialogHeader>
                                    <AlertDialogTitle
                                        >Delete user?</AlertDialogTitle
                                    >
                                    <AlertDialogDescription>
                                        This action cannot be undone. This will
                                        permanently delete the user.
                                    </AlertDialogDescription>
                                </AlertDialogHeader>
                                <AlertDialogFooter>
                                    <AlertDialogCancel as-child>
                                        <Button
                                            variant="outline"
                                            @click="deleting = null"
                                            >Cancel</Button
                                        >
                                    </AlertDialogCancel>
                                    <AlertDialogAction as-child>
                                        <Button
                                            variant="destructive"
                                            @click="confirmDelete"
                                            >Delete</Button
                                        >
                                    </AlertDialogAction>
                                </AlertDialogFooter>
                            </AlertDialogContent>
                        </AlertDialog>
                    </div>
                </template>
            </DataTable>

            <!-- Create/Edit Dialog -->
            <Dialog :open="openForm" @update:open="(v: any) => (openForm = v)">
                <DialogTrigger as-child>
                    <!-- Hidden trigger (we control programmatically) -->
                    <span />
                </DialogTrigger>
                <DialogContent class="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>{{
                            editing ? 'Edit user' : 'Add user'
                        }}</DialogTitle>
                    </DialogHeader>
                    <div class="space-y-3 py-2">
                        <div class="space-y-1">
                            <label class="text-sm font-medium">Name</label>
                            <Input
                                v-model="form.name"
                                placeholder="Full name"
                            />
                        </div>
                        <div class="space-y-1">
                            <label class="text-sm font-medium">Email</label>
                            <Input
                                v-model="form.email"
                                type="email"
                                placeholder="user@example.com"
                            />
                        </div>
                    </div>
                    <DialogFooter class="gap-2">
                        <DialogClose as-child>
                            <Button variant="outline">Cancel</Button>
                        </DialogClose>
                        <Button
                            :disabled="form.processing"
                            @click="submitForm"
                            >{{
                                form.processing ? 'Saving...' : 'Save'
                            }}</Button
                        >
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
