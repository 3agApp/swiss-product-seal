import { Form, Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import TemplateFormFields from '@/components/template-form';
import { Button } from '@/components/ui/button';
import { index, create, store } from '@/routes/templates';
import type { TemplateFormData } from '@/types';

type Props = {
    categories: { id: number; name: string }[];
    documentTypes: Record<string, string>;
};

export default function TemplatesCreate({ categories, documentTypes }: Props) {
    return (
        <>
            <Head title="Add Template" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Add Template"
                        description="Create a new document requirement template"
                    />
                    <Button variant="outline" asChild>
                        <Link href={index()}>Back to list</Link>
                    </Button>
                </div>

                <div className="rounded-xl border p-6">
                    <Form<TemplateFormData>
                        {...store.form()}
                        className="space-y-6"
                    >
                        {({ processing, errors }) => (
                            <TemplateFormFields
                                errors={errors}
                                processing={processing}
                                categories={categories}
                                documentTypes={documentTypes}
                                submitLabel="Create Template"
                            />
                        )}
                    </Form>
                </div>
            </div>
        </>
    );
}

TemplatesCreate.layout = {
    breadcrumbs: [
        {
            title: 'Templates',
            href: index.url(),
        },
        {
            title: 'Add Template',
            href: create.url(),
        },
    ],
};
