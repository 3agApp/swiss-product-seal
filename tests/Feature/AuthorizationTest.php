<?php

use App\Enums\Role;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Distributor;
use App\Models\Document;
use App\Models\Invitation;
use App\Models\Product;
use App\Models\ProductSafetyEntry;
use App\Models\Supplier;
use App\Models\Template;
use App\Models\User;
use Filament\Facades\Filament;

beforeEach(function () {
    $this->distributor = Distributor::factory()->create();
    $this->owner = User::factory()->create();
    $this->admin = User::factory()->create();
    $this->supplierUser = User::factory()->create();
    $this->outsider = User::factory()->create();
    $this->systemAdmin = User::factory()->create(['email' => 'system-admin@example.com']);
    $this->supplier = Supplier::factory()->create(['distributor_id' => $this->distributor->id]);
    $this->otherSupplier = Supplier::factory()->create(['distributor_id' => $this->distributor->id]);

    config()->set('admin.allowed_emails', [$this->systemAdmin->email]);

    $this->distributor->members()->attach($this->owner, ['role' => Role::Owner->value]);
    $this->distributor->members()->attach($this->admin, ['role' => Role::Admin->value]);
    $this->distributor->members()->attach($this->supplierUser, [
        'role' => Role::Supplier->value,
        'supplier_id' => $this->supplier->id,
    ]);

    $this->actingAs($this->owner);

    Filament::setCurrentPanel(Filament::getPanel('dashboard'));
    Filament::setTenant($this->distributor);
});

describe('DistributorPolicy', function () {
    it('allows any user to view any distributors', function () {
        expect($this->owner->can('viewAny', Distributor::class))->toBeTrue();
    });

    it('allows members to view their distributor', function () {
        expect($this->owner->can('view', $this->distributor))->toBeTrue()
            ->and($this->admin->can('view', $this->distributor))->toBeTrue();
    });

    it('prevents outsiders from viewing an distributor', function () {
        expect($this->outsider->can('view', $this->distributor))->toBeFalse();
    });

    it('allows owner and admin to update distributor', function () {
        expect($this->owner->can('update', $this->distributor))->toBeTrue()
            ->and($this->admin->can('update', $this->distributor))->toBeTrue();
    });
});

describe('InvitationPolicy', function () {
    it('allows owner and admin to manage invitations', function () {
        $invitation = Invitation::factory()->create(['distributor_id' => $this->distributor->id]);

        expect($this->owner->can('viewAny', Invitation::class))->toBeTrue()
            ->and($this->owner->can('create', Invitation::class))->toBeTrue()
            ->and($this->owner->can('delete', $invitation))->toBeTrue()
            ->and($this->admin->can('viewAny', Invitation::class))->toBeTrue()
            ->and($this->admin->can('create', Invitation::class))->toBeTrue();
    });

});

describe('SupplierPolicy', function () {
    it('allows owner and admin to manage suppliers', function () {
        $supplier = Supplier::factory()->create(['distributor_id' => $this->distributor->id]);

        expect($this->owner->can('viewAny', Supplier::class))->toBeTrue()
            ->and($this->owner->can('create', Supplier::class))->toBeTrue()
            ->and($this->owner->can('delete', $supplier))->toBeTrue()
            ->and($this->admin->can('viewAny', Supplier::class))->toBeTrue()
            ->and($this->admin->can('create', Supplier::class))->toBeTrue()
            ->and($this->admin->can('delete', $supplier))->toBeTrue();
    });

});

describe('BrandPolicy', function () {
    it('allows owner and admin to manage brands', function () {
        $supplier = Supplier::factory()->create(['distributor_id' => $this->distributor->id]);
        $brand = Brand::factory()->create([
            'distributor_id' => $this->distributor->id,
            'supplier_id' => $supplier->id,
        ]);

        expect($this->owner->can('viewAny', Brand::class))->toBeTrue()
            ->and($this->owner->can('create', Brand::class))->toBeTrue()
            ->and($this->owner->can('delete', $brand))->toBeTrue()
            ->and($this->admin->can('viewAny', Brand::class))->toBeTrue()
            ->and($this->admin->can('create', Brand::class))->toBeTrue()
            ->and($this->admin->can('delete', $brand))->toBeTrue();
    });

});

describe('ProductPolicy', function () {
    it('allows owner and admin to manage products', function () {
        $product = Product::factory()->create(['distributor_id' => $this->distributor->id]);

        expect($this->owner->can('viewAny', Product::class))->toBeTrue()
            ->and($this->owner->can('create', Product::class))->toBeTrue()
            ->and($this->owner->can('delete', $product))->toBeTrue()
            ->and($this->admin->can('viewAny', Product::class))->toBeTrue()
            ->and($this->admin->can('create', Product::class))->toBeTrue()
            ->and($this->admin->can('delete', $product))->toBeTrue();
    });

    it('limits supplier users to their own supplier products', function () {
        $supplierProduct = Product::factory()->create([
            'distributor_id' => $this->distributor->id,
            'supplier_id' => $this->supplier->id,
        ]);
        $otherSupplierProduct = Product::factory()->create([
            'distributor_id' => $this->distributor->id,
            'supplier_id' => $this->otherSupplier->id,
        ]);

        expect($this->supplierUser->can('viewAny', Product::class))->toBeTrue()
            ->and($this->supplierUser->can('view', $supplierProduct))->toBeTrue()
            ->and($this->supplierUser->can('update', $supplierProduct))->toBeTrue()
            ->and($this->supplierUser->can('create', Product::class))->toBeFalse()
            ->and($this->supplierUser->can('delete', $supplierProduct))->toBeFalse()
            ->and($this->supplierUser->can('view', $otherSupplierProduct))->toBeFalse()
            ->and($this->supplierUser->can('update', $otherSupplierProduct))->toBeFalse();
    });

    it('scopes the product resource query to the supplier user membership', function () {
        $supplierProduct = Product::factory()->create([
            'distributor_id' => $this->distributor->id,
            'supplier_id' => $this->supplier->id,
        ]);
        Product::factory()->create([
            'distributor_id' => $this->distributor->id,
            'supplier_id' => $this->otherSupplier->id,
        ]);

        $this->actingAs($this->supplierUser);

        expect(ProductResource::getEloquentQuery()->pluck('id')->all())
            ->toBe([$supplierProduct->id]);
    });

});

describe('DocumentPolicy', function () {
    it('allows supplier users to manage documents for their own supplier products only', function () {
        $supplierProduct = Product::factory()->create([
            'distributor_id' => $this->distributor->id,
            'supplier_id' => $this->supplier->id,
        ]);
        $otherSupplierProduct = Product::factory()->create([
            'distributor_id' => $this->distributor->id,
            'supplier_id' => $this->otherSupplier->id,
        ]);
        $supplierDocument = Document::factory()->create(['product_id' => $supplierProduct->id]);
        $otherSupplierDocument = Document::factory()->create(['product_id' => $otherSupplierProduct->id]);

        expect($this->supplierUser->can('create', Document::class))->toBeTrue()
            ->and($this->supplierUser->can('update', $supplierDocument))->toBeTrue()
            ->and($this->supplierUser->can('delete', $supplierDocument))->toBeTrue()
            ->and($this->supplierUser->can('update', $otherSupplierDocument))->toBeFalse();
    });
});

describe('ProductSafetyEntryPolicy', function () {
    it('allows supplier users to manage safety entries for their own supplier products only', function () {
        $supplierProduct = Product::factory()->create([
            'distributor_id' => $this->distributor->id,
            'supplier_id' => $this->supplier->id,
        ]);
        $otherSupplierProduct = Product::factory()->create([
            'distributor_id' => $this->distributor->id,
            'supplier_id' => $this->otherSupplier->id,
        ]);
        $supplierEntry = ProductSafetyEntry::factory()->create([
            'distributor_id' => $this->distributor->id,
            'product_id' => $supplierProduct->id,
        ]);
        $otherSupplierEntry = ProductSafetyEntry::factory()->create([
            'distributor_id' => $this->distributor->id,
            'product_id' => $otherSupplierProduct->id,
        ]);

        expect($this->supplierUser->can('create', ProductSafetyEntry::class))->toBeTrue()
            ->and($this->supplierUser->can('update', $supplierEntry))->toBeTrue()
            ->and($this->supplierUser->can('delete', $supplierEntry))->toBeTrue()
            ->and($this->supplierUser->can('update', $otherSupplierEntry))->toBeFalse();
    });
});

describe('CategoryPolicy', function () {
    it('allows system admins to manage categories', function () {
        $category = Category::factory()->create();

        expect($this->systemAdmin->can('viewAny', Category::class))->toBeTrue()
            ->and($this->systemAdmin->can('create', Category::class))->toBeTrue()
            ->and($this->systemAdmin->can('delete', $category))->toBeTrue();
    });

    it('prevents non system admins from managing categories', function () {
        $category = Category::factory()->create();

        expect($this->owner->can('viewAny', Category::class))->toBeFalse()
            ->and($this->admin->can('create', Category::class))->toBeFalse();
    });
});

describe('TemplatePolicy', function () {
    it('allows system admins to manage templates', function () {
        $category = Category::factory()->create();
        $template = Template::factory()->create([
            'category_id' => $category->id,
        ]);

        expect($this->systemAdmin->can('viewAny', Template::class))->toBeTrue()
            ->and($this->systemAdmin->can('create', Template::class))->toBeTrue()
            ->and($this->systemAdmin->can('delete', $template))->toBeTrue();
    });

    it('prevents non system admins from managing templates', function () {
        $category = Category::factory()->create();
        $template = Template::factory()->create([
            'category_id' => $category->id,
        ]);

        expect($this->owner->can('viewAny', Template::class))->toBeFalse()
            ->and($this->admin->can('create', Template::class))->toBeFalse();
    });
});

describe('Role enum', function () {
    it('exposes the role permissions', function () {
        expect(Role::Owner->canManageMembers())->toBeTrue()
            ->and(Role::Admin->canManageMembers())->toBeTrue()
            ->and(Role::Supplier->canManageMembers())->toBeFalse()
            ->and(Role::Owner->canManageDistributor())->toBeTrue()
            ->and(Role::Admin->canManageDistributor())->toBeTrue()
            ->and(Role::Supplier->canManageDistributor())->toBeFalse()
            ->and(Role::Supplier->canAccessProducts())->toBeTrue()
            ->and(Role::Supplier->canEditProductDetails())->toBeFalse()
            ->and(Role::Supplier->canSubmitProducts())->toBeFalse()
            ->and(Role::Owner->canDeleteDistributor())->toBeTrue()
            ->and(Role::Admin->canDeleteDistributor())->toBeFalse();
    });
});
