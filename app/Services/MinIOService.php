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

        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function checkMainBucketExists(): bool
    {
        try {
            
            $exists = $this->s3Client->doesBucketExist($this->bucket);
            
            
            return $exists;
        } catch (AwsException $e) {
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function createMainBucket(): bool
    {
        try {

            $this->s3Client->createBucket([
                'Bucket' => $this->bucket,
                'CreateBucketConfiguration' => [
                    'LocationConstraint' => $this->region,
                ],
            ]);
            
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

            return true;
        } catch (AwsException $e) {
            if ($e->getAwsErrorCode() === 'BucketAlreadyExists' || 
                $e->getAwsErrorCode() === 'BucketAlreadyOwnedByYou' ||
                str_contains($e->getMessage(), 'already exists')) {
                return true;
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function checkUserBucketExists(string $username): bool
    {
        try {
            $userBucket = $this->bucket . '/' . $username;
            
            $exists = $this->s3Client->doesBucketExist($userBucket);
            
            
            return $exists;
        } catch (AwsException $e) {
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function createUserBucket(string $username): bool
    {
        try {

            $userBucket = $this->bucket . '/' . $username;
            
            try {
                $assetTypes = AssetType::pluck('key')->toArray();
            } catch (\Exception $e) {
                $assetTypes = ['images', 'videos', 'documents', 'others'];
            }
            
            foreach ($assetTypes as $assetType) {
                $folderKey = $username . '/' . $assetType . '/.keep';
                
                $this->s3Client->putObject([
                    'Bucket' => $this->bucket,
                    'Key' => $folderKey,
                    'Body' => '',
                ]);
            }

            return true;
        } catch (AwsException $e) {
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function uploadFile(UploadedFile $file, string $username, string $assetType, string $filename): array
    {
        try {

            if (!$this->checkMainBucketExists()) {
                if (!$this->createMainBucket()) {
                    throw new \Exception('Failed to create main bucket');
                }
            }

            if (!$this->checkUserBucketExists($username)) {
                if (!$this->createUserBucket($username)) {
                    throw new \Exception('Failed to create user bucket');
                }
            }

            $key = $username . '/' . $assetType . '/' . $filename;
            
            $result = $this->s3Client->putObject([
                'Bucket' => $this->bucket,
                'Key' => $key,
                'SourceFile' => $file->getRealPath(),
                'ContentType' => $file->getMimeType(),
                'ACL' => 'public-read',
            ]);

            $publicUrl = config('services.minio.public_url', $this->endpoint);
            $url = rtrim($publicUrl, '/') . '/' . $this->bucket . '/' . $key;


            return [
                'success' => true,
                'etag' => $result['ETag'],
                'url' => $url,
                'key' => $key,
            ];

        } catch (AwsException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

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
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getFileUrl(string $username, string $assetType, string $filename): string
    {
        $publicUrl = config('services.minio.public_url', $this->endpoint);
        $key = $username . '/' . $assetType . '/' . $filename;
        
        return rtrim($publicUrl, '/') . '/' . $this->bucket . '/' . $key;
    }

    public function testConnection(): array
    {
        try {
            $result = $this->s3Client->listBuckets();
            
            return [
                'success' => true,
                'message' => 'Connection successful',
                'buckets' => $result['Buckets']
            ];
        } catch (AwsException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => $e->getAwsErrorCode(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
} 