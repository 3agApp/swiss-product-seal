import { useMemo, useState } from 'react';
import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, Check, FileText, Layers, Package } from 'lucide-react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import ProductFormFields from '@/components/product-form';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { create, index, store } from '@/routes/products';
import type { ProductFormData } from '@/types';
import { cn } from '@/lib/utils';

type CategoryItem = { id: number; name: string; description: string | null };
type TemplateItem = {
    id: number;
    name: string;
    category_id: number;
    required_document_types: string[];
    optional_document_types: string[];
};

type Props = {
    suppliers: { id: number; name: string }[];
    brands: { id: number; name: string; supplier_id: number }[];
    categories: CategoryItem[];
    templates: TemplateItem[];
    statuses: Record<string, string>;
    documentTypes: Record<string, string>;
};

const STEPS = [
    { label: 'Category', icon: Layers },
    { label: 'Template', icon: FileText },
    { label: 'Product Details', icon: Package },
] as const;

function StepIndicator({ currentStep }: { currentStep: number }) {
    return (
        <nav aria-label="Progress" className="mb-8">
            <ol className="flex items-center justify-center gap-2">
                {STEPS.map((step, idx) => {
                    const StepIcon = step.icon;
                    const isCompleted = idx < currentStep;
                    const isCurrent = idx === currentStep;

                    return (
                        <li key={step.label} className="flex items-center gap-2">
                            <div
                                className={cn(
                                    'flex items-center gap-2 rounded-full px-4 py-2 text-sm font-medium transition-colors',
                                    isCompleted && 'bg-primary/10 text-primary',
                                    isCurrent && 'bg-primary text-primary-foreground',
                                    !isCompleted && !isCurrent && 'bg-muted text-muted-foreground',
                                )}
                            >
                                {isCompleted ? (
                                    <Check className="size-4" />
                                ) : (
                                    <StepIcon className="size-4" />
                                )}
                                <span className="hidden sm:inline">{step.label}</span>
                            </div>
                            {idx < STEPS.length - 1 && (
                                <div
                                    className={cn(
                                        'h-px w-8 transition-colors',
                                        idx < currentStep ? 'bg-primary' : 'bg-border',
                                    )}
                                />
                            )}
                        </li>
                    );
                })}
            </ol>
        </nav>
    );
}

function CategoryStep({
    categories,
    selectedId,
    onSelect,
}: {
    categories: CategoryItem[];
    selectedId: number | null;
    onSelect: (category: CategoryItem) => void;
}) {
    return (
        <div className="space-y-4">
            <Heading title="Select a Category" description="Choose the product category to get started" variant="small" />
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                {categories.map((category) => (
                    <Card
                        key={category.id}
                        className={cn(
                            'cursor-pointer transition-all hover:border-primary/50 hover:shadow-md',
                            selectedId === category.id && 'border-primary ring-2 ring-primary/20',
                        )}
                        onClick={() => onSelect(category)}
                    >
                        <CardHeader>
                            <div className="flex items-center justify-between">
                                <CardTitle className="text-base">{category.name}</CardTitle>
                                {selectedId === category.id && (
                                    <div className="flex size-5 items-center justify-center rounded-full bg-primary text-primary-foreground">
                                        <Check className="size-3" />
                                    </div>
                                )}
                            </div>
                            {category.description && (
                                <CardDescription>{category.description}</CardDescription>
                            )}
                        </CardHeader>
                    </Card>
                ))}
            </div>
        </div>
    );
}

function TemplateStep({
    templates,
    selectedId,
    documentTypes,
    onSelect,
}: {
    templates: TemplateItem[];
    selectedId: number | null;
    documentTypes: Record<string, string>;
    onSelect: (template: TemplateItem) => void;
}) {
    if (templates.length === 0) {
        return (
            <div className="py-12 text-center text-muted-foreground">
                No templates available for this category.
            </div>
        );
    }

    return (
        <div className="space-y-4">
            <Heading title="Select a Template" description="Choose a compliance template that defines required documents" variant="small" />
            <div className="grid gap-4 sm:grid-cols-2">
                {templates.map((template) => (
                    <Card
                        key={template.id}
                        className={cn(
                            'cursor-pointer transition-all hover:border-primary/50 hover:shadow-md',
                            selectedId === template.id && 'border-primary ring-2 ring-primary/20',
                        )}
                        onClick={() => onSelect(template)}
                    >
                        <CardHeader>
                            <div className="flex items-center justify-between">
                                <CardTitle className="text-base">{template.name}</CardTitle>
                                {selectedId === template.id && (
                                    <div className="flex size-5 items-center justify-center rounded-full bg-primary text-primary-foreground">
                                        <Check className="size-3" />
                                    </div>
                                )}
                            </div>
                        </CardHeader>
                        <CardContent>
                            {template.required_document_types.length > 0 && (
                                <div className="space-y-2">
                                    <p className="text-xs font-medium text-muted-foreground uppercase tracking-wide">Required Documents</p>
                                    <div className="flex flex-wrap gap-1.5">
                                        {template.required_document_types.map((type) => (
                                            <Badge key={type} variant="secondary" className="text-xs">
                                                {documentTypes[type] ?? type}
                                            </Badge>
                                        ))}
                                    </div>
                                </div>
                            )}
                            {template.optional_document_types.length > 0 && (
                                <div className="mt-3 space-y-2">
                                    <p className="text-xs font-medium text-muted-foreground uppercase tracking-wide">Optional Documents</p>
                                    <div className="flex flex-wrap gap-1.5">
                                        {template.optional_document_types.map((type) => (
                                            <Badge key={type} variant="outline" className="text-xs">
                                                {documentTypes[type] ?? type}
                                            </Badge>
                                        ))}
                                    </div>
                                </div>
                            )}
                            {template.required_document_types.length === 0 && template.optional_document_types.length === 0 && (
                                <p className="text-sm text-muted-foreground">No document requirements defined.</p>
                            )}
                        </CardContent>
                    </Card>
                ))}
            </div>
        </div>
    );
}

export default function ProductsCreate({ suppliers, brands, categories, templates, statuses, documentTypes }: Props) {
    const [step, setStep] = useState(0);
    const [selectedCategory, setSelectedCategory] = useState<CategoryItem | null>(null);
    const [selectedTemplate, setSelectedTemplate] = useState<TemplateItem | null>(null);

    const filteredTemplates = useMemo(
        () => (selectedCategory ? templates.filter((t) => t.category_id === selectedCategory.id) : []),
        [templates, selectedCategory],
    );

    function handleCategorySelect(category: CategoryItem) {
        if (selectedCategory?.id !== category.id) {
            setSelectedTemplate(null);
        }
        setSelectedCategory(category);
    }

    function handleTemplateSelect(template: TemplateItem) {
        setSelectedTemplate(template);
    }

    function canProceed(): boolean {
        if (step === 0) {
            return selectedCategory !== null;
        }
        if (step === 1) {
            return selectedTemplate !== null;
        }
        return true;
    }

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
                    <StepIndicator currentStep={step} />

                    {step === 0 && (
                        <CategoryStep
                            categories={categories}
                            selectedId={selectedCategory?.id ?? null}
                            onSelect={handleCategorySelect}
                        />
                    )}

                    {step === 1 && (
                        <TemplateStep
                            templates={filteredTemplates}
                            selectedId={selectedTemplate?.id ?? null}
                            documentTypes={documentTypes}
                            onSelect={handleTemplateSelect}
                        />
                    )}

                    {step === 2 && selectedCategory && selectedTemplate && (
                        <div className="space-y-6">
                            <Heading title="Product Details" description="Fill in the product information" variant="small" />

                            <div className="flex flex-wrap gap-3 rounded-lg border bg-muted/50 p-4">
                                <div className="flex items-center gap-2 text-sm">
                                    <Layers className="size-4 text-muted-foreground" />
                                    <span className="text-muted-foreground">Category:</span>
                                    <span className="font-medium">{selectedCategory.name}</span>
                                </div>
                                <div className="hidden h-4 w-px bg-border sm:block" />
                                <div className="flex items-center gap-2 text-sm">
                                    <FileText className="size-4 text-muted-foreground" />
                                    <span className="text-muted-foreground">Template:</span>
                                    <span className="font-medium">{selectedTemplate.name}</span>
                                </div>
                            </div>

                            <Form<ProductFormData>
                                {...store.form()}
                                className="space-y-6"
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <input type="hidden" name="category_id" value={selectedCategory.id} />
                                        <input type="hidden" name="template_id" value={selectedTemplate.id} />
                                        <ProductFormFields
                                            errors={errors}
                                            processing={processing}
                                            suppliers={suppliers}
                                            brands={brands}
                                            categories={categories}
                                            templates={templates}
                                            statuses={statuses}
                                            submitLabel="Create Product"
                                            hideCategoryTemplate
                                        />
                                    </>
                                )}
                            </Form>
                        </div>
                    )}

                    {step < 2 && (
                        <div className="mt-8 flex items-center justify-between">
                            <Button
                                variant="outline"
                                onClick={() => setStep((s) => s - 1)}
                                disabled={step === 0}
                            >
                                <ArrowLeft className="size-4" />
                                Back
                            </Button>
                            <Button
                                onClick={() => setStep((s) => s + 1)}
                                disabled={!canProceed()}
                            >
                                Next
                                <ArrowRight className="size-4" />
                            </Button>
                        </div>
                    )}
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
