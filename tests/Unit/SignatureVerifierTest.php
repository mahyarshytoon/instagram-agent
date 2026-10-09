<?php

namespace Tests\Unit;

use App\Services\SignatureVerifier;
use Illuminate\Http\Request;
use Tests\TestCase;

class SignatureVerifierTest extends TestCase
{
    public function test_it_accepts_valid_signature(): void
    {
        $secret = 'test_secret';
        config(['agent.instagram.app_secret' => $secret]);

        $payload = '{"test":"data"}';
        $signature = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        $request = Request::create('/webhook', 'POST', [], [], [], [], $payload);
        $request->headers->set('X-Hub-Signature-256', $signature);

        $verifier = new SignatureVerifier();
        $this->assertTrue($verifier->verify($request));
    }

    public function test_it_rejects_invalid_signature(): void
    {
        config(['agent.instagram.app_secret' => 'test_secret']);

        $request = Request::create('/webhook', 'POST', [], [], [], [], '{"test":"data"}');
        $request->headers->set('X-Hub-Signature-256', 'sha256=wrong_signature');

        $verifier = new SignatureVerifier();
        $this->assertFalse($verifier->verify($request));
    }

    public function test_it_rejects_missing_signature(): void
    {
        $request = Request::create('/webhook', 'POST');

        $verifier = new SignatureVerifier();
        $this->assertFalse($verifier->verify($request));
    }
}