<?php

class helper {

    public static function getApiDoc($domain = null)
    {
        if (empty($domain)) {
            throw new Exception('缺少 domain');
        }

        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => $domain,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "GET",
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
          echo "cURL Error #:" . $err;
        }

        return json_decode($response, true);
    }

    public static function generate_json_file($doc, $title)
    {
        $date = date('Y-m-d');
        $file_name = "{$title}_{$date}.json";
        $doc = json_encode($doc, JSON_UNESCAPED_UNICODE);
        file_put_contents("json/" . $file_name, $doc);
        // $swaggerServerPath = "/home/zong/data/swagger-doc/api/doc/{$title}.json";
        // file_put_contents($swaggerServerPath, $doc);

        echo "{$title} 檔案產生完畢!\n";
    }
}