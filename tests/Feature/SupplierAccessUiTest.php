<?php

use App\Enums\Role;
use App\Filament\Resources\DistributorMemberResource\Pages\ListDistributorMembers;
use App\Filament\Resources\Invitations\Pages\CreateInvitation;
use App\Models\Distributor;
use App\Models\Invitation;
use App\Models\Supplier;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

beforeEach(function () {
    $this->distributor = Distributor::factory()->create(['slug' => 'acme-corp']);
    $this->owner = User::factory()->create();
    $this->member = User::factory()->create();
    $this->supplier = Supplier::factory()->create([
        'distributor_id' => $this->distributor->id,
    ]);

    $this->distributor->members()->attach($this->owner, ['role' => Role::Owner->value]);
    $this->distributor->members()->attach($this->member, ['role' => Role::Admin->value]);

    $this->actingAs($this->owner);

    Filament::setCurrentPanel(Filament::getPanel('dashboard'));
    Filament::setTenant($this->distributor);
});

it('creates a supplier invitation from the invitation form page', function () {
    Mail::fake();

    Livewire::test(CreateInvitation::class)
        ->assertOk()
        ->assertFormFieldExists('role')
        ->assertFormFieldExists('supplier_id')
        ->fillForm([
            'email' => 'supplier-user@example.com',
            'role' => Role::Supplier->value,
            'supplier_id' => $this->supplier->id,
        ])
        ->call('create')
        ->assertNotified()
        ->assertRedirect();

    $invitation = Invitation::query()
        ->where('distributor_id', $this->distributor->id)
        ->where('email', 'supplier-user@example.com')
        ->first();

    expect($invitation)->not->toBeNull()
        ->and($invitation->role)->toBe(Role::Supplier)
        ->and($invitation->supplier_id)->toBe($this->supplier->id);
});

it('updates a distributor member to a supplier-scoped role from the members table action', function () {
    Livewire::test(ListDistributorMembers::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$this->member])
        ->assertTableActionVisible('changeRole', $this->member)
        ->callTableAction('changeRole', $this->member, data: [
            'role' => Role::Supplier->value,
            'supplier_id' => $this->supplier->id,
        ])
        ->assertNotified();

    expect($this->member->fresh()->getRoleForDistributor($this->distributor))->toBe(Role::Supplier)
        ->and($this->member->fresh()->getSupplierIdForDistributor($this->distributor))->toBe($this->supplier->id);
});

it('has tenant-scoped unique indexes for supplier code and name', function () {
    $indexes = collect(Schema::getIndexes('suppliers'));

    expect($indexes->contains(function (array $index): bool {
        return $index['unique']
            && $index['columns'] === ['distributor_id', 'supplier_code'];
    }))->toBeTrue()
        ->and($indexes->contains(function (array $index): bool {
            return $index['unique']
                && $index['columns'] === ['distributor_id', 'name'];
        }))->toBeTrue();
});
