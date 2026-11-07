<?php

use App\Enums\LeaveRequestStatus;

it('renders a reusable badge with its semantic color', function () {
    $this->blade('<x-ui.badge color="green">Aktif</x-ui.badge>')
        ->assertSee('Aktif')
        ->assertSee('sanmal-badge-success', false)
        ->assertSee('green', false);
});

it('uses a neutral color by default and forwards presentation attributes', function () {
    $this->blade('<x-ui.badge class="shrink-0" data-testid="request-status">Menunggu</x-ui.badge>')
        ->assertSee('Menunggu')
        ->assertSee('shrink-0', false)
        ->assertSee('data-testid="request-status"', false);
});

it('maps zinc and gray badges to the dedicated neutral badge class only', function () {
    $this->blade('<x-ui.badge>Ditolak</x-ui.badge>')
        ->assertSee('sanmal-badge-neutral', false)
        ->assertDontSee('sanmal-badge-success', false)
        ->assertDontSee('sanmal-badge-warning', false);

    $this->blade('<x-ui.badge color="gray">Selesai</x-ui.badge>')
        ->assertSee('sanmal-badge-neutral', false);

    $this->blade('<x-ui.badge color="amber">Menunggu</x-ui.badge>')
        ->assertSee('sanmal-badge-warning', false)
        ->assertDontSee('sanmal-badge-neutral', false);
});

it('supports status labels and colors supplied by existing enums', function () {
    $status = LeaveRequestStatus::Pending;

    $this->blade('<x-ui.badge :color="$status->badgeColor()">{{ $status->label() }}</x-ui.badge>', compact('status'))
        ->assertSee($status->label())
        ->assertSee($status->badgeColor(), false);
});
