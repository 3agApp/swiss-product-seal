<?php

use App\Enums\Role;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Distributor;
use App\Models\Invitation;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Template;
use App\Models\User;
use Filament\Facades\Filament;

beforeEach(function () {
    $this->distributor = Distributor::factory()->create();
    $this->owner = User::factory()->create();
    $this->admin = User::factory()->create();
    $this->outsider = User::factory()->create();
    $this->systemAdmin = User::factory()->create(['email' => 'system-admin@example.com']);

    config()->set('admin.allowed_emails', [$this->systemAdmin->email]);

    $this->distributor->members()->attach($this->owner, ['role' => Role::Owner->value]);
    $this->distributor->members()->attach($this->admin, ['role' => Role::Admin->value]);

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

});

describe('CategoryPolicy', function () {
    it('allows system admins to manage categories', function () {
        $category = Category::factory()->create(['distributor_id' => $this->distributor->id]);

        expect($this->systemAdmin->can('viewAny', Category::class))->toBeTrue()
            ->and($this->systemAdmin->can('create', Category::class))->toBeTrue()
            ->and($this->systemAdmin->can('delete', $category))->toBeTrue();
    });

    it('prevents non system admins from managing categories', function () {
        $category = Category::factory()->create(['distributor_id' => $this->distributor->id]);

        expect($this->owner->can('viewAny', Category::class))->toBeFalse()
            ->and($this->admin->can('create', Category::class))->toBeFalse();
    });
});

describe('TemplatePolicy', function () {
    it('allows system admins to manage templates', function () {
        $category = Category::factory()->create(['distributor_id' => $this->distributor->id]);
        $template = Template::factory()->create([
            'distributor_id' => $this->distributor->id,
            'category_id' => $category->id,
        ]);

        expect($this->systemAdmin->can('viewAny', Template::class))->toBeTrue()
            ->and($this->systemAdmin->can('create', Template::class))->toBeTrue()
            ->and($this->systemAdmin->can('delete', $template))->toBeTrue();
    });

    it('prevents non system admins from managing templates', function () {
        $category = Category::factory()->create(['distributor_id' => $this->distributor->id]);
        $template = Template::factory()->create([
            'distributor_id' => $this->distributor->id,
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
            ->and(Role::Owner->canManageDistributor())->toBeTrue()
            ->and(Role::Admin->canManageDistributor())->toBeTrue()
            ->and(Role::Owner->canDeleteDistributor())->toBeTrue()
            ->and(Role::Admin->canDeleteDistributor())->toBeFalse();
    });
});
