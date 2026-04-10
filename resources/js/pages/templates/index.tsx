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
import { LoaderCircle } from 'lucide-react';
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
import { create, destroy, edit, index } from '@/routes/templates';
import type { PaginatedData, Template } from '@/types';

type Filters = {
    search: string;
    sort: string;
    direction: 'asc' | 'desc';
};

type Props = {
    templates: PaginatedData<Template>;
    filters: Filters;
};

export default function TemplatesIndex({ templates, filters }: Props) {
    const [deleteId, setDeleteId] = useState<number | null>(null);
    const [deleting, setDeleting] = useState(false);
    const [localSearch, setLocalSearch] = useState<string | null>(null);
    const [searching, setSearching] = useState(false);
    const debounceRef = useRef<ReturnType<typeof setTimeout>>(null);

    const search = localSearch ?? filters.search ?? '';

    const applyFilters = useCallback(
        (params: Record<string, string | undefined>) => {
            setSearching(true);
            router.get(index.url(), params, {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => setLocalSearch(null),
                onFinish: () => setSearching(false),
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
            <Head title="Templates" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Templates"
                        description="Manage document requirement templates"
                    />
                    <Button asChild>
                        <Link href={create()}>
                            <Plus />
                            Add Template
                        </Link>
                    </Button>
                </div>

                <div className="relative max-w-sm">
                    <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        placeholder="Search templates..."
                        value={search}
                        onChange={(e) => handleSearchChange(e.target.value)}
                        className="pr-9 pl-9"
                    />
                    {searching ? (
                        <LoaderCircle className="absolute top-1/2 right-3 size-4 -translate-y-1/2 animate-spin text-muted-foreground" />
                    ) : search ? (
                        <button
                            type="button"
                            onClick={() => handleSearchChange('')}
                            className="absolute top-1/2 right-3 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                        >
                            <X className="size-4" />
                        </button>
                    ) : null}
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
                                        Category
                                    </th>
                                    <th className="px-4 py-3 font-medium whitespace-nowrap">
                                        Required Docs
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
                                {templates.data.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={5}
                                            className="px-4 py-12 text-center"
                                        >
                                            <p className="text-muted-foreground">No templates found.</p>
                                            {search && (
                                                <button
                                                    type="button"
                                                    onClick={() => handleSearchChange('')}
                                                    className="mt-2 text-sm text-primary hover:underline"
                                                >
                                                    Clear search
                                                </button>
                                            )}
                                        </td>
                                    </tr>
                                )}
                                {templates.data.map((template) => (
                                    <tr
                                        key={template.id}
                                        className="cursor-pointer border-b transition-colors last:border-0 hover:bg-muted/30"
                                        onClick={() => router.visit(edit(template.id))}
                                    >
                                        <td className="px-4 py-3 font-medium whitespace-nowrap">
                                            {template.name}
                                        </td>
                                        <td className="px-4 py-3 text-muted-foreground">
                                            {template.category?.name ?? '—'}
                                        </td>
                                        <td className="px-4 py-3 text-muted-foreground">
                                            {
                                                template.required_document_types
                                                    .length
                                            }
                                        </td>
                                        <td className="px-4 py-3 whitespace-nowrap text-muted-foreground">
                                            {template.products_count ?? 0}
                                        </td>
                                        <td className="px-4 py-3 text-right whitespace-nowrap" onClick={(e) => e.stopPropagation()}>
                                            <div className="flex items-center justify-end gap-1">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    asChild
                                                >
                                                    <Link
                                                        href={edit(template.id)}
                                                    >
                                                        <Pencil className="size-4" />
                                                    </Link>
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    onClick={() =>
                                                        setDeleteId(template.id)
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

                    <Pagination paginator={templates} itemName="templates" />
                </div>
            </div>

            <Dialog
                open={deleteId !== null}
                onOpenChange={() => setDeleteId(null)}
            >
                <DialogContent>
                    <DialogTitle>Delete Template</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete this template? Templates
                        with assigned products cannot be deleted.
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

TemplatesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Templates',
            href: index.url(),
        },
    ],
};
