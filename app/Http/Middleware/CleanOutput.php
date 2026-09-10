<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CleanOutput
{
    public function handle(Request $request, Closure $next): Response
    {
        // Clean any output that happened BEFORE response
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        ob_start();
        
        $response = $next($request);
        
        // Clean stray output AFTER response generation
        $strayOutput = ob_get_clean();
        
        // If JSON response, ensure no junk prefix
        if ($response->headers->get('Content-Type') 
            && str_contains($response->headers->get('Content-Type'), 'json')) {
            $content = $response->getContent();
            
            // Find first { or [ and strip everything before
            $jsonStart = -1;
            for ($i = 0; $i < strlen($content); $i++) {
                if ($content[$i] === '{' || $content[$i] === '[') {
                    $jsonStart = $i;
                    break;
                }
            }
            
            if ($jsonStart > 0) {
                $response->setContent(substr($content, $jsonStart));
            }
        }
        
        return $response;
    }
}