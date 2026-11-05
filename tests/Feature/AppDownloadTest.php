<?php

namespace Tests\Feature;

use Tests\TestCase;

class AppDownloadTest extends TestCase
{
    public function test_app_downloads_as_an_apk_and_not_a_zip(): void
    {
        if (! file_exists(public_path('downloads/app-release.apk'))) {
            $this->markTestSkipped('The APK is not present.');
        }

        $response = $this->get('/download/app')->assertOk();

        $this->assertSame('application/vnd.android.package-archive', $response->headers->get('Content-Type'));
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('mdrrmo-app.apk', $response->headers->get('Content-Disposition'));
    }
}
