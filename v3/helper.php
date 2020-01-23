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
        file_put_contents("/home/zong/Applications/apidocToSwagger/v3/json/" . $file_name, $doc);
        $swaggerServerPath = "/home/zong/data/swagger-doc/api/doc/{$title}.json";
        file_put_contents($swaggerServerPath, $doc);

        echo "{$title} 檔案產生完畢!\n";
    }


    public static function sendMessage($params)
    {
        $user = self::login($params['rocketUrl'], $params['account']);

        $curl = curl_init();

        $body = [
            'channel' => 'swagger_api_channel',
            'text'    => $params['message']
        ];

        curl_setopt_array($curl, array(
             CURLOPT_URL            => "{$params['rocketUrl']}/api/v1/chat.postMessage",
             CURLOPT_RETURNTRANSFER => true,
             CURLOPT_ENCODING       => "",
             CURLOPT_MAXREDIRS      => 10,
             CURLOPT_TIMEOUT        => 0,
             CURLOPT_SSL_VERIFYPEER => false,
             CURLOPT_SSL_VERIFYHOST => false,
             CURLOPT_FOLLOWLOCATION => true,
             CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
             CURLOPT_CUSTOMREQUEST  => "POST",
             CURLOPT_POSTFIELDS     => json_encode($body, JSON_UNESCAPED_UNICODE),
             CURLOPT_HTTPHEADER     => array(
                                        "Content-Type: application/json",
                                        "X-Auth-Token: {$user['authToken']}",
                                        "X-User-Id: {$user['userId']}"
             ),
        ));

        $response = curl_exec($curl);

        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if ($httpcode != 200) {
            $swaggerServerPath = "/home/zong/data/swagger-doc/api/doc/errorLog.json";
            file_put_contents($swaggerServerPath, "Rocket curl error {$response} \n");
        }
    }

    private static function login($rocketUrl, $account)
    {
        $curl = curl_init();

        curl_setopt_array($curl,
            array(
            CURLOPT_URL            => "{$rocketUrl}/api/v1/login",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => "",
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => "POST",
            CURLOPT_POSTFIELDS     => json_encode($account, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER     => [ "Content-type: application/json" ],
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $response = json_decode($response, true);

        if ($response['status'] != 'success') {
            throw new \Exception('ERROR - User Login');
        }

        return [
          'userId'    => $response['data']['userId'],
          'authToken' => $response['data']['authToken']
      ];
    }
}
