import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import type { Supplier, SupplierFormData } from '@/types';

type Props = {
    errors: Partial<Record<keyof SupplierFormData, string>>;
    processing: boolean;
    supplier?: Supplier;
    submitLabel: string;
};

export default function SupplierFormFields({
    errors,
    processing,
    supplier,
    submitLabel,
}: Props) {
    return (
        <>
            <div className="grid gap-x-6 gap-y-6 sm:grid-cols-2 sm:items-start">
                <div className="grid gap-2">
                    <Label htmlFor="supplier_code">Supplier Code</Label>
                    <Input
                        id="supplier_code"
                        name="supplier_code"
                        required
                        autoFocus
                        defaultValue={supplier?.supplier_code ?? ''}
                        placeholder="e.g. SUP-00001"
                    />
                    <InputError message={errors.supplier_code} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="name">Name</Label>
                    <Input
                        id="name"
                        name="name"
                        required
                        defaultValue={supplier?.name ?? ''}
                        placeholder="Company name"
                    />
                    <InputError message={errors.name} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="email">Email</Label>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        defaultValue={supplier?.email ?? ''}
                        placeholder="contact@company.com"
                    />
                    <InputError message={errors.email} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="phone">Phone</Label>
                    <Input
                        id="phone"
                        name="phone"
                        defaultValue={supplier?.phone ?? ''}
                        placeholder="+1 234 567 890"
                    />
                    <InputError message={errors.phone} />
                </div>

                <div className="grid gap-2 sm:col-span-2">
                    <Label htmlFor="address">Address</Label>
                    <Input
                        id="address"
                        name="address"
                        defaultValue={supplier?.address ?? ''}
                        placeholder="Full address"
                    />
                    <InputError message={errors.address} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="country">Country</Label>
                    <Input
                        id="country"
                        name="country"
                        defaultValue={supplier?.country ?? ''}
                        placeholder="e.g. Germany, China, United States"
                    />
                    <InputError message={errors.country} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="kontor_id">Kontor ID</Label>
                    <Input
                        id="kontor_id"
                        name="kontor_id"
                        defaultValue={supplier?.kontor_id ?? ''}
                        placeholder="e.g. KON-0001"
                    />
                    <InputError message={errors.kontor_id} />
                </div>
            </div>

            <div className="flex items-center gap-3">
                <Checkbox
                    id="active"
                    name="active"
                    defaultChecked={supplier?.active ?? true}
                    value="1"
                />
                <Label htmlFor="active">Active</Label>
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
