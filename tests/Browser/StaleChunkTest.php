<?php

declare(strict_types=1);

function failPageChunkLoad(object $page): void
{
    $page->script("window.alive = true; window.dispatchEvent(new Event('vite:preloadError', { cancelable: true }))");
    $page->wait(1.5);
}

it('reloads once when a page chunk fails to load, instead of leaving the screen blank', function () {
    $page = visit('/login')
        ->fill('email', seedDemoOwner()->email)
        ->fill('password', 'secret-pass-123')
        ->press('sign in')
        ->wait(1.5)
        ->assertSee('Focus project');

    failPageChunkLoad($page);

    expect($page->script('window.alive'))->toBeNull();
    $page->assertSee('Focus project');

    failPageChunkLoad($page);

    expect($page->script('window.alive'))->toBeTrue();
});
