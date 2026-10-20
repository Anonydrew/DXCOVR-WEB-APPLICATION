<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\TeamMemberRequest;
use App\Models\TeamMember;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * The founder profile is a single team member record flagged as founder.
 */
class FounderController extends AdminController
{
    public function __construct(protected TeamMemberController $team) {}

    public function edit(): View
    {
        $founder = TeamMember::founder() ?? new TeamMember(['is_published' => false]);
        $founder->loadMissing(['photo', 'projects']);

        return view('admin.team.form', array_merge($this->team->formData($founder), ['isFounder' => true]));
    }

    public function update(TeamMemberRequest $request): RedirectResponse
    {
        $founder = TeamMember::founder() ?? new TeamMember;
        $founder->is_founder = true;

        $this->team->persist($founder, $request);

        $this->audit()->record('content.updated', $founder, 'Updated the founder profile', $this->audit()->changes($founder));

        return redirect()->route('admin.founder.edit')->with('status', 'Founder profile saved.');
    }
}
