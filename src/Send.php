<?php

namespace DealNews\JitsuAnalytics;

use DealNews\GetConfig\GetConfig;

/**
 * Sends events to Jitsu
 *
 * @author      Brian Moon <brianm@dealnews.com>
 * @copyright   1997-Present DealNews.com, Inc
 * @package     DealNews\JitsuAnalytics
 */
class Send {

    /**
     * Constructs a new instance.
     *
     * @param      ?string              $write_key  The Jitsu write key
     * @param      ?string              $domain     The Jitsu domain
     * @param      ?\GuzzleHttp\Client  $guzzle     Optional Guzzle client
     */
    public function __construct(protected ?string $write_key = null, protected ?string $domain = null, protected ?\GuzzleHttp\Client $guzzle = null) {
        $config = GetConfig::init();
        $this->write_key ??= $config->get('jitsu.write_key');
        $this->domain    ??= $config->get('jitsu.domain');
        $this->guzzle    ??= new \GuzzleHttp\Client();
    }

    /**
     * SEnds a page event to Jitsu
     *
     * @param      null|string       $title       The title of the page
     * @param      array             $properties  The page properties
     *
     * @throws     RuntimeException
     *
     * @return     bool
     */
    public function page(?string $title = null, array $properties = []): bool {

        if (empty($title) && empty($properties)) {
            throw new RuntimeException("Either title or properties are required for sending a page event");
        }

        $properties['type'] = 'page';
        if ($title) {
            $properties['title'] = $title;
        }
        return $this->send('page', $properties);
    }

    /**
     * Sends an identify event to Jitsu
     *
     * @param      string  $user_id     The unique user id
     * @param      array   $properties  The user properties
     *
     * @return     bool
     */
    public function identify(string $user_id, array $properties = []): bool {
        $properties['type'] = 'identify';
        $properties['userId'] = $user_id;
        return $this->send('identify', $properties);
    }

    /**
     * Sends a tracking event to Jitsu
     *
     * @param      string  $event       The event name
     * @param      array   $properties  The event properties
     *
     * @return     bool
     */
    public function track(string $event, array $properties = []): bool {
        $properties['type'] = 'track';
        $properties['event'] = $event;
        return $this->send('track', $properties);
    }

    /**
     * Handles the actual sending of the events to Jitsu
     *
     * @param      string             $type     The event type
     * @param      array              $payload  The event payload
     *
     * @throws     \RuntimeException
     *
     * @return     bool
     */
    protected function send(string $type, array $payload): bool {

        $success = false;
        $tries = 0;

        while ($tries < 3) {

            $tries++;

            $res = $this->guzzle->request(
                'POST',
                "https://{$this->domain}/api/s/{$type}",
                [
                    'http_errors' => false,
                    'json'        => $payload,
                    'headers'     => [
                        'Content-type' => 'application/json',
                        'X-Write-Key'  => $this->write_key
                    ],
                ]
            );

            $body = (string)$res->getBody();

            if ($res->getStatusCode() !== 200) {
                if ($tries < 3) {
                    continue;
                }
                // Jitsu returns an HTML body when it is failing so strip the tags of the body
                throw new \RuntimeException(strip_tags($body), $res->getStatusCode());
            }

            $data = json_decode($body, true);

            if (!empty($data['error'])) {
                throw new \RuntimeException($data['error'], 400);
            }

            if (!empty($data['ok']) && $data['ok'] === true) {
                $success = true;
                break;
            }
        }

        return $success;
    }
}
