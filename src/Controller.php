<?php

declare(strict_types=1);

namespace Engelsystem\Reporter;

use Engelsystem\Config\Config;
use Engelsystem\Controllers\BaseController;
use Engelsystem\Controllers\HasUserNotifications;
use Engelsystem\Controllers\NotificationType;
use Engelsystem\Http\Redirector;
use Engelsystem\Http\Request;
use Engelsystem\Http\Response;
use Engelsystem\Models\User\User;

class Controller extends BaseController
{
    use HasUserNotifications;

    protected array $permissions = [
        'report.send',
    ];

    protected array $reasons = [];

    public function __construct(
        protected Redirector $redirector,
        protected Response $response,
        protected Report $report,
        Config $config,
    ) {
        $this->reasons = $config->get('reporter_reasons');
    }

    public function form()
    {
        return $this->response->withView('reporter/page.twig');
    }

    public function send(Request $request): Response
    {
        $data = $this->validate($request, [
            'reason' => 'required|in:' . implode(',', $this->reasons),
            'message' => 'optional',
            'use_content' => 'optional|checked',
            'content' => 'optional',
        ]);

        if (!$data['use_content'] || !$data['content']) {
            $data['content'] = null;
        }

        $data['about_user'] = null;
        if (
            $data['content']
            && ($content = json_decode($data['content'], true)) !== null
            && is_array($content)
            && array_key_exists('user', $content)
        ) {
            $data['about_user'] = User::findOrFail($content['user']);
        }

        $referer = $request->getHeader('referer');
        $referer = array_pop($referer);
        $data['referer'] = $referer;

        $success = $this->report->send($data);
        if (in_array('application/json', $request->getAcceptableContentTypes())) {
            // Async request, send a JSON response
            return $this->response
                ->withHeader('content-type', 'application/json')
                ->withContent(json_encode([
                    'success' => $success,
                    'message' => $success ? __('reporter.success') : __('reporter.failed'),
                ]));
        }

        if ($success) {
            $this->addNotification('reporter.success', NotificationType::INFORMATION);
        } else {
            $this->addNotification('reporter.failed', NotificationType::ERROR);
        }

        return $this->redirector->back();
    }
}
