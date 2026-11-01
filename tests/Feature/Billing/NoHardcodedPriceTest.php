<?php

namespace Tests\Feature\Billing;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Prices and promos come only from config via PlanPricingInterface. Guards
 * against hard-coded prices or fake discount badges coming back.
 */
class NoHardcodedPriceTest extends TestCase
{
    public function test_views_have_no_hardcoded_prices_or_fake_discounts(): void
    {
        $offenders = [];

        foreach (File::allFiles(resource_path('views')) as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            if (preg_match('/\b(24|29|41)[,.](90|58)\b|40%\s*OFF|-40%/i', $file->getContents())) {
                $offenders[] = $file->getRelativePathname();
            }
        }

        $this->assertSame([], $offenders, 'Hard-coded price/promo found in: '.implode(', ', $offenders));
    }

    public function test_app_code_has_no_hardcoded_prices(): void
    {
        $offenders = [];

        foreach (File::allFiles(app_path()) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $contents = $file->getContents();

            foreach (['24.90', '24,90', '41.58', '41,58'] as $needle) {
                if (str_contains($contents, $needle)) {
                    $offenders[] = $file->getRelativePathname().' ('.$needle.')';
                }
            }
        }

        $this->assertSame([], $offenders, 'Hard-coded price found in: '.implode(', ', $offenders));
    }
}
