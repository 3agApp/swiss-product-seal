import { Form, Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import ProductFormFields from '@/components/product-form';
import ProductImages from '@/components/product-images';
import { Button } from '@/components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { index, update } from '@/routes/products';
import type { Product, ProductFormData } from '@/types';

type Props = {
    product: Product;
    suppliers: { id: number; name: string }[];
    brands: { id: number; name: string; supplier_id: number }[];
    statuses: Record<string, string>;
};

export default function ProductsEdit({
    product,
    suppliers,
    brands,
    statuses,
}: Props) {
    return (
        <>
            <Head title={`Edit ${product.name}`} />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title={product.name}
                        description={
                            product.internal_article_number
                                ? `Article ${product.internal_article_number}`
                                : 'Edit product details'
                        }
                    />
                    <Button variant="outline" asChild>
                        <Link href={index()}>Back to list</Link>
                    </Button>
                </div>

                <div className="rounded-xl border p-6">
                    <Form<ProductFormData>
                        {...update.form(product.id)}
                        className="space-y-6"
                    >
                        {({ processing, errors }) => (
                            <ProductFormFields
                                errors={errors}
                                processing={processing}
                                product={product}
                                suppliers={suppliers}
                                brands={brands}
                                statuses={statuses}
                                submitLabel="Update Product"
                            />
                        )}
                    </Form>
                </div>

                <div className="rounded-xl border p-6">
                    <Tabs defaultValue="images">
                        <TabsList>
                            <TabsTrigger value="images">Images</TabsTrigger>
                            <TabsTrigger value="documents">
                                Documents
                            </TabsTrigger>
                        </TabsList>
                        <TabsContent value="images">
                            <ProductImages
                                productId={product.id}
                                initialImages={product.images ?? []}
                            />
                        </TabsContent>
                        <TabsContent value="documents">
                            <p className="py-8 text-center text-sm text-muted-foreground">
                                No documents yet.
                            </p>
                        </TabsContent>
                    </Tabs>
                </div>
            </div>
        </>
    );
}

ProductsEdit.layout = {
    breadcrumbs: [
        {
            title: 'Products',
            href: index.url(),
        },
        {
            title: 'Edit Product',
            href: '#',
        },
    ],
};
