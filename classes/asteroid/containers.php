<?php

namespace asteroid;

class containers {

    private $containerurl = "";
    private $containerport = "";
    private $containeradminkey = "";
    private $containerkey = "";

    private $localStorage = false;
    private $storagePath;

    public function __construct() {

        $this->localStorage =
            filter_var($_ENV["LOCAL_STORAGE"] ?? false, FILTER_VALIDATE_BOOLEAN);

        $this->storagePath = dirname(__DIR__, 2) . "/storage/assets";

        if ($this->localStorage) {
            if (!is_dir($this->storagePath)) {
                mkdir($this->storagePath, 0755, true);
            }

            $this->containerkey = "local";
            return;
        }

        $this->containerurl = $_ENV["CONTAINERURL"] ?? "";
        $this->containerport = $_ENV["CONTAINERPORT"] ?? "";
        $this->containeradminkey = $_ENV["CONTAINERADMINKEY"] ?? "";

        if ($this->containerurl === "" || $this->containerport === "") {
            throw new \ErrorException(
                "Container service is not configured. Set LOCAL_STORAGE=true or configure CONTAINERURL and CONTAINERPORT."
            );
        }

        $post_data = [
            "role" => "site",
            "adminKey" => $this->containeradminkey,
        ];

        $url = $this->compile_url('/api/auth/request-token');

        $newkey = $this->send_post_request($url, json_encode($post_data));
        $decoded = json_decode($newkey);

        if (!empty($decoded) && isset($decoded->token)) {
            $this->containerkey = $decoded->token;
        } else {
            throw new \ErrorException("Failed to fetch storage token");
        }
    }

    public function send_get_request($url) {

        if ($this->localStorage) {
            return false;
        }

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            "Authorization: " . $this->containerkey,
        ]);

        $response = curl_exec($curl);

        return $response ?: false;
    }

    public function send_post_request($url, $data = []) {

        if ($this->localStorage) {
            return false;
        }

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            "Content-type: application/json",
            "Authorization: " . $this->containerkey,
        ]);

        $response = curl_exec($curl);

        return $response ?: false;
    }

    public function compile_url($uri) {

        if ($this->localStorage) {
            return $uri;
        }

        return $this->containerurl . ":" . $this->containerport . $uri;
    }

    public function set_container_key($newkey) {
        $this->containerkey = $newkey;
    }

    public function get_container_files($container = null) {

        if (!$this->localStorage) {
            $url = $this->compile_url("/api/files/list");
            $response = $this->send_get_request($url);

            return $response ? json_decode($response) : false;
        }

        $files = [];

        if (!is_dir($this->storagePath)) {
            return $files;
        }

        foreach (scandir($this->storagePath) as $file) {

            if ($file === "." || $file === "..") {
                continue;
            }

            $path = $this->storagePath . "/" . $file;

            if (is_file($path)) {
                $files[] = [
                    "name" => $file,
                    "size" => filesize($path)
                ];
            }
        }

        return $files;
    }

    public function get_container_info($container = null) {

        if (!$this->localStorage) {
            $url = $this->compile_url("/api/about-instance");
            $response = $this->send_get_request($url);

            return $response ? json_decode($response) : false;
        }

        return (object)[
            "name" => "Local Storage",
            "type" => "local",
            "status" => "online"
        ];
    }

    public function upload_file(
        $filepath = '',
        $base64 = '',
        $filename = '',
        $location = ''
    ) {

        if (!$this->localStorage) {

            if ($filename === '') {
                $filename = basename($filepath);
            }

            $location = $location . $filename;

            if ($base64 === '') {
                $rawfile = file_get_contents($filepath);
                $base64 = base64_encode($rawfile);
            }

            $upload = [
                "file" => $base64,
                "path" => $location
            ];

            $url = $this->compile_url("/api/files/upload");
            $response = $this->send_post_request(
                $url,
                json_encode($upload)
            );

            return $response ? json_decode($response) : false;
        }

        if ($filename === '') {
            $filename = basename($filepath);
        }

        $filename = basename($filename);

        if ($base64 === '') {

            if (!is_file($filepath)) {
                return false;
            }

            $base64 = base64_encode(
                file_get_contents($filepath)
            );
        }

        $data = base64_decode($base64, true);

        if ($data === false) {
            return false;
        }

        $target = $this->storagePath . "/" . $filename;

        if (file_put_contents($target, $data) === false) {
            return false;
        }

        return (object)[
            "success" => true,
            "path" => $target,
            "filename" => $filename,
            "size" => strlen($data)
        ];
    }
}
