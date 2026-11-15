<?php

namespace Engelsystem\Reporter;

use Engelsystem\Config\Config;
use Engelsystem\Events\DataEvent;
use Engelsystem\Mail\EngelsystemMailer;
use Engelsystem\Models\EventConfig;
use Engelsystem\Models\Shifts\ShiftEntry;
use Engelsystem\Renderer\Renderer;
use Psr\Log\LoggerInterface;

class Report
{
    public function __construct(
        protected LoggerInterface $log,
        protected EngelsystemMailer $mailer,
        protected Config $config,
        protected Renderer $renderer,
    ) {
    }

    public function send(array $data): bool
    {
        $isAbuse = $data['reason'] !== 'technical';
        $to = $this->config->get($isAbuse ? 'reporter_abuse_report_email' : 'reporter_technical_report_email');
        if (!$to) {
            $this->log->error('Reporter: Unable to send {reason} email, missing recipient address', ['reason' => $data['reason']]);
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
            (new EventConfig())
                ->findOrNew('reporter_abuse_messages')
                ->setAttribute('name', 'reporter_abuse_messages')
                ->setAttribute('value', $this->config->get('reporter_abuse_messages', 0) + 1)
                ->save();
        } else {
            $this->log->error('Reporter: Unable to send {reason} email, error sending', ['reason' => $data['reason']]);
        }

        return $success;
    }

    public function metrics(DataEvent $event): void
    {
        $event->data['metrics'] = [
            ...$event->data['metrics'],
            'reporter_abuse_messages' => [
                'type' => 'gauge',
                'help' => 'Number of abuse messages sent',
                $this->config->get('reporter_abuse_messages', 0),
            ],
        ];
    }

    public function userPageName(DataEvent $event): void
    {
        $event->data['parts'] = [
            ...$event->data['parts'],
            'report' => $this->renderer->render(
                'reporter/report.twig',
                ['report_data' => ['user' => $event->data['user']->id]]
            ),
        ];
    }

    public function shiftPageName(DataEvent $event): void
    {
        $entries = [];
        foreach ($event->data['shift']->shiftEntries as $entry) {
            /** @var ShiftEntry $entry */
            $entries[$entry->user->id] = $entry->user->name;
        }
        asort($entries);

        $event->data['parts'] = [
            ...$event->data['parts'],
            'report' => $this->renderer->render(
                'reporter/report.twig',
                ['report_data' => [
                    'shift' => $event->data['shift']->shiftType->name,
                    'in' => $event->data['shift']->location->name,
                    'with' => $entries,
                    'id' => $event->data['shift']->id,
                    'start' => $event->data['shift']->start,
                    'end' => $event->data['shift']->end,
                ]]
            ),
        ];
    }
}
