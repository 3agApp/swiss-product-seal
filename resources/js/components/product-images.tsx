import { useCallback, useRef, useState } from 'react';
import { DragDropProvider } from '@dnd-kit/react';
import { useSortable } from '@dnd-kit/react/sortable';
import { GripVertical, Plus, Trash2, Upload } from 'lucide-react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { store, destroy, reorder } from '@/routes/products/images';
import type { ProductImage } from '@/types';

type Props = {
    productId: number;
    initialImages: ProductImage[];
};

const MAX_IMAGES = 10;

function getCsrfToken(): string {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
}

async function request(
    method: string,
    url: string,
    body?: FormData | string,
    contentType?: string,
): Promise<{ images: ProductImage[] }> {
    const headers: Record<string, string> = {
        Accept: 'application/json',
        'X-XSRF-TOKEN': getCsrfToken(),
        'X-Requested-With': 'XMLHttpRequest',
    };
    if (contentType) headers['Content-Type'] = contentType;

    const res = await fetch(url, { method, headers, body });

    if (!res.ok) {
        const error = await res.json().catch(() => ({}));
        throw new Error(error.message ?? 'Request failed');
    }

    return res.json();
}

function SortableImage({
    image,
    index,
    productId,
    onDelete,
    deletingId,
}: {
    image: ProductImage;
    index: number;
    productId: number;
    onDelete: (id: number) => void;
    deletingId: number | null;
}) {
    const { ref, handleRef, isDragging } = useSortable({
        id: image.id,
        index,
    });

    return (
        <div
            ref={ref}
            className={`group relative aspect-square overflow-hidden rounded-lg border bg-muted transition-opacity ${isDragging ? 'opacity-40' : ''}`}
        >
            <img
                src={image.preview_url || image.url}
                alt={image.name}
                className="size-full object-cover"
                draggable={false}
            />
            <div className="absolute inset-x-0 top-0 flex items-center justify-between p-1.5 opacity-0 transition-opacity group-hover:opacity-100">
                <button
                    type="button"
                    ref={handleRef}
                    className="cursor-grab rounded bg-black/50 p-1 text-white active:cursor-grabbing"
                >
                    <GripVertical className="size-3.5" />
                </button>
                <button
                    type="button"
                    disabled={deletingId === image.id}
                    onClick={() => onDelete(image.id)}
                    className="rounded bg-black/50 p-1 text-white transition-colors hover:bg-destructive"
                >
                    {deletingId === image.id ? (
                        <Spinner className="size-3.5" />
                    ) : (
                        <Trash2 className="size-3.5" />
                    )}
                </button>
            </div>
            {index === 0 && (
                <span className="absolute bottom-1.5 left-1.5 rounded bg-black/60 px-1.5 py-0.5 text-[10px] font-medium text-white">
                    Cover
                </span>
            )}
        </div>
    );
}

export default function ProductImages({ productId, initialImages }: Props) {
    const [images, setImages] = useState<ProductImage[]>(initialImages);
    const [uploading, setUploading] = useState(false);
    const [deletingId, setDeletingId] = useState<number | null>(null);
    const fileInputRef = useRef<HTMLInputElement>(null);

    async function handleUpload(files: FileList | null) {
        if (!files || files.length === 0) return;

        const remaining = MAX_IMAGES - images.length;
        if (remaining <= 0) {
            toast.warning('Maximum of 10 images reached.');
            return;
        }

        const filesToUpload = Array.from(files).slice(0, remaining);
        const formData = new FormData();
        filesToUpload.forEach((file) => formData.append('images[]', file));

        setUploading(true);
        try {
            const data = await request('POST', store.url(productId), formData);
            setImages(data.images);
            toast.success(
                `${filesToUpload.length} image${filesToUpload.length > 1 ? 's' : ''} uploaded.`,
            );
        } catch (err) {
            toast.error(
                err instanceof Error ? err.message : 'Upload failed.',
            );
        } finally {
            setUploading(false);
            if (fileInputRef.current) fileInputRef.current.value = '';
        }
    }

    async function handleDelete(mediaId: number) {
        setDeletingId(mediaId);
        try {
            const data = await request(
                'DELETE',
                destroy.url({ product: productId, media: mediaId }),
            );
            setImages(data.images);
            toast.success('Image removed.');
        } catch (err) {
            toast.error(
                err instanceof Error ? err.message : 'Delete failed.',
            );
        } finally {
            setDeletingId(null);
        }
    }

    const handleDragEnd = useCallback(
        async (event: {
            canceled: boolean;
            operation: { source: { id: unknown } | null; target: { id: unknown } | null };
        }) => {
            if (event.canceled) return;

            const sourceId = Number(event.operation.source?.id);
            const targetId = Number(event.operation.target?.id);
            if (!sourceId || !targetId || sourceId === targetId) return;

            const oldIndex = images.findIndex((img) => img.id === sourceId);
            const newIndex = images.findIndex((img) => img.id === targetId);
            if (oldIndex === -1 || newIndex === -1) return;

            const reordered = [...images];
            const [moved] = reordered.splice(oldIndex, 1);
            reordered.splice(newIndex, 0, moved);
            setImages(reordered);

            const ids = reordered.map((img) => img.id);
            try {
                await request(
                    'PUT',
                    reorder.url(productId),
                    JSON.stringify({ ids }),
                    'application/json',
                );
            } catch {
                setImages(images);
                toast.error('Failed to reorder images.');
            }
        },
        [images, productId],
    );

    return (
        <div className="space-y-4">
            <div className="flex items-center justify-between">
                <p className="text-sm text-muted-foreground">
                    {images.length} / {MAX_IMAGES} images
                </p>
                {images.length < MAX_IMAGES && (
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        disabled={uploading}
                        onClick={() => fileInputRef.current?.click()}
                    >
                        {uploading ? (
                            <Spinner />
                        ) : (
                            <Plus className="size-4" />
                        )}
                        Add images
                    </Button>
                )}
                <input
                    ref={fileInputRef}
                    type="file"
                    className="hidden"
                    multiple
                    accept="image/jpeg,image/png,image/webp"
                    onChange={(e) => handleUpload(e.target.files)}
                />
            </div>

            {images.length === 0 ? (
                <button
                    type="button"
                    disabled={uploading}
                    onClick={() => fileInputRef.current?.click()}
                    className="flex w-full flex-col items-center justify-center gap-2 rounded-lg border border-dashed p-8 text-muted-foreground transition-colors hover:border-foreground/25 hover:text-foreground"
                >
                    {uploading ? (
                        <Spinner />
                    ) : (
                        <Upload className="size-8 opacity-50" />
                    )}
                    <span className="text-sm">Click to upload images</span>
                    <span className="text-xs">
                        JPEG, PNG, WebP — max 10MB each
                    </span>
                </button>
            ) : (
                <DragDropProvider onDragEnd={handleDragEnd}>
                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                        {images.map((image, index) => (
                            <SortableImage
                                key={image.id}
                                image={image}
                                index={index}
                                productId={productId}
                                onDelete={handleDelete}
                                deletingId={deletingId}
                            />
                        ))}
                    </div>
                </DragDropProvider>
            )}
        </div>
    );
}
