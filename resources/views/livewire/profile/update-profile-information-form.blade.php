<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

new class extends Component
{
    public string $name = '';
    public string $email = '';
    public ?string $department_id = '';
    public ?string $position = '';
    public ?string $grade = '';
    public ?string $telephone_num = '';
    public ?string $office_num = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
        $this->department_id = Auth::user()->department_id;
        $this->position = Auth::user()->position;
        $this->grade = Auth::user()->grade;
        $this->telephone_num = Auth::user()->telephone_num;
        $this->office_num = Auth::user()->office_num;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            'department_id' => ['required'],
            'position' => ['required', 'string'],
            'grade' => ['required', 'string'],
            'telephone_num' => ['required', 'string'],
            'office_num' => ['required', 'string'],        
        ]);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        session()->flash('status', 'Profil anda berjaya dikemaskini!');

        $this->dispatch('profile-updated', name: $user->name);
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function sendVerification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }
}; ?>

<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Maklumat Profil') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Kemaskini maklumat profil akaun anda.") }}
        </p>
    </header>

    @if (session('status'))
    <div class="mt-6 p-4 bg-green-50 border-l-4 border-green-400 text-green-700 rounded-lg shadow-sm flex items-center">
        <svg class="h-5 w-5 text-green-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
        </svg>
        <span class="text-sm font-bold uppercase tracking-wide">
            {{ session('status') }}
        </span>
    </div>
    @endif

    <form wire:submit="updateProfileInformation" class="mt-6 space-y-6">
        <div>
            <x-input-label for="name" :value="__('Nama')" />
            <x-text-input wire:model="name" id="name" name="name" type="text" class="mt-1 block w-full" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Emel')" />
            <x-text-input wire:model="email" id="email" name="email" type="email" class="mt-1 block w-full" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! auth()->user()->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800">
                        {{ __('Your email address is unverified.') }}

                        <button wire:click.prevent="sendVerification" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div>
            <x-input-label for="department_id" :value="__('Bahagian')" />
            <select wire:model="department_id" id="department_id" name="department_id" 
                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                <option value="">-- Pilih Bahagian --</option>
                    @foreach(\App\Models\Department::orderBy('name')->get() as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                    @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('department_id')" />        
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <x-input-label for="position" :value="__('Jawatan')" />
                <x-text-input wire:model="position" id="position" name="position" type="text" class="mt-1 block w-full" required placeholder="cth: PP(U)I2" autocomplete="position" />
                <x-input-error class="mt-2" :messages="$errors->get('position')" />
            </div>

            <div>
                <x-input-label for="grade" :value="__('Gred')" />
                <x-text-input wire:model="grade" id="grade" name="grade" type="text" class="mt-1 block w-full" required placeholder="cth: M10" autocomplete="grade" />
                <x-input-error class="mt-2" :messages="$errors->get('grade')" />
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <x-input-label for="telephone_num" :value="__('No. Telefon')" />
                <x-text-input wire:model="telephone_num" id="telephone_num" name="telephone_num" type="text" class="mt-1 block w-full" required placeholder="cth: 012-34567890" autocomplete="telephone_num" />
                <x-input-error class="mt-2" :messages="$errors->get('telephone_num')" />
            </div>

            <div>
                <x-input-label for="office_num" :value="__('No. Pejabat')" />
                <x-text-input wire:model="office_num" id="office_num" name="office_num" type="text" class="mt-1 block w-full" required placeholder="cth: 03-8885 3132" autocomplete="office_num" />
                <x-input-error class="mt-2" :messages="$errors->get('office_num')" />
            </div>
        </div>


        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            <x-action-message class="me-3" on="profile-updated">
                {{ __('Saved.') }}
            </x-action-message>
        </div>
    </form>
</section>
