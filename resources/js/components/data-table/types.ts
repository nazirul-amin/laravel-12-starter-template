export type SortDirection = 'asc' | 'desc';

export type Accessor = (row: any) => unknown;

export type ColumnDef = {
    key: string;
    header: string;
    accessor?: Accessor;
    sortable?: boolean;
    visible?: boolean;
};

export type SortState = {
    key: string | null;
    direction: SortDirection;
};
