<?php

namespace Engelsystem\Reporter;

use Engelsystem\Config\Config;
use Engelsystem\Events\DataEvent;
use Engelsystem\Mail\EngelsystemMailer;

class Report
{
    public function __construct(
        protected EngelsystemMailer $mailer,
        protected Config $config,
    ) {
    }

    public function send(array $data): bool
    {
        $isAbuse = $data['reason'] !== 'technical_issue';
        $to = $this->config->get($isAbuse ? 'reporter_abuse_report_email' : 'reporter_technical_report_email');
        if ($isAbuse) {
            $to = $this->config->get('technical_report_email');
        }
        if (!$to) {
            return false;
        }

        $success = $this->mailer->sendViewTranslated(
            $to,
            $isAbuse ? 'reporter.mail.abuse' : 'reporter.mail.technical',
            'emails/reporter-report.text.twig',
            $data,
            $this->config->get('default_locale'),
        );

        if ($success) {
            $this->config->set(
                'abuse_messages',
                $this->config->get('abuse_messages', 0) + 1
            );
        }

        return $success;
    }

    public function metrics(DataEvent $event): void
    {
        $event->data['metrics'] = [
            ...$event->data['metrics'],
            'abuse_messages' => [
                'type' => 'gauge',
                'help' => 'Number of abuse messages sent',
                $this->config->get('abuse_messages', 0),
            ],
        ];
    }
}
