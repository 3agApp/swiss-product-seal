import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import type { Template, TemplateFormData } from '@/types';

type Props = {
    errors: Partial<
        Record<
            | keyof TemplateFormData
            | `required_document_types.${number}`
            | `required_data_fields.${number}`,
            string
        >
    >;
    processing: boolean;
    template?: Template;
    categories: { id: number; name: string }[];
    documentTypes: Record<string, string>;
    submitLabel: string;
};

const DATA_FIELDS: { value: string; label: string }[] = [
    { value: 'safety_text', label: 'Safety notice text' },
    { value: 'warning_text', label: 'Warning text' },
    { value: 'age_grading', label: 'Age grading' },
    { value: 'material_information', label: 'Material information' },
    { value: 'usage_restrictions', label: 'Usage restrictions' },
    { value: 'safety_instructions', label: 'Safety instructions' },
    { value: 'additional_notes', label: 'Additional notes' },
];

function toggleSet(
    set: Set<string>,
    value: string,
    checked: boolean,
): Set<string> {
    const next = new Set(set);

    if (checked) {
        next.add(value);
    } else {
        next.delete(value);
    }

    return next;
}

export default function TemplateFormFields({
    errors,
    processing,
    template,
    categories,
    documentTypes,
    submitLabel,
}: Props) {
    const [categoryId, setCategoryId] = useState<string>(
        template?.category_id?.toString() ?? '',
    );
    const [selectedDocTypes, setSelectedDocTypes] = useState<Set<string>>(
        () => new Set(template?.required_document_types ?? []),
    );
    const [selectedDataFields, setSelectedDataFields] = useState<Set<string>>(
        () => new Set(template?.required_data_fields ?? []),
    );

    const requiredDocTypes = Array.from(selectedDocTypes);
    const requiredDataFields = Array.from(selectedDataFields);

    return (
        <>
            <div className="grid gap-x-6 gap-y-6 sm:grid-cols-2 sm:items-start">
                <div className="grid gap-2">
                    <Label htmlFor="category_id">Category</Label>
                    <input
                        type="hidden"
                        name="category_id"
                        value={categoryId}
                    />
                    <Select
                        value={categoryId || '__placeholder__'}
                        onValueChange={(value) =>
                            setCategoryId(
                                value === '__placeholder__' ? '' : value,
                            )
                        }
                    >
                        <SelectTrigger className="w-full">
                            <SelectValue placeholder="Select a category" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="__placeholder__" disabled>
                                Select a category
                            </SelectItem>
                            {categories.map((category) => (
                                <SelectItem
                                    key={category.id}
                                    value={category.id.toString()}
                                >
                                    {category.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.category_id} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="name">Name</Label>
                    <Input
                        id="name"
                        name="name"
                        required
                        autoFocus
                        defaultValue={template?.name ?? ''}
                        placeholder="Template name"
                    />
                    <InputError message={errors.name} />
                </div>

                <div className="space-y-4 sm:col-span-2">
                    <div className="flex items-center justify-between">
                        <div>
                            <Label>Document Types</Label>
                            <p className="text-sm text-muted-foreground">
                                Select which document types are required for products
                                using this template.
                            </p>
                        </div>
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            onClick={() => {
                                const allKeys = Object.keys(documentTypes);
                                setSelectedDocTypes((prev) =>
                                    prev.size === allKeys.length
                                        ? new Set()
                                        : new Set(allKeys),
                                );
                            }}
                        >
                            {selectedDocTypes.size === Object.keys(documentTypes).length
                                ? 'Deselect all'
                                : 'Select all'}
                        </Button>
                    </div>

                    {/* Hidden inputs for form submission */}
                    {requiredDocTypes.map((type) => (
                        <input
                            key={`req-${type}`}
                            type="hidden"
                            name="required_document_types[]"
                            value={type}
                        />
                    ))}
                    {requiredDocTypes.length === 0 && (
                        <input
                            type="hidden"
                            name="required_document_types"
                            value=""
                        />
                    )}

                    <div className="rounded-lg border">
                        <div className="grid grid-cols-[1fr_5rem] items-center gap-4 border-b bg-muted/50 px-4 py-2.5 text-sm font-medium">
                            <span>Document Type</span>
                            <span className="text-center">Required</span>
                        </div>
                        {Object.entries(documentTypes).map(([value, label]) => (
                            <div
                                key={value}
                                className="grid grid-cols-[1fr_5rem] items-center gap-4 border-b px-4 py-2.5 last:border-0"
                            >
                                <span className="text-sm">{label}</span>
                                <div className="flex justify-center">
                                    <Checkbox
                                        checked={selectedDocTypes.has(value)}
                                        onCheckedChange={(checked) =>
                                            setSelectedDocTypes((prev) =>
                                                toggleSet(
                                                    prev,
                                                    value,
                                                    !!checked,
                                                ),
                                            )
                                        }
                                    />
                                </div>
                            </div>
                        ))}
                    </div>

                    <InputError message={errors.required_document_types} />
                </div>

                <div className="space-y-4 sm:col-span-2">
                    <div className="flex items-center justify-between">
                        <div>
                            <Label>Required Data Fields</Label>
                            <p className="text-sm text-muted-foreground">
                                Select which safety data fields are required for
                                products using this template.
                            </p>
                        </div>
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            onClick={() => {
                                const allKeys = DATA_FIELDS.map((f) => f.value);
                                setSelectedDataFields((prev) =>
                                    prev.size === allKeys.length
                                        ? new Set()
                                        : new Set(allKeys),
                                );
                            }}
                        >
                            {selectedDataFields.size === DATA_FIELDS.length
                                ? 'Deselect all'
                                : 'Select all'}
                        </Button>
                    </div>

                    {requiredDataFields.map((field) => (
                        <input
                            key={`df-${field}`}
                            type="hidden"
                            name="required_data_fields[]"
                            value={field}
                        />
                    ))}
                    {requiredDataFields.length === 0 && (
                        <input
                            type="hidden"
                            name="required_data_fields"
                            value=""
                        />
                    )}

                    <div className="rounded-lg border">
                        <div className="grid grid-cols-[1fr_5rem] items-center gap-4 border-b bg-muted/50 px-4 py-2.5 text-sm font-medium">
                            <span>Data Field</span>
                            <span className="text-center">Required</span>
                        </div>
                        {DATA_FIELDS.map(({ value, label }) => (
                            <div
                                key={value}
                                className="grid grid-cols-[1fr_5rem] items-center gap-4 border-b px-4 py-2.5 last:border-0"
                            >
                                <span className="text-sm">{label}</span>
                                <div className="flex justify-center">
                                    <Checkbox
                                        checked={selectedDataFields.has(value)}
                                        onCheckedChange={(checked) =>
                                            setSelectedDataFields((prev) =>
                                                toggleSet(
                                                    prev,
                                                    value,
                                                    !!checked,
                                                ),
                                            )
                                        }
                                    />
                                </div>
                            </div>
                        ))}
                    </div>

                    <InputError message={errors.required_data_fields} />
                </div>
            </div>

            <div className="flex items-center gap-4">
                <Button type="submit" disabled={processing}>
                    {processing && <Spinner />}
                    {submitLabel}
                </Button>
            </div>
        </>
    );
}
