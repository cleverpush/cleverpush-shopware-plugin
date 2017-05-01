<?php

namespace CleverPush;

const CLEVERPUSH_API_ENDPOINT = 'https://api.cleverpush.com';

class CleverPushApi
{
    private $channelId;
    private $apiKeyPrivate;

    public function __construct($channelId, $apiKeyPrivate)
    {
        $this->channelId = $channelId;
        $this->apiKeyPrivate = $apiKeyPrivate;
    }

    public function request($path, $params)
    {
        if (empty($this->channelId) || empty($this->apiKeyPrivate))
        {
            return null;
        }

        $options = array(
            'http' => array(
                'header'  => "authorization: " . $this->apiKeyPrivate . "\r\ncontent-type: application/json\r\n",
                'method'  => 'POST',
                'content' => json_encode(array_merge(
                    array('channel' => $this->channelId),
                    $params
                ))
            )
        );
        $context  = stream_context_create($options);
        $response = file_get_contents(CLEVERPUSH_API_ENDPOINT . $path, false, $context);
        if ($response === false) {
            $error_message = $response->get_error_message();
        }

        // var_dump($response);

        if (!empty($error_message)) {
            throw new \Exception($error_message);
        }

        return $response;
    }

    public function sendNotification($title, $body, $url, $iconUrl = null, $subscriptionId = null)
    {
        $params = array(
            'title' => $title,
            'text' => $body,
            'url' => $url
        );
        if ($iconUrl) {
            $params['iconUrl'] = $iconUrl;
        }
        if ($subscriptionId) {
            $params['subscriptionId'] = $subscriptionId;
        }
        return $this->request('/notification/send', $params);
    }
}
