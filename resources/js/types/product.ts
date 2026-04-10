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

export type ProductSafetyEntry = {
    id: number;
    product_id: number;
    safety_text: string | null;
    warning_text: string | null;
    age_grading: string | null;
    material_information: string | null;
    usage_restrictions: string | null;
    safety_instructions: string | null;
    additional_notes: string | null;
    created_at: string;
    updated_at: string;
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
    template_id: number;
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
    safety_entry?: ProductSafetyEntry | null;
    supplier?: { id: number; name: string } | null;
    brand?: { id: number; name: string } | null;
    category?: { id: number; name: string } | null;
    template?: { id: number; name: string; required_data_fields?: string[] } | null;
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
    template_id: string;
    status: string;
    kontor_id: string;
};

export type DuplicateStrategy = 'add_new' | 'replace_existing';

export type DocumentFormState = {
    file: File | null;
    type: string;
    expiry_date: string;
    review_comment: string;
    duplicate_strategy: DuplicateStrategy;
    replace_document_id: string;
};

export type DocumentFormErrors = Partial<Record<keyof DocumentFormState, string>>;

export type SafetyFormState = {
    safety_text: string;
    warning_text: string;
    age_grading: string;
    material_information: string;
    usage_restrictions: string;
    safety_instructions: string;
    additional_notes: string;
};

export type CategoryItem = { id: number; name: string; description: string | null };

export type TemplateItem = {
    id: number;
    name: string;
    category_id: number;
    required_document_types: string[];
};
