<?php
use function Livewire\Volt\{state, rules, on};
use App\Models\AccountType;

state([
    'name' => '',
    'can_give' => false,
    'can_take' => false,
    'account_types' => fn() => AccountType::all(),
    'showModal' => false,
    'editAccountType' => null,
]);

rules([
    'name' => 'required|string|max:50|unique:account_types,name,{$this->editAccountType}',
    'can_give' => 'boolean',
    'can_take' => 'boolean',
]);

$createAccountType = function () {
    if (!$this->can_give && !$this->can_take) {
        $this->addError('can_give', 'At least one option (Can Give or Can Take) must be selected.');
        $this->addError('can_take', 'At least one option (Can Give or Can Take) must be selected.');
        return;
    }

    $validated = $this->validate();

    try {
        AccountType::create([
            'name' => $validated['name'],
            'can_give' => $this->can_give,
            'can_take' => $this->can_take,
        ]);

        $this->dispatch('account-type-created', message: 'Account type created successfully!');
        $this->resetForm();
        $this->account_types = AccountType::all();
    } catch (\Exception $e) {
        \Log::error($e->getMessage());
        $this->dispatch('error', message: 'Failed to create account type. Please try again.');
    }
};

$updateAccountType = function () {
    if (!$this->can_give && !$this->can_take) {
        $this->addError('can_give', 'At least one option (Can Give or Can Take) must be selected.');
        $this->addError('can_take', 'At least one option (Can Give or Can Take) must be selected.');
        return;
    }

    $validated = $this->validate();

    try {
        $accountType = AccountType::findOrFail($this->editAccountType);
        $accountType->update([
            'name' => $validated['name'],
            'can_give' => $this->can_give,
            'can_take' => $this->can_take,
        ]);

        $this->dispatch('account-type-updated', message: 'Account type updated successfully!');
        $this->resetForm();
        $this->account_types = AccountType::all();
    } catch (\Exception $e) {
        \Log::error($e->getMessage());
        $this->dispatch('error', message: 'Failed to create account type. Please try again.');
    }
};

$openEditModal = function ($id) {
    $accountType = AccountType::findOrFail($id);
    $this->name = $accountType->name;
    $this->can_give = (bool) $accountType->can_give;
    $this->can_take = (bool) $accountType->can_take;
    $this->editAccountType = $id;
    $this->showModal = true;
};

$deleteAccountType = function ($id) {
    try {
        AccountType::findOrFail($id)->delete();
        $this->dispatch('account-type-deleted', message: 'Account type deleted successfully!');
        $this->account_types = AccountType::all();
    } catch (\Exception $e) {
        \Log::error($e->getMessage());
        $this->dispatch('error', message: 'Failed to delete account type. Please try again.');
    }
};

$resetForm = function () {
    $this->reset(['name', 'can_give', 'can_take', 'editAccountType', 'showModal']);
    $this->can_give = false;
    $this->can_take = false;
};

on([
    'account-type-created' => function ($message) {
        session()->flash('success', $message);
    },
    'account-type-updated' => function ($message) {
        session()->flash('success', $message);
    },
    'account-type-deleted' => function ($message) {
        session()->flash('success', $message);
    },
    'error' => function ($message) {
        session()->flash('error', $message);
    },
]);
?>

<div class="border-gray-100 mx-auto max-w-4xl rounded-xl border">
    <!-- Header and Create Button -->
    {{--
   <div class="flex justify-between items-center mb-8">
   <h1 class="text-3xl font-bold text-gray-800">Account Types</h1>
   <button
   x-on:click="$wire.showModal = true"
   class="flex items-center gap-2 bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
   <svg
   class="w-5 h-5"
   fill="none"
   stroke="currentColor"
   viewBox="0 0 24 24">
   <path
   stroke-linecap="round"
   stroke-linejoin="round"
   stroke-width="2"
   d="M12 4v16m8-8H4"></path>
   </svg>
   Add Account Type
   </button>
   </div>
 --}}

    <!-- Modal -->
    <div x-show="$wire.showModal" x-cloak class="bg-black/50 fixed inset-0 z-50 flex items-center justify-center"
        aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div x-show="$wire.showModal" x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="bg-white max-h-[90vh] w-full max-w-md overflow-y-auto rounded-xl p-6 shadow-2xl">
            <form wire:submit="{{ $editAccountType ? 'updateAccountType' : 'createAccountType' }}" x-ref="form"
                class="space-y-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-gray-800 text-2xl font-semibold">
                        {{ $editAccountType ? 'Update Account Type' : 'Create Account Type' }}
                    </h2>
                    <button type="button" x-on:click="
        $wire.resetForm()
        $refs.form.reset()
      "
                        class="text-gray-500 hover:text-gray-700">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Name Input -->
                <div>
                    <label for="name" class="text-gray-700 mb-1 block text-sm font-medium">
                        Account Type Name
                    </label>
                    <input type="text" wire:model="name" id="name"
                        class="border-gray-300 focus:border-blue-500 focus:ring-blue-200 w-full rounded-lg border p-3 outline-none transition duration-150 focus:ring-2"
                        placeholder="e.g., Bank, Credit Card" />
                    @error('name')
                        <span class="text-red-500 mt-1 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Checkboxes -->
                <div class="space-y-3">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" wire:model="can_give" value="1"
                            class="text-blue-600 border-gray-300 focus:ring-blue-500 h-5 w-5 rounded" />
                        <span class="text-gray-700 text-sm">
                            Can have outflows (Can Give)
                        </span>
                    </label>
                    @error('can_give')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror

                    <label class="flex items-center gap-2">
                        <input type="checkbox" wire:model="can_take" value="1"
                            class="text-blue-600 border-gray-300 focus:ring-blue-500 h-5 w-5 rounded" />
                        <span class="text-gray-700 text-sm">
                            Can have inflows (Can Take)
                        </span>
                    </label>
                    @error('can_take')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Validation Note -->
                <p class="text-gray-500 text-sm italic">
                    * At least one option must be selected
                </p>

                <!-- Modal Buttons -->
                <div class="flex justify-end gap-3">
                    <button type="button" x-on:click="
        $wire.resetForm()
        $refs.form.reset()
      "
                        class="bg-gray-200 text-gray-700 hover:bg-gray-300 focus:ring-gray-500 rounded-lg px-4 py-2 transition duration-150 focus:outline-none focus:ring-2">
                        Cancel
                    </button>
                    <button type="submit"
                        class="bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500 rounded-lg px-4 py-2 transition duration-150 focus:outline-none focus:ring-2">
                        {{ $editAccountType ? 'Update' : 'Create' }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Success/Error Messages -->
    @if (session('success'))
        <div class="bg-green-100 text-green-800 mb-6 flex items-center gap-2 rounded-lg p-4">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="bg-red-100 text-red-800 mb-6 flex items-center gap-2 rounded-lg p-4">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
            {{ session('error') }}
        </div>
    @endif

    <!-- Account Types Table -->
    <div class="bg-white rounded-xl shadow-lg">
        <div class="p-6">
            <div class="mb-6 flex justify-between">
                <h2 class="text-gray-800 text-xl font-semibold">
                    Existing Account Types
                </h2>
                <button x-on:click="$wire.showModal = true"
                    class="bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500 flex items-center gap-2 rounded-lg px-4 py-2 transition duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Add Account Type
                </button>
            </div>
            @if ($account_types->isEmpty())
                <p class="text-gray-500 py-6 text-center">No account types found.</p>
            @else
                <div class="h-64 overflow-x-auto">
                    <table class="divide-gray-200 min-w-full divide-y">
                        <thead class="bg-gray-50 sticky top-0">
                            <tr>
                                <th
                                    class="text-gray-500 px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                                    Name
                                </th>
                                <th
                                    class="text-gray-500 px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                                    Can Give
                                </th>
                                <th
                                    class="text-gray-500 px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                                    Can Take
                                </th>
                                <th
                                    class="text-gray-500 px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-gray-200 divide-y">
                            @foreach ($account_types as $accountType)
                                <tr class="hover:bg-gray-50 transition duration-150">
                                    <td class="text-gray-700 whitespace-nowrap px-6 py-4">
                                        {{ $accountType->name }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <span class="{{ $accountType->can_give ? 'text-green-600' : 'text-red-600' }}">
                                            {{ $accountType->can_give ? 'Yes' : 'No' }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <span
                                            class="{{ $accountType->can_take ? 'text-green-600' : 'text-red-600' }}">
                                            {{ $accountType->can_take ? 'Yes' : 'No' }}
                                        </span>
                                    </td>
                                    <td class="space-x-3 whitespace-nowrap px-6 py-4">
                                        <button wire:click="openEditModal({{ $accountType->id }})"
                                            class="text-blue-600 hover:text-blue-800 transition duration-150">
                                            Edit
                                        </button>
                                        <button wire:click="deleteAccountType({{ $accountType->id }})"
                                            class="text-red-600 hover:text-red-800 transition duration-150"
                                            onclick="return confirm('Are you sure you want to delete this account type?')">
                                            Delete
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
</div>

<script>
    document.addEventListener('livewire:initialized', () => {
        Alpine.data('modal', () => ({
            init() {
                this.$watch('$wire.showModal', (value) => {
                    if (!value) {
                        this.$refs.form?.reset();
                    }
                });
            },
        }));
    });
</script>
