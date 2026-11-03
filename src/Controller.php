<?php

declare(strict_types=1);

namespace Engelsystem\Reporter;

use Engelsystem\Controllers\BaseController;
use Engelsystem\Controllers\HasUserNotifications;
use Engelsystem\Controllers\NotificationType;
use Engelsystem\Helpers\Authenticator;
use Engelsystem\Http\Redirector;
use Engelsystem\Http\Request;
use Engelsystem\Http\Response;
use Engelsystem\Http\UrlGenerator;
use Engelsystem\Models\User\User;

class Controller extends BaseController
{
    use HasUserNotifications;

    protected array $permissions = [
        'report.send',
    ];

    protected array $reasons = [
        'abuse',
        'content',
        'person',
        'technical',
        'other',
    ];

    // TODO: Inject list of reports

    public function __construct(
        protected Redirector $redirector,
        protected Report $report,
    ) {
    }

    public function send(Request $request): Response
    {
        $data = $this->validate($request, [
            'reason' => 'required|in:' . implode(',', $this->reasons),
            'about_user' => 'optional|int',
            'message' => 'optional',
            'use_content' => 'optional|checked',
            'content' => 'optional',
        ]);

        if (!$data['use_content']) {
            $data['content'] = null;
        }

        if ($data['about_user']) {
            $data['about_user'] = User::findOrFail($data['about_user']);
        }

        $referer = $request->getHeader('referer');
        $referer = array_pop($referer);
        $data['referer'] = $referer;

        if ($this->report->send($data)) {
            $this->addNotification('reporter.success', NotificationType::INFORMATION);
        } else {
            $this->addNotification('reporter.failed', NotificationType::ERROR);
        }

        return $this->redirector->back();
    }
}
