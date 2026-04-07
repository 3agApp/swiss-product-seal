import { Form, Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import ProductFormFields from '@/components/product-form';
import { Button } from '@/components/ui/button';
import { create, index, store } from '@/routes/products';
import type { ProductFormData } from '@/types';

type Props = {
    suppliers: { id: number; name: string }[];
    brands: { id: number; name: string; supplier_id: number }[];
    statuses: Record<string, string>;
};

export default function ProductsCreate({ suppliers, brands, statuses }: Props) {
    return (
        <>
            <Head title="Add Product" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Add Product"
                        description="Create a new product in the catalog"
                    />
                    <Button variant="outline" asChild>
                        <Link href={index()}>Back to list</Link>
                    </Button>
                </div>

                <div className="rounded-xl border p-6">
                    <Form<ProductFormData>
                        {...store.form()}
                        className="space-y-6"
                    >
                        {({ processing, errors }) => (
                            <ProductFormFields
                                errors={errors}
                                processing={processing}
                                suppliers={suppliers}
                                brands={brands}
                                statuses={statuses}
                                submitLabel="Create Product"
                            />
                        )}
                    </Form>
                </div>
            </div>
        </>
    );
}

ProductsCreate.layout = {
    breadcrumbs: [
        {
            title: 'Products',
            href: index.url(),
        },
        {
            title: 'Add Product',
            href: create.url(),
        },
    ],
};
