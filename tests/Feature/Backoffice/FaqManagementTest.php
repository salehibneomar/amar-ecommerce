<?php

use App\Enums\Content\FaqStatusEnum;
use App\Livewire\Backoffice\Faqs\Index;
use App\Models\Faq;
use App\Models\User;
use Livewire\Livewire;

test('guests cannot access faq management', function () {
    $this->get(route('manage.faqs.index'))
        ->assertRedirect(route('login'));
});

test('regular users are redirected from faq management', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('manage.faqs.index'))
        ->assertRedirect(route('user.dashboard'));
});

test('administrators can create update and delete faqs', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    $this->get(route('manage.faqs.index'))
        ->assertOk()
        ->assertSeeLivewire('backoffice.faqs.index');

    Livewire::test(Index::class)
        ->call('createFaq')
        ->set('title', 'How do I pay?')
        ->set('description', 'With bKash or Nagad.')
        ->set('faqStatus', 'inactive')
        ->call('saveFaq')
        ->assertHasNoErrors()
        ->assertSee('How do I pay?');

    $faq = Faq::query()->sole();

    expect($faq->description)->toBe('With bKash or Nagad.')
        ->and($faq->status)->toBe(FaqStatusEnum::Inactive);

    Livewire::test(Index::class)
        ->call('editFaq', $faq->id)
        ->assertSet('title', 'How do I pay?')
        ->set('title', 'How can I pay?')
        ->set('faqStatus', 'active')
        ->call('saveFaq')
        ->assertHasNoErrors();

    expect($faq->refresh()->title)->toBe('How can I pay?')
        ->and($faq->status)->toBe(FaqStatusEnum::Active);

    Livewire::test(Index::class)
        ->call('confirmDeleteFaq', $faq->id)
        ->call('deleteFaq');

    expect(Faq::find($faq->id))->toBeNull();
});

test('faq title description and status are required', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(Index::class)
        ->call('createFaq')
        ->set('faqStatus', '')
        ->call('saveFaq')
        ->assertHasErrors(['title' => 'required', 'description' => 'required', 'faqStatus' => 'required']);

    expect(Faq::count())->toBe(0);
});
