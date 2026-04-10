import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import type { PaginatedData } from '@/types';

type Props<T> = {
    paginator: PaginatedData<T>;
    itemName?: string;
};

export default function Pagination<T>({
    paginator,
    itemName = 'items',
}: Props<T>) {
    if (paginator.last_page <= 1) {
        return null;
    }

    return (
        <nav
            aria-label={`${itemName} pagination`}
            className="flex items-center justify-between border-t px-4 py-3"
        >
            <p className="text-sm text-muted-foreground">
                Showing {paginator.from} to {paginator.to} of {paginator.total}{' '}
                {itemName}
            </p>
            <div className="flex gap-1" role="list">
                {paginator.links.map((link, i) => {
                    const isFirst = i === 0;
                    const isLast = i === paginator.links.length - 1;
                    const ariaLabel = isFirst
                        ? 'Previous page'
                        : isLast
                          ? 'Next page'
                          : link.active
                            ? `Page ${link.label}, current page`
                            : `Go to page ${link.label}`;

                    return (
                        <Button
                            key={i}
                            variant={link.active ? 'default' : 'outline'}
                            size="sm"
                            disabled={!link.url}
                            asChild={!!link.url}
                            aria-label={ariaLabel}
                            aria-current={link.active ? 'page' : undefined}
                        >
                            {link.url ? (
                                <Link
                                    href={link.url}
                                    preserveScroll
                                    dangerouslySetInnerHTML={{
                                        __html: link.label,
                                    }}
                                />
                            ) : (
                                <span
                                    dangerouslySetInnerHTML={{
                                        __html: link.label,
                                    }}
                                />
                            )}
                        </Button>
                    );
                })}
            </div>
        </nav>
    );
}
