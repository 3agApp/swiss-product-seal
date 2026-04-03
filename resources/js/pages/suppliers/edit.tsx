import { Form, Head, Link, router } from '@inertiajs/react';
import { Pencil, Plus, Trash2, X } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import SupplierFormFields from '@/components/supplier-form';
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
import { index, update } from '@/routes/suppliers';
import {
    store as brandStore,
    update as brandUpdate,
    destroy as brandDestroy,
} from '@/routes/suppliers/brands';
import type { Brand, Supplier, SupplierFormData } from '@/types';

type Props = {
    supplier: Supplier;
};

export default function SuppliersEdit({ supplier }: Props) {
    const [deleteId, setDeleteId] = useState<number | null>(null);
    const [deleting, setDeleting] = useState(false);
    const [editingBrand, setEditingBrand] = useState<Brand | null>(null);

    const brands = supplier.brands ?? [];

    function handleDeleteBrand() {
        if (!deleteId) {
            return;
        }

        setDeleting(true);
        router.delete(
            brandDestroy.url({ supplier: supplier.id, brand: deleteId }),
            {
                preserveScroll: true,
                onFinish: () => {
                    setDeleting(false);
                    setDeleteId(null);
                },
            },
        );
    }

    return (
        <>
            <Head title={`Edit ${supplier.name}`} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title={supplier.name}
                        description={`Supplier ${supplier.supplier_code}`}
                    />
                    <Button variant="outline" asChild>
                        <Link href={index()}>Back to list</Link>
                    </Button>
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    <div className="lg:col-span-2">
                        <div className="rounded-xl border p-6">
                            <h3 className="mb-4 text-sm font-medium text-muted-foreground">
                                Supplier Details
                            </h3>
                            <Form<SupplierFormData>
                                {...update.form(supplier.id)}
                                className="space-y-6"
                            >
                                {({ processing, errors }) => (
                                    <SupplierFormFields
                                        errors={errors}
                                        processing={processing}
                                        supplier={supplier}
                                        submitLabel="Update Supplier"
                                    />
                                )}
                            </Form>
                        </div>
                    </div>

                    <div>
                        <div className="rounded-xl border">
                            <div className="flex items-center justify-between border-b px-4 py-3">
                                <h3 className="text-sm font-medium text-muted-foreground">
                                    Brands
                                </h3>
                            </div>

                            <div className="divide-y">
                                {brands.length === 0 && (
                                    <p className="px-4 py-6 text-center text-sm text-muted-foreground">
                                        No brands yet.
                                    </p>
                                )}
                                {brands.map((brand) => (
                                    <div
                                        key={brand.id}
                                        className="flex items-center justify-between px-4 py-2.5"
                                    >
                                        {editingBrand?.id === brand.id ? (
                                            <Form
                                                {...brandUpdate.form({
                                                    supplier: supplier.id,
                                                    brand: brand.id,
                                                })}
                                                className="flex flex-1 items-center gap-2"
                                                options={{
                                                    preserveScroll: true,
                                                }}
                                                onSuccess={() =>
                                                    setEditingBrand(null)
                                                }
                                            >
                                                {({ processing, errors }) => (
                                                    <>
                                                        <div className="flex-1">
                                                            <Input
                                                                name="name"
                                                                defaultValue={
                                                                    brand.name
                                                                }
                                                                autoFocus
                                                                className="h-8 text-sm"
                                                            />
                                                            <InputError
                                                                message={
                                                                    errors.name
                                                                }
                                                            />
                                                        </div>
                                                        <Button
                                                            type="submit"
                                                            size="sm"
                                                            variant="ghost"
                                                            disabled={
                                                                processing
                                                            }
                                                        >
                                                            Save
                                                        </Button>
                                                        <Button
                                                            type="button"
                                                            size="sm"
                                                            variant="ghost"
                                                            onClick={() =>
                                                                setEditingBrand(
                                                                    null,
                                                                )
                                                            }
                                                        >
                                                            <X className="size-3.5" />
                                                        </Button>
                                                    </>
                                                )}
                                            </Form>
                                        ) : (
                                            <>
                                                <span className="text-sm">
                                                    {brand.name}
                                                </span>
                                                <div className="flex items-center gap-0.5">
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="size-7"
                                                        onClick={() =>
                                                            setEditingBrand(
                                                                brand,
                                                            )
                                                        }
                                                    >
                                                        <Pencil className="size-3.5" />
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="size-7"
                                                        onClick={() =>
                                                            setDeleteId(
                                                                brand.id,
                                                            )
                                                        }
                                                    >
                                                        <Trash2 className="size-3.5 text-destructive" />
                                                    </Button>
                                                </div>
                                            </>
                                        )}
                                    </div>
                                ))}
                            </div>

                            <div className="border-t px-4 py-3">
                                <Form
                                    {...brandStore.form(supplier.id)}
                                    className="flex items-center gap-2"
                                    options={{ preserveScroll: true }}
                                    resetOnSuccess
                                >
                                    {({ processing, errors }) => (
                                        <>
                                            <div className="flex-1">
                                                <Input
                                                    name="name"
                                                    placeholder="New brand name"
                                                    className="h-8 text-sm"
                                                />
                                                <InputError
                                                    message={errors.name}
                                                />
                                            </div>
                                            <Button
                                                type="submit"
                                                size="sm"
                                                disabled={processing}
                                            >
                                                <Plus className="size-3.5" />
                                                Add
                                            </Button>
                                        </>
                                    )}
                                </Form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <Dialog
                open={deleteId !== null}
                onOpenChange={() => setDeleteId(null)}
            >
                <DialogContent>
                    <DialogTitle>Delete Brand</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete this brand? This action
                        cannot be undone.
                    </DialogDescription>
                    <DialogFooter>
                        <DialogClose asChild>
                            <Button variant="outline">Cancel</Button>
                        </DialogClose>
                        <Button
                            variant="destructive"
                            onClick={handleDeleteBrand}
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

SuppliersEdit.layout = {
    breadcrumbs: [
        {
            title: 'Suppliers',
            href: index.url(),
        },
        {
            title: 'Edit Supplier',
            href: '#',
        },
    ],
};
