<?php
declare(strict_types=1);

namespace IMS\Tests;

use IMS\Security\CsrfGuard;
use IMS\Security\RateLimiter;
use IMS\Security\InputValidator;

class SecurityTest extends TestCase
{
    public function testCsrfTokenGenerationAndValidation(): void
    {
        $token = CsrfGuard::generateToken();

        $this->assertTrue(strlen($token) === 64);
        $this->assertTrue(CsrfGuard::isValidToken($token));
        $this->assertFalse(CsrfGuard::isValidToken('invalid_token_xyz'));
        $this->assertFalse(CsrfGuard::isValidToken(null));
        $this->assertFalse(CsrfGuard::isValidToken(''));
    }

    public function testRateLimiterSlidingWindow(): void
    {
        $action = 'test_login_' . uniqid();

        // 3 allowed attempts in 10-second window
        $this->assertTrue(RateLimiter::isAllowed($action, 3, 10));
        $this->assertTrue(RateLimiter::isAllowed($action, 3, 10));
        $this->assertTrue(RateLimiter::isAllowed($action, 3, 10));

        // 4th attempt should be blocked
        $this->assertFalse(RateLimiter::isAllowed($action, 3, 10));

        // Reset clears throttling
        RateLimiter::reset($action);
        $this->assertTrue(RateLimiter::isAllowed($action, 3, 10));
    }

    public function testInputValidatorRules(): void
    {
        // Email
        $this->assertTrue(InputValidator::validateEmail('info@apextechnologies.com.np'));
        $this->assertFalse(InputValidator::validateEmail('not-an-email'));

        // Nepali Phone (10 digits starting with 98 or 97)
        $this->assertTrue(InputValidator::validateNepaliPhone('9841234567'));
        $this->assertTrue(InputValidator::validateNepaliPhone('9751234567'));
        $this->assertFalse(InputValidator::validateNepaliPhone('12345'));

        // Nepali PAN (9 digits)
        $this->assertTrue(InputValidator::validatePanNumber('601234567'));
        $this->assertFalse(InputValidator::validatePanNumber('12345'));

        // SKU
        $this->assertTrue(InputValidator::validateSku('SKU-DELL-15'));
        $this->assertFalse(InputValidator::validateSku('bad sku with spaces!'));

        // XSS Sanitizer via htmlspecialchars
        $dirty = '<script>alert("hacked")</script>';
        $this->assertEquals('&lt;script&gt;alert(&quot;hacked&quot;)&lt;/script&gt;', InputValidator::sanitizeHtml($dirty));
    }
}
