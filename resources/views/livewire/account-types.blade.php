<?php

namespace App\Http\Livewire;

use App\Models\AccountType;
use Illuminate\Validation\Rule;
use function Livewire\Volt\{state, rules, on};

state([
	'name' => '',
	'can_give' => false,
	'can_take' => false,
	'account_types' => fn () => AccountType::all(),
	'editAccountType' => null,
	'show_modal' => false,
]);

rules([
	'name' => ['required', 'string', 'max:50'],
	'can_give' => ['boolean'],
	'can_take' => ['boolean'],
]);

$openCreateModal = function () {
	$this->resetForm();
	$this->modal('add-edit-modal')->show();
};

$openEditModal = function ($id) {
	$accountType = AccountType::findOrFail($id);
	$this->name = $accountType->name;
	$this->can_give = (bool) $accountType->can_give;
	$this->can_take = (bool) $accountType->can_take;
	$this->editAccountType = $id;
	$this->resetValidation();
	session()->forget(['success', 'error']);
	$this->modal('add-edit-modal')->show();
};

$resetForm = function () {
	$this->reset(['name', 'can_give', 'can_take', 'editAccountType', 'show_modal']);
	$this->resetValidation();
	session()->forget(['success', 'error']);
};

$validateCheckbox = function (): bool {
	if (! $this->can_give && ! $this->can_take) {
		$this->addError('checkbox', 'At least one option (Can Give or Can Take) must be selected.');
		return false;
	}
	$this->resetErrorBag('checkbox');
	return true;
};

$handleSuccess = function (string $message, string $event) {
	$this->dispatch($event, message: $message);
	$this->resetForm();
	$this->account_types = AccountType::all();
	$this->modal('add-edit-modal')->close();
};

$handleError = function (\Exception $e, string $message) {
	\Log::error($e->getMessage());
	$this->dispatch('error', message: $message);
};

$createAccountType = function () {
	if (! $this->validateCheckbox()) {
		return;
	}

	$validated = $this->validate([
		'name' => ['required', 'string', 'max:50', Rule::unique('account_types', 'name')],
		'can_give' => ['boolean'],
		'can_take' => ['boolean'],
	]);

	try {
		AccountType::create([
			'name' => $validated['name'],
			'can_give' => $this->can_give,
			'can_take' => $this->can_take,
		]);

		$this->handleSuccess('Account type created successfully!', 'account-type-created');
	} catch (\Exception $e) {
		$this->handleError($e, 'Failed to create account type.');
	}
};

$updateAccountType = function () {
	if (! $this->validateCheckbox()) {
		return;
	}

	if (! $this->editAccountType) {
		$this->dispatch('error', message: 'No account type selected for update.');
		return;
	}

	$validated = $this->validate([
		'name' => ['required', 'string', 'max:50', Rule::unique('account_types', 'name')->ignore($this->editAccountType)],
		'can_give' => ['boolean'],
		'can_take' => ['boolean'],
	]);

	try {
		$accountType = AccountType::findOrFail($this->editAccountType);
		$accountType->update([
			'name' => $validated['name'],
			'can_give' => $this->can_give,
			'can_take' => $this->can_take,
		]);

		$this->handleSuccess('Account type updated successfully!', 'account-type-updated');
	} catch (\Exception $e) {
		$this->handleError($e, 'Failed to update account type.');
	}
};

$deleteAccountType = function ($id) {
	try {
		AccountType::findOrFail($id)->delete();
		$this->handleSuccess('Account type deleted successfully!', 'account-type-deleted');
	} catch (\Exception $e) {
		$this->handleError($e, 'Failed to delete account type.');
	}
};

on([
	'account-type-created' => fn ($message) => session()->flash('success', $message),
	'account-type-updated' => fn ($message) => session()->flash('success', $message),
	'account-type-deleted' => fn ($message) => session()->flash('success', $message),
	'error' => fn ($message) => session()->flash('error', $message),
]);

?>

<div class="font-inter mx-auto max-w-4xl">
	<!-- Header and Create Button -->
	<div class="mb-6 flex items-center justify-between">
		<h2 class="text-xl font-semibold text-[oklch(0.3_0.1_260)]">Account Types</h2>
		<button wire:click="openCreateModal" class="cursor-pointer text-white flex items-center gap-2 rounded-lg bg-[oklch(0.7_0.2_250)] px-4 py-2 transition duration-200 hover:bg-[oklch(0.65_0.22_250)] focus:ring focus:ring-[oklch(0.8_0.15_250)]">
			<flux:icon.plus />
			Add Account Type
		</button>
	</div>

	<!-- Toast Notifications -->
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
				<svg alchemy-icon="check" class="h-5 w-5"></svg>
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
				<svg alchemy-icon="x" class="h-5 w-5"></svg>
				{{ session('error') }}
			</div>
		@endif
	</div>

	<!-- Account Types Table -->
	<div class="bg-white rounded-xl border border-[oklch(0.8_0.05_260)] shadow-lg">
		<div class="p-6">
			@if ($account_types->isEmpty())
				<p class="py-6 text-center text-[oklch(0.5_0.1_260)]">No account types found.</p>
			@else
				<div class="overflow-x-auto max-h-[calc(100vh-200px)]">
					<table class="table-fixed min-w-full divide-y divide-[oklch(0.95_0.02_260)]">
						<thead class="sticky top-0 bg-[oklch(0.98_0.01_260)]">
							<tr>
								<th class="px-4 py-3 text-left text-sm font-semibold uppercase tracking-wide text-[oklch(0.5_0.1_260)]">Name</th>
								<th class="text-nowrap w-32 px-4 py-3 text-sm font-semibold uppercase tracking-wide text-[oklch(0.5_0.1_260)]">Can Give</th>
								<th class="text-nowrap w-32 px-4 py-3 text-sm font-semibold uppercase tracking-wide text-[oklch(0.5_0.1_260)]">Can Take</th>
								<th class="w-32 px-4 py-3 text-sm font-semibold uppercase tracking-wide text-[oklch(0.5_0.1_260)]">Actions</th>
							</tr>
						</thead>
						<tbody class="divide-y divide-[oklch(0.95_0.02_260)]">
							@foreach ($account_types as $accountType)
								<tr class="transition duration-150 hover:bg-[oklch(0.99_0.01_260)]">
									<td class="text-nowrap px-4 py-4 text-sm text-[oklch(0.3_0.1_260)]">{{ $accountType->name }}</td>
									<td class="text-center px-4 py-4 text-sm">
										<span class="{{ $accountType->can_give ? 'text-[oklch(0.5_0.15_140)]' : 'text-[oklch(0.5_0.15_10)]' }}">
											{{ $accountType->can_give ? 'Yes' : 'No' }}
										</span>
									</td>
									<td class="text-center px-4 py-4 text-sm">
										<span class="{{ $accountType->can_take ? 'text-[oklch(0.5_0.15_140)]' : 'text-[oklch(0.5_0.15_10)]' }}">
											{{ $accountType->can_take ? 'Yes' : 'No' }}
										</span>
									</td>
									<td class="text-center space-x-3 px-4 py-4 text-sm flex items-center justify-center">
										<button wire:click="openEditModal({{ $accountType->id }})" class="text-[oklch(0.6_0.2_250)] transition duration-150 hover:text-[oklch(0.55_0.22_250)]" aria-label="Edit {{ $accountType->name }}">Edit</button>
										<button wire:click="deleteAccountType({{ $accountType->id }})" class="text-[oklch(0.6_0.2_10)] transition duration-150 hover:text-[oklch(0.55_0.22_10)]" aria-label="Delete {{ $accountType->name }}" wire:confirm="Are you sure you want to delete this account type?">Delete</button>
									</td>
								</tr>
							@endforeach
						</tbody>
					</table>
				</div>
			@endif
		</div>
	</div>

	<flux:modal name="add-edit-modal" class="md:w-96">
		<form wire:submit="{{ $editAccountType ? 'updateAccountType' : 'createAccountType' }}" x-ref="form">
			<div class="space-y-6">
				<div>
					<flux:heading size="lg">{{ $editAccountType ? 'Update Account Type' : 'Create Account Type' }}</flux:heading>
				</div>

				<div>
					<flux:input label="Account Type Name" wire:model.live="name" id="name" placeholder="e.g., Bank, Credit Card" />
					@error('name')
						<p class="mt-1 text-sm text-red-500">{{ $message }}</p>
					@enderror
				</div>

				<div class="space-y-3">
					<flux:checkbox label="Can have outflows (Can Give)" wire:model.live="can_give" id="can_give" />
					<flux:checkbox label="Can have inflows (Can Take)" wire:model.live="can_take" id="can_take" />
					@error('checkbox')
						<p class="text-sm text-red-500 font-medium">
							<svg class="inline h-5 w-5" data-flux-icon="" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
								<path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495ZM10 5a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 5Zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"></path>
							</svg>
							{{ $message }}
						</p>
					@enderror

					<p class="text-sm italic text-[oklch(0.5_0.1_260)]">* At least one option must be selected</p>
				</div>

				<div class="flex gap-2">
					<flux:spacer />
					<flux:modal.close>
						<flux:button variant="ghost">Cancel</flux:button>
					</flux:modal.close>
					<flux:button type="submit" variant="primary">{{ $editAccountType ? 'Update' : 'Create' }}</flux:button>
				</div>
			</div>
		</form>
	</flux:modal>
</div>
