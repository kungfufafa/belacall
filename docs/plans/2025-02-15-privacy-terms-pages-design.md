# Privacy Policy and Terms of Service Pages Design

**Date:** 2025-02-15
**Status:** Approved
**Scope:** Add Privacy Policy and Terms of Service pages for BELACALL village government reporting system

## Overview

Add static Privacy Policy and Terms of Service pages accessible from the footer, focusing on Warga (citizen) access level. Content will cover comprehensive aspects including standard reporting, Telegram integration, magic link authentication, and SLA commitments.

## Architecture

### Controller
- **File:** `app/Http/Controllers/LegalController.php`
- **Methods:**
  - `privacy()` - Returns privacy policy view
  - `terms()` - Returns terms of service view
- **Access:** Publicly accessible (no authentication required)

### Routes
- `GET /privacy` → `LegalController@privacy` → route name: `legal.privacy`
- `GET /terms` → `LegalController@terms` → route name: `legal.terms`

### Views
- `resources/views/legal/privacy.blade.php` - Privacy Policy page
- `resources/views/legal/terms.blade.php` - Terms of Service page
- Both extend `layouts/app.blade.php`

### Footer Update
- Modify `resources/views/layouts/app.blade.php` lines 74-75
- Convert plain text to clickable route links

## Content Structure

### Privacy Policy Sections
1. Pendahuluan (Introduction)
2. Informasi yang Kami Kumpulkan (Data Collection)
3. Penggunaan Informasi (Data Usage)
4. Integrasi Telegram
5. Magic Link Authentication
6. Penyimpanan dan Keamanan Data
7. Hak Warga (Citizen Rights)
8. SLA dan Komitmen Layanan
9. Kontak
10. Perubahan Kebijakan

### Terms of Service Sections
1. Ketentuan Umum
2. Layanan Pelaporan
3. Kewajiban Warga
4. Penggunaan Telegram Bot
5. SLA (Service Level Agreement)
6. Batasan Tanggung Jawab
7. Penyelesaian Sengketa
8. Perubahan Syarat
9. Hukum yang Berlaku

## UI/UX Design

### Visual Design
- Uses existing app layout with green branding
- Content container: `max-w-4xl mx-auto` for optimal readability
- Typography:
  - h1: `text-4xl font-bold text-gray-900 mb-4`
  - h2: `text-2xl font-semibold text-gray-800 mt-8 mb-4`
  - h3: `text-xl font-medium text-gray-700 mt-6 mb-3`
  - p: `text-base text-gray-600 leading-relaxed mb-4`
  - ul: `list-disc list-inside text-gray-600 space-y-2 mb-4`

### Footer Links
- Add hover effects matching existing nav links
- Use `route('legal.privacy')` and `route('legal.terms')` helpers

### Responsive Design
- Mobile: Single column, full-width
- Desktop: Centered with comfortable reading width

## Implementation Details

### Controller Code
```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LegalController extends Controller
{
    public function privacy()
    {
        return view('legal.privacy');
    }

    public function terms()
    {
        return view('legal.terms');
    }
}
```

### Route Registration
Add to `routes/web.php`:
```php
use App\Http\Controllers\LegalController;

Route::get('/privacy', [LegalController::class, 'privacy'])->name('legal.privacy');
Route::get('/terms', [LegalController::class, 'terms'])->name('legal.terms');
```

### Footer Modification
Update lines 74-75 in `resources/views/layouts/app.blade.php`:
```blade
<a href="{{ route('legal.privacy') }}" class="text-gray-500 hover:text-gray-900 transition-colors text-sm">Privacy</a>
<a href="{{ route('legal.terms') }}" class="text-gray-500 hover:text-gray-900 transition-colors text-sm">Terms</a>
```

## Testing Strategy

- Feature test: Verify both routes return 200 status
- Feature test: Verify routes are accessible without authentication
- Visual test: Check pages render correctly on mobile and desktop
- Link test: Verify footer links point to correct routes

## Database Changes

None required - all content is static in Blade views.

## Security Considerations

- Pages are publicly accessible as intended
- No user input processing
- No sensitive data exposure
- Content version-controlled through git

## Future Enhancements

- Table of contents sidebar for desktop
- "Back to top" button
- Version history tracking
- Print-friendly styles
