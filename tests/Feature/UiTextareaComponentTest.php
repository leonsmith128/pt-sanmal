<?php

it('renders a labeled textarea with description and validation error', function () {
    $this->withViewErrors([
        'reason' => 'Alasan wajib diisi.',
    ]);

    $this->blade('
        <x-ui.textarea
            name="reason"
            label="Alasan"
            description="Maksimal 5.000 karakter."
            rows="5"
            resize="none"
            placeholder="Tuliskan alasan..."
            maxlength="5000"
            required
        >Isi awal</x-ui.textarea>
    ')
        ->assertSee('for="reason"', false)
        ->assertSee('id="reason"', false)
        ->assertSee('name="reason"', false)
        ->assertSee('rows="5"', false)
        ->assertSee('maxlength="5000"', false)
        ->assertSee('required', false)
        ->assertSee('Alasan')
        ->assertSee('Maksimal 5.000 karakter.')
        ->assertSee('Isi awal')
        ->assertSee('Alasan wajib diisi.');
});

it('forwards Livewire, Alpine, and native attributes to the textarea control', function () {
    $this->blade('
        <x-ui.textarea
            name="reason"
            label="Alasan"
            wire:model.live="reason"
            wire:loading.attr="disabled"
            x-model="reason"
            x-on:input="countCharacters()"
            aria-describedby="reason-help"
        />
    ')
        ->assertSee('wire:model.live="reason"', false)
        ->assertSee('wire:loading.attr="disabled"', false)
        ->assertSee('x-model="reason"', false)
        ->assertSee('x-on:input="countCharacters()"', false)
        ->assertSee('aria-describedby="reason-help"', false);
});

it('supports a custom textarea id and suppresses its validation error', function () {
    $this->withViewErrors([
        'reason' => 'Alasan wajib diisi.',
    ]);

    $this->blade('
        <x-ui.textarea
            name="reason"
            id="leave-reason"
            label="Alasan"
            :show-error="false"
        />
    ')
        ->assertSee('for="leave-reason"', false)
        ->assertSee('id="leave-reason"', false)
        ->assertDontSee('Alasan wajib diisi.');
});
