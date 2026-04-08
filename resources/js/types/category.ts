export type Category = {
    id: number;
    name: string;
    description: string | null;
    created_at: string;
    updated_at: string;
    products_count?: number;
};

export type CategoryFormData = {
    name: string;
    description: string;
};
