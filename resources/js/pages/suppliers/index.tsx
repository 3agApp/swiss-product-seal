import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowDown,
    ArrowUp,
    ArrowUpDown,
    Pencil,
    Plus,
    Search,
    Trash2,
    X,
} from 'lucide-react';
import { useCallback, useRef, useState } from 'react';
import Heading from '@/components/heading';
import Pagination from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { create, destroy, edit, index } from '@/routes/suppliers';
import type { PaginatedData, Supplier } from '@/types';

type Filters = {
    search: string;
    sort: string;
    direction: 'asc' | 'desc';
};

type Props = {
    suppliers: PaginatedData<Supplier>;
    filters: Filters;
};

export default function SuppliersIndex({ suppliers, filters }: Props) {
    const [deleteId, setDeleteId] = useState<number | null>(null);
    const [deleting, setDeleting] = useState(false);
    const [localSearch, setLocalSearch] = useState<string | null>(null);
    const debounceRef = useRef<ReturnType<typeof setTimeout>>(null);

    const search = localSearch ?? filters.search ?? '';

    const applyFilters = useCallback(
        (params: Record<string, string | undefined>) => {
            router.get(index.url(), params, {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => setLocalSearch(null),
            });
        },
        [],
    );

    function handleSearchChange(value: string) {
        setLocalSearch(value);

        if (debounceRef.current) {
            clearTimeout(debounceRef.current);
        }

        debounceRef.current = setTimeout(() => {
            applyFilters({
                search: value || undefined,
                sort: filters.sort || undefined,
                direction: filters.sort ? filters.direction : undefined,
                page: undefined,
            });
        }, 300);
    }

    function handleSort(column: string) {
        let newSort: string | undefined;
        let newDirection: string | undefined;

        if (filters.sort === column && filters.direction === 'asc') {
            newSort = column;
            newDirection = 'desc';
        } else if (filters.sort === column && filters.direction === 'desc') {
            newSort = undefined;
            newDirection = undefined;
        } else {
            newSort = column;
            newDirection = 'asc';
        }

        applyFilters({
            search: search || undefined,
            sort: newSort,
            direction: newDirection,
        });
    }

    function sortIcon(column: string) {
        if (filters.sort !== column) {
            return <ArrowUpDown className="size-3.5 opacity-40" />;
        }

        return filters.direction === 'asc' ? (
            <ArrowUp className="size-3.5" />
        ) : (
            <ArrowDown className="size-3.5" />
        );
    }

    function handleDelete() {
        if (!deleteId) {
            return;
        }

        setDeleting(true);
        router.delete(destroy.url(deleteId), {
            onFinish: () => {
                setDeleting(false);
                setDeleteId(null);
            },
        });
    }

    return (
        <>
            <Head title="Suppliers" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Suppliers"
                        description="Manage your supplier directory"
                    />
                    <Button asChild>
                        <Link href={create()}>
                            <Plus />
                            Add Supplier
                        </Link>
                    </Button>
                </div>

                <div className="relative max-w-sm">
                    <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        placeholder="Search suppliers..."
                        value={search}
                        onChange={(e) => handleSearchChange(e.target.value)}
                        className="pr-9 pl-9"
                    />
                    {search && (
                        <button
                            type="button"
                            onClick={() => handleSearchChange('')}
                            className="absolute top-1/2 right-3 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                        >
                            <X className="size-4" />
                        </button>
                    )}
                </div>

                <div className="rounded-xl border">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b bg-muted/50 text-left">
                                    <th className="px-4 py-3 font-medium whitespace-nowrap">
                                        <button
                                            type="button"
                                            onClick={() =>
                                                handleSort('supplier_code')
                                            }
                                            className="inline-flex items-center gap-1"
                                        >
                                            Code {sortIcon('supplier_code')}
                                        </button>
                                    </th>
                                    <th className="px-4 py-3 font-medium whitespace-nowrap">
                                        <button
                                            type="button"
                                            onClick={() => handleSort('name')}
                                            className="inline-flex items-center gap-1"
                                        >
                                            Name {sortIcon('name')}
                                        </button>
                                    </th>
                                    <th className="px-4 py-3 font-medium whitespace-nowrap">
                                        <button
                                            type="button"
                                            onClick={() =>
                                                handleSort('country')
                                            }
                                            className="inline-flex items-center gap-1"
                                        >
                                            Country {sortIcon('country')}
                                        </button>
                                    </th>
                                    <th className="px-4 py-3 font-medium whitespace-nowrap">
                                        <button
                                            type="button"
                                            onClick={() => handleSort('email')}
                                            className="inline-flex items-center gap-1"
                                        >
                                            Email {sortIcon('email')}
                                        </button>
                                    </th>
                                    <th className="px-4 py-3 font-medium whitespace-nowrap">
                                        Phone
                                    </th>
                                    <th className="px-4 py-3 font-medium whitespace-nowrap">
                                        Status
                                    </th>
                                    <th className="px-4 py-3 text-right font-medium whitespace-nowrap">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {suppliers.data.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={7}
                                            className="px-4 py-8 text-center text-muted-foreground"
                                        >
                                            No suppliers found.
                                        </td>
                                    </tr>
                                )}
                                {suppliers.data.map((supplier) => (
                                    <tr
                                        key={supplier.id}
                                        className="border-b transition-colors last:border-0 hover:bg-muted/30"
                                    >
                                        <td className="px-4 py-3 font-mono text-xs whitespace-nowrap">
                                            {supplier.supplier_code}
                                        </td>
                                        <td className="px-4 py-3 font-medium whitespace-nowrap">
                                            {supplier.name}
                                        </td>
                                        <td className="px-4 py-3 whitespace-nowrap text-muted-foreground">
                                            {supplier.country ?? '—'}
                                        </td>
                                        <td className="px-4 py-3 whitespace-nowrap text-muted-foreground">
                                            {supplier.email ?? '—'}
                                        </td>
                                        <td className="px-4 py-3 whitespace-nowrap text-muted-foreground">
                                            {supplier.phone ?? '—'}
                                        </td>
                                        <td className="px-4 py-3 whitespace-nowrap">
                                            {supplier.active === null ? (
                                                <Badge variant="outline">
                                                    Unknown
                                                </Badge>
                                            ) : supplier.active ? (
                                                <Badge variant="default">
                                                    Active
                                                </Badge>
                                            ) : (
                                                <Badge variant="secondary">
                                                    Inactive
                                                </Badge>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-right whitespace-nowrap">
                                            <div className="flex items-center justify-end gap-1">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    asChild
                                                >
                                                    <Link
                                                        href={edit(supplier.id)}
                                                    >
                                                        <Pencil className="size-4" />
                                                    </Link>
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    onClick={() =>
                                                        setDeleteId(supplier.id)
                                                    }
                                                >
                                                    <Trash2 className="size-4 text-destructive" />
                                                </Button>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    <Pagination paginator={suppliers} itemName="suppliers" />
                </div>
            </div>

            <Dialog
                open={deleteId !== null}
                onOpenChange={() => setDeleteId(null)}
            >
                <DialogContent>
                    <DialogTitle>Delete Supplier</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete this supplier? This
                        action cannot be undone.
                    </DialogDescription>
                    <DialogFooter>
                        <DialogClose asChild>
                            <Button variant="outline">Cancel</Button>
                        </DialogClose>
                        <Button
                            variant="destructive"
                            onClick={handleDelete}
                            disabled={deleting}
                        >
                            {deleting ? 'Deleting...' : 'Delete'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

SuppliersIndex.layout = {
    breadcrumbs: [
        {
            title: 'Suppliers',
            href: index.url(),
        },
    ],
};
