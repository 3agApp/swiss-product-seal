import { useState } from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { update } from '@/routes/products/safety-entry';
import type { ProductSafetyEntry, SafetyFormState } from '@/types';

type Props = {
    productId: number;
    initialSafetyEntry: ProductSafetyEntry | null;
    requiredDataFields: string[];
};

const FIELDS: { key: keyof SafetyFormState; label: string }[] = [
    { key: 'safety_text', label: 'Safety notice text' },
    { key: 'warning_text', label: 'Warning text' },
    { key: 'age_grading', label: 'Age grading' },
    { key: 'material_information', label: 'Material information' },
    { key: 'usage_restrictions', label: 'Usage restrictions' },
    { key: 'safety_instructions', label: 'Safety instructions' },
    { key: 'additional_notes', label: 'Additional notes' },
];

function getCsrfToken(): string {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
}

export default function ProductSafetyEntries({
    productId,
    initialSafetyEntry,
    requiredDataFields,
}: Props) {
    const [saving, setSaving] = useState(false);
    const [form, setForm] = useState<SafetyFormState>(() => ({
        safety_text: initialSafetyEntry?.safety_text ?? '',
        warning_text: initialSafetyEntry?.warning_text ?? '',
        age_grading: initialSafetyEntry?.age_grading ?? '',
        material_information: initialSafetyEntry?.material_information ?? '',
        usage_restrictions: initialSafetyEntry?.usage_restrictions ?? '',
        safety_instructions: initialSafetyEntry?.safety_instructions ?? '',
        additional_notes: initialSafetyEntry?.additional_notes ?? '',
    }));
    const [errors, setErrors] = useState<Partial<Record<keyof SafetyFormState, string>>>({});

    function handleChange(key: keyof SafetyFormState, value: string) {
        setForm((prev) => ({ ...prev, [key]: value }));
        setErrors((prev) => ({ ...prev, [key]: undefined }));
    }

    async function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        setSaving(true);
        setErrors({});

        try {
            const response = await fetch(update.url(productId), {
                method: 'PUT',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-XSRF-TOKEN': getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(form),
            });

            const payload = await response.json().catch(() => null);

            if (!response.ok) {
                if (payload?.errors) {
                    const mapped: Partial<Record<keyof SafetyFormState, string>> = {};
                    for (const [key, value] of Object.entries(payload.errors)) {
                        mapped[key as keyof SafetyFormState] = Array.isArray(value) ? value[0] : (value as string);
                    }
                    setErrors(mapped);
                }
                toast.error(payload?.message ?? 'Failed to save safety data.');
                return;
            }

            toast.success('Safety data saved successfully.');
        } catch {
            toast.error('An unexpected error occurred.');
        } finally {
            setSaving(false);
        }
    }

    return (
        <form onSubmit={handleSubmit} className="space-y-6 pt-4">
            <div className="grid gap-6 sm:grid-cols-2">
                {FIELDS.map(({ key, label }) => {
                    const isRequired = requiredDataFields.includes(key);

                    return (
                        <div key={key} className="grid gap-2">
                            <Label htmlFor={key}>
                                {label}
                                {isRequired && (
                                    <span className="ml-1 text-destructive">*</span>
                                )}
                            </Label>
                            <Textarea
                                id={key}
                                value={form[key]}
                                onChange={(e) => handleChange(key, e.target.value)}
                                placeholder={`Enter ${label.toLowerCase()}...`}
                                rows={3}
                            />
                            {errors[key] && (
                                <p className="text-sm text-destructive">{errors[key]}</p>
                            )}
                        </div>
                    );
                })}
            </div>

            <div className="flex items-center gap-4">
                <Button type="submit" disabled={saving}>
                    {saving && <Spinner />}
                    Save Safety Data
                </Button>
            </div>
        </form>
    );
}
