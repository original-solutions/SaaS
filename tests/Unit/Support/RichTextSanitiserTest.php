<?php

use App\Support\RichTextSanitiser;

it('strips script tags', function (): void {
    $html = '<p>Hello</p><script>alert("xss")</script>';
    $result = RichTextSanitiser::sanitise($html);

    expect($result)->toBe('<p>Hello</p>alert("xss")');
    expect($result)->not->toContain('<script>');
});

it('allows safe tags like b and p', function (): void {
    $html = '<p>This is <b>bold</b> and <em>italic</em>.</p>';
    $result = RichTextSanitiser::sanitise($html);

    expect($result)->toBe('<p>This is <b>bold</b> and <em>italic</em>.</p>');
});

it('removes malicious event handler attributes', function (): void {
    $html = '<p onmouseover="alert(1)">Hello</p>';
    $result = RichTextSanitiser::sanitise($html);

    expect($result)->not->toContain('onmouseover');
});

it('removes javascript URIs', function (): void {
    $html = '<a href="javascript:alert(1)">Click</a>';
    $result = RichTextSanitiser::sanitise($html);

    expect($result)->not->toContain('javascript:');
});

it('is idempotent when re-sanitised', function (): void {
    $html = '<p>Safe <b>content</b></p>';
    $first = RichTextSanitiser::sanitise($html);
    $second = RichTextSanitiser::sanitise($first);

    expect($second)->toBe($first);
});
