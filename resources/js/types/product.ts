export type Product = {
    id: number;
    name: string;
    internal_article_number: string | null;
    supplier_article_number: string | null;
    order_number: string | null;
    ean: string | null;
    supplier_id: number | null;
    brand_id: number | null;
    status: string | null;
    kontor_id: string | null;
    source_last_sync_at: string | null;
    public_uuid: string;
    created_at: string;
    updated_at: string;
    image_url: string | null;
    image_preview_url: string | null;
    supplier?: { id: number; name: string } | null;
    brand?: { id: number; name: string } | null;
};

export type ProductFormData = {
    name: string;
    internal_article_number: string;
    supplier_article_number: string;
    order_number: string;
    ean: string;
    supplier_id: string;
    brand_id: string;
    status: string;
    kontor_id: string;
    image: File | string;
};
