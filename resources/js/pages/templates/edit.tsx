import { Form, Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import TemplateFormFields from '@/components/template-form';
import { Button } from '@/components/ui/button';
import { index, update } from '@/routes/templates';
import type { Template, TemplateFormData } from '@/types';

type Props = {
    template: Template;
    categories: { id: number; name: string }[];
    documentTypes: Record<string, string>;
};

export default function TemplatesEdit({
    template,
    categories,
    documentTypes,
}: Props) {
    return (
        <>
            <Head title={`Edit ${template.name}`} />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title={template.name}
                        description="Edit template details"
                    />
                    <Button variant="outline" asChild>
                        <Link href={index()}>Back to list</Link>
                    </Button>
                </div>

                <div className="rounded-xl border p-6">
                    <Form<TemplateFormData>
                        {...update.form(template.id)}
                        className="space-y-6"
                    >
                        {({ processing, errors }) => (
                            <TemplateFormFields
                                errors={errors}
                                processing={processing}
                                template={template}
                                categories={categories}
                                documentTypes={documentTypes}
                                submitLabel="Update Template"
                            />
                        )}
                    </Form>
                </div>
            </div>
        </>
    );
}

TemplatesEdit.layout = {
    breadcrumbs: [
        {
            title: 'Templates',
            href: index.url(),
        },
        {
            title: 'Edit Template',
            href: '#',
        },
    ],
};
