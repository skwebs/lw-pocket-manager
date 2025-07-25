<?php
namespace App\Http\Livewire;

use App\Models\AccountType;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use function Livewire\Volt\{state, rules, on};
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;

state([
	'name' => '',
	'can_give' => false,
	'can_take' => false,
	'account_types' => fn () => AccountType::all(),
	'editAccountType' => null,
	'show_modal' => false,
]);

// Extend the validator with a custom rule
Validator::extend('at_least_one_checked', function ($attribute, $value, $parameters, $validator) {
	$data = $validator->getData();
	return $data['can_give'] || $data['can_take'];
});

// Define custom error message for at_least_one_checked
Validator::replacer('at_least_one_checked', fn ($message, $attribute, $rule, $parameters) => 'At least one of Can Give or Can Take must be selected.');

// Define validation rules
rules([
	'name' => ['required', 'string', 'max:50'],
	'can_give' => ['boolean'],
	'can_take' => ['boolean', 'at_least_one_checked'],
]);

// Real-time validation for can_give and can_take
$updated = function ($propertyName) {
	if (in_array($propertyName, ['can_give', 'can_take'])) {
		try {
			$this->validateOnly($propertyName, [
				'can_give' => ['boolean'],
				'can_take' => ['boolean', 'at_least_one_checked'],
			]);
			$this->resetErrorBag('can_take');
		} catch (\Illuminate\Validation\ValidationException $e) {
			// Errors are automatically added to the error bag
		}
	}
};

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
	$this->can_give = false;
	$this->can_take = false;
};

$handleSuccess = function (string $message, string $event) {
	LivewireAlert::title($message)
		->success()
		->timer(3000)
		->toast()
		->show();
	$this->dispatch($event, message: $message);
	$this->resetForm();
	$this->account_types = AccountType::all();
	$this->modal('add-edit-modal')->close();
};

$handleError = function (\Exception $e, string $message) {
	\Log::error($e->getMessage());
	LivewireAlert::title($message)
		->error()
		->timer(3000)
		->toast()
		->show();
	$this->dispatch('error', message: $message);
};

$createAccountType = function () {
	$validated = $this->validate([
		'name' => ['required', 'string', 'max:50', Rule::unique('account_types', 'name')],
		'can_give' => ['boolean'],
		'can_take' => ['boolean', 'at_least_one_checked'],
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
	if (! $this->editAccountType) {
		$this->dispatch('error', message: 'No account type selected for update.');
		return;
	}

	$validated = $this->validate([
		'name' => ['required', 'string', 'max:50', Rule::unique('account_types', 'name')->ignore($this->editAccountType)],
		'can_give' => ['boolean'],
		'can_take' => ['boolean', 'at_least_one_checked'],
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

$deleteAccountType = function ($data) {
	try {
		AccountType::findOrFail($data['id'])->delete();
		$this->handleSuccess('Account type deleted successfully!', 'account-type-deleted');
	} catch (\Exception $e) {
		$this->handleError($e, 'Failed to delete account type.');
	}
};

$deleteActionTrigger = function ($id) {
	LivewireAlert::title('Confirm Delete?')
		->text('Are you sure you want to delete this account type?')
		->withConfirmButton('Delete')
		->confirmButtonText('Delete')
		->timer(0)
		->toast()
		->withCancelButton('Cancel')
		->confirmButtonColor('red')
		->onConfirm('deleteAccountType', ['id' => $id])
		->show();
	// LivewireAlert::title('Confirm Delete?')
	// 	->text('Are you sure you want to delete this account type?')
	// 	->asConfirm()
	// 	->toast()
	// 	->onConfirm('deleteAccountType', ['id' => $id])
	// 	->show();
};

// on([
// 	'account-type-created' => fn ($message) => session()->flash('success', $message),
// 	'account-type-updated' => fn ($message) => session()->flash('success', $message),
// 	'account-type-deleted' => fn ($message) => session()->flash('success', $message),
// 	'error' => fn ($message) => session()->flash('error', $message),
// ]);

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
		<div class="p-2">
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
									<td class="text-nowrap px-4 py-2 text-sm text-[oklch(0.3_0.1_260)]">{{ $accountType->name }}</td>
									<td class="text-center px-4 py-2 text-sm">
										<span class="{{ $accountType->can_give ? 'text-[oklch(0.5_0.15_140)]' : 'text-[oklch(0.5_0.15_10)]' }}">
											{{ $accountType->can_give ? 'Yes' : 'No' }}
										</span>
									</td>
									<td class="text-center px-4 py-2 text-sm">
										<span class="{{ $accountType->can_take ? 'text-[oklch(0.5_0.15_140)]' : 'text-[oklch(0.5_0.15_10)]' }}">
											{{ $accountType->can_take ? 'Yes' : 'No' }}
										</span>
									</td>
									<td class="text-center space-x-3 px-4 py-2 text-sm flex items-center justify-center">
										{{--
            <button wire:click="openEditModal({{ $accountType->id }})" class="text-[oklch(0.6_0.2_250)] transition duration-150 hover:text-[oklch(0.55_0.22_250)]" aria-label="Edit {{ $accountType->name }}"><flux:icon.pencil-square class="cursor-pointer" /></button>
            <button wire:click="deleteActionTrigger({{ $accountType->id }})" class="text-[oklch(0.6_0.2_10)] transition duration-150 hover:text-[oklch(0.55_0.22_10)]" aria-label="Delete {{ $accountType->name }}"><flux:icon.trash class="cursor-pointer" /></button>
          --}}

										<button wire:click="openEditModal({{ $accountType->id }})" class="cursor-pointer relative p-2 rounded-md bg-[oklch(0.95_0.02_260)] text-[oklch(0.6_0.2_250)] hover:bg-[oklch(0.9_0.03_250)] hover:text-[oklch(0.55_0.22_250)] transition duration-150 focus:outline-none focus:ring-2 focus:ring-[oklch(0.6_0.2_250)]" title="Edit {{ $accountType->name }}" aria-label="Edit {{ $accountType->name }}">
											<flux:icon.pencil-square class="w-5 h-5" />
										</button>
										<!-- Delete Button -->
										<button wire:click="deleteActionTrigger({{ $accountType->id }})" class="cursor-pointer relative p-2 rounded-md bg-[oklch(0.95_0.02_260)] text-[oklch(0.6_0.2_10)] hover:bg-[oklch(0.9_0.03_10)] hover:text-[oklch(0.55_0.22_10)] transition duration-150 focus:outline-none focus:ring-2 focus:ring-[oklch(0.6_0.2_10)]" title="Delete {{ $accountType->name }}" aria-label="Delete {{ $accountType->name }}">
											<flux:icon.trash class="w-5 h-5" />
										</button>
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
				</div>

				<div class="space-y-3">
					<flux:checkbox label="Can have outflows (Can Give)" wire:model.live="can_give" id="can_give" />
					<flux:checkbox label="Can have inflows (Can Take)" wire:model.live="can_take" id="can_take" />

					@error('can_take')
					@else
						<p class="text-sm italic text-gray-500" aria-describedby="can_take-hint">* At least one option must be selected</p>
					@enderror
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
