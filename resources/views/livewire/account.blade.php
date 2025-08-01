<?php

use App\Models\Account;
use App\Models\AccountType;
use function Livewire\Volt\{state, computed, on, messages};

state([
	'accounts' => fn () => Account::with('accountType')->get(),
	'account_types' => fn () => AccountType::all(),
	'name' => '',
	'account_type_id' => null,
	'edit_id' => null,
	'show_modal' => false,
	'error_message' => null,
]);

// Define computed property
$refreshAccounts = computed(fn () => Account::with('accountType')->get());

// Open modal for creating a new account
$openCreateModal = function () {
	$this->reset(['name', 'account_type_id', 'edit_id', 'error_message']);
	$this->resetValidation();
	session()->forget(['success', 'error']);
	$this->modal('add-edit-modal')->show();
};

// Open modal for editing an existing account
$openEditModal = function ($id) {
	$account = Account::findOrFail($id);
	$this->name = $account->name;
	$this->account_type_id = $account->account_type_id;
	$this->edit_id = $id;
	$this->error_message = null;
	$this->resetValidation();
	session()->forget(['success', 'error']);
	$this->modal('add-edit-modal')->show();
};

// Close modal and reset form
$resetForm = function () {
	$this->reset(['name', 'account_type_id', 'edit_id', 'show_modal', 'error_message']);
	$this->resetValidation();
	session()->forget(['success', 'error']);
};

// Save (create or update) account
$save = function () {
	$validated = $this->validate(
		[
			'name' => 'required|string|max:100|unique:accounts,name,' . ($this->edit_id ? $this->edit_id : 'NULL'),
			'account_type_id' => 'required|exists:account_types,id',
		],
		[
			'name.required' => 'Account Name is required.',
			'account_type_id.required' => 'Account Type is required.',
			'account_type_id.exists' => 'Selected Account Type does not exist.',
		],
	);

	try {
		if ($this->edit_id) {
			Account::findOrFail($this->edit_id)->update($validated);
			$this->dispatch('account-updated', message: 'Account updated successfully!');
		} else {
			Account::create($validated);
			$this->dispatch('account-created', message: 'Account created successfully!');
		}
		$this->accounts = $this->refreshAccounts;
		$this->resetForm();
		$this->modal('add-edit-modal')->close();
	} catch (\Exception $e) {
		\Log::error($e->getMessage());
		$this->dispatch('error', message: 'Failed to save account. Please try again.');
	}
};

// Delete account
$delete = function ($id) {
	try {
		$account = Account::findOrFail($id);
		if ($account->fromTransactions()->exists() || $account->toTransactions()->exists()) {
			$this->dispatch('error', message: 'Cannot delete account with transactions.');
			return;
		}
		$account->delete();
		$this->dispatch('account-deleted', message: 'Account deleted successfully!');
		$this->accounts = $this->refreshAccounts;
	} catch (\Exception $e) {
		\Log::error($e->getMessage());
		$this->dispatch('error', message: 'Failed to delete account. Please try again.');
	}
};

// Listen for events
on([
	'account-created' => function ($message) {
		session()->flash('success', $message);
	},
	'account-updated' => function ($message) {
		session()->flash('success', $message);
	},
	'account-deleted' => function ($message) {
		session()->flash('success', $message);
	},
	'error' => function ($message) {
		session()->flash('error', $message);
	},
]);

?>

<div class="font-inter mx-auto max-w-4xl">
	<!-- Header and Create Button -->
	<div class="mb-6 flex items-center justify-between">
		<h2 class="text-xl font-semibold text-[oklch(0.3_0.1_260)]">Accounts</h2>

		<button wire:click="openCreateModal" class="cursor-pointer text-white flex items-center gap-2 rounded-lg bg-[oklch(0.7_0.2_250)] px-4 py-2 transition duration-200 hover:bg-[oklch(0.65_0.22_250)] focus:ring focus:ring-[oklch(0.8_0.15_250)]">
			<flux:icon.plus />
			Add Account
		</button>
	</div>

	<!-- Toast Notifications for Success/Error -->
	<div class="fixed right-4 top-4 z-50 space-y-2">
		@if (session('success'))
			<div
				x-data="{ show: true }"
				x-show="show"
				x-init="setTimeout(() => (show = false), 3000)"
				x-transition:enter="transition ease-out duration-300"
				x-transition:enter-start="opacity-0 transform translate-y-4"
				x-transition:enter-end="opacity-100 transform translate-y-0"
				x-transition:leave="transition ease-in duration-200"
				x-transition:leave-end="opacity-0 transform translate-y-4"
				class="flex items-center gap-2 rounded-lg bg-[oklch(0.95_0.05_140)] p-4 text-[oklch(0.5_0.15_140)] shadow-md">
				<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
				</svg>
				{{ session('success') }}
			</div>
		@endif

		@if (session('error'))
			<div
				x-data="{ show: true }"
				x-show="show"
				x-init="setTimeout(() => (show = false), 3000)"
				x-transition:enter="transition ease-out duration-300"
				x-transition:enter-start="opacity-0 transform translate-y-4"
				x-transition:enter-end="opacity-100 transform translate-y-0"
				x-transition:leave="transition ease-in duration-200"
				x-transition:leave-end="opacity-0 transform translate-y-4"
				class="flex items-center gap-2 rounded-lg bg-[oklch(0.95_0.05_10)] p-4 text-[oklch(0.5_0.15_10)] shadow-md">
				<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
				</svg>
				{{ session('error') }}
			</div>
		@endif
	</div>

	<!-- Accounts Table -->
	<div class="bg-white rounded-xl border border-[oklch(0.8_0.05_260)] shadow-lg">
		<div class="p-6">
			@if ($accounts->isEmpty())
				<p class="py-6 text-center text-[oklch(0.5_0.1_260)]">No accounts found.</p>
			@else
				<div class="overflow-x-auto h-[calc(100vh-220px)]">
					<table class="min-w-full divide-y divide-[oklch(0.95_0.02_260)]">
						<thead class="sticky top-0 bg-[oklch(0.98_0.01_260)]">
							<tr>
								<th class="px-6 py-3 text-left text-sm font-semibold uppercase tracking-wide text-[oklch(0.5_0.1_260)]">Name</th>
								<th class="px-6 py-3 text-left text-sm font-semibold uppercase tracking-wide text-[oklch(0.5_0.1_260)]">Type</th>
								<th class="px-6 py-3 text-left text-sm font-semibold uppercase tracking-wide text-[oklch(0.5_0.1_260)]">Actions</th>
							</tr>
						</thead>
						<tbody class="divide-y divide-[oklch(0.95_0.02_260)]">
							@foreach ($accounts as $account)
								<tr class="transition duration-150 hover:bg-[oklch(0.99_0.01_260)]">
									<td class="px-6 py-4 text-sm text-[oklch(0.3_0.1_260)]">
										{{ $account->name }}
									</td>
									<td class="px-6 py-4 text-sm text-[oklch(0.5_0.1_260)]">
										{{ $account->accountType->name }}
									</td>
									<td class="space-x-3 px-6 py-4 text-sm">
										<button wire:click="openEditModal({{ $account->id }})" class="text-[oklch(0.6_0.2_250)] cursor-pointer transition duration-150 hover:text-[oklch(0.55_0.22_250)]" aria-label="Edit {{ $account->name }}">Edit</button>
										<button wire:click="delete({{ $account->id }})" class="text-[oklch(0.6_0.2_10)] transition cursor-pointer duration-150 hover:text-[oklch(0.55_0.22_10)]" aria-label="Delete {{ $account->name }}" wire:confirm="Are you sure you want to delete this account?">Delete</button>
									</td>
								</tr>
							@endforeach
						</tbody>
					</table>
				</div>
			@endif
		</div>
	</div>

	<flux:modal name="add-edit-modal" class="sm:w-96">
		<form wire:submit="save" x-ref="form">
			<div class="space-y-6">
				<div>
					<flux:heading size="lg">{{ $edit_id ? 'Update Account' : 'Create Account' }}</flux:heading>
				</div>

				<!-- Name Input -->
				<div>
					<flux:input label="Account Name" wire:model="name" id="name" placeholder="e.g., Savings, Credit Card" />
				</div>

				<!-- Account Type Select -->
				<div>
					<flux:select label="Account Type" wire:model="account_type_id" id="account_type_id">
						<flux:select.option value="">Choose Account Type</flux:select.option>
						@foreach ($account_types as $type)
							<flux:select.option value="{{ $type->id }}">{{ $type->name }}</flux:select.option>
						@endforeach
					</flux:select>
				</div>

				<div class="flex gap-2">
					<flux:spacer />
					<flux:modal.close>
						<flux:button variant="ghost">Cancel</flux:button>
					</flux:modal.close>
					<flux:button type="submit" variant="primary">{{ $edit_id ? 'Update' : 'Create' }}</flux:button>
				</div>
			</div>
		</form>
	</flux:modal>
</div>
