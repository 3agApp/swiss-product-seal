import { useMemo, useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
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
import type { Product, ProductFormData } from '@/types';

type Props = {
    errors: Partial<Record<keyof ProductFormData, string>>;
    processing: boolean;
    product?: Product;
    suppliers: { id: number; name: string }[];
    brands: { id: number; name: string; supplier_id: number }[];
    categories: { id: number; name: string }[];
    templates: { id: number; name: string; category_id: number }[];
    statuses: Record<string, string>;
    submitLabel: string;
};

export default function ProductFormFields({
    errors,
    processing,
    product,
    suppliers,
    brands,
    categories,
    templates,
    statuses,
    submitLabel,
}: Props) {
    const [supplierId, setSupplierId] = useState<string>(
        product?.supplier_id?.toString() ?? '',
    );
    const [brandId, setBrandId] = useState<string>(
        product?.brand_id?.toString() ?? '',
    );
    const [categoryId, setCategoryId] = useState<string>(
        product?.category_id?.toString() ?? '',
    );
    const [templateId, setTemplateId] = useState<string>(
        product?.template_id?.toString() ?? '',
    );

    const filteredBrands = useMemo(
        () =>
            supplierId && supplierId !== '__none__'
                ? brands.filter((b) => b.supplier_id === Number(supplierId))
                : [],
        [brands, supplierId],
    );

    const filteredTemplates = useMemo(
        () =>
            categoryId && categoryId !== '__placeholder__'
                ? templates.filter((t) => t.category_id === Number(categoryId))
                : [],
        [templates, categoryId],
    );

    function handleSupplierChange(value: string) {
        setSupplierId(value === '__none__' ? '' : value);
        setBrandId('');
    }

    return (
        <>
            <div className="grid gap-x-6 gap-y-6 sm:grid-cols-2 sm:items-start">
                <div className="grid gap-2 sm:col-span-2">
                    <Label htmlFor="name">Product Name</Label>
                    <Input
                        id="name"
                        name="name"
                        required
                        autoFocus
                        defaultValue={product?.name ?? ''}
                        placeholder="Product name"
                    />
                    <InputError message={errors.name} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="internal_article_number">
                        Internal Article Number
                    </Label>
                    <Input
                        id="internal_article_number"
                        name="internal_article_number"
                        defaultValue={product?.internal_article_number ?? ''}
                        placeholder="e.g. INT-00001"
                    />
                    <InputError message={errors.internal_article_number} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="supplier_article_number">
                        Supplier Article Number
                    </Label>
                    <Input
                        id="supplier_article_number"
                        name="supplier_article_number"
                        defaultValue={product?.supplier_article_number ?? ''}
                        placeholder="e.g. SUP-00001"
                    />
                    <InputError message={errors.supplier_article_number} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="order_number">Order Number</Label>
                    <Input
                        id="order_number"
                        name="order_number"
                        defaultValue={product?.order_number ?? ''}
                        placeholder="e.g. ORD-00001"
                    />
                    <InputError message={errors.order_number} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="ean">EAN</Label>
                    <Input
                        id="ean"
                        name="ean"
                        defaultValue={product?.ean ?? ''}
                        placeholder="e.g. 4006381333931"
                    />
                    <InputError message={errors.ean} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="supplier_id">Supplier</Label>
                    <input
                        type="hidden"
                        name="supplier_id"
                        value={supplierId === '__none__' ? '' : supplierId}
                    />
                    <Select
                        value={supplierId || '__none__'}
                        onValueChange={handleSupplierChange}
                    >
                        <SelectTrigger className="w-full">
                            <SelectValue placeholder="Select a supplier" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="__none__">None</SelectItem>
                            {suppliers.map((supplier) => (
                                <SelectItem
                                    key={supplier.id}
                                    value={supplier.id.toString()}
                                >
                                    {supplier.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.supplier_id} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="brand_id">Brand</Label>
                    <input
                        type="hidden"
                        name="brand_id"
                        value={brandId === '__none__' ? '' : brandId}
                    />
                    <Select
                        value={brandId || '__none__'}
                        onValueChange={setBrandId}
                        disabled={!supplierId || supplierId === '__none__'}
                    >
                        <SelectTrigger className="w-full">
                            <SelectValue
                                placeholder={
                                    supplierId && supplierId !== '__none__'
                                        ? filteredBrands.length === 0
                                            ? 'No brands for this supplier'
                                            : 'Select a brand'
                                        : 'Select a supplier first'
                                }
                            />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="__none__">None</SelectItem>
                            {filteredBrands.map((brand) => (
                                <SelectItem
                                    key={brand.id}
                                    value={brand.id.toString()}
                                >
                                    {brand.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.brand_id} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="category_id">Category</Label>
                    <input type="hidden" name="category_id" value={categoryId} />
                    <Select
                        value={categoryId || '__placeholder__'}
                        onValueChange={(value) => {
                            const newCategoryId = value === '__placeholder__' ? '' : value;
                            setCategoryId(newCategoryId);
                            setTemplateId('');
                        }}
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
                    <Label htmlFor="template_id">Template</Label>
                    <input
                        type="hidden"
                        name="template_id"
                        value={templateId === '__placeholder__' ? '' : templateId}
                    />
                    <Select
                        value={templateId || '__placeholder__'}
                        onValueChange={(value) =>
                            setTemplateId(value === '__placeholder__' ? '' : value)
                        }
                        disabled={!categoryId || categoryId === '__placeholder__'}
                    >
                        <SelectTrigger className="w-full">
                            <SelectValue
                                placeholder={
                                    categoryId && categoryId !== '__placeholder__'
                                        ? filteredTemplates.length === 0
                                            ? 'No templates for this category'
                                            : 'Select a template'
                                        : 'Select a category first'
                                }
                            />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="__placeholder__" disabled>
                                Select a template
                            </SelectItem>
                            {filteredTemplates.map((template) => (
                                <SelectItem
                                    key={template.id}
                                    value={template.id.toString()}
                                >
                                    {template.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.template_id} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="status">Status</Label>
                    <Select
                        name="status"
                        defaultValue={product?.status ?? 'open'}
                    >
                        <SelectTrigger className="w-full">
                            <SelectValue placeholder="Select a status" />
                        </SelectTrigger>
                        <SelectContent>
                            {Object.entries(statuses).map(([value, label]) => (
                                <SelectItem key={value} value={value}>
                                    {label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.status} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="kontor_id">Kontor ID</Label>
                    <Input
                        id="kontor_id"
                        name="kontor_id"
                        defaultValue={product?.kontor_id ?? ''}
                        placeholder="e.g. KON-0001"
                    />
                    <InputError message={errors.kontor_id} />
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
