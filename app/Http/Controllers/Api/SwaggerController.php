<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SwaggerController extends Controller
{
    private const ALLOWED_FILES = ['api-docs.json'];
    private const CACHE_TTL = 3600; // 1 hour
    
    /**
     * Serve API documentation JSON file
     */
    public function serveDocumentation(?string $jsonFile = null): JsonResponse|BinaryFileResponse
    {
        try {
            $filename = $this->validateAndNormalizeFilename($jsonFile);
            $filePath = $this->getDocumentationPath($filename);
            
            $this->ensureFileExists($filePath);
            
            return $this->serveFile($filePath);
            
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        } catch (\RuntimeException $e) {
            return $this->errorResponse($e->getMessage(), 404);
        } catch (\Exception $e) {
            Log::error('Unexpected error serving API documentation', [
                'file' => $jsonFile,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->errorResponse('Internal server error', 500);
        }
    }
    
    /**
     * Validate and normalize the requested filename
     */
    private function validateAndNormalizeFilename(?string $jsonFile): string
    {
        $filename = $jsonFile ?? 'api-docs.json';
        
        if (!in_array($filename, self::ALLOWED_FILES, true)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid documentation file "%s". Available files: %s', 
                    $filename, 
                    implode(', ', self::ALLOWED_FILES)
                )
            );
        }
        
        return $filename;
    }
    
    /**
     * Get the full path to the documentation file
     */
    private function getDocumentationPath(string $filename): string
    {
        return storage_path("api-docs/{$filename}");
    }
    
    /**
     * Ensure the documentation file exists
     */
    private function ensureFileExists(string $filePath): void
    {
        if (!File::exists($filePath)) {
            Log::info('API documentation file not found', [
                'path' => $filePath,
                'suggestion' => 'Run "php artisan l5-swagger:generate" to generate documentation'
            ]);
            
            throw new \RuntimeException('API documentation not available. Please generate the documentation first.');
        }
    }
    
    /**
     * Serve the file with appropriate headers and caching
     */
    private function serveFile(string $filePath): BinaryFileResponse
    {
        $lastModified = File::lastModified($filePath);
        $etag = md5($lastModified . $filePath);
        
        return response()->file($filePath, [
            'Content-Type' => 'application/json',
            'Cache-Control' => 'public, max-age=' . self::CACHE_TTL,
            'ETag' => $etag,
            'Last-Modified' => gmdate('D, d M Y H:i:s', $lastModified) . ' GMT'
        ]);
    }
    
    /**
     * Create standardized error response
     */
    private function errorResponse(string $message, int $status): JsonResponse
    {
        return response()->json([
            'error' => true,
            'message' => $message,
            'status' => $status
        ], $status);
    }
}
