<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { computed, ref, watch } from 'vue';
import type { ColumnDef, SortDirection, SortState } from './types';

interface Props {
    columns: ColumnDef[];
    rows: any[];
    pageSize?: number;
    searchable?: boolean;
    searchPlaceholder?: string;
}

const props = defineProps<Props>();

const page = ref(1);
const pageSize = ref(props.pageSize ?? 10);
const search = ref('');
const sort = ref<SortState>({ key: null, direction: 'asc' });
const visibility = ref<Record<string, boolean>>({});

watch(
    () => props.columns,
    (cols) => {
        const v: Record<string, boolean> = {};
        for (const c of cols) v[String(c.key)] = c.visible ?? true;
        visibility.value = v;
    },
    { immediate: true },
);

const visibleColumns = computed(() =>
    props.columns.filter((c) => visibility.value[String(c.key)] !== false),
);

function toggleSort(key: string) {
    if (sort.value.key !== key) {
        sort.value = { key, direction: 'asc' };
        return;
    }
    sort.value.direction = (
        sort.value.direction === 'asc' ? 'desc' : 'asc'
    ) as SortDirection;
}

const filtered = computed(() => {
    if (!props.searchable || !search.value.trim()) return props.rows;
    const q = search.value.toLowerCase();
    return props.rows.filter((row: any) => {
        return visibleColumns.value.some((c) => {
            const accessor = c.accessor ?? ((r: any) => r[c.key]);
            const val = accessor(row);
            return String(val ?? '')
                .toLowerCase()
                .includes(q);
        });
    });
});

const sorted = computed(() => {
    if (!sort.value.key) return filtered.value;
    const key = sort.value.key;
    const dir = sort.value.direction === 'asc' ? 1 : -1;
    const col = props.columns.find((c) => String(c.key) === key);
    const accessor = col?.accessor ?? ((r: any) => r[key]);
    return [...filtered.value].sort((a, b) => {
        const va = accessor(a);
        const vb = accessor(b);
        if (va == null && vb == null) return 0;
        if (va == null) return -1 * dir;
        if (vb == null) return 1 * dir;
        if (va < vb) return -1 * dir;
        if (va > vb) return 1 * dir;
        return 0;
    });
});

const totalPages = computed(() =>
    Math.max(1, Math.ceil(sorted.value.length / pageSize.value)),
);
watch([sorted, pageSize], () => {
    page.value = 1;
});

const paginated = computed(() => {
    const start = (page.value - 1) * pageSize.value;
    return sorted.value.slice(start, start + pageSize.value);
});
</script>

<template>
    <div class="w-full space-y-3">
        <div
            v-if="searchable !== false"
            class="flex items-center justify-between gap-2"
        >
            <Input
                v-model="search"
                :placeholder="searchPlaceholder ?? 'Search...'"
                class="max-w-sm"
            />
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button variant="outline" size="sm">Columns</Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent class="w-48">
                    <DropdownMenuCheckboxItem
                        v-for="c in columns"
                        :key="String(c.key)"
                        v-model:checked="visibility[String(c.key)]"
                    >
                        {{ c.header }}
                    </DropdownMenuCheckboxItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </div>

        <div class="rounded-md border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50">
                    <tr>
                        <th
                            v-for="c in visibleColumns"
                            :key="String(c.key)"
                            class="px-3 py-2 text-left align-middle font-medium select-none"
                        >
                            <button
                                v-if="c.sortable"
                                class="inline-flex items-center gap-1 hover:underline"
                                @click="toggleSort(String(c.key))"
                            >
                                <slot :name="`header-${String(c.key)}`">{{
                                    c.header
                                }}</slot>
                                <span v-if="sort.key === String(c.key)">
                                    {{ sort.direction === 'asc' ? '▲' : '▼' }}
                                </span>
                            </button>
                            <template v-else>
                                <slot :name="`header-${String(c.key)}`">{{
                                    c.header
                                }}</slot>
                            </template>
                        </th>
                        <th class="px-3 py-2 text-right"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="row in paginated"
                        :key="JSON.stringify(row)"
                        class="border-t"
                    >
                        <td
                            v-for="c in visibleColumns"
                            :key="String(c.key)"
                            class="px-3 py-2 align-middle"
                        >
                            <slot :name="`cell-${String(c.key)}`" :row="row">
                                {{
                                    (
                                        c.accessor ??
                                        ((r: any) => r[c.key as keyof typeof r])
                                    )(row)
                                }}
                            </slot>
                        </td>
                        <td class="px-3 py-2 text-right">
                            <slot name="row-actions" :row="row" />
                        </td>
                    </tr>

                    <tr v-if="paginated.length === 0">
                        <td
                            :colspan="visibleColumns.length + 1"
                            class="px-3 py-6 text-center text-muted-foreground"
                        >
                            No results
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="flex items-center justify-between gap-2">
            <div class="text-xs text-muted-foreground">
                Page {{ page }} of {{ totalPages }} • Showing
                {{ paginated.length }} of {{ sorted.length }} items
            </div>
            <div class="flex items-center gap-2">
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="page <= 1"
                    @click="page--"
                    >Prev</Button
                >
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="page >= totalPages"
                    @click="page++"
                    >Next</Button
                >
            </div>
        </div>
    </div>
</template>
