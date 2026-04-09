export type Template = {
    id: number;
    category_id: number;
    name: string;
    required_document_types: string[];
    optional_document_types: string[];
    created_at: string;
    updated_at: string;
    products_count?: number;
    category?: { id: number; name: string } | null;
};

export type TemplateFormData = {
    category_id: string;
    name: string;
    required_document_types: string[];
    optional_document_types: string[];
};
