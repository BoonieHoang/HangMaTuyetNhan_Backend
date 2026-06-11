<?php

namespace App\Mail\Transports;

use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Email;
use GuzzleHttp\Client;

class BrevoTransport extends AbstractTransport
{
    protected string $apiKey;

    public function __construct(string $apiKey)
    {
        parent::__construct();
        $this->apiKey = $apiKey;
    }

    protected function doSend(SentMessage $message): void
    {
        /** @var Email $email */
        $email = $message->getOriginalMessage();

        $client = new Client();

        $to = [];
        foreach ($email->getTo() as $address) {
            $name = $address->getName();
            $to[] = [
                'email' => $address->getAddress(),
                'name' => $name !== '' ? $name : null,
            ];
        }

        $sender = null;
        if ($email->getFrom()) {
            $fromAddress = $email->getFrom()[0];
            $name = $fromAddress->getName();
            $sender = [
                'email' => $fromAddress->getAddress(),
                'name' => $name !== '' ? $name : null,
            ];
        }

        $payload = [
            'sender' => $sender,
            'to' => $to,
            'subject' => $email->getSubject(),
        ];

        $htmlBody = $email->getHtmlBody();
        if (is_resource($htmlBody)) {
            $htmlBody = stream_get_contents($htmlBody);
        }

        $textBody = $email->getTextBody();
        if (is_resource($textBody)) {
            $textBody = stream_get_contents($textBody);
        }

        if ($htmlBody !== null) {
            $payload['htmlContent'] = $htmlBody;
        }
        if ($textBody !== null) {
            $payload['textContent'] = $textBody;
        }

        $response = $client->post('https://api.brevo.com/v3/smtp/email', [
            'headers' => [
                'accept' => 'application/json',
                'api-key' => $this->apiKey,
                'content-type' => 'application/json',
            ],
            'json' => $payload,
        ]);

        if ($response->getStatusCode() >= 300) {
            throw new \RuntimeException('Failed to send email to Brevo API: ' . $response->getBody()->getContents());
        }
    }

    public function __toString(): string
    {
        return 'brevo';
    }
}
