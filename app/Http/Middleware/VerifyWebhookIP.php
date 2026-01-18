<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class VerifyWebhookIP
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $service): Response
    {
        $allowedIPs = $this->getAllowedIPs($service);
        $requestIP = $request->ip();

        // Log webhook request
        Log::info("Webhook request from {$service}", [
            'ip' => $requestIP,
            'path' => $request->path(),
            'method' => $request->method(),
        ]);

        // Allow all IPs in local/development environment
        if (app()->environment('local', 'development')) {
            Log::info("Local/development environment - allowing all IPs for {$service}");
            return $next($request);
        }

        // Allow localhost/127.0.0.1 for testing purposes
        if (in_array($requestIP, ['127.0.0.1', '::1', 'localhost'])) {
            Log::info("Localhost access - allowing for testing purposes");
            return $next($request);
        }

        // Check if IP is allowed
        if (!in_array($requestIP, $allowedIPs) && !$this->isIPInRange($requestIP, $allowedIPs)) {
            Log::warning("Unauthorized webhook access attempt", [
                'service' => $service,
                'ip' => $requestIP,
                'allowed_ips' => $allowedIPs,
            ]);

            return response()->json([
                'message' => 'Unauthorized IP address'
            ], 403);
        }

        return $next($request);
    }

    /**
     * Get allowed IPs for specific service
     */
    private function getAllowedIPs(string $service): array
    {
        return match ($service) {
            'duitku' => [
                // Duitku Production IPs
            ],
            'ipaymu' => [
                // Ipaymu Production IPs
                '120.89.93.249',
                '120.89.93.222',
            ],
            'digiflazz' => [
                // Digiflazz IPs
                '52.74.250.133'
            ],
            'suppliers' => [
                // Example supplier IPs (replace with actual supplier IPs)
                '52.74.250.133', // Supplier Digiflazz IP
                '159.223.78.143', // Supplier OnoyStore IP
                '103.180.125.252', // Supplier YokkStore IP
                // Tambahkan IP YokkStore di sini setelah mendapatkan dari YokkStore
                // Contoh: '103.xxx.xxx.xxx', // YokkStore IP
            ],
            default => [],
        };
    }

    /**
     * Check if IP is in CIDR range
     */
    private function isIPInRange(string $ip, array $allowedIPs): bool
    {
        foreach ($allowedIPs as $range) {
            // If it's a CIDR notation
            if (str_contains($range, '/')) {
                if ($this->ipInCIDR($ip, $range)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Check if IP is in CIDR range
     */
    private function ipInCIDR(string $ip, string $cidr): bool
    {
        [$subnet, $mask] = explode('/', $cidr);

        $ip_long = ip2long($ip);
        $subnet_long = ip2long($subnet);
        $mask_long = -1 << (32 - (int)$mask);

        return ($ip_long & $mask_long) === ($subnet_long & $mask_long);
    }
}
