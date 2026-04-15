<?php

use App\Enums\Role;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Template;
use App\Models\User;
use Filament\Facades\Filament;

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->owner = User::factory()->create();
    $this->admin = User::factory()->create();
    $this->outsider = User::factory()->create();
    $this->systemAdmin = User::factory()->create(['email' => 'system-admin@example.com']);

    config()->set('admin.allowed_emails', [$this->systemAdmin->email]);

    $this->organization->members()->attach($this->owner, ['role' => Role::Owner->value]);
    $this->organization->members()->attach($this->admin, ['role' => Role::Admin->value]);

    $this->actingAs($this->owner);

    Filament::setCurrentPanel(Filament::getPanel('dashboard'));
    Filament::setTenant($this->organization);
});

describe('OrganizationPolicy', function () {
    it('allows any user to view any organizations', function () {
        expect($this->owner->can('viewAny', Organization::class))->toBeTrue();
    });

    it('allows members to view their organization', function () {
        expect($this->owner->can('view', $this->organization))->toBeTrue()
            ->and($this->admin->can('view', $this->organization))->toBeTrue();
    });

    it('prevents outsiders from viewing an organization', function () {
        expect($this->outsider->can('view', $this->organization))->toBeFalse();
    });

    it('allows owner and admin to update organization', function () {
        expect($this->owner->can('update', $this->organization))->toBeTrue()
            ->and($this->admin->can('update', $this->organization))->toBeTrue();
    });
});

describe('InvitationPolicy', function () {
    it('allows owner and admin to manage invitations', function () {
        $invitation = Invitation::factory()->create(['organization_id' => $this->organization->id]);

        expect($this->owner->can('viewAny', Invitation::class))->toBeTrue()
            ->and($this->owner->can('create', Invitation::class))->toBeTrue()
            ->and($this->owner->can('delete', $invitation))->toBeTrue()
            ->and($this->admin->can('viewAny', Invitation::class))->toBeTrue()
            ->and($this->admin->can('create', Invitation::class))->toBeTrue();
    });

});

describe('SupplierPolicy', function () {
    it('allows owner and admin to manage suppliers', function () {
        $supplier = Supplier::factory()->create(['organization_id' => $this->organization->id]);

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
        $supplier = Supplier::factory()->create(['organization_id' => $this->organization->id]);
        $brand = Brand::factory()->create([
            'organization_id' => $this->organization->id,
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
        $product = Product::factory()->create(['organization_id' => $this->organization->id]);

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
        $category = Category::factory()->create(['organization_id' => $this->organization->id]);

        expect($this->systemAdmin->can('viewAny', Category::class))->toBeTrue()
            ->and($this->systemAdmin->can('create', Category::class))->toBeTrue()
            ->and($this->systemAdmin->can('delete', $category))->toBeTrue();
    });

    it('prevents non system admins from managing categories', function () {
        $category = Category::factory()->create(['organization_id' => $this->organization->id]);

        expect($this->owner->can('viewAny', Category::class))->toBeFalse()
            ->and($this->admin->can('create', Category::class))->toBeFalse();
    });
});

describe('TemplatePolicy', function () {
    it('allows system admins to manage templates', function () {
        $category = Category::factory()->create(['organization_id' => $this->organization->id]);
        $template = Template::factory()->create([
            'organization_id' => $this->organization->id,
            'category_id' => $category->id,
        ]);

        expect($this->systemAdmin->can('viewAny', Template::class))->toBeTrue()
            ->and($this->systemAdmin->can('create', Template::class))->toBeTrue()
            ->and($this->systemAdmin->can('delete', $template))->toBeTrue();
    });

    it('prevents non system admins from managing templates', function () {
        $category = Category::factory()->create(['organization_id' => $this->organization->id]);
        $template = Template::factory()->create([
            'organization_id' => $this->organization->id,
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
            ->and(Role::Owner->canManageOrganization())->toBeTrue()
            ->and(Role::Admin->canManageOrganization())->toBeTrue()
            ->and(Role::Owner->canDeleteOrganization())->toBeTrue()
            ->and(Role::Admin->canDeleteOrganization())->toBeFalse();
    });
});
