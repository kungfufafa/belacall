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

    public function test_privacy_page_displays_required_sections(): void
    {
        $response = $this->get(route('legal.privacy'));

        $response->assertStatus(200);

        // Verify main title
        $response->assertSee('Kebijakan Privasi');

        // Verify key sections are present
        $response->assertSee('Pendahuluan');
        $response->assertSee('Informasi yang Kami Kumpulkan');
        $response->assertSee('Integrasi Telegram');
        $response->assertSee('Magic Link Authentication');
        $response->assertSee('Hak Warga');

        // Verify some specific content
        $response->assertSee('BELACALL');
        $response->assertSee('sistem pelaporan masyarakat');
        $response->assertSee('Pemerintah Desa');
    }

    public function test_terms_page_displays_required_sections(): void
    {
        $response = $this->get(route('legal.terms'));

        $response->assertStatus(200);

        // Verify main title
        $response->assertSee('Syarat dan Ketentuan');

        // Verify key sections are present
        $response->assertSee('Ketentuan Umum');
        $response->assertSee('Layanan Pelaporan');
        $response->assertSee('Kewajiban Warga');
        $response->assertSee('Service Level Agreement');

        // Verify some specific content
        $response->assertSee('BELACALL');
        $response->assertSee('sistem pelaporan');
        $response->assertSee('Telegram Bot');
    }

    public function test_legal_pages_are_publicly_accessible(): void
    {
        // Test privacy page without authentication
        $privacyResponse = $this->get(route('legal.privacy'));
        $privacyResponse->assertStatus(200);
        $privacyResponse->assertViewIs('legal.privacy');

        // Test terms page without authentication
        $termsResponse = $this->get(route('legal.terms'));
        $termsResponse->assertStatus(200);
        $termsResponse->assertViewIs('legal.terms');
    }
}
