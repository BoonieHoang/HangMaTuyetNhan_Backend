<?php

namespace App\Mail\Transports;

use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\MessageConverter;
use Illuminate\Support\Facades\Http;

class ResendTransport extends AbstractTransport
{
    protected $key;

    public function __construct($key)
    {
        parent::__construct();
        $this->key = $key;
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());
        
        $from = $email->getFrom();
        $fromAddress = count($from) > 0 ? $from[0]->getAddress() : 'onboarding@resend.dev';
        $fromName = count($from) > 0 ? $from[0]->getName() : 'Tuyết Nhàn';

        // Resend sandbox only allows sending from onboarding@resend.dev unless a custom domain is verified
        if (str_contains($fromAddress, 'localhost') || str_contains($fromAddress, 'leapham.vn') || str_contains($fromAddress, 'example.com')) {
            $fromAddress = 'onboarding@resend.dev';
        }

        $to = [];
        foreach ($email->getTo() as $recipient) {
            $to[] = $recipient->getAddress();
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->key,
            'Content-Type' => 'application/json',
        ])->post('https://api.resend.com/emails', [
            'from' => $fromName ? "{$fromName} <{$fromAddress}>" : $fromAddress,
            'to' => $to,
            'subject' => $email->getSubject(),
            'html' => $email->getHtmlBody() ?? $email->getTextBody(),
        ]);

        if ($response->failed()) {
            throw new \Exception('Resend API failed: ' . $response->body());
        }
    }

    public function __toString(): string
    {
        return 'resend';
    }
}
