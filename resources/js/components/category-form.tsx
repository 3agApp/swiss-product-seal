import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import type { Category, CategoryFormData } from '@/types';

type Props = {
    errors: Partial<Record<keyof CategoryFormData, string>>;
    processing: boolean;
    category?: Category;
    submitLabel: string;
};

export default function CategoryFormFields({
    errors,
    processing,
    category,
    submitLabel,
}: Props) {
    return (
        <>
            <div className="grid gap-x-6 gap-y-6 sm:grid-cols-2 sm:items-start">
                <div className="grid gap-2 sm:col-span-2">
                    <Label htmlFor="name">Name</Label>
                    <Input
                        id="name"
                        name="name"
                        required
                        autoFocus
                        defaultValue={category?.name ?? ''}
                        placeholder="Category name"
                    />
                    <InputError message={errors.name} />
                </div>

                <div className="grid gap-2 sm:col-span-2">
                    <Label htmlFor="description">Description</Label>
                    <Input
                        id="description"
                        name="description"
                        defaultValue={category?.description ?? ''}
                        placeholder="Optional description"
                    />
                    <InputError message={errors.description} />
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
