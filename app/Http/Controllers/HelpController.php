<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class HelpController extends Controller
{
    
    // default help page
    public function index(Request $request)
    {
        $page = $request->query('page');
        
        // Sanitize page parameter to support subdirectories while preventing directory traversal
        if ($page) {
            // Split by slash to handle subdirectories
            $segments = explode('/', $page);
            $cleanSegments = [];
            
            foreach ($segments as $segment) {
                // Sanitize each segment
                $cleanSegment = Str::slug($segment);
                if ($cleanSegment && $cleanSegment !== '.' && $cleanSegment !== '..') {
                    $cleanSegments[] = $cleanSegment;
                }
            }
            
            // Reconstruct the path if we have valid segments
            $page = !empty($cleanSegments) ? implode('/', $cleanSegments) : null;
        }
        
        return view('help.index', [
            'page' => $page,
        ]);
    }
}
