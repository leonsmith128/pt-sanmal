<?php

it('renders a reusable badge with its semantic color', function () {
    $this->blade('<x-ui.badge color="green">Aktif</x-ui.badge>')
        ->assertSee('Aktif')
        ->assertSee('green', false);
});

it('uses a neutral color by default and forwards presentation attributes', function () {
    $this->blade('<x-ui.badge class="shrink-0" data-testid="request-status">Menunggu</x-ui.badge>')
        ->assertSee('Menunggu')
        ->assertSee('shrink-0', false)
        ->assertSee('data-testid="request-status"', false);
});

it('supports status labels and colors supplied by existing enums', function () {
    $status = \App\Enums\LeaveRequestStatus::Pending;

    $this->blade('<x-ui.badge :color="$status->badgeColor()">{{ $status->label() }}</x-ui.badge>', compact('status'))
        ->assertSee($status->label())
        ->assertSee($status->badgeColor(), false);
});
