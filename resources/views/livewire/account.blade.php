<?php

use App\Models\Account;
use App\Models\AccountType;
use function Livewire\Volt\{state, computed, on};

state([
	'accounts' => fn () => Account::with('accountType')->get(),
	'account_types' => fn () => AccountType::all(),
	'name' => '',
	'account_type_id' => null,
	'balance' => 0.0,
	'edit_id' => null,
	'show_modal' => false,
	'error_message' => null,
]);

// Define computed property
$refreshAccounts = computed(fn () => Account::with('accountType')->get());

// Open modal for creating a new account
$openCreateModal = function () {
	$this->reset([
		'name',
		'account_type_id',
		'balance',
		'edit_id',
		'error_message',
	]);
	$this->show_modal = true;
};

// Open modal for editing an existing account
$openEditModal = function ($id) {
	$account = Account::findOrFail($id);
	$this->name = $account->name;
	$this->account_type_id = $account->account_type_id;
	$this->balance = $account->balance;
	$this->edit_id = $id;
	$this->show_modal = true;
	$this->error_message = null;
};

// Close modal
$closeModal = function () {
	$this->reset([
		'name',
		'account_type_id',
		'balance',
		'edit_id',
		'show_modal',
		'error_message',
	]);
};

// Save (create or update) account
$save = function () {
	$validated = $this->validate([
		'name' => 'required|string|max:100',
		'account_type_id' => 'required|exists:account_types,id',
		'balance' => 'required|numeric|min:0',
	]);

	try {
		if ($this->edit_id) {
			Account::findOrFail($this->edit_id)->update($validated);
		} else {
			Account::create($validated);
		}
		$this->accounts = $this->refreshAccounts;
		$this->closeModal();
	} catch (\Exception $e) {
		$this->error_message = 'Failed to save account: ' . $e->getMessage();
	}
};

// Delete account
$delete = function ($id) {
	try {
		$account = Account::findOrFail($id);
		if (
			$account->fromTransactions()->exists() ||
			$account->toTransactions()->exists()
		) {
			$this->error_message = 'Cannot delete account with transactions.';
			return;
		}
		$account->delete();
		$this->accounts = $this->refreshAccounts;
	} catch (\Exception $e) {
		$this->error_message = 'Failed to delete account: ' . $e->getMessage();
	}
};

// Listen for events to refresh accounts
on(['account-updated' => fn () => ($this->accounts = $this->refreshAccounts)]);

?>

<div class="font-inter mx-auto max-w-7xl p-6">
	<!-- Header and Create Button -->
	<div class="mb-6 flex items-center justify-between">
		<h1 class="text-2xl font-bold text-[oklch(0.3_0.1_260)]">Accounts</h1>
		<button
			wire:click="openCreateModal"
			class="text-white group relative overflow-hidden rounded-lg bg-[oklch(0.7_0.2_250)] px-5 py-2.5 shadow-md transition duration-300 hover:bg-[oklch(0.65_0.22_250)] focus:ring focus:ring-[oklch(0.8_0.15_250)]">
			<span
				class="absolute inset-0 bg-gradient-to-r from-[oklch(0.7_0.2_250)] to-[oklch(0.7_0.2_280)] opacity-0 transition-opacity duration-300 group-hover:opacity-100"></span>
			<span class="relative flex items-center">
				<svg
					class="mr-2 h-5 w-5"
					fill="none"
					stroke="currentColor"
					viewBox="0 0 24 24"
					xmlns="http://www.w3.org/2000/svg">
					<path
						stroke-linecap="round"
						stroke-linejoin="round"
						stroke-width="2"
						d="M12 4v16m8-8H4"></path>
				</svg>
				New Account
			</span>
		</button>
	</div>

	<!-- Error Message -->
	<div
		x-data="{ show: $wire.error_message }"
		x-show.transition.opacity.duration.500ms="show"
		x-init="
    $watch('$wire.error_message', (value) => {
    	if (value) {
    		show = true
    		setTimeout(() => {
    			show = false
    			$wire.error_message = null
    		}, 3000)
    	}
    })
  "
		class="mb-6 rounded-r-lg border-l-4 border-[oklch(0.7_0.2_10)] bg-[oklch(0.95_0.05_10)] p-4 text-[oklch(0.5_0.15_10)] shadow-sm"
		role="alert">
		<span>{{ $error_message }}</span>
	</div>

	<!-- Accounts Table -->
	<div class="bg-white overflow-hidden rounded-xl shadow-lg">
		<table class="min-w-full divide-y divide-[oklch(0.95_0.02_260)]">
			<thead class="bg-[oklch(0.98_0.01_260)]">
				<tr>
					<th
						class="px-6 py-3 text-left text-sm font-semibold uppercase tracking-wide text-[oklch(0.5_0.1_260)]">
						Name
					</th>
					<th
						class="px-6 py-3 text-left text-sm font-semibold uppercase tracking-wide text-[oklch(0.5_0.1_260)]">
						Type
					</th>
					<th
						class="px-6 py-3 text-left text-sm font-semibold uppercase tracking-wide text-[oklch(0.5_0.1_260)]">
						Balance
					</th>
					<th
						class="px-6 py-3 text-left text-sm font-semibold uppercase tracking-wide text-[oklch(0.5_0.1_260)]">
						Actions
					</th>
				</tr>
			</thead>
			<tbody class="divide-y divide-[oklch(0.95_0.02_260)]">
				@foreach ($accounts as $account)
					<tr class="transition duration-150 hover:bg-[oklch(0.99_0.01_260)]">
						<td class="px-6 py-4 text-sm font-medium text-[oklch(0.3_0.1_260)]">
							{{ $account->name }}
						</td>
						<td class="px-6 py-4 text-sm text-[oklch(0.5_0.1_260)]">
							{{ $account->accountType->name }}
						</td>
						<td class="px-6 py-4 text-sm text-[oklch(0.5_0.1_260)]">
							${{ number_format($account->balance, 2) }}
						</td>
						<td class="px-6 py-4 text-sm">
							<button
								wire:click="openEditModal({{ $account->id }})"
								class="font-medium text-[oklch(0.6_0.2_250)] transition duration-150 hover:text-[oklch(0.55_0.22_250)]"
								aria-label="Edit {{ $account->name }}">
								Edit
							</button>
							<button
								wire:click="delete({{ $account->id }})"
								class="ml-4 font-medium text-[oklch(0.6_0.2_10)] transition duration-150 hover:text-[oklch(0.55_0.22_10)]"
								aria-label="Delete {{ $account->name }}">
								Delete
							</button>
						</td>
					</tr>
				@endforeach
			</tbody>
		</table>
	</div>

	<!-- Modal -->
	<div
		x-data="{ show: @entangle('show_modal') }"
		x-show.transition.opacity.duration.500ms="show"
		class="fixed inset-0 z-50 flex items-center justify-center bg-[oklch(0.2_0.1_260/0.5)]"
		role="dialog"
		aria-modal="true">
		<div
			class="w-full max-w-md transform rounded-2xl bg-[oklch(1_0_0/0.8)] p-8 shadow-2xl backdrop-blur-lg transition duration-300"
			x-transition:enter-start="scale-95 opacity-0"
			x-transition:leave-end="scale-95 opacity-0">
			<h2 class="mb-6 text-xl font-bold text-[oklch(0.3_0.1_260)]">
				{{ $edit_id ? 'Edit Account' : 'Create Account' }}
			</h2>
			<form wire:submit="save" class="space-y-6">
				<div class="relative">
					<input
						wire:model="name"
						id="name"
						type="text"
						class="outline-none bg-transparent placeholder-transparent peer w-full rounded-lg border border-[oklch(0.8_0.05_260)] px-4 py-3 transition duration-200 focus:border-[oklch(0.7_0.2_250)] focus:ring-2 focus:ring-[oklch(0.7_0.2_250)]"
						placeholder="Name" />
					<label
						for="name"
						class="absolute left-4 top-3 transform text-sm text-[oklch(0.5_0.1_260)] transition duration-200 peer-placeholder-shown:top-3 peer-placeholder-shown:text-base peer-valid:-top-6 peer-valid:text-sm peer-valid:text-[oklch(0.6_0.2_250)] peer-focus:-top-6 peer-focus:text-sm peer-focus:text-[oklch(0.6_0.2_250)]">
						Name
					</label>
					@error('name')
						<span class="mt-1 block text-sm text-[oklch(0.6_0.2_10)]">
							{{ $message }}
						</span>
					@enderror
				</div>
				<div class="relative">
					<select
						wire:model="account_type_id"
						id="account_type_id"
						class="outline-none bg-transparent w-full appearance-none rounded-lg border border-[oklch(0.8_0.05_260)] px-4 py-3 transition duration-200 focus:border-[oklch(0.7_0.2_250)] focus:ring-2 focus:ring-[oklch(0.7_0.2_250)]">
						<option value="">Select Type</option>
						@foreach ($account_types as $type)
							<option value="{{ $type->id }}">{{ $type->name }}</option>
						@endforeach
					</select>
					<span class="pointer-events-none absolute right-3 top-4">
						<svg
							class="h-5 w-5 text-[oklch(0.5_0.1_260)]"
							fill="none"
							stroke="currentColor"
							viewBox="0 0 24 24">
							<path
								stroke-linecap="round"
								stroke-linejoin="round"
								stroke-width="2"
								d="M19 9l-7 7-7-7"></path>
						</svg>
					</span>
					<label
						for="account_type_id"
						class="absolute -top-6 left-4 text-sm text-[oklch(0.5_0.1_260)] transition duration-200">
						Account Type
					</label>
					@error('account_type_id')
						<span class="mt-1 block text-sm text-[oklch(0.6_0.2_10)]">
							{{ $message }}
						</span>
					@enderror
				</div>
				<div class="relative">
					<input
						wire:model="balance"
						id="balance"
						type="number"
						step="0.01"
						class="outline-none bg-transparent placeholder-transparent peer w-full rounded-lg border border-[oklch(0.8_0.05_260)] px-4 py-3 transition duration-200 focus:border-[oklch(0.7_0.2_250)] focus:ring-2 focus:ring-[oklch(0.7_0.2_250)]"
						placeholder="Balance" />
					<label
						for="balance"
						class="absolute left-4 top-3 transform text-sm text-[oklch(0.5_0.1_260)] transition duration-200 peer-placeholder-shown:top-3 peer-placeholder-shown:text-base peer-valid:-top-6 peer-valid:text-sm peer-valid:text-[oklch(0.6_0.2_250)] peer-focus:-top-6 peer-focus:text-sm peer-focus:text-[oklch(0.6_0.2_250)]">
						Balance
					</label>
					@error('balance')
						<span class="mt-1 block text-sm text-[oklch(0.6_0.2_10)]">
							{{ $message }}
						</span>
					@enderror
				</div>
				<div class="flex justify-end gap-3">
					<button
						type="button"
						wire:click="closeModal"
						class="rounded-lg bg-[oklch(0.9_0.03_260)] px-5 py-2.5 text-[oklch(0.4_0.1_260)] transition duration-200 hover:bg-[oklch(0.85_0.04_260)] focus:ring focus:ring-[oklch(0.9_0.05_260)]">
						Cancel
					</button>
					<button
						type="submit"
						class="text-white group relative overflow-hidden rounded-lg bg-[oklch(0.7_0.2_250)] px-5 py-2.5 shadow-md transition duration-300 hover:bg-[oklch(0.65_0.22_250)] focus:ring focus:ring-[oklch(0.8_0.15_250)]">
						<span
							class="absolute inset-0 bg-gradient-to-r from-[oklch(0.7_0.2_250)] to-[oklch(0.7_0.2_280)] opacity-0 transition-opacity duration-300 group-hover:opacity-100"></span>
						<span class="relative">{{ $edit_id ? 'Update' : 'Create' }}</span>
					</button>
				</div>
			</form>
		</div>
	</div>
</div>
