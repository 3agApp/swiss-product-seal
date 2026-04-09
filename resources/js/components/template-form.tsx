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

type Requirement = 'none' | 'required' | 'optional';

type Props = {
    errors: Partial<Record<keyof TemplateFormData | `required_document_types.${number}` | `optional_document_types.${number}`, string>>;
    processing: boolean;
    template?: Template;
    categories: { id: number; name: string }[];
    documentTypes: Record<string, string>;
    submitLabel: string;
};

function buildInitialAssignments(
    documentTypes: Record<string, string>,
    requiredTypes: string[],
    optionalTypes: string[],
): Record<string, Requirement> {
    const assignments: Record<string, Requirement> = {};
    for (const key of Object.keys(documentTypes)) {
        if (requiredTypes.includes(key)) {
            assignments[key] = 'required';
        } else if (optionalTypes.includes(key)) {
            assignments[key] = 'optional';
        } else {
            assignments[key] = 'none';
        }
    }
    return assignments;
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
    const [assignments, setAssignments] = useState<Record<string, Requirement>>(
        () =>
            buildInitialAssignments(
                documentTypes,
                template?.required_document_types ?? [],
                template?.optional_document_types ?? [],
            ),
    );

    const requiredTypes = Object.entries(assignments)
        .filter(([, v]) => v === 'required')
        .map(([k]) => k);
    const optionalTypes = Object.entries(assignments)
        .filter(([, v]) => v === 'optional')
        .map(([k]) => k);

    function handleAssignmentChange(docType: string, value: Requirement) {
        setAssignments((prev) => ({ ...prev, [docType]: value }));
    }

    return (
        <>
            <div className="grid gap-x-6 gap-y-6 sm:grid-cols-2 sm:items-start">
                <div className="grid gap-2">
                    <Label htmlFor="category_id">Category</Label>
                    <input type="hidden" name="category_id" value={categoryId} />
                    <Select
                        value={categoryId || '__placeholder__'}
                        onValueChange={(value) =>
                            setCategoryId(value === '__placeholder__' ? '' : value)
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
                    <Label>Document Types</Label>
                    <p className="text-sm text-muted-foreground">
                        For each document type, select whether it is required, optional, or not included in this template.
                    </p>

                    {/* Hidden inputs for form submission */}
                    {requiredTypes.map((type) => (
                        <input
                            key={`req-${type}`}
                            type="hidden"
                            name="required_document_types[]"
                            value={type}
                        />
                    ))}
                    {requiredTypes.length === 0 && (
                        <input type="hidden" name="required_document_types" value="" />
                    )}
                    {optionalTypes.map((type) => (
                        <input
                            key={`opt-${type}`}
                            type="hidden"
                            name="optional_document_types[]"
                            value={type}
                        />
                    ))}
                    {optionalTypes.length === 0 && (
                        <input type="hidden" name="optional_document_types" value="" />
                    )}

                    <div className="rounded-lg border">
                        <div className="grid grid-cols-[1fr_5rem_5rem] items-center gap-4 border-b bg-muted/50 px-4 py-2.5 text-sm font-medium">
                            <span>Document Type</span>
                            <span className="text-center">Required</span>
                            <span className="text-center">Optional</span>
                        </div>
                        {Object.entries(documentTypes).map(([value, label]) => (
                            <div
                                key={value}
                                className="grid grid-cols-[1fr_5rem_5rem] items-center gap-4 border-b px-4 py-2.5 last:border-0"
                            >
                                <span className="text-sm">{label}</span>
                                <div className="flex justify-center">
                                    <Checkbox
                                        checked={assignments[value] === 'required'}
                                        onCheckedChange={(checked) =>
                                            handleAssignmentChange(value, checked ? 'required' : 'none')
                                        }
                                    />
                                </div>
                                <div className="flex justify-center">
                                    <Checkbox
                                        checked={assignments[value] === 'optional'}
                                        onCheckedChange={(checked) =>
                                            handleAssignmentChange(value, checked ? 'optional' : 'none')
                                        }
                                    />
                                </div>
                            </div>
                        ))}
                    </div>

                    <InputError message={errors.required_document_types} />
                    <InputError message={errors.optional_document_types} />
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
