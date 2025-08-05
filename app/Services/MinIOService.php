<?php

namespace App\Services;

use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;
use App\Models\AssetType;

class MinIOService
{
    private S3Client $s3Client;
    private string $endpoint;
    private string $bucket;
    private string $region;
    private string $accessKey;
    private string $secretKey;
    private bool $useSSL;

    public function __construct()
    {
        $this->endpoint = config('services.minio.endpoint', 'http://localhost:4811');
        $this->bucket = config('services.minio.bucket', 'projectsdashboard');
        $this->region = config('services.minio.region', 'us-east-1');
        $this->accessKey = config('services.minio.access_key');
        $this->secretKey = config('services.minio.secret_key');
        $this->useSSL = config('services.minio.use_ssl', false);

        Log::info('MinIOService: Initializing with config', [
            'endpoint' => $this->endpoint,
            'bucket' => $this->bucket,
            'region' => $this->region,
            'access_key' => $this->accessKey ? '***' : 'NOT_SET',
            'secret_key' => $this->secretKey ? '***' : 'NOT_SET',
            'use_ssl' => $this->useSSL,
        ]);

        try {
            $this->s3Client = new S3Client([
                'version' => 'latest',
                'region' => $this->region,
                'endpoint' => $this->endpoint,
                'use_path_style_endpoint' => true,
                'credentials' => [
                    'key' => $this->accessKey,
                    'secret' => $this->secretKey,
                ],
                'scheme' => $this->useSSL ? 'https' : 'http',
            ]);

            Log::info('MinIOService: S3Client initialized successfully');
        } catch (\Exception $e) {
            Log::error('MinIOService: Failed to initialize S3Client', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Check if the main projectsdashboard bucket exists
     */
    public function checkMainBucketExists(): bool
    {
        try {
            Log::info('MinIOService: Checking if main bucket exists', ['bucket' => $this->bucket]);
            
            $exists = $this->s3Client->doesBucketExist($this->bucket);
            
            Log::info('MinIOService: Main bucket check result', [
                'bucket' => $this->bucket,
                'exists' => $exists
            ]);
            
            return $exists;
        } catch (AwsException $e) {
            Log::error('MinIOService: Error checking main bucket existence', [
                'bucket' => $this->bucket,
                'error' => $e->getMessage(),
                'code' => $e->getAwsErrorCode(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error('MinIOService: Unexpected error checking main bucket', [
                'bucket' => $this->bucket,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Create the main projectsdashboard bucket
     */
    public function createMainBucket(): bool
    {
        try {
            Log::info('MinIOService: Creating main bucket', [
                'bucket' => $this->bucket,
                'region' => $this->region
            ]);

            $this->s3Client->createBucket([
                'Bucket' => $this->bucket,
                'CreateBucketConfiguration' => [
                    'LocationConstraint' => $this->region,
                ],
            ]);

            Log::info('MinIOService: Main bucket created successfully', ['bucket' => $this->bucket]);

            // Set bucket policy to allow public read access
            Log::info('MinIOService: Setting bucket policy for public read access', ['bucket' => $this->bucket]);
            
            $this->s3Client->putBucketPolicy([
                'Bucket' => $this->bucket,
                'Policy' => json_encode([
                    'Version' => '2012-10-17',
                    'Statement' => [
                        [
                            'Sid' => 'PublicReadGetObject',
                            'Effect' => 'Allow',
                            'Principal' => '*',
                            'Action' => 's3:GetObject',
                            'Resource' => "arn:aws:s3:::$this->bucket/*"
                        ]
                    ]
                ])
            ]);

            Log::info('MinIOService: Bucket policy set successfully', ['bucket' => $this->bucket]);
            return true;
        } catch (AwsException $e) {
            // Check if the error is because the bucket already exists
            if ($e->getAwsErrorCode() === 'BucketAlreadyExists' || 
                $e->getAwsErrorCode() === 'BucketAlreadyOwnedByYou' ||
                str_contains($e->getMessage(), 'already exists')) {
                Log::info('MinIOService: Main bucket already exists', [
                    'bucket' => $this->bucket,
                    'code' => $e->getAwsErrorCode()
                ]);
                return true; // Consider this a success since the bucket exists
            }
            
            Log::error('MinIOService: Error creating main bucket', [
                'bucket' => $this->bucket,
                'error' => $e->getMessage(),
                'code' => $e->getAwsErrorCode(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error('MinIOService: Unexpected error creating main bucket', [
                'bucket' => $this->bucket,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Check if user bucket exists
     */
    public function checkUserBucketExists(string $username): bool
    {
        try {
            $userBucket = $this->bucket . '/' . $username;
            Log::info('MinIOService: Checking if user bucket exists', [
                'username' => $username,
                'user_bucket' => $userBucket
            ]);
            
            $exists = $this->s3Client->doesBucketExist($userBucket);
            
            Log::info('MinIOService: User bucket check result', [
                'username' => $username,
                'user_bucket' => $userBucket,
                'exists' => $exists
            ]);
            
            return $exists;
        } catch (AwsException $e) {
            Log::error('MinIOService: Error checking user bucket existence', [
                'username' => $username,
                'user_bucket' => $userBucket ?? 'unknown',
                'error' => $e->getMessage(),
                'code' => $e->getAwsErrorCode(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error('MinIOService: Unexpected error checking user bucket', [
                'username' => $username,
                'user_bucket' => $userBucket ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Create user bucket and asset type folders
     */
    public function createUserBucket(string $username): bool
    {
        try {
            Log::info('MinIOService: Creating user bucket and folders', [
                'username' => $username,
                'main_bucket' => $this->bucket
            ]);

            // Create user bucket (which is actually a folder in MinIO)
            $userBucket = $this->bucket . '/' . $username;
            
            // Get asset types from database, fallback to hardcoded if database is not available
            try {
                $assetTypes = AssetType::pluck('key')->toArray();
                Log::info('MinIOService: Using asset types from database', [
                    'asset_types' => $assetTypes
                ]);
            } catch (\Exception $e) {
                Log::warning('MinIOService: Could not fetch asset types from database, using fallback', [
                    'error' => $e->getMessage()
                ]);
                $assetTypes = ['images', 'videos', 'documents', 'others'];
            }
            
            foreach ($assetTypes as $assetType) {
                $folderKey = $username . '/' . $assetType . '/.keep';
                Log::info('MinIOService: Creating asset type folder', [
                    'username' => $username,
                    'asset_type' => $assetType,
                    'folder_key' => $folderKey
                ]);
                
                $this->s3Client->putObject([
                    'Bucket' => $this->bucket,
                    'Key' => $folderKey,
                    'Body' => '',
                ]);
                
                Log::info('MinIOService: Asset type folder created successfully', [
                    'username' => $username,
                    'asset_type' => $assetType,
                    'folder_key' => $folderKey
                ]);
            }

            Log::info('MinIOService: User bucket and all folders created successfully', [
                'username' => $username,
                'user_bucket' => $userBucket
            ]);

            return true;
        } catch (AwsException $e) {
            Log::error('MinIOService: Error creating user bucket', [
                'username' => $username,
                'user_bucket' => $userBucket ?? 'unknown',
                'error' => $e->getMessage(),
                'code' => $e->getAwsErrorCode(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error('MinIOService: Unexpected error creating user bucket', [
                'username' => $username,
                'user_bucket' => $userBucket ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Upload file to MinIO
     */
    public function uploadFile(UploadedFile $file, string $username, string $assetType, string $filename): array
    {
        try {
            Log::info('MinIOService: Starting file upload', [
                'username' => $username,
                'asset_type' => $assetType,
                'filename' => $filename,
                'original_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'real_path' => $file->getRealPath(),
            ]);

            // Ensure main bucket exists
            Log::info('MinIOService: Checking main bucket existence');
            if (!$this->checkMainBucketExists()) {
                Log::info('MinIOService: Main bucket does not exist, creating it');
                if (!$this->createMainBucket()) {
                    Log::error('MinIOService: Failed to create main bucket');
                    throw new \Exception('Failed to create main bucket');
                }
            } else {
                Log::info('MinIOService: Main bucket exists');
            }

            // Ensure user bucket exists
            Log::info('MinIOService: Checking user bucket existence', ['username' => $username]);
            if (!$this->checkUserBucketExists($username)) {
                Log::info('MinIOService: User bucket does not exist, creating it', ['username' => $username]);
                if (!$this->createUserBucket($username)) {
                    Log::error('MinIOService: Failed to create user bucket', ['username' => $username]);
                    throw new \Exception('Failed to create user bucket');
                }
            } else {
                Log::info('MinIOService: User bucket exists', ['username' => $username]);
            }

            // Upload file
            $key = $username . '/' . $assetType . '/' . $filename;
            
            Log::info('MinIOService: Uploading file to MinIO', [
                'bucket' => $this->bucket,
                'key' => $key,
                'source_file' => $file->getRealPath(),
                'content_type' => $file->getMimeType(),
            ]);
            
            $result = $this->s3Client->putObject([
                'Bucket' => $this->bucket,
                'Key' => $key,
                'SourceFile' => $file->getRealPath(),
                'ContentType' => $file->getMimeType(),
                'ACL' => 'public-read',
            ]);

            Log::info('MinIOService: File uploaded successfully', [
                'bucket' => $this->bucket,
                'key' => $key,
                'etag' => $result['ETag'],
            ]);

            // Generate public URL
            $publicUrl = config('services.minio.public_url', $this->endpoint);
            $url = rtrim($publicUrl, '/') . '/' . $this->bucket . '/' . $key;

            Log::info('MinIOService: Generated public URL', [
                'public_url' => $publicUrl,
                'final_url' => $url,
            ]);

            return [
                'success' => true,
                'etag' => $result['ETag'],
                'url' => $url,
                'key' => $key,
            ];

        } catch (AwsException $e) {
            Log::error('MinIOService: AWS error uploading file to MinIO', [
                'username' => $username,
                'asset_type' => $assetType,
                'filename' => $filename,
                'error' => $e->getMessage(),
                'code' => $e->getAwsErrorCode(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        } catch (\Exception $e) {
            Log::error('MinIOService: Unexpected error in uploadFile', [
                'username' => $username,
                'asset_type' => $assetType,
                'filename' => $filename,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Delete file from MinIO
     */
    public function deleteFile(string $username, string $assetType, string $filename): bool
    {
        try {
            $key = $username . '/' . $assetType . '/' . $filename;
            
            $this->s3Client->deleteObject([
                'Bucket' => $this->bucket,
                'Key' => $key,
            ]);

            return true;
        } catch (AwsException $e) {
            Log::error('Error deleting file from MinIO: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get file URL
     */
    public function getFileUrl(string $username, string $assetType, string $filename): string
    {
        $publicUrl = config('services.minio.public_url', $this->endpoint);
        $key = $username . '/' . $assetType . '/' . $filename;
        
        return rtrim($publicUrl, '/') . '/' . $this->bucket . '/' . $key;
    }

    /**
     * Test MinIO connection
     */
    public function testConnection(): array
    {
        try {
            Log::info('MinIOService: Testing MinIO connection');
            
            // List buckets to test connection
            $result = $this->s3Client->listBuckets();
            
            Log::info('MinIOService: Connection test successful', [
                'buckets_count' => count($result['Buckets']),
                'buckets' => array_map(function($bucket) {
                    return $bucket['Name'];
                }, $result['Buckets'])
            ]);
            
            return [
                'success' => true,
                'message' => 'Connection successful',
                'buckets' => $result['Buckets']
            ];
        } catch (AwsException $e) {
            Log::error('MinIOService: Connection test failed (AWS)', [
                'error' => $e->getMessage(),
                'code' => $e->getAwsErrorCode(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => $e->getAwsErrorCode(),
            ];
        } catch (\Exception $e) {
            Log::error('MinIOService: Connection test failed (Unexpected)', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
} 