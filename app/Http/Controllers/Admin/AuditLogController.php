<?php

namespace App\Http\Controllers\Admin;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class AuditLogController extends AdminController
{
    public function __invoke(Request $request): View
    {
        $entries = AuditLog::query()
            ->with('user')
            ->when($request->query('user'), fn (Builder $query, $userId) => $query->where('user_id', $userId))
            ->when($request->query('area'), fn (Builder $query, $area) => $query->where('action', 'like', str_replace(['%', '_'], '', (string) $area).'.%'))
            ->when($request->query('q'), fn (Builder $query, $term) => $query->where('description', 'like', '%'.addcslashes((string) $term, '%_').'%'))
            ->latest('created_at')
            ->latest('id')
            ->paginate(50)
            ->withQueryString();

        return view('admin.audit.index', [
            'entries' => $entries,
            'users' => User::query()->orderBy('name')->pluck('name', 'id'),
            'areas' => [
                'auth' => 'Sign-in',
                'content' => 'Content & publishing',
                'media' => 'Media',
                'applications' => 'Applications',
                'careers' => 'Careers',
                'enquiries' => 'Enquiries',
                'settings' => 'Settings',
                'user' => 'Users & roles',
                'export' => 'Exports',
            ],
        ]);
    }
}
