import { useRef, useState } from 'react';
import {
    DndContext,
    closestCenter,
    KeyboardSensor,
    PointerSensor,
    useSensor,
    useSensors,
    type DragEndEvent,
} from '@dnd-kit/core';
import {
    arrayMove,
    SortableContext,
    sortableKeyboardCoordinates,
    useSortable,
    rectSortingStrategy,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
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
    onDelete,
    deletingId,
    isFirst,
}: {
    image: ProductImage;
    onDelete: (id: number) => void;
    deletingId: number | null;
    isFirst: boolean;
}) {
    const {
        attributes,
        listeners,
        setNodeRef,
        transform,
        transition,
        isDragging,
    } = useSortable({ id: image.id });

    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
    };

    return (
        <div
            ref={setNodeRef}
            style={style}
            className={`group relative aspect-square overflow-hidden rounded-lg border bg-muted ${isDragging ? 'z-10 opacity-50 shadow-lg' : ''}`}
        >
            <img
                src={image.preview_url || image.url}
                alt={image.name}
                className="size-full object-cover"
                draggable={false}
            />
            <div className="absolute inset-x-0 top-0 flex items-center justify-between p-1.5">
                <button
                    type="button"
                    className="cursor-grab rounded bg-black/50 p-1 text-white opacity-60 transition-opacity hover:opacity-100 active:cursor-grabbing"
                    {...attributes}
                    {...listeners}
                >
                    <GripVertical className="size-3.5" />
                </button>
                <button
                    type="button"
                    disabled={deletingId === image.id}
                    onClick={() => onDelete(image.id)}
                    className="rounded bg-black/50 p-1 text-white opacity-0 transition-opacity hover:bg-destructive group-hover:opacity-100"
                >
                    {deletingId === image.id ? (
                        <Spinner className="size-3.5" />
                    ) : (
                        <Trash2 className="size-3.5" />
                    )}
                </button>
            </div>
            {isFirst && (
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

    const sensors = useSensors(
        useSensor(PointerSensor, {
            activationConstraint: { distance: 5 },
        }),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        }),
    );

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

    async function handleDragEnd(event: DragEndEvent) {
        const { active, over } = event;
        if (!over || active.id === over.id) return;

        const oldIndex = images.findIndex((img) => img.id === active.id);
        const newIndex = images.findIndex((img) => img.id === over.id);
        if (oldIndex === -1 || newIndex === -1) return;

        const reordered = arrayMove(images, oldIndex, newIndex);
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
    }

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
                <DndContext
                    sensors={sensors}
                    collisionDetection={closestCenter}
                    onDragEnd={handleDragEnd}
                >
                    <SortableContext
                        items={images.map((img) => img.id)}
                        strategy={rectSortingStrategy}
                    >
                        <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                            {images.map((image, index) => (
                                <SortableImage
                                    key={image.id}
                                    image={image}
                                    onDelete={handleDelete}
                                    deletingId={deletingId}
                                    isFirst={index === 0}
                                />
                            ))}
                        </div>
                    </SortableContext>
                </DndContext>
            )}
        </div>
    );
}
