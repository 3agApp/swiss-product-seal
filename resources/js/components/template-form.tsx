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
    errors: Partial<Record<keyof TemplateFormData | `required_document_types.${number}` | `optional_document_types.${number}`, string>>;
    processing: boolean;
    template?: Template;
    categories: { id: number; name: string }[];
    documentTypes: Record<string, string>;
    submitLabel: string;
};

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
    const [requiredTypes, setRequiredTypes] = useState<string[]>(
        template?.required_document_types ?? [],
    );
    const [optionalTypes, setOptionalTypes] = useState<string[]>(
        template?.optional_document_types ?? [],
    );

    function toggleRequired(value: string, checked: boolean) {
        if (checked) {
            setRequiredTypes((prev) => [...prev, value]);
            setOptionalTypes((prev) => prev.filter((t) => t !== value));
        } else {
            setRequiredTypes((prev) => prev.filter((t) => t !== value));
        }
    }

    function toggleOptional(value: string, checked: boolean) {
        if (checked) {
            setOptionalTypes((prev) => [...prev, value]);
            setRequiredTypes((prev) => prev.filter((t) => t !== value));
        } else {
            setOptionalTypes((prev) => prev.filter((t) => t !== value));
        }
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

                <div className="grid gap-2 sm:col-span-2">
                    <Label>Required Document Types</Label>
                    {requiredTypes.map((type) => (
                        <input
                            key={type}
                            type="hidden"
                            name="required_document_types[]"
                            value={type}
                        />
                    ))}
                    {requiredTypes.length === 0 && (
                        <input type="hidden" name="required_document_types" value="" />
                    )}
                    <div className="grid gap-3 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                        {Object.entries(documentTypes).map(([value, label]) => (
                            <label
                                key={value}
                                className="flex items-center gap-2 text-sm"
                            >
                                <Checkbox
                                    checked={requiredTypes.includes(value)}
                                    onCheckedChange={(checked) =>
                                        toggleRequired(value, !!checked)
                                    }
                                />
                                {label}
                            </label>
                        ))}
                    </div>
                    <InputError message={errors.required_document_types} />
                </div>

                <div className="grid gap-2 sm:col-span-2">
                    <Label>Optional Document Types</Label>
                    {optionalTypes.map((type) => (
                        <input
                            key={type}
                            type="hidden"
                            name="optional_document_types[]"
                            value={type}
                        />
                    ))}
                    {optionalTypes.length === 0 && (
                        <input type="hidden" name="optional_document_types" value="" />
                    )}
                    <div className="grid gap-3 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                        {Object.entries(documentTypes).map(([value, label]) => (
                            <label
                                key={value}
                                className="flex items-center gap-2 text-sm"
                            >
                                <Checkbox
                                    checked={optionalTypes.includes(value)}
                                    onCheckedChange={(checked) =>
                                        toggleOptional(value, !!checked)
                                    }
                                />
                                {label}
                            </label>
                        ))}
                    </div>
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
