<?php

namespace App\Service;

use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class AmazonS3Client
{
    private $amazonS3Client;
    private $bucket;

    public function __construct()
    {
        $data = file_get_contents('../config/secrets/secrets.json');
        $obj = json_decode($data);

        $region = $obj->region;
        $version = $obj->version;
        $access_key_id = $obj->access_key_id;
        $secret_access_key = $obj->secret_access_key;
        $this->bucket = $obj->bucket;

        $this->amazonS3Client = new S3Client([
            'version' => $version,
            'region' => $region,
            'credentials' => [
                'key' => $access_key_id,
                'secret' => $secret_access_key
            ]
        ]);
    }

    public function insertObject(UploadedFile $object) {
        
        $file_type = $object->guessExtension();
        $file_name = 'doc-'.uniqid().'.'.$file_type;
        $mime_type = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $object);
        return $this->finalInsert($file_name, $object, $mime_type);
    }

    public function finalInsert($file_name, $file, $mime_type) {

        try { 
            $result = $this->amazonS3Client->putObject([ 
                'Bucket' => $this->bucket, 
                'Key'    => $file_name, 
                'SourceFile' => $file,
                'ContentType' => $mime_type
            ]); 
            $result_arr = $result->toArray(); 
            
            if(!empty($result_arr['ObjectURL'])) { 
                $s3_file_link = $result_arr['ObjectURL']; 
                return [
                    'success'=>true,
                    'link'=>$s3_file_link,
                    'error'=>NULL
                ];
            } else { 
                return [
                    'success'=>false,
                    'link'=>NULL,
                    'error'=>'Upload Failed! S3 Object URL not found.'
                ];
            } 
        } catch (S3Exception $e) { 
            $api_error = $e->getMessage();
            return [
                'success'=>false,
                'link'=>NULL,
                'error'=>$api_error
            ];
        }
    }
}
