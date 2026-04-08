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
import { create, destroy, edit, index } from '@/routes/categories';
import type { Category, PaginatedData } from '@/types';

type Filters = {
    search: string;
    sort: string;
    direction: 'asc' | 'desc';
};

type Props = {
    categories: PaginatedData<Category>;
    filters: Filters;
};

export default function CategoriesIndex({ categories, filters }: Props) {
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
            <Head title="Categories" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Categories"
                        description="Manage product categories"
                    />
                    <Button asChild>
                        <Link href={create()}>
                            <Plus />
                            Add Category
                        </Link>
                    </Button>
                </div>

                <div className="relative max-w-sm">
                    <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        placeholder="Search categories..."
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
                                                handleSort('description')
                                            }
                                            className="inline-flex items-center gap-1"
                                        >
                                            Description{' '}
                                            {sortIcon('description')}
                                        </button>
                                    </th>
                                    <th className="px-4 py-3 font-medium whitespace-nowrap">
                                        Products
                                    </th>
                                    <th className="px-4 py-3 text-right font-medium whitespace-nowrap">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {categories.data.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={4}
                                            className="px-4 py-8 text-center text-muted-foreground"
                                        >
                                            No categories found.
                                        </td>
                                    </tr>
                                )}
                                {categories.data.map((category) => (
                                    <tr
                                        key={category.id}
                                        className="border-b transition-colors last:border-0 hover:bg-muted/30"
                                    >
                                        <td className="px-4 py-3 font-medium whitespace-nowrap">
                                            {category.name}
                                        </td>
                                        <td className="max-w-xs truncate px-4 py-3 text-muted-foreground">
                                            {category.description ?? '—'}
                                        </td>
                                        <td className="px-4 py-3 whitespace-nowrap text-muted-foreground">
                                            {category.products_count ?? 0}
                                        </td>
                                        <td className="px-4 py-3 text-right whitespace-nowrap">
                                            <div className="flex items-center justify-end gap-1">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    asChild
                                                >
                                                    <Link
                                                        href={edit(
                                                            category.id,
                                                        )}
                                                    >
                                                        <Pencil className="size-4" />
                                                    </Link>
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    onClick={() =>
                                                        setDeleteId(
                                                            category.id,
                                                        )
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

                    <Pagination
                        paginator={categories}
                        itemName="categories"
                    />
                </div>
            </div>

            <Dialog
                open={deleteId !== null}
                onOpenChange={() => setDeleteId(null)}
            >
                <DialogContent>
                    <DialogTitle>Delete Category</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete this category? Products
                        in this category will be uncategorized.
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

CategoriesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Categories',
            href: index.url(),
        },
    ],
};
