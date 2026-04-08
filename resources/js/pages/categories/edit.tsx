import { Form, Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import CategoryFormFields from '@/components/category-form';
import { Button } from '@/components/ui/button';
import { index, update } from '@/routes/categories';
import type { Category, CategoryFormData } from '@/types';

type Props = {
    category: Category;
};

export default function CategoriesEdit({ category }: Props) {
    return (
        <>
            <Head title={`Edit ${category.name}`} />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title={category.name}
                        description="Edit category details"
                    />
                    <Button variant="outline" asChild>
                        <Link href={index()}>Back to list</Link>
                    </Button>
                </div>

                <div className="rounded-xl border p-6">
                    <Form<CategoryFormData>
                        {...update.form(category.id)}
                        className="space-y-6"
                    >
                        {({ processing, errors }) => (
                            <CategoryFormFields
                                errors={errors}
                                processing={processing}
                                category={category}
                                submitLabel="Update Category"
                            />
                        )}
                    </Form>
                </div>
            </div>
        </>
    );
}

CategoriesEdit.layout = {
    breadcrumbs: [
        {
            title: 'Categories',
            href: index.url(),
        },
        {
            title: 'Edit Category',
            href: '#',
        },
    ],
};
