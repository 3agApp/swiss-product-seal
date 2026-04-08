import { Form, Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import CategoryFormFields from '@/components/category-form';
import { Button } from '@/components/ui/button';
import { store, index, create } from '@/routes/categories';
import type { CategoryFormData } from '@/types';

export default function CategoriesCreate() {
    return (
        <>
            <Head title="Add Category" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Add Category"
                        description="Create a new product category"
                    />
                    <Button variant="outline" asChild>
                        <Link href={index()}>Back to list</Link>
                    </Button>
                </div>

                <div className="rounded-xl border p-6">
                    <Form<CategoryFormData>
                        {...store.form()}
                        className="space-y-6"
                    >
                        {({ processing, errors }) => (
                            <CategoryFormFields
                                errors={errors}
                                processing={processing}
                                submitLabel="Create Category"
                            />
                        )}
                    </Form>
                </div>
            </div>
        </>
    );
}

CategoriesCreate.layout = {
    breadcrumbs: [
        {
            title: 'Categories',
            href: index.url(),
        },
        {
            title: 'Add Category',
            href: create.url(),
        },
    ],
};
