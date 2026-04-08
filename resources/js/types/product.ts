export type ProductImage = {
    id: number;
    url: string;
    preview_url: string;
    name: string;
    order: number;
};

export type ProductDocumentVersion = {
    id: number;
    type: string;
    type_label: string;
    version: number;
    expiry_date: string | null;
    review_comment: string | null;
    file_name: string | null;
    file_url: string | null;
    file_size: number | null;
    mime_type: string | null;
    uploaded_at: string | null;
    updated_at: string | null;
    replaces_document_id: number | null;
    is_current: boolean;
};

export type ProductDocument = ProductDocumentVersion & {
    history: ProductDocumentVersion[];
};

export type Product = {
    id: number;
    name: string;
    internal_article_number: string | null;
    supplier_article_number: string | null;
    order_number: string | null;
    ean: string | null;
    supplier_id: number | null;
    brand_id: number | null;
    category_id: number | null;
    status: string | null;
    kontor_id: string | null;
    source_last_sync_at: string | null;
    public_uuid: string;
    created_at: string;
    updated_at: string;
    image_url: string | null;
    image_preview_url: string | null;
    images?: ProductImage[];
    documents?: ProductDocument[];
    supplier?: { id: number; name: string } | null;
    brand?: { id: number; name: string } | null;
    category?: { id: number; name: string } | null;
};

export type ProductFormData = {
    name: string;
    internal_article_number: string;
    supplier_article_number: string;
    order_number: string;
    ean: string;
    supplier_id: string;
    brand_id: string;
    category_id: string;
    status: string;
    kontor_id: string;
};
