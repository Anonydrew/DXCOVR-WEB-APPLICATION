<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\TeamMemberRequest;
use App\Models\Project;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\HtmlSanitizer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TeamMemberController extends AdminController
{
    public function __construct(protected HtmlSanitizer $sanitizer) {}

    public function index(Request $request): View
    {
        return view('admin.team.index', [
            'members' => TeamMember::query()->where('is_founder', false)->with('photo')->orderBy('sort_order')->get(),
            'founder' => TeamMember::founder(),
            'canManage' => $request->user()->can('company.manage'),
        ]);
    }

    public function create(): View
    {
        return view('admin.team.form', $this->formData(new TeamMember(['is_published' => false])));
    }

    public function store(TeamMemberRequest $request): RedirectResponse
    {
        $member = new TeamMember;
        $member->sort_order = (int) TeamMember::query()->max('sort_order') + 1;
        $this->persist($member, $request);

        $this->audit()->record('content.created', $member, 'Added team member '.$member->name);

        return redirect()->route('admin.team.edit', $member)->with('status', 'Team member saved.');
    }

    public function edit(TeamMember $member): View|RedirectResponse
    {
        if ($member->is_founder) {
            return redirect()->route('admin.founder.edit');
        }

        $member->load(['photo', 'projects']);

        return view('admin.team.form', $this->formData($member));
    }

    public function update(TeamMemberRequest $request, TeamMember $member): RedirectResponse
    {
        $this->persist($member, $request);

        $this->audit()->record('content.updated', $member, 'Updated team member '.$member->name, $this->audit()->changes($member));

        return redirect()->route('admin.team.edit', $member)->with('status', 'Team member updated.');
    }

    public function destroy(TeamMember $member): RedirectResponse
    {
        abort_if($member->is_founder, 403, 'The founder profile cannot be deleted. Unpublish it instead.');

        $name = $member->name;
        $member->delete();

        $this->audit()->record('content.deleted', null, 'Removed team member '.$name);

        return redirect()->route('admin.team.index')->with('status', 'Team member removed.');
    }

    public function persist(TeamMember $member, TeamMemberRequest $request): void
    {
        $member->fill($request->safe()->only(['name', 'slug', 'role_title', 'short_bio', 'photo_media_id', 'user_id']));
        $member->bio = $this->sanitizer->rich($request->validated('bio'));
        $member->links = $request->validated('links') ?: null;
        $member->skills = $request->validated('skills') ?: null;
        $member->expertise = $request->validated('expertise') ?: null;
        $member->experience = $request->validated('experience') ?: null;
        $member->is_published = $request->boolean('is_published');
        $member->save();

        $member->projects()->sync($request->validated('projects') ?? []);
    }

    /**
     * @return array<string, mixed>
     */
    public function formData(TeamMember $member): array
    {
        return [
            'member' => $member,
            'users' => User::query()->orderBy('name')->pluck('name', 'id'),
            'projects' => Project::query()->orderBy('title')->pluck('title', 'id'),
        ];
    }
}
