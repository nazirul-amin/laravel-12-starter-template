<script setup lang="ts">
import { DataTable, type ColumnDef } from '@/components/data-table';
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
import {
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogOverlay,
    AlertDialogPortal,
    AlertDialogRoot,
    AlertDialogTitle,
    AlertDialogTrigger,
} from 'reka-ui';
import { computed, ref } from 'vue';

// Types
interface UserRow {
    id: string;
    name: string;
    email: string;
    created_at: string;
    created_by?: string | null;
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
        key: 'created_at',
        header: 'Created',
        sortable: true,
        accessor: (r: UserRow) => new Date(r.created_at).toLocaleString(),
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
                        <AlertDialogRoot
                            v-if="is('super-admin | admin')"
                            :open="deleting?.id === row.id"
                            @update:open="
                                (v: any) => (deleting = v ? row : null)
                            "
                        >
                            <AlertDialogTrigger as-child>
                                <Button size="sm" variant="destructive"
                                    >Delete</Button
                                >
                            </AlertDialogTrigger>
                            <AlertDialogPortal>
                                <AlertDialogOverlay
                                    class="fixed inset-0 z-50 bg-black/40"
                                />
                                <AlertDialogContent
                                    class="fixed top-1/2 left-1/2 z-50 w-full max-w-md -translate-x-1/2 -translate-y-1/2 rounded-lg border bg-background p-6 shadow-lg"
                                >
                                    <AlertDialogTitle
                                        class="text-lg font-semibold"
                                        >Delete user?</AlertDialogTitle
                                    >
                                    <AlertDialogDescription
                                        class="text-sm text-muted-foreground"
                                    >
                                        This action cannot be undone. This will
                                        permanently delete the user.
                                    </AlertDialogDescription>
                                    <div class="mt-6 flex justify-end gap-2">
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
                                    </div>
                                </AlertDialogContent>
                            </AlertDialogPortal>
                        </AlertDialogRoot>
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
