import { Form, Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import SupplierFormFields from '@/components/supplier-form';
import { Button } from '@/components/ui/button';
import { store, index, create } from '@/routes/suppliers';
import type { SupplierFormData } from '@/types';

export default function SuppliersCreate() {
    return (
        <>
            <Head title="Add Supplier" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Add Supplier"
                        description="Create a new supplier in the directory"
                    />
                    <Button variant="outline" asChild>
                        <Link href={index()}>Back to list</Link>
                    </Button>
                </div>

                <div className="rounded-xl border p-6">
                    <Form<SupplierFormData>
                        {...store.form()}
                        className="space-y-6"
                    >
                        {({ processing, errors }) => (
                            <SupplierFormFields
                                errors={errors}
                                processing={processing}
                                submitLabel="Create Supplier"
                            />
                        )}
                    </Form>
                </div>
            </div>
        </>
    );
}

SuppliersCreate.layout = {
    breadcrumbs: [
        {
            title: 'Suppliers',
            href: index.url(),
        },
        {
            title: 'Add Supplier',
            href: create.url(),
        },
    ],
};
