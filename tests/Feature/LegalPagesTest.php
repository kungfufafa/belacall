<?php

namespace Tests\Feature;

use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    public function test_privacy_page_returns_successful_response(): void
    {
        $response = $this->get(route('legal.privacy'));

        $response->assertStatus(200);
        $response->assertViewIs('legal.privacy');
    }

    public function test_terms_page_can_be_accessed(): void
    {
        $response = $this->get(route('legal.terms'));

        $response->assertStatus(200);
        $response->assertViewIs('legal.terms');
    }

    public function test_footer_contains_privacy_and_terms_links(): void
    {
        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertSee(route('legal.privacy'));
        $response->assertSee(route('legal.terms'));
    }
}
