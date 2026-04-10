import { useMemo, useState } from 'react';
import {
    Download,
    FilePlus2,
    FileText,
    History,
    RefreshCw,
} from 'lucide-react';
import { toast } from 'sonner';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';
import { store } from '@/routes/products/documents';
import type { DocumentFormErrors, DocumentFormState, DuplicateStrategy, ProductDocument } from '@/types';

type Props = {
    productId: number;
    documentTypes: Record<string, string>;
    initialDocuments: ProductDocument[];
};

class RequestError extends Error {
    public errors: DocumentFormErrors;

    constructor(message: string, errors: DocumentFormErrors = {}) {
        super(message);
        this.name = 'RequestError';
        this.errors = errors;
    }
}

const defaultFormState: DocumentFormState = {
    file: null,
    type: '',
    expiry_date: '',
    review_comment: '',
    duplicate_strategy: 'add_new',
    replace_document_id: '',
};

function getCsrfToken(): string {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : '';
}

function mapErrors(errors: Record<string, string[]> | undefined): DocumentFormErrors {
    if (!errors) {
        return {};
    }

    return Object.fromEntries(
        Object.entries(errors).map(([key, value]) => [key, value[0]]),
    ) as DocumentFormErrors;
}

async function request(
    url: string,
    body: FormData,
): Promise<{ documents: ProductDocument[] }> {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'X-XSRF-TOKEN': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        body,
    });

    const payload = await response.json().catch(() => null);

    if (!response.ok) {
        throw new RequestError(
            payload?.message ?? 'Document upload failed.',
            mapErrors(payload?.errors),
        );
    }

    return payload;
}

function formatDate(value: string | null): string {
    if (!value) {
        return 'Not set';
    }

    const normalizedValue = /^\d{4}-\d{2}-\d{2}$/.test(value)
        ? `${value}T12:00:00`
        : value;

    return new Intl.DateTimeFormat(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    }).format(new Date(normalizedValue));
}

function formatBytes(value: number | null): string {
    if (!value) {
        return 'Unknown size';
    }

    if (value < 1024) {
        return `${value} B`;
    }

    const units = ['KB', 'MB', 'GB'];
    let size = value / 1024;
    let unitIndex = 0;

    while (size >= 1024 && unitIndex < units.length - 1) {
        size /= 1024;
        unitIndex++;
    }

    return `${size.toFixed(size >= 10 ? 0 : 1)} ${units[unitIndex]}`;
}

function formatDocumentLabel(document: ProductDocument): string {
    return `${document.file_name ?? document.type_label} · v${document.version}`;
}

function renderComment(value: string | null): string {
    return value && value.trim() !== '' ? value : 'No comment';
}

export default function ProductDocuments({
    productId,
    documentTypes,
    initialDocuments,
}: Props) {
    const [documents, setDocuments] = useState<ProductDocument[]>(
        initialDocuments,
    );
    const [isDialogOpen, setIsDialogOpen] = useState(false);
    const [isUploading, setIsUploading] = useState(false);
    const [errors, setErrors] = useState<DocumentFormErrors>({});
    const [form, setForm] = useState<DocumentFormState>(defaultFormState);

    const existingDocumentsForType = useMemo(
        () => documents.filter((document) => document.type === form.type),
        [documents, form.type],
    );

    const selectedReplacementId =
        form.duplicate_strategy === 'replace_existing'
            ? form.replace_document_id ||
              (existingDocumentsForType.length === 1
                  ? String(existingDocumentsForType[0].id)
                  : '')
            : '';

    function resetForm() {
        setForm(defaultFormState);
        setErrors({});
    }

    function handleOpenChange(open: boolean) {
        setIsDialogOpen(open);

        if (!open) {
            resetForm();
        }
    }

    function handleTypeChange(value: string) {
        const matchingDocuments = documents.filter(
            (document) => document.type === value,
        );

        setForm((current) => ({
            ...current,
            type: value,
            duplicate_strategy:
                matchingDocuments.length === 0
                    ? 'add_new'
                    : current.duplicate_strategy,
            replace_document_id:
                matchingDocuments.length === 1
                    ? String(matchingDocuments[0].id)
                    : '',
        }));
        setErrors((current) => ({ ...current, type: undefined }));
    }

    function handleDuplicateStrategyChange(strategy: DuplicateStrategy) {
        setForm((current) => ({
            ...current,
            duplicate_strategy: strategy,
            replace_document_id:
                strategy === 'replace_existing' &&
                existingDocumentsForType.length === 1
                    ? String(existingDocumentsForType[0].id)
                    : '',
        }));
        setErrors((current) => ({
            ...current,
            duplicate_strategy: undefined,
            replace_document_id: undefined,
        }));
    }

    async function handleSubmit(event: React.FormEvent<HTMLFormElement>) {
        event.preventDefault();

        setIsUploading(true);
        setErrors({});

        const formData = new FormData();

        if (form.file) {
            formData.append('file', form.file);
        }

        formData.append('type', form.type);
        formData.append('expiry_date', form.expiry_date);
        formData.append('review_comment', form.review_comment);
        formData.append('duplicate_strategy', form.duplicate_strategy);

        if (selectedReplacementId) {
            formData.append('replace_document_id', selectedReplacementId);
        }

        try {
            const data = await request(store.url(productId), formData);
            setDocuments(data.documents);
            setIsDialogOpen(false);
            resetForm();
            toast.success('Document uploaded successfully.');
        } catch (error) {
            if (error instanceof RequestError) {
                setErrors(error.errors);
                toast.error(error.message);
            } else {
                toast.error('Document upload failed.');
            }
        } finally {
            setIsUploading(false);
        }
    }

    return (
        <div className="space-y-4">
            <div className="flex items-center justify-between gap-4 border-b pb-3">
                <div>
                    <h3 className="text-sm font-medium text-foreground">
                        Product documents
                    </h3>
                    <p className="text-sm text-muted-foreground">
                        {documents.length} current document{documents.length === 1 ? '' : 's'}
                    </p>
                </div>

                <Button type="button" size="sm" onClick={() => setIsDialogOpen(true)}>
                    <FilePlus2 className="size-4" />
                    Add document
                </Button>
            </div>

            {documents.length === 0 ? (
                <button
                    type="button"
                    onClick={() => setIsDialogOpen(true)}
                    className="flex w-full flex-col items-center justify-center gap-2 rounded-lg border border-dashed px-6 py-10 text-center text-muted-foreground transition-colors hover:border-foreground/20 hover:text-foreground"
                >
                    <FileText className="size-8 opacity-50" />
                    <span className="text-sm font-medium">
                        No product documents yet
                    </span>
                    <span className="max-w-md text-xs">
                        Add the first file to start tracking manuals, certificates, and reports.
                    </span>
                </button>
            ) : (
                <div className="overflow-hidden rounded-lg border">
                    {documents.map((document) => (
                        <article
                            key={document.id}
                            className="border-b p-4 last:border-b-0"
                        >
                            <div className="space-y-3">
                                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div className="space-y-1">
                                        <p className="text-sm font-medium text-foreground">
                                            {document.file_name ?? 'Uploaded document'}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {document.type_label} · v{document.version}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            Uploaded {formatDate(document.uploaded_at)}
                                        </p>
                                    </div>

                                    {document.file_url && (
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            asChild
                                        >
                                            <a
                                                href={document.file_url}
                                                target="_blank"
                                                rel="noreferrer"
                                            >
                                                <Download className="size-4" />
                                                Open
                                            </a>
                                        </Button>
                                    )}
                                </div>

                                <dl className="grid gap-3 text-sm sm:grid-cols-3">
                                    <div>
                                        <dt className="text-xs text-muted-foreground">
                                            Expires
                                        </dt>
                                        <dd className="mt-1 text-foreground">
                                            {formatDate(document.expiry_date)}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="text-xs text-muted-foreground">
                                            File size
                                        </dt>
                                        <dd className="mt-1 text-foreground">
                                            {formatBytes(document.file_size)}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="text-xs text-muted-foreground">
                                            Comment
                                        </dt>
                                        <dd className="mt-1 text-foreground">
                                            {renderComment(document.review_comment)}
                                        </dd>
                                    </div>
                                </dl>

                                {document.history.length > 0 && (
                                    <details className="rounded-md border">
                                        <summary className="flex cursor-pointer list-none items-center gap-2 px-3 py-2 text-sm text-foreground">
                                            <span className="flex items-center gap-2">
                                                <History className="size-4 text-muted-foreground" />
                                                Version history ({document.history.length})
                                            </span>
                                        </summary>
                                        <div className="space-y-2 border-t px-3 py-3">
                                            {document.history.map((version) => (
                                                <div
                                                    key={version.id}
                                                    className="flex flex-col gap-2 rounded-md bg-muted/30 p-3 sm:flex-row sm:items-center sm:justify-between"
                                                >
                                                    <div>
                                                        <p className="text-sm font-medium text-foreground">
                                                            {version.file_name ?? 'Previous version'}
                                                        </p>
                                                        <p className="text-xs text-muted-foreground">
                                                            v{version.version} · uploaded {formatDate(version.uploaded_at)}
                                                        </p>
                                                    </div>
                                                    <div className="flex items-center gap-3">
                                                        <p className="text-xs text-muted-foreground">
                                                            {renderComment(version.review_comment)}
                                                        </p>
                                                        {version.file_url && (
                                                            <Button
                                                                type="button"
                                                                variant="ghost"
                                                                size="sm"
                                                                asChild
                                                            >
                                                                <a
                                                                    href={version.file_url}
                                                                    target="_blank"
                                                                    rel="noreferrer"
                                                                >
                                                                    <Download className="size-4" />
                                                                    Open
                                                                </a>
                                                            </Button>
                                                        )}
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    </details>
                                )}
                            </div>
                        </article>
                    ))}
                </div>
            )}

            <Dialog open={isDialogOpen} onOpenChange={handleOpenChange}>
                <DialogContent className="sm:max-w-xl">
                    <DialogHeader>
                        <DialogTitle>Add Product Document</DialogTitle>
                        <DialogDescription>
                            Upload a file and choose its type. Replacing keeps the older file in version history.
                        </DialogDescription>
                    </DialogHeader>

                    <form className="space-y-5" onSubmit={handleSubmit}>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2 sm:col-span-2">
                                <Label htmlFor="document-file">File</Label>
                                <Input
                                    id="document-file"
                                    type="file"
                                    accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx"
                                    onChange={(event) =>
                                        setForm((current) => ({
                                            ...current,
                                            file:
                                                event.target.files?.[0] ?? null,
                                        }))
                                    }
                                />
                                <InputError message={errors.file} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="document-type">Type</Label>
                                <Select
                                    value={form.type || undefined}
                                    onValueChange={handleTypeChange}
                                >
                                    <SelectTrigger id="document-type" className="w-full">
                                        <SelectValue placeholder="Select a document type" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {Object.entries(documentTypes).map(
                                            ([value, label]) => (
                                                <SelectItem
                                                    key={value}
                                                    value={value}
                                                >
                                                    {label}
                                                </SelectItem>
                                            ),
                                        )}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.type} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="expiry-date">Expiry Date</Label>
                                <Input
                                    id="expiry-date"
                                    type="date"
                                    value={form.expiry_date}
                                    onChange={(event) =>
                                        setForm((current) => ({
                                            ...current,
                                            expiry_date: event.target.value,
                                        }))
                                    }
                                />
                                <InputError message={errors.expiry_date} />
                            </div>

                            <div className="grid gap-2 sm:col-span-2">
                                <Label htmlFor="review-comment">
                                    Review / Comment
                                </Label>
                                <Textarea
                                    id="review-comment"
                                    value={form.review_comment}
                                    onChange={(event) =>
                                        setForm((current) => ({
                                            ...current,
                                            review_comment: event.target.value,
                                        }))
                                    }
                                    placeholder="Optional note"
                                />
                                <InputError message={errors.review_comment} />
                            </div>
                        </div>

                        {form.type && existingDocumentsForType.length > 0 && (
                            <div className="space-y-3 rounded-md border p-3">
                                <p className="text-sm text-muted-foreground">
                                    This type already has {existingDocumentsForType.length} current document{existingDocumentsForType.length === 1 ? '' : 's'}.
                                </p>

                                <div className="grid gap-2 sm:grid-cols-2">
                                    <button
                                        type="button"
                                        className={cn(
                                            'rounded-md border px-3 py-2 text-left text-sm transition-colors',
                                            form.duplicate_strategy === 'add_new'
                                                ? 'border-primary text-foreground'
                                                : 'text-muted-foreground hover:border-foreground/20 hover:text-foreground',
                                        )}
                                        onClick={() =>
                                            handleDuplicateStrategyChange(
                                                'add_new',
                                            )
                                        }
                                    >
                                        Add new
                                    </button>

                                    <button
                                        type="button"
                                        className={cn(
                                            'rounded-md border px-3 py-2 text-left text-sm transition-colors',
                                            form.duplicate_strategy ===
                                                'replace_existing'
                                                ? 'border-primary text-foreground'
                                                : 'text-muted-foreground hover:border-foreground/20 hover:text-foreground',
                                        )}
                                        onClick={() =>
                                            handleDuplicateStrategyChange(
                                                'replace_existing',
                                            )
                                        }
                                    >
                                        Replace existing
                                    </button>
                                </div>

                                <InputError
                                    message={errors.duplicate_strategy}
                                />

                                {form.duplicate_strategy ===
                                    'replace_existing' &&
                                    (existingDocumentsForType.length === 1 ? (
                                        <p className="text-sm text-muted-foreground">
                                            <span className="inline-flex items-center gap-2">
                                                <RefreshCw className="size-4" />
                                                Replacing{' '}
                                                {formatDocumentLabel(
                                                    existingDocumentsForType[0],
                                                )}
                                            </span>
                                        </p>
                                    ) : (
                                        <div className="grid gap-2">
                                            <Label htmlFor="replace-document-id">
                                                Select document to replace
                                            </Label>
                                            <Select
                                                value={
                                                    form.replace_document_id ||
                                                    undefined
                                                }
                                                onValueChange={(value) =>
                                                    setForm((current) => ({
                                                        ...current,
                                                        replace_document_id:
                                                            value,
                                                    }))
                                                }
                                            >
                                                <SelectTrigger
                                                    id="replace-document-id"
                                                    className="w-full"
                                                >
                                                    <SelectValue placeholder="Choose a document" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {existingDocumentsForType.map(
                                                        (document) => (
                                                            <SelectItem
                                                                key={
                                                                    document.id
                                                                }
                                                                value={String(
                                                                    document.id,
                                                                )}
                                                            >
                                                                {formatDocumentLabel(
                                                                    document,
                                                                )}
                                                            </SelectItem>
                                                        ),
                                                    )}
                                                </SelectContent>
                                            </Select>
                                            <InputError
                                                message={
                                                    errors.replace_document_id
                                                }
                                            />
                                        </div>
                                    ))}
                            </div>
                        )}

                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => handleOpenChange(false)}
                                disabled={isUploading}
                            >
                                Cancel
                            </Button>
                            <Button type="submit" disabled={isUploading}>
                                {isUploading && <Spinner />}
                                Upload document
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    );
}