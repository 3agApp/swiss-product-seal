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
import { create, destroy, edit, index } from '@/routes/products';
import type { PaginatedData, Product } from '@/types';

type Filters = {
    search: string;
    sort: string;
    direction: 'asc' | 'desc';
};

type Props = {
    products: PaginatedData<Product>;
    filters: Filters;
};

const statusVariant: Record<
    string,
    'default' | 'secondary' | 'outline' | 'destructive'
> = {
    open: 'outline',
    in_progress: 'secondary',
    submitted: 'secondary',
    under_review: 'secondary',
    clarification_needed: 'destructive',
    approved: 'default',
    rejected: 'destructive',
    completed: 'default',
};

const statusLabel: Record<string, string> = {
    open: 'Open',
    in_progress: 'In progress',
    submitted: 'Submitted',
    under_review: 'Under review',
    clarification_needed: 'Clarification needed',
    approved: 'Approved',
    rejected: 'Rejected',
    completed: 'Completed',
};

export default function ProductsIndex({ products, filters }: Props) {
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
            <Head title="Products" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Products"
                        description="Manage your product catalog"
                    />
                    <Button asChild>
                        <Link href={create()}>
                            <Plus />
                            Add Product
                        </Link>
                    </Button>
                </div>

                <div className="relative max-w-sm">
                    <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        placeholder="Search products..."
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
                                        Image
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
                                                handleSort(
                                                    'internal_article_number',
                                                )
                                            }
                                            className="inline-flex items-center gap-1"
                                        >
                                            Article No.{' '}
                                            {sortIcon(
                                                'internal_article_number',
                                            )}
                                        </button>
                                    </th>
                                    <th className="px-4 py-3 font-medium whitespace-nowrap">
                                        <button
                                            type="button"
                                            onClick={() => handleSort('ean')}
                                            className="inline-flex items-center gap-1"
                                        >
                                            EAN {sortIcon('ean')}
                                        </button>
                                    </th>
                                    <th className="px-4 py-3 font-medium whitespace-nowrap">
                                        Supplier
                                    </th>
                                    <th className="px-4 py-3 font-medium whitespace-nowrap">
                                        Brand
                                    </th>
                                    <th className="px-4 py-3 font-medium whitespace-nowrap">
                                        <button
                                            type="button"
                                            onClick={() => handleSort('status')}
                                            className="inline-flex items-center gap-1"
                                        >
                                            Status {sortIcon('status')}
                                        </button>
                                    </th>
                                    <th className="px-4 py-3 text-right font-medium whitespace-nowrap">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {products.data.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={8}
                                            className="px-4 py-8 text-center text-muted-foreground"
                                        >
                                            No products found.
                                        </td>
                                    </tr>
                                )}
                                {products.data.map((product) => (
                                    <tr
                                        key={product.id}
                                        className="border-b transition-colors last:border-0 hover:bg-muted/30"
                                    >
                                        <td className="px-4 py-3 whitespace-nowrap">
                                            {product.image_preview_url ? (
                                                <img
                                                    src={
                                                        product.image_preview_url
                                                    }
                                                    alt={product.name}
                                                    className="size-10 rounded-md border object-cover"
                                                />
                                            ) : (
                                                <div className="flex size-10 items-center justify-center rounded-md border bg-muted text-xs text-muted-foreground">
                                                    —
                                                </div>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 font-medium whitespace-nowrap">
                                            {product.name}
                                        </td>
                                        <td className="px-4 py-3 font-mono text-xs whitespace-nowrap">
                                            {product.internal_article_number ??
                                                '—'}
                                        </td>
                                        <td className="px-4 py-3 font-mono text-xs whitespace-nowrap">
                                            {product.ean ?? '—'}
                                        </td>
                                        <td className="px-4 py-3 whitespace-nowrap text-muted-foreground">
                                            {product.supplier?.name ?? '—'}
                                        </td>
                                        <td className="px-4 py-3 whitespace-nowrap text-muted-foreground">
                                            {product.brand?.name ?? '—'}
                                        </td>
                                        <td className="px-4 py-3 whitespace-nowrap">
                                            {product.status ? (
                                                <Badge
                                                    variant={
                                                        statusVariant[
                                                            product.status
                                                        ] ?? 'outline'
                                                    }
                                                >
                                                    {statusLabel[
                                                        product.status
                                                    ] ?? product.status}
                                                </Badge>
                                            ) : (
                                                <Badge variant="outline">
                                                    Unknown
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
                                                        href={edit(product.id)}
                                                    >
                                                        <Pencil className="size-4" />
                                                    </Link>
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    onClick={() =>
                                                        setDeleteId(product.id)
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

                    <Pagination paginator={products} itemName="products" />
                </div>
            </div>

            <Dialog
                open={deleteId !== null}
                onOpenChange={() => setDeleteId(null)}
            >
                <DialogContent>
                    <DialogTitle>Delete Product</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete this product? This
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

ProductsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Products',
            href: index.url(),
        },
    ],
};
