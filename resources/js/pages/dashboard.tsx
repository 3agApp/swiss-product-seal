import { Deferred, Head, Link } from '@inertiajs/react';
import {
    ArrowRight,
    BoxIcon,
    FolderOpen,
    Plus,
    Tags,
    TrendingUp,
    Truck,
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { create as createProduct, index as productsIndex, show as showProduct } from '@/routes/products';
import { create as createSupplier } from '@/routes/suppliers';
import { create as createCategory } from '@/routes/categories';
import { dashboard } from '@/routes';

type DashboardStats = {
    totalProducts: number;
    totalSuppliers: number;
    totalCategories: number;
    statusCounts: Record<string, number>;
    completenessDistribution: {
        low: number;
        medium: number;
        high: number;
    };
};

type RecentProduct = {
    id: number;
    name: string;
    status: string | null;
    completeness_score: number;
    updated_at: string;
    supplier_name: string | null;
    category_name: string | null;
    image_preview_url: string | null;
};

const statusVariant: Record<string, 'default' | 'secondary' | 'outline' | 'destructive'> = {
    open: 'outline',
    in_progress: 'secondary',
    submitted: 'secondary',
    under_review: 'secondary',
    clarification_needed: 'destructive',
    approved: 'default',
    rejected: 'destructive',
    completed: 'default',
};

const statusLabel: Record<string, string> = {
    open: 'Open',
    in_progress: 'In progress',
    submitted: 'Submitted',
    under_review: 'Under review',
    clarification_needed: 'Clarification needed',
    approved: 'Approved',
    rejected: 'Rejected',
    completed: 'Completed',
};

function StatCardSkeleton() {
    return (
        <Card>
            <CardHeader>
                <div className="flex items-center justify-between">
                    <div className="h-4 w-24 animate-pulse rounded bg-muted" />
                    <div className="h-5 w-5 animate-pulse rounded bg-muted" />
                </div>
            </CardHeader>
            <CardContent>
                <div className="h-8 w-16 animate-pulse rounded bg-muted" />
            </CardContent>
        </Card>
    );
}

function RecentProductsSkeleton() {
    return (
        <div className="space-y-3">
            {Array.from({ length: 5 }).map((_, i) => (
                <div key={i} className="flex items-center gap-3 rounded-lg border p-3">
                    <div className="h-10 w-10 animate-pulse rounded-md bg-muted" />
                    <div className="flex-1 space-y-2">
                        <div className="h-4 w-40 animate-pulse rounded bg-muted" />
                        <div className="h-3 w-24 animate-pulse rounded bg-muted" />
                    </div>
                    <div className="h-5 w-16 animate-pulse rounded-full bg-muted" />
                </div>
            ))}
        </div>
    );
}

function CompletenessBarSkeleton() {
    return (
        <div className="space-y-4">
            {Array.from({ length: 3 }).map((_, i) => (
                <div key={i} className="space-y-2">
                    <div className="h-4 w-32 animate-pulse rounded bg-muted" />
                    <div className="h-2 w-full animate-pulse rounded bg-muted" />
                </div>
            ))}
        </div>
    );
}

function StatsCards({ stats }: { stats: DashboardStats }) {
    return (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <Card>
                <CardHeader>
                    <div className="flex items-center justify-between">
                        <CardDescription>Total Products</CardDescription>
                        <BoxIcon className="size-4 text-muted-foreground" />
                    </div>
                </CardHeader>
                <CardContent>
                    <div className="text-3xl font-bold">{stats.totalProducts}</div>
                </CardContent>
            </Card>
            <Card>
                <CardHeader>
                    <div className="flex items-center justify-between">
                        <CardDescription>Suppliers</CardDescription>
                        <Truck className="size-4 text-muted-foreground" />
                    </div>
                </CardHeader>
                <CardContent>
                    <div className="text-3xl font-bold">{stats.totalSuppliers}</div>
                </CardContent>
            </Card>
            <Card>
                <CardHeader>
                    <div className="flex items-center justify-between">
                        <CardDescription>Categories</CardDescription>
                        <Tags className="size-4 text-muted-foreground" />
                    </div>
                </CardHeader>
                <CardContent>
                    <div className="text-3xl font-bold">{stats.totalCategories}</div>
                </CardContent>
            </Card>
            <Card>
                <CardHeader>
                    <div className="flex items-center justify-between">
                        <CardDescription>Approved</CardDescription>
                        <TrendingUp className="size-4 text-muted-foreground" />
                    </div>
                </CardHeader>
                <CardContent>
                    <div className="text-3xl font-bold">
                        {stats.statusCounts['approved'] ?? 0}
                    </div>
                    {stats.totalProducts > 0 && (
                        <p className="text-xs text-muted-foreground">
                            {Math.round(((stats.statusCounts['approved'] ?? 0) / stats.totalProducts) * 100)}% of total
                        </p>
                    )}
                </CardContent>
            </Card>
        </div>
    );
}

function CompletenessDistribution({ stats }: { stats: DashboardStats }) {
    const { completenessDistribution: dist } = stats;
    const total = dist.low + dist.medium + dist.high;

    if (total === 0) {
        return (
            <p className="py-4 text-center text-sm text-muted-foreground">
                No products yet. Create your first product to see completeness data.
            </p>
        );
    }

    const items = [
        { label: 'High (80%+)', count: dist.high, percentage: Math.round((dist.high / total) * 100), color: 'bg-emerald-500' },
        { label: 'Medium (50–79%)', count: dist.medium, percentage: Math.round((dist.medium / total) * 100), color: 'bg-amber-500' },
        { label: 'Low (<50%)', count: dist.low, percentage: Math.round((dist.low / total) * 100), color: 'bg-red-500' },
    ];

    return (
        <div className="space-y-4">
            {items.map((item) => (
                <div key={item.label} className="space-y-1.5">
                    <div className="flex items-center justify-between text-sm">
                        <span>{item.label}</span>
                        <span className="font-medium">{item.count} ({item.percentage}%)</span>
                    </div>
                    <div className="relative h-2 w-full overflow-hidden rounded-full bg-primary/20">
                        <div
                            className={`h-full rounded-full transition-all ${item.color}`}
                            style={{ width: `${item.percentage}%` }}
                        />
                    </div>
                </div>
            ))}
        </div>
    );
}

function StatusOverview({ stats }: { stats: DashboardStats }) {
    const { statusCounts } = stats;
    const entries = Object.entries(statusCounts).sort(([, a], [, b]) => b - a);

    if (entries.length === 0) {
        return (
            <p className="py-4 text-center text-sm text-muted-foreground">
                No products yet.
            </p>
        );
    }

    return (
        <div className="space-y-2">
            {entries.map(([status, count]) => (
                <div key={status} className="flex items-center justify-between">
                    <Badge variant={statusVariant[status] ?? 'outline'}>
                        {statusLabel[status] ?? status}
                    </Badge>
                    <span className="text-sm font-medium">{count}</span>
                </div>
            ))}
        </div>
    );
}

function RecentProductsList({ recentProducts }: { recentProducts: RecentProduct[] }) {
    if (recentProducts.length === 0) {
        return (
            <div className="flex flex-col items-center justify-center py-8 text-center">
                <FolderOpen className="mb-2 size-10 text-muted-foreground/50" />
                <p className="text-sm text-muted-foreground">No products yet</p>
                <Button asChild size="sm" className="mt-3">
                    <Link href={createProduct()}>
                        <Plus className="size-4" />
                        Create your first product
                    </Link>
                </Button>
            </div>
        );
    }

    return (
        <div className="space-y-2">
            {recentProducts.map((product) => (
                <Link
                    key={product.id}
                    href={showProduct(product.id)}
                    className="flex items-center gap-3 rounded-lg border p-3 transition-colors hover:bg-muted/50"
                >
                    {product.image_preview_url ? (
                        <img
                            src={product.image_preview_url}
                            alt={product.name}
                            className="size-10 rounded-md border object-cover"
                        />
                    ) : (
                        <div className="flex size-10 items-center justify-center rounded-md border bg-muted text-xs text-muted-foreground">
                            —
                        </div>
                    )}
                    <div className="min-w-0 flex-1">
                        <p className="truncate text-sm font-medium">{product.name}</p>
                        <p className="text-xs text-muted-foreground">
                            {[product.supplier_name, product.category_name].filter(Boolean).join(' · ') || 'No details'}
                        </p>
                    </div>
                    <div className="flex shrink-0 items-center gap-2">
                        <div className="hidden items-center gap-1.5 sm:flex">
                            <Progress value={product.completeness_score} className="h-1.5 w-12" />
                            <span className="text-xs text-muted-foreground">{Math.round(product.completeness_score)}%</span>
                        </div>
                        {product.status && (
                            <Badge variant={statusVariant[product.status] ?? 'outline'} className="text-xs">
                                {statusLabel[product.status] ?? product.status}
                            </Badge>
                        )}
                    </div>
                </Link>
            ))}
            <div className="pt-2">
                <Button variant="ghost" size="sm" asChild className="w-full">
                    <Link href={productsIndex()}>
                        View all products
                        <ArrowRight className="size-4" />
                    </Link>
                </Button>
            </div>
        </div>
    );
}

export default function Dashboard({
    stats,
    recentProducts,
}: {
    stats?: DashboardStats;
    recentProducts?: RecentProduct[];
}) {
    return (
        <>
            <Head title="Dashboard" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold">Dashboard</h1>
                        <p className="text-sm text-muted-foreground">Overview of your compliance data</p>
                    </div>
                    <div className="flex gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <Link href={createSupplier()}>
                                <Plus className="size-4" />
                                Supplier
                            </Link>
                        </Button>
                        <Button variant="outline" size="sm" asChild>
                            <Link href={createCategory()}>
                                <Plus className="size-4" />
                                Category
                            </Link>
                        </Button>
                        <Button size="sm" asChild>
                            <Link href={createProduct()}>
                                <Plus className="size-4" />
                                Product
                            </Link>
                        </Button>
                    </div>
                </div>

                <Deferred data="stats" fallback={
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <StatCardSkeleton />
                        <StatCardSkeleton />
                        <StatCardSkeleton />
                        <StatCardSkeleton />
                    </div>
                }>
                    <StatsCards stats={stats!} />
                </Deferred>

                <div className="grid gap-6 lg:grid-cols-3">
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle>Recent Products</CardTitle>
                            <CardDescription>Recently updated products</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Deferred data="recentProducts" fallback={<RecentProductsSkeleton />}>
                                <RecentProductsList recentProducts={recentProducts!} />
                            </Deferred>
                        </CardContent>
                    </Card>

                    <div className="flex flex-col gap-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Completeness</CardTitle>
                                <CardDescription>Product documentation scores</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Deferred data="stats" fallback={<CompletenessBarSkeleton />}>
                                    <CompletenessDistribution stats={stats!} />
                                </Deferred>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>By Status</CardTitle>
                                <CardDescription>Product status breakdown</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Deferred data="stats" fallback={<CompletenessBarSkeleton />}>
                                    <StatusOverview stats={stats!} />
                                </Deferred>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
